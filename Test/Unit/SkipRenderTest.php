<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Test\Unit;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\TemplateEngine\Php as TemplateEngine;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\RootTagMissingFromViewException;
use Magewirephp\Magewire\Helper\Functions;
use Magewirephp\Magewire\Model\ComponentManager;
use Magewirephp\Magewire\Model\ComponentResolver;
use Magewirephp\Magewire\Model\HttpFactory;
use Magewirephp\Magewire\Model\Hydrator\BrowserEvent;
use Magewirephp\Magewire\Model\Hydrator\Children;
use Magewirephp\Magewire\Model\Hydrator\Hash;
use Magewirephp\Magewire\Model\Hydrator\Security;
use Magewirephp\Magewire\Model\LayoutRenderLifecycle;
use Magewirephp\Magewire\Model\Request;
use Magewirephp\Magewire\Model\Response;
use Magewirephp\Magewire\Observer\Frontend\ViewBlockAbstractToHtmlAfter;
use Magewirephp\Magewire\Plugin\Magento\Framework\View\TemplateEngine\Php as TemplatePlugin;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class SkipRenderTest extends TestCase
{
    public function testSkippedUpdateDoesNotExecuteQuoteDependentTemplate(): void
    {
        $component = $this->component();
        $component->dispatchBrowserEvent('order:placed', ['order_id' => 42]);

        $html = $this->render($component, static function (): string {
            throw new RuntimeException('The child component requires the quote that was just converted to an order.');
        });

        self::assertNull($html);
        self::assertNull($component->getResponse()->effects['html']);
        self::assertTrue($component->dehydrated);
        self::assertSame([
            ['event' => 'order:placed', 'data' => ['order_id' => 42]]
        ], $component->getResponse()->effects['dispatches']);
        self::assertSame(['result' => 'success'], $component->getResponse()->memo['evaluation']);
    }

    public function testSkippedUpdatePreservesChildrenHashAndValidChecksum(): void
    {
        $component = $this->component();
        $previous = $component->getRequest()->getServerMemo();

        $this->render($component, static function (): string {
            throw new RuntimeException('The skipped template must not execute.');
        });

        $response = $component->getResponse();
        self::assertSame($previous['children'], $response->memo['children']);
        self::assertSame($previous['htmlHash'], $response->memo['htmlHash']);
        self::assertArrayHasKey('checksum', $response->memo);

        $nextRequest = (new Request())
            ->setFingerprint($response->fingerprint)
            ->setServerMemo($response->memo)
            ->isSubsequent(true);

        // A following update must accept the snapshot produced without a template render.
        $this->security()->hydrate($component, $nextRequest);
    }

    /**
     * @dataProvider renderingRequests
     */
    public function testRenderingRequestsStillExecuteTheirTemplates(bool $subsequent, bool $skip): void
    {
        $component = $this->component($subsequent, $skip);
        $called = false;

        $html = $this->render($component, static function () use (&$called): string {
            $called = true;
            return '<div><p>Checkout details</p></div>';
        });

        self::assertTrue($called);
        self::assertStringContainsString('wire:id="checkout.main"', $html);
        self::assertStringContainsString('wire-end:checkout.main', $html);

        if ($skip) {
            // Preserve the existing initial-page behavior: retain the component root.
            self::assertStringNotContainsString('Checkout details', $html);
        } else {
            self::assertStringContainsString('Checkout details', $html);
        }
    }

    public static function renderingRequests(): array
    {
        return [
            'normal update' => [true, false],
            'initial render' => [false, false],
            'initial render with skip flag' => [false, true]
        ];
    }

    public function testRenderingCanBeEnabledAgain(): void
    {
        $component = $this->component();
        $component->skipRender(false);

        $html = $this->render($component, static fn (): string => '<div>Checkout details</div>');

        self::assertStringContainsString('Checkout details', $html);
    }

    public function testUnchangedHtmlStillUsesExistingHashSuppression(): void
    {
        $component = $this->component(true, false);
        $html = '<div>Unchanged checkout</div>';
        $component->getRequest()->memo['htmlHash'] = hash('crc32b', $html);
        $component->getResponse()->memo['htmlHash'] = hash('crc32b', $html);

        self::assertNull($this->render($component, static fn (): string => $html));
        self::assertSame(hash('crc32b', $html), $component->getResponse()->memo['htmlHash']);
    }

    public function testNormalUpdatesStillRejectMissingRoot(): void
    {
        $this->expectException(RootTagMissingFromViewException::class);

        $this->render($this->component(true, false), static fn (): string => 'Missing root element', false);
    }

    public function testNonMagewireBlocksStillRender(): void
    {
        $block = $this->getMockBuilder(Template::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $engine = $this->createMock(TemplateEngine::class);
        $plugin = new TemplatePlugin(new LayoutRenderLifecycle());
        $arguments = $plugin->beforeRender($engine, $block, 'ordinary.phtml', ['label' => 'Hello']);
        $template = static function (Template $actualBlock, string $filename, array $dictionary) use ($block): string {
            self::assertSame($block, $actualBlock);
            self::assertSame('ordinary.phtml', $filename);
            self::assertSame(['label' => 'Hello'], $dictionary);

            return '<div>Hello</div>';
        };

        $html = method_exists($plugin, 'aroundRender')
            ? $plugin->aroundRender($engine, $template, ...$arguments)
            : $template(...$arguments);

        self::assertSame('<div>Hello</div>', $html);
    }

    private function component(bool $subsequent = true, bool $skip = true): Component
    {
        $component = new class extends Component {
            public bool $dehydrated = false;

            public function dehydrate(): void
            {
                $this->dehydrated = true;
            }
        };
        $component->id = $component->name = 'checkout.main';
        $component->skipRender($skip);

        $request = (new Request())
            ->setFingerprint(['id' => $component->id, 'name' => $component->name])
            ->setServerMemo([
                'htmlHash' => 'original-checkout-hash',
                'children' => ['checkout.address' => ['id' => 'checkout.address', 'tag' => 'section']],
                'data' => []
            ])
            ->isSubsequent($subsequent);

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnArgument(0);
        $response = (new Response(new Functions(new Json(), $escaper)))
            ->setRequest($request)
            ->setFingerprint($request->getFingerprint())
            ->setServerMemo($request->getServerMemo())
            ->setEffects([]);

        return $component->setRequest($request)->setResponse($response);
    }

    private function render(Component $component, callable $template, bool $dehydrate = true): ?string
    {
        $block = $this->getMockBuilder(Template::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $block->setNameInLayout($component->name)->setData('magewire', $component);
        $component->setParent($block);

        $lifecycle = new LayoutRenderLifecycle();
        $lifecycle->start($component->name);
        $plugin = new TemplatePlugin($lifecycle);
        $engine = $this->createMock(TemplateEngine::class);
        $arguments = $plugin->beforeRender($engine, $block, 'checkout.phtml');

        // Match Magento's plugin dispatch, including the unpatched V1 before-only implementation.
        $html = method_exists($plugin, 'aroundRender')
            ? $plugin->aroundRender($engine, $template, ...$arguments)
            : $template(...$arguments);

        $hash = new Hash();
        $hash->hydrate($component, $component->getRequest());
        $children = new Children();
        $children->hydrate($component, $component->getRequest());
        $security = $this->security();
        $manager = $this->createMock(ComponentManager::class);
        $manager->expects($dehydrate ? self::once() : self::never())->method('dehydrate')->willReturnCallback(
            static function (Component $component) use ($hash, $children, $security, $lifecycle): Component {
                // The checkout evaluation hydrator uses this lifecycle state to decide whether to run.
                self::assertTrue($lifecycle->isChild($component->name));
                $response = $component->getResponse();
                $response->memo['evaluation'] = ['result' => 'success'];
                (new BrowserEvent())->dehydrate($component, $response);
                $hash->dehydrate($component, $response);
                $children->dehydrate($component, $response);
                $security->dehydrate($component, $response);

                return $component;
            }
        );
        $observer = new ViewBlockAbstractToHtmlAfter(
            $this->createMock(State::class),
            $manager,
            $this->createMock(ComponentResolver::class),
            $this->createMock(HttpFactory::class),
            $lifecycle,
            $this->createMock(LoggerInterface::class)
        );
        $transport = new DataObject(['html' => $html]);
        $observer->execute(new Observer(['block' => $block, 'transport' => $transport]));

        self::assertSame([], $lifecycle->getViews());
        self::assertTrue($lifecycle->hasHistory());

        return $transport->getHtml();
    }

    private function security(): Security
    {
        $deploymentConfig = $this->createMock(DeploymentConfig::class);
        $deploymentConfig->method('get')->with('crypt/key')->willReturn('skip-render-test-key');

        return new Security($deploymentConfig, new Json());
    }
}
