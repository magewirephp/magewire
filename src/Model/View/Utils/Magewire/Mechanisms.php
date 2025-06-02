<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Utils\Magewire;

use BadMethodCallException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magewirephp\Magewire\Mechanisms as MechanismsServiceType;
use Magewirephp\Magewire\Mechanisms\FrontendAssets\FrontendAssetsViewModel as FrontendAssetsViewModel;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ResolveComponentsViewModel as ResolveComponentsViewModel;
use Psr\Log\LoggerInterface;

/**
 * @method FrontendAssetsViewModel frontendAssets()
 * @method ResolveComponentsViewModel resolveComponents()
 */
class Mechanisms
{
    public function __construct(
        private readonly MechanismsServiceType $mechanismsServiceType,
        private readonly LoggerInterface $logger
    ) {
        //
    }

    /**
     * @return ArgumentInterface
     */
    public function __call(string $utility, array $arguments = []): ArgumentInterface
    {
        $utility = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $utility));

        try {
            return $this->mechanismsServiceType->viewModel($utility);
        } catch (NotFoundException $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

            throw new BadMethodCallException(
                sprintf('Mechanism view model "%s" does not exist.', $utility)
            );
        }
    }
}
