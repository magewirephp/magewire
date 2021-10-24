<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

/**
 * Trait View.
 */
trait View
{
    /** @var bool */
    protected $skipRender = false;

    /** @var bool|array */
    protected $loader = false;

    /**
     * Avoid block rendering on a subsequent request.
     *
     * @return $this
     */
    public function skipRender(): self
    {
        $this->skipRender = true;

        return $this;
    }

    /**
     * Check if the component can be rendered.
     *
     * @return bool
     */
    public function canRender(): bool
    {
        return !$this->skipRender;
    }

    /**
     * Switch template of the parent block.
     *
     * @param string $template
     */
    public function switchTemplate(string $template): void
    {
        if ($parent = $this->getParent()) {
            $parent->setTemplate($template);
        }
    }

    /**
     * @return bool|array
     */
    public function getLoader()
    {
        return $this->loader;
    }
}
