<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Model\View\Fragment;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Framework\ObjectManager\Config\Config;
use Magento\Framework\ObjectManager\Factory\Dynamic\Developer;
use Magento\Framework\ObjectManager\ObjectManager as NativeObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Model\Csp;
use Magewirephp\Magewire\Model\View\ComponentAttributeBag;
use Magewirephp\Magewire\Model\View\Fragment\Script;
use Magewirephp\Magewire\Support\DataArray;
use Magewirephp\Magewire\Support\DataCollection;
use Magewirephp\Magewire\Support\DataCollection\Filter;
use Magewirephp\Magewire\Support\DataCollection\Filter\DataCollectionFilter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

class ScriptTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager = null;

    protected function setUp(): void
    {
        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();

        // The fragment builds its attribute collections through the object manager, configured as in etc/di.xml.
        $config = new Config();
        $config->extend([
            'preferences' => [
                DataCollection::class => DataArray::class,
                Filter::class => DataCollectionFilter::class
            ],
            DataArray::class => ['shared' => false],
            ComponentAttributeBag::class => ['shared' => false],
            DataCollectionFilter::class => ['shared' => false]
        ]);
        $factory = new Developer($config);
        $objectManager = new NativeObjectManager($factory, $config);
        $factory->setObjectManager($objectManager);

        ObjectManager::setInstance($objectManager);
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);
    }

    public function test_the_csp_hash_matches_the_rendered_script(): void
    {
        $csp = new Csp();
        $hash = null;

        // Hashes the script the way the CSP fragment modifier does, and adds a nonce like it does without FPC.
        $script = $this->script(static function (Script $script) use ($csp, &$hash): void {
            $hash = $csp->generateHash($script->getScriptCode());
            $script->withAttribute('nonce', 'abc123');
        });

        ob_start();
        $script->start();
        echo "<script>\n    document.addEventListener('magewire:init', () => {});\n</script>";
        $script->end();
        $output = ob_get_clean();

        self::assertSame("<script nonce=\"abc123\">\n    document.addEventListener('magewire:init', () => {});\n</script>", $output);
        self::assertMatchesRegularExpression('#^<script[^>]*>(.*)</script>$#s', $output);

        preg_match('#^<script[^>]*>(.*)</script>$#s', $output, $matches);

        self::assertSame(base64_encode(hash('sha256', $matches[1], true)), $hash);
    }

    public function test_the_script_code_is_the_content_between_the_tags(): void
    {
        $script = $this->script();

        ob_start();
        $script->start();
        echo "<script type=\"module\">\n\tconst a = 1;\n</script>";
        $script->end();
        ob_end_clean();

        self::assertSame("\n\tconst a = 1;\n", $script->getScriptCode());
    }

    private function script(callable|null $modifier = null): Script
    {
        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtmlAttr')->willReturnArgument(0);

        return new Script($this->createMock(LoggerInterface::class), $escaper, $modifier ? [$modifier] : []);
    }
}
