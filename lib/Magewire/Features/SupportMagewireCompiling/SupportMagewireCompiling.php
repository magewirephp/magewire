<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management\CompilerManager;
use function Magewirephp\Magewire\on;
use function Magewirephp\Magewire\trigger;

class SupportMagewireCompiling extends ComponentHook
{
    public function __construct(
        private MagewireUnderscoreViewModelFactory $underscoreViewModelFactory,
        private CompilerManager $compilerManager
    ) {
        //
    }

    public function provide(): void
    {
        on('magento:template:render', function (AbstractBlock $block, string $filename, array $dictionary, Component $component) {
            $compiler = $component->magewireCompiler() ?? $component->magewireCompiler(
                $this->compilerManager->factory()->newCompilerInstance()
            );

            return function (array $result) use ($component, $compiler, $block) {
                // Although named "filename", this actually represents the full file path,
                // including the filename and its extension.
                $path = $result['filename'];

                if ($component->magewireCompiler()->canCompile()) {
                    $result['filename'] = $compiler->management()->file()->generateFilePath($path);

                    if ($compiler->requiresRecompile($path)) {
                        trigger('magewire:view:compile', $compiler, $component, $block);
                        $compiler->compile($path, $result['filename']);
                    }
                }

                // Include the Magewire underscore object optionally required by compiled views.
                $result['dictionary']['__magewire'] ??= $this->underscoreViewModelFactory->create();

                return $result;
            };
        });

        on('magewire:view:compile', function (Compiler $compiler) {
            $middleware = $compiler->pipelines()->template()->middleware();

            // Appends a basepath comment to compiled template output.
            $middleware->group('last')->pipe(
                function (string $throughput) use ($compiler) {
                    return $throughput . '<?php /** Template Basepath: ' . $compiler->basePath() . ' **/ ?>' . PHP_EOL;
                }
            );

            // Appends a compilation duration comment to the compiled template output.
            $middleware->group('shutdown')->pipe(
                function (string $throughput) use ($compiler) {
                    $duration = round((microtime(true) - $compiler->compileStartTime()) * 1000, 2) . 'ms';
                    return $throughput . '<?php /** Compile Duration: ' . $duration . ' **/ ?>' . PHP_EOL;
                }
            );
        });
    }

    /**
     * WIP
     *
     * Generate a readable file structure for generated files.
     *
     * Instead of unreadable filenames, create organized directories that map to source files,
     * making it easier for developers to locate and debug generated output.
     *
     * TODO: Consider enabling this only in development mode.
     */
    private function transformToViewPath(Template $block): string
    {
        $template = $block->getTemplate();
        $parts = explode('::', $template);

        if (count($parts) === 2) {
            $parts[0] = str_replace('_', '/', $parts[0]);

            return implode('/', $parts);
        }

        return $block->getTemplateFile();
    }
}
