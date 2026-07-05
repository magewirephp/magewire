<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling;

use DateTime;
use Magento\Framework\DataObject;
use Magewirephp\Magento\Framework\View\TemplateEngine\Php\TemplateRenderDataTransferObject;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Compiler;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Management\CompilerManager;
use Magewirephp\Magewire\Model\View\Management\SlotsManager;

use function Magewirephp\Magewire\before;
use function Magewirephp\Magewire\on;
use function Magewirephp\Magewire\trigger;

class HandleCompiling
{
    public function __construct(
        private MagewireUnderscoreViewModelFactory $underscoreViewModelFactory,
        private CompilerManager $compilerManager,
        private SlotsManager $slotsManager
    ) {
    }

    public function boot(): void
    {
        on('magento:template:render', function (TemplateRenderDataTransferObject $dto) {
            if (! $dto->block() instanceof DataObject) {
                return;
            }

            $component = $dto->block()->getData('magewire');

            if (! $component instanceof Component) {
                return;
            }

            $compiler = $component->magewireCompiler() ?? $component->magewireCompiler($this->compilerManager->factory()->newCompilerInstance());

            $dto->dictionary(['magewire' => $component]);

            if ($component->magewireCompiler()->canCompile()) {
                $compiledPath = $compiler->management()->file()->generateFilePath($dto->filename());

                if ($compiler->requiresRecompile($dto->filename())) {
                    trigger('magewire:view:compile', $compiler, $component, $dto->block());
                    $compiler->compile($dto->filename(), $compiledPath);
                }

                $dto->filename($compiledPath);
            }

            // Concept: Include the Magewire underscore object optionally required by compiled views.
            $dto->dictionary(['__magewire' => $dto->dictionary()['__magewire'] ?? $this->underscoreViewModelFactory->create()]);

            if ($this->slotsManager->registry()->hasAreas()) {
                $snapshot = $this->slotsManager->registry()->makeSnapshot();

                $dto->dictionary([
                    '__slot' => $snapshot,
                    '__component' => $snapshot()->component(),
                    '__attributes' => $snapshot()->component()->attrs()
                ]);
            }
        });

        before('magewire:view:compile', static function (Compiler $compiler) {
            $runs['html'] = 0;

            $compiler
                ->pipelines()
                ->html()
                ->middleware()
                ->group('first-line', 2)
                ->pipe(static function (string $throughput, callable $next) use (&$runs, $compiler) {
                    $runs['html']++;

                    if ($runs['html'] === 1) {
                        return '@template()' . PHP_EOL . $next($throughput);
                    }

                    return $next($throughput);
                });

            $compiler
                ->pipelines()
                ->template()
                ->middleware()
                ->group('last')
                ->pipe(static function (string $throughput, callable $next): string {
                    return $next($throughput) . '@endtemplate';
                });

            $compiler
                ->pipelines()
                ->template()
                ->middleware()
                ->group('last')
                ->pipe(static function (string $throughput, callable $next): string {
                    $result = $next($throughput);
                    $date = new DateTime();

                    return sprintf('%s<?php /** Compile Date/Time: %s **/ ?>' . PHP_EOL, $result, $date->format('Y-m-d H:i:s.u'));
                })
                ->pipe(static function (string $throughput, callable $next) use ($compiler): string {
                    $result = $next($throughput);

                    return sprintf('%s<?php /** Template Basepath: %s **/ ?>' . PHP_EOL, $result, $compiler->basePath());
                })
                ->pipe(static function (string $throughput, callable $next) use ($compiler): string {
                    $start = $compiler->compileStartTime();
                    $result = $next($throughput);

                    $durationMs = round(( microtime(true) - $start ) * 1000, 2);
                    $durationSec = round($durationMs / 1000, 4);

                    return sprintf('%s<?php /** Compile Duration: %.2f ms (%.4f s) **/ ?>' . PHP_EOL, $result, $durationMs, $durationSec);
                });
        });
    }
}
