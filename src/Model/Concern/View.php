<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

trait View
{
    protected bool $skipRender = false;
    /** @var bool|array */
    protected $loader = false;

    /**
     * Avoid template rendering and preserve the existing HTML on a subsequent request.
     */
    public function skipRender(bool $skip = true): self
    {
        $this->skipRender = $skip;
        return $this;
    }

    /**
     * Check if the component can be rendered.
     */
    public function canRender(): bool
    {
        return !$this->skipRender;
    }

    /**
     * Switch template of the parent block.
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
