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

            $block = $dto->block();
            $component = $block->getData('magewire');
            $compiler = $this->compilerManager->resolve($block);

            if (! $compiler instanceof Compiler) {
                return;
            }

            if ($component instanceof Component) {
                $dto->dictionary(['magewire' => $component]);
            }

            if ($compiler->canCompile()) {
                $compiledPath = $compiler->management()->file()->generateFilePath($dto->filename());

                if ($compiler->requiresRecompile($dto->filename())) {
                    trigger('magewire:view:compile', $compiler, $component, $block);
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
            $compiler
                ->pipelines()
                ->template()
                ->middleware()
                ->group('first-line', 2)
                ->pipe(static function (string $throughput, callable $next): string {
                    $scope = '@template()' . PHP_EOL;

                    if (! str_starts_with(ltrim($throughput), '<?php')) {
                        return $next($scope . $throughput);
                    }

                    $headerEnd = strpos($throughput, '?>');

                    if ($headerEnd === false) {
                        return $next($throughput);
                    }

                    return $next(substr_replace($throughput, PHP_EOL . $scope, $headerEnd + 2, 0));
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
