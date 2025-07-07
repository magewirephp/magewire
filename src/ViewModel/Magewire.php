<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magewirephp\Magewire\Model\Magento\System\ConfigMagewire as MagewireSystemConfig;
use Magewirephp\Magewire\Model\View\Utils as ViewUtils;

class Magewire implements ArgumentInterface
{
    function __construct(
        private readonly ViewUtils $utils
    ) {
        //
    }

    public function utils(string|null $name = null, array $arguments = []): ViewUtils
    {
        return $name ? $this->utils->$name($arguments) : $this->utils;
    }

    /**
     * @deprecated To make backwards compatible, utils()->environment() should be used instead.
     * @see ViewUtils\Environment
     */
    function isDeveloperMode(): bool
    {
        return $this->utils->env()->isDeveloperMode();
    }

    /**
     * @deprecated To make backwards compatible, utils()->environment() should be used instead.
     * @see ViewUtils\Environment
     */
    function isProductionMode(): bool
    {
        return $this->utils->env()->isProductionMode();
    }

    /**
     * @deprecated To make backwards compatible, utils()->magewire() should be used instead.
     * @see ViewUtils\Magewire
     */
    function getSystemConfig(): MagewireSystemConfig
    {
        return $this->utils()->magewire()->config();
    }

    /**
     * @deprecated To make backwards compatible, utils()->magewire() method should be used instead.
     * @see ViewUtils\Magewire
     */
    function pageRequiresMagewire(): bool
    {
        return $this->utils()->magewire()->mechanisms()->resolveComponents()->doesPageHaveComponents();
    }

    /**
     * @deprecated To make backwards compatible, utils()->magewire() method should be used instead.
     * @see ViewUtils\Magewire
     */
    function getUpdateUri(): string
    {
        return $this->utils()->magewire()->getUpdateUri();
    }

    /**
     * @deprecated To make backwards compatible, utils()->magewire() method should be used instead.
     * @see ViewUtils\Magewire
     */
    function getScriptPath(): string
    {
        return $this->utils()->magewire()->mechanisms()->frontendAssets()->getScriptPath();
    }
}
