<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes;

use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Fragment\FlakeFragmentFactory;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use function Magewirephp\Magewire\on;

class SupportMagewireFlakes extends ComponentHook
{
    public function __construct(
        private readonly FlakeFragmentFactory $flakeFragmentFactory
    ) {
        //
    }

    function provide(): void
    {
        on('render', function (Component $component, AbstractBlock $block) {
            return function (string $html) use ($block) {
                $metadata = $block->getData('magewire:flake');

                if (is_array($metadata)) {
                    $fragment = $this->flakeFragmentFactory->create();

                    if (is_array($metadata['element'] ?? null)) {
                        $fragment->withAttributes($metadata['element']['attributes']);
                    }

                    return $fragment->wrap($html);
                }

                return $html;
            };
        });

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
    }
}
