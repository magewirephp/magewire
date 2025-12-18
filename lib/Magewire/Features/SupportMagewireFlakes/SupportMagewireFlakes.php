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
use Magewirephp\Magewire\Support\Pipeline;
use function Magewirephp\Magewire\on;

class SupportMagewireFlakes extends ComponentHook
{
    use AsDataObject;

    public function __construct(private FlakeCompiler $flakeCompiler)
    {
    }

    public function provide(): void
    {
        on('hydrate', function (Component $component, array $memo) {
            $block = $component->block();

            if (is_array($memo['flake'] ?? null)) {
                $block->setData('magewire:flake', $memo['flake']);
            }
        });

        on('dehydrate', function (Component $component, ComponentContext $context) {
            $metadata = $component->block()->getData('magewire:flake');

            if (is_array($metadata) && is_array($metadata['element'] ?? null)) {
                $context->pushMemo('flake', $metadata['element'], 'element');
            }
        });

        on('magewire:view:compile', function (Compiler $compiler) {
            if ($compiler instanceof Compiler\MagewireCompiler) {
                $compiler->pipeline()->middleware()->group('components')->pipe(
                    fn (string $throughput, callable $next) => $next($this->flakeCompiler->compile($throughput))
                );
            }
        });
    }
}
