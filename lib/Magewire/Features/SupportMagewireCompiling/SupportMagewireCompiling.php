<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling;

use DateTime;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management\CompilerManager;
use Magewirephp\Magewire\Model\View\SlotsRegistry;
use function Magewirephp\Magewire\on;
use function Magewirephp\Magewire\trigger;

class SupportMagewireCompiling extends ComponentHook
{
    public function __construct(
        private MagewireUnderscoreViewModelFactory $underscoreViewModelFactory,
        private CompilerManager $compilerManager,
        private SlotsRegistry $slotsRegistry
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

                // Concept: Include the Magewire underscore object optionally required by compiled views.
                $result['dictionary']['__magewire'] ??= $this->underscoreViewModelFactory->create();

                if ($this->slotsRegistry->hasAreas()) {
                    $result['dictionary']['__slot'] ??= $this->slotsRegistry->snapshot();
                    $result['dictionary']['__el'] = $this->slotsRegistry->element();
                }

                return $result;
            };
        });

        on('magewire:view:compile', function (Compiler $compiler) {
            $compiler->pipelines()->template()->middleware()->group('components')

                ->pipe(function (string $throughput, callable $next) use ($compiler): string {
                    $result = $next($throughput);

                    $date = new DateTime();
                    return $result . '<?php /** Compile Date/Time: ' . $date->format('Y-m-d H:i:s.u') . ' **/ ?>' . PHP_EOL;
                })

                ->pipe(function (string $throughput, callable $next) use ($compiler): string {
                    $result = $next($throughput);

                    return $result . '<?php /** Template Basepath: ' . $compiler->basePath() . ' **/ ?>' . PHP_EOL;
                })

                // Render the final compilation duration.
                ->pipe(function (string $throughput, callable $next) use ($compiler): string {
                    $result = $next($throughput);

                    $durationMs  = round((microtime(true) - $compiler->compileStartTime()) * 1000, 2);
                    $durationSec = round($durationMs / 1000, 4);

                    return $result . '<?php /** Compile Duration: ' . $durationMs . ' milliseconds (' . $durationSec . ' seconds) **/ ?>' . PHP_EOL;
                });
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
