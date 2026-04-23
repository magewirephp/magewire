<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Concern;

use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Exception\BackwardsIncompatibilityException;

use function Magewirephp\Magewire\store;

/**
 * @deprecated Replaced by topic-specific features
 *             or direct component methods to reduce API surface complexity.
 */
trait View
{
    /**
     * Check if the component can be rendered.
     *
     * @deprecated Has been replaced with a storage 'skipRender' property.
     */
    public function canRender(): bool
    {
        if (property_exists($this, 'skipRender')) {
            return ! $this->skipRender;
        }

        return store($this)->get('skipRender', false);
    }

    /**
     * Switch template of the parent block.
     *
     * @deprecated Has been replaced with calling the 'magewireBlock' manually and is no longer a shortcut method.
     * @throws BackwardsIncompatibilityException
     */
    public function switchTemplate(string $template): void
    {
        if (method_exists($this, 'magewireBlock')) {
            $block = $this->magewireBlock();

            if ($block instanceof Template) {
                $block->setTemplate($template);
                return;
            }
        }

        throw new BackwardsIncompatibilityException('Something went wrong while switching template.');
    }
}
