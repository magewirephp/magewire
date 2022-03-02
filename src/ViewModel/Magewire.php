<?php declare(strict_types=1);
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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class Magewire implements ArgumentInterface
{
    protected FormKey $formKey;
    protected ApplicationState $applicationState;
    protected ProductMetadataInterface $productMetaData;
    protected StoreManagerInterface $storeManager;

    /**
     * @param FormKey $formKey
     * @param ApplicationState $applicationState
     * @param ProductMetadataInterface $productMetadata
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        FormKey $formKey,
        ApplicationState $applicationState,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager
    ) {
        $this->formKey = $formKey;
        $this->applicationState = $applicationState;
        $this->productMetaData = $productMetadata;
        $this->storeManager = $storeManager;
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

    /**
     * @return string
     */
    public function getApplicationUrl(): string
    {
        try {
            $base = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
            return $base . trim($this->getPostRoute(), '/');
        } catch (NoSuchEntityException $exception) {
            return $this->getPostRoute();
        }
    }
}
