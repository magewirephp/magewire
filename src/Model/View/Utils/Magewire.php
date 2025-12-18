<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils;

use BadMethodCallException;
use Magewirephp\Magewire\Model\Magento\System\ConfigMagewire as MagewireSystemConfig;
use Magewirephp\Magewire\Model\View\Utils\Magewire\Builder;
use Magewirephp\Magewire\Model\View\Utils\Magewire\Features as FeaturesViewUtil;
use Magewirephp\Magewire\Model\View\Utils\Magewire\Mechanisms as MechanismsViewUtil;
use Magewirephp\Magewire\Model\View\UtilsInterface;
use Psr\Log\LoggerInterface;

class Magewire implements UtilsInterface
{
    public function __construct(
        private readonly Builder $builder,
        private readonly FeaturesViewUtil $features,
        private readonly MechanismsViewUtil $mechanisms,
        private readonly MagewireSystemConfig $config,
        private readonly LoggerInterface $logger
    ) {
        //
    }

    public function features(): FeaturesViewUtil
    {
        return $this->features;
    }

    public function mechanisms(): MechanismsViewUtil
    {
        return $this->mechanisms;
    }

    public function config(): MagewireSystemConfig
    {
        return $this->config;
    }

    public function build(): Builder
    {
        return $this->builder;
    }

    public function getUpdateUri(): string
    {
        return '/magewire/update';
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Returns if Magewire should be loaded.
     */
    public function canRequireMagewire(): bool
    {
        try {
            return $this->mechanisms()->resolveComponents()->doesPageHaveComponents();
        } catch (BadMethodCallException $exception) {
            $this->logger()->critical($exception->getMessage(), ['exception' => $exception]);
        }

        return false;
    }
}
