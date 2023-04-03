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
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Layout;
use Magento\Store\Model\StoreManagerInterface;
use Magewirephp\Magewire\Model\ComponentFactory;
use Magewirephp\Magewire\Model\LayoutRenderLifecycle;

/**
 * @api
 */
class Magewire implements ArgumentInterface
{
    protected FormKey $formKey;
    protected ApplicationState $applicationState;
    protected ProductMetadataInterface $productMetaData;
    protected StoreManagerInterface $storeManager;
    protected LayoutRenderLifecycle $layoutRenderLifecycle;
    protected Layout $layout;
    protected ComponentFactory $componentFactory;

    public function __construct(
        FormKey $formKey,
        ApplicationState $applicationState,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager,
        LayoutRenderLifecycle $layoutRenderLifecycle,
        ComponentFactory $componentFactory
    ) {
        $this->formKey = $formKey;
        $this->applicationState = $applicationState;
        $this->productMetaData = $productMetadata;
        $this->storeManager = $storeManager;
        $this->layoutRenderLifecycle = $layoutRenderLifecycle;
        $this->componentFactory = $componentFactory;
    }

    public function isDeveloperMode(): bool
    {
        return $this->applicationState->getMode() === ApplicationState::MODE_DEVELOPER;
    }

    public function isProductionMode(): bool
    {
        return $this->applicationState->getMode() === ApplicationState::MODE_PRODUCTION;
    }
    
    public function isBeforeTwoFourZero(): bool
    {
        return version_compare($this->productMetaData->getVersion(), '2.4.0', '<');
    }

    public function getPostRoute(): string
    {
        return $this->isBeforeTwoFourZero() ? '/magewire/vintage' : '/magewire/post';
    }

    public function getApplicationUrl(): string
    {
        try {
            return $this->storeManager->getStore()->getBaseUrl() . trim($this->getPostRoute(), '/');
        } catch (NoSuchEntityException $exception) {
            return $this->getPostRoute();
        }
    }

    /**
     * Check whether the page contains any Magewire components.
     */
    public function pageRequiresMagewire(): bool
    {
        return $this->layoutRenderLifecycle->hasHistory();
    }
}
