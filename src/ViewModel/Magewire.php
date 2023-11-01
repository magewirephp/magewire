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
use Magewirephp\Magewire\Model\Magento\System\ConfigMagewire as MagewireSystemConfig;

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
    protected MagewireSystemConfig $magewireSystemConfig;

    public function __construct(
        FormKey $formKey,
        ApplicationState $applicationState,
        ProductMetadataInterface $productMetadata,
        StoreManagerInterface $storeManager,
        LayoutRenderLifecycle $layoutRenderLifecycle,
        MagewireSystemConfig $magewireSystemConfig
    ) {
        $this->formKey = $formKey;
        $this->applicationState = $applicationState;
        $this->productMetaData = $productMetadata;
        $this->storeManager = $storeManager;
        $this->layoutRenderLifecycle = $layoutRenderLifecycle;
        $this->magewireSystemConfig = $magewireSystemConfig;
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
        $postRoute = '/magewire/post';
        if (($this->getSystemName() === 'Magento') && ($this->isBeforeTwoFourZero())) {
            $postRoute = '/magewire/vintage';
        }

        return $postRoute;
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

    public function getSystemConfig(): MagewireSystemConfig
    {
        return $this->magewireSystemConfig;
    }

    /**
     * Get system name
     *
     * @return string
     */
    public function getSystemName(): string
    {
        return $this->productMetaData->getName();
    }
}
