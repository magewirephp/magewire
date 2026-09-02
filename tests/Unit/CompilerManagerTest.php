<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Compiler;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\CompilerFactory;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\CompilerUtils;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Management\CompilerManager;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Management\DirectiveManager;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Management\FileManager;
use PHPUnit\Framework\TestCase;

class CompilerManagerTest extends TestCase
{
    public function test_it_resolves_the_default_compiler_for_an_opted_in_block(): void
    {
        $compiler = $this->createMock(Compiler::class);
        $factory = $this->createMock(CompilerFactory::class);
        $factory->expects(self::once())->method('newCompilerInstance')->willReturn($compiler);
        $data = ['magewire:compile' => true];
        $block = $this->block($data);

        $resolved = $this->manager($factory)->resolve($block);

        self::assertSame($compiler, $resolved);
        self::assertInstanceOf(ArgumentInterface::class, $compiler);
        self::assertSame($compiler, $data['magewire:compiler']);
        self::assertArrayNotHasKey('magewire', $data);
    }

    public function test_it_uses_a_configured_custom_compiler(): void
    {
        $compiler = $this->createMock(Compiler::class);
        $factory = $this->createMock(CompilerFactory::class);
        $factory->expects(self::never())->method('newCompilerInstance');
        $data = [
            'magewire:compile' => true,
            'magewire:compiler' => $compiler,
        ];

        self::assertSame($compiler, $this->manager($factory)->resolve($this->block($data)));
    }

    public function test_a_custom_compiler_does_not_enable_an_ordinary_block_by_itself(): void
    {
        $compiler = $this->createMock(Compiler::class);
        $factory = $this->createMock(CompilerFactory::class);
        $factory->expects(self::never())->method('newCompilerInstance');
        $data = ['magewire:compiler' => $compiler];

        self::assertNull($this->manager($factory)->resolve($this->block($data)));
    }

    public function test_it_preserves_a_compiler_owned_by_a_magewire_component(): void
    {
        $compiler = $this->createMock(Compiler::class);
        $component = new class extends Component {
        };
        $component->magewireCompiler($compiler);
        $factory = $this->createMock(CompilerFactory::class);
        $factory->expects(self::never())->method('newCompilerInstance');
        $data = ['magewire' => $component];

        self::assertSame($compiler, $this->manager($factory)->resolve($this->block($data)));
    }

    public function test_a_block_compiler_overrides_the_component_default(): void
    {
        $componentCompiler = $this->createMock(Compiler::class);
        $blockCompiler = $this->createMock(Compiler::class);
        $component = new class extends Component {
        };
        $component->magewireCompiler($componentCompiler);
        $factory = $this->createMock(CompilerFactory::class);
        $factory->expects(self::never())->method('newCompilerInstance');
        $data = [
            'magewire' => $component,
            'magewire:compiler' => $blockCompiler,
        ];

        $resolved = $this->manager($factory)->resolve($this->block($data));

        self::assertSame($blockCompiler, $resolved);
        self::assertSame($blockCompiler, $component->magewireCompiler());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function block(array &$data): AbstractBlock
    {
        $block = $this->createMock(AbstractBlock::class);
        $block->method('getData')->willReturnCallback(
            static fn (string $key): mixed => $data[$key] ?? null
        );
        $block->method('setData')->willReturnCallback(
            static function (string $key, mixed $value) use (&$data, $block): AbstractBlock {
                $data[$key] = $value;

                return $block;
            }
        );

        return $block;
    }

    private function manager(CompilerFactory $factory): CompilerManager
    {
        return new CompilerManager(
            $this->createMock(DirectiveManager::class),
            $this->createMock(FileManager::class),
            $factory,
            $this->createMock(CompilerUtils::class)
        );
    }
}
