<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Compiler;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Compiler\FlakeCompiler;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Support\Concerns\AsDataObject;
use function Magewirephp\Magewire\on;

class SupportMagewireFlakes extends ComponentHook
{
    use AsDataObject;

    public function __construct(
        private FlakeCompiler $flakeCompiler
    ) {
        //
    }

    public function provide(): void
    {
        on('hydrate', function (Component $component, array $memo) {
            $block = $component->magewireBlock();

            if (is_array($memo['flake'] ?? null)) {
                $block->setData('magewire:flake', $memo['flake']);
            }
        });

        on('dehydrate', function (Component $component, ComponentContext $context) {
            $metadata = $component->magewireBlock()->getData('magewire:flake');

            if (is_array($metadata) && is_array($metadata['element'] ?? null)) {
                $context->pushMemo('flake', $metadata['element'], 'element');
            }
        });

        on('magewire:view:compile', function (Compiler $compiler) {
            /*
             * Register a middleware in the 'components' group that processes template output
             * through the <flake: compiler. This middleware intercepts the template rendering
             * pipeline, compiles any Flake syntax in the throughput, and passes the compiled
             * result to the next middleware in the chain.
             */
            $compiler->pipelines()->template()->middleware()->group('components')->pipe(
                function (string $throughput, callable $next) {
                    return $next($this->flakeCompiler->compile($throughput));
                }
            );
        });
    }
}
