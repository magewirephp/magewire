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
use function Magewirephp\Magewire\before;
use function Magewirephp\Magewire\on;
use function Magewirephp\Magewire\trigger;

class SupportMagewireCompiling extends ComponentHook
{
    private int $i = 0;

    public function __construct(
        private MagewireUnderscoreViewModelFactory $underscoreViewModelFactory,
        private CompilerManager $compilerManager,
        private SlotsRegistry $slotsRegistry
    ) {
        
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

                // Currently only for dev-purposes, will change over time and shouldn't be used.
                if ($this->slotsRegistry->hasAreas()) {
                    $result['dictionary']['__slot'] ??= $this->slotsRegistry->snapshot();
                    $result['dictionary']['__el'] = $this->slotsRegistry->element();
                }

                return $result;
            };
        });

        before('magewire:view:compile', static function (Compiler $compiler) {
            $runs['html'] = 0;

            $compiler->pipelines()->html()->middleware()->group('first-line', 2)

                ->pipe(static function (string $throughput, callable $next) use (&$runs, $compiler) {
                    $runs['html']++;

                    if ($runs['html'] === 1) {
                        return '@template()' . PHP_EOL . $next($throughput);
                    }

                    return $next($throughput);
                });

            $compiler->pipelines()->template()->middleware()->group('last')
                ->pipe(static function (string $throughput, callable $next): string {
                    return $next($throughput) . '@endtemplate';
                });

            $compiler->pipelines()->template()->middleware()->group('last')

                ->pipe(static function (string $throughput, callable $next): string {
                    $result = $next($throughput);
                    $date = new DateTime();

                    return $result . sprintf(
                            '<?php /** Compile Date/Time: %s **/ ?>' . PHP_EOL,
                            $date->format('Y-m-d H:i:s.u')
                        );
                })

                ->pipe(static function (string $throughput, callable $next) use ($compiler): string {
                    $result = $next($throughput);

                    return $result . sprintf(
                            '<?php /** Template Basepath: %s **/ ?>' . PHP_EOL,
                            $compiler->basePath()
                        );
                })

                ->pipe(static function (string $throughput, callable $next) use ($compiler): string {
                    $start = $compiler->compileStartTime();
                    $result = $next($throughput);

                    $durationMs  = round((microtime(true) - $start) * 1000, 2);
                    $durationSec = round($durationMs / 1000, 4);

                    return $result . sprintf(
                            '<?php /** Compile Duration: %.2f ms (%.4f s) **/ ?>' . PHP_EOL,
                            $durationMs,
                            $durationSec
                        );
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
