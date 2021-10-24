<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\ViewModel;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class Magewire.
 */
class Magewire implements ArgumentInterface
{
    /** @var FormKey */
    protected $formKey;

    /** @var ApplicationState */
    protected $applicationState;

    /** @var ProductMetadataInterface */
    protected $productMetaData;

    /**
     * @param FormKey                  $formKey
     * @param ApplicationState         $applicationState
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        FormKey $formKey,
        ApplicationState $applicationState,
        ProductMetadataInterface $productMetadata
    ) {
        $this->formKey = $formKey;
        $this->applicationState = $applicationState;
        $this->productMetaData = $productMetadata;
    }

    /**
     * @return bool
     */
    public function isDeveloperMode(): bool
    {
        return $this->applicationState->getMode() === ApplicationState::MODE_DEVELOPER;
    }

    /**
     * @return bool
     */
    public function isBeforeTwoFourZero(): bool
    {
        return version_compare($this->productMetaData->getVersion(), '2.4.0', '<');
    }

    /**
     * @return string
     */
    public function getPostRoute(): string
    {
        return $this->isBeforeTwoFourZero() ? '/magewire/vintage' : '/magewire/post';
    }
}
