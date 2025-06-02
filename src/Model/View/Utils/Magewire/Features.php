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
use Magewirephp\Magewire\Features as FeaturesServiceType;
use Psr\Log\LoggerInterface;

class Features
{
    public function __construct(
        private readonly FeaturesServiceType $featuresServiceType,
        private readonly LoggerInterface $logger
    ) {
        //
    }

    /**
     * @return ArgumentInterface
     */
    public function __call(string $utility, array $arguments = []): ArgumentInterface
    {
        try {
            return $this->featuresServiceType->viewModel($utility);
        } catch (NotFoundException $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

            throw new BadMethodCallException(
                sprintf('Feature view model "%1" does not exist.', $utility)
            );
        }
    }
}
