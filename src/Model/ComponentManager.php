<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Exception\AcceptableException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Context\Hydrator as HydratorContext;

class ComponentManager
{
    protected Resolver $localeResolver;
    protected HttpFactory $httpFactory;
    protected array $updateActionsPool;
    protected array $hydrationPool;

    /**
     * ComponentManager constructor.
     * @param HydratorContext $hydratorContext
     * @param Resolver $localeResolver
     * @param HttpFactory $httpFactory
     * @param array $updateActionsPool
     * @param array $hydrationPool
     */
    public function __construct(
        HydratorContext $hydratorContext,
        Resolver $localeResolver,
        HttpFactory $httpFactory,
        array $updateActionsPool = [],
        array $hydrationPool = []
    ) {
        $this->localeResolver = $localeResolver;
        $this->updateActionsPool = $updateActionsPool;
        $this->httpFactory = $httpFactory;

        // Core Hydrate & Dehydrate lifecycle sort order.
        $this->hydrationPool = $this->sortHydrators($hydrationPool, [
            $hydratorContext->getFormKeyHydrator(),
            $hydratorContext->getSecurityHydrator(),
            $hydratorContext->getPostDeploymentHydrator(),
            $hydratorContext->getChildrenHydrator(),
            $hydratorContext->getBrowserEventHydrator(),
            $hydratorContext->getFlashMessageHydrator(),
            $hydratorContext->getErrorHydrator(),
            $hydratorContext->getHashHydrator(),
            $hydratorContext->getQueryStringHydrator(),
            $hydratorContext->getPropertyHydrator(),
            $hydratorContext->getListenerHydrator(),
            $hydratorContext->getLoaderHydrator(),
            $hydratorContext->getEmitHydrator(),
            $hydratorContext->getRedirectHydrator(),
            $hydratorContext->getReloadSectionDataHydrator(),
        ]);
    }

    /**
     * @param Component $component
     * @param array $updates
     * @return Component
     * @throws LocalizedException
     */
    public function processUpdates(Component $component, array $updates): Component
    {
        if ($component->hasRequest() === false) {
            throw new LocalizedException(__('No request object found'));
        }

        foreach ($updates as $update) {
            try {
                $this->updateActionsPool[$update['type']]->handle($component, $update['payload']);
            } catch (AcceptableException $exception) {
                continue;
            } catch (Exception $exception) {
                throw new LocalizedException(__($exception->getMessage()));
            }
        }

        return $component;
    }

    /**
     * Runs on every request, after the component is hydrated,
     * but before an action is performed, or the layout block
     * has been rendered.
     *
     * @param Component $component
     * @return Component
     */
    public function hydrate(Component $component): Component
    {
        foreach ($this->hydrationPool as $hydrator) {
            $hydrator->hydrate($component, $component->getRequest());
        }

        return $component;
    }

    /**
     * Runs on every request, before the component is dehydrated,
     * right before the layout block gets rendered.
     *
     * @param Component $component
     * @return Component
     */
    public function dehydrate(Component $component): Component
    {
        foreach (array_reverse($this->hydrationPool) as $dehydrator) {
            $dehydrator->dehydrate($component, $component->getResponse());
        }

        return $component;
    }

    /**
     * @param Template $block
     * @param Component $component
     * @param array $arguments
     * @param string|null $handle
     * @return Request
     * @throws LocalizedException
     */
    public function createInitialRequest(
        Template $block,
        Component $component,
        array $arguments,
        string $handle = null
    ): Request {
        $properties = $component->getPublicProperties();
        $request = $block->getRequest();
        $data = array_intersect_key(array_replace($properties, $arguments), $properties);

        $handle = $handle ?? $request->getFullActionName();
        $locale = $this->localeResolver->getLocale();

        return $this->httpFactory->createRequest([
            'fingerprint' => [
                'id' => $component->id,
                'name' => $component->name,
                'locale' => $locale,
                'path' => '/',
                'method' => 'GET',

                // Custom relative to Livewire's core.
                'handle' => $handle,
                'type' => $component::COMPONENT_TYPE
            ],
            'serverMemo' => [
                'data' => $data
            ]
        ]);
    }

    /**
     * [
     *   "class" => object,
     *   "order" => int
     * ]
     *
     * @param array $hydrators
     * @param $systemHydrators
     * @return array
     *
     * @see ComponentManager::dehydrate()
     * @see ComponentManager::hydrate()
     */
    protected function sortHydrators(array $hydrators, $systemHydrators): array
    {
        usort($hydrators, static function ($x, $y) {
            return $x['order'] - $y['order'];
        });

        return array_merge($systemHydrators, array_column($hydrators, 'class'));
    }
}
