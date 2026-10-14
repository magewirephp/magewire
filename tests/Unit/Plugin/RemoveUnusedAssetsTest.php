<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Plugin;

use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Result\Layout as LayoutResult;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ResolveComponentsViewModel;
use Magewirephp\Magewire\Plugin\Magento\Framework\View\Result\RemoveUnusedAssets;
use PHPUnit\Framework\TestCase;

class RemoveUnusedAssetsTest extends TestCase
{
    private const STYLES = '<link  rel="stylesheet" type="text/css"  media="all" href="https://shop.test/static/version1/frontend/Hyva/default/en_US/Magewirephp_Magewire/css/magewire.css" />';
    private const THEME_STYLES = '<link  rel="stylesheet" type="text/css"  media="all" href="https://shop.test/static/version1/frontend/Hyva/default/en_US/css/styles.css" />';
    private const PROXY = '<script>window.Magewire = new Proxy({}, {});</script>';

    public function test_a_page_without_components_loses_the_styles_and_the_early_scripts(): void
    {
        $response = $this->render($this->page(), components: false);

        self::assertSame(
            '<html><head>' . self::THEME_STYLES . "\n" . '</head><body><p>content</p></body></html>',
            $response->getContent()
        );
    }

    public function test_a_page_with_components_keeps_both_without_the_markers(): void
    {
        $response = $this->render($this->page(), components: true);

        self::assertSame(
            '<html><head>' . self::THEME_STYLES . "\n" . self::STYLES . "\n" . '</head><body>' . "\n" . self::PROXY . "\n"
            . '<p>content</p></body></html>',
            $response->getContent()
        );
    }

    public function test_the_root_argument_keeps_everything_on_a_page_without_components(): void
    {
        $response = $this->render($this->page(), components: false, renderWithoutComponents: true);

        self::assertStringContainsString(self::STYLES, $response->getContent());
        self::assertStringContainsString(self::PROXY, $response->getContent());
        self::assertStringNotContainsString('data-magewire-priority', $response->getContent());
    }

    public function test_a_page_without_the_markers_stays_as_it_is(): void
    {
        $page = '<html><head>' . self::STYLES . '</head><body></body></html>';

        self::assertSame($page, $this->render($page, components: false)->getContent());
    }

    public function test_minified_styles_with_a_query_are_removed_too(): void
    {
        $styles = '<link rel="stylesheet" href="/static/frontend/Hyva/default/en_US/Magewirephp_Magewire/css/magewire.min.css?v=2">';

        self::assertSame(
            '<head></head>',
            ( new RemoveUnusedAssets($this->createMock(ResolveComponentsViewModel::class)) )->removeOutput('<head>' . $styles . '</head>')
        );
    }

    private function page(): string
    {
        return '<html><head>' . self::THEME_STYLES . "\n" . self::STYLES . "\n" . '</head><body>'
            . RemoveUnusedAssets::PRIORITY_START . "\n" . self::PROXY . "\n" . RemoveUnusedAssets::PRIORITY_END . "\n"
            . '<p>content</p></body></html>';
    }

    private function render(string $page, bool ...$flags): HttpResponse
    {
        $components = $flags['components'];
        $renderWithoutComponents = $flags['renderWithoutComponents'] ?? false;
        $resolveComponents = $this->createMock(ResolveComponentsViewModel::class);
        $resolveComponents->method('doesPageHaveComponents')->willReturn($components);

        $root = $this->createMock(Template::class);
        $root->method('getData')->willReturnCallback(
            static fn ($key) => $key === 'render_without_components' ? $renderWithoutComponents : null
        );
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getBlock')->willReturnCallback(static fn ($name) => $name === 'magewire' ? $root : false);
        $result = $this->createMock(LayoutResult::class);
        $result->method('getLayout')->willReturn($layout);

        $response = $this->createMock(HttpResponse::class);
        $content = $page;
        $response->method('getContent')->willReturnCallback(static function () use (&$content) {
            return $content;
        });
        $response->method('setContent')->willReturnCallback(static function ($value) use (&$content, $response) {
            $content = $value;

            return $response;
        });

        ( new RemoveUnusedAssets($resolveComponents) )->afterRenderResult($result, $result, $response);

        return $response;
    }
}
