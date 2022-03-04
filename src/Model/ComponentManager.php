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
use Magewirephp\Magewire\Exception\ComponentActionException;
use Magewirephp\Magewire\Exception\LifecycleException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Context\Hydrator as HydratorContext;
use ReflectionClass;

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
            $hydratorContext->getSecurityHydrator(),
            $hydratorContext->getBrowserEventHydrator(),
            $hydratorContext->getFlashMessageHydrator(),
            $hydratorContext->getErrorHydrator(),
            $hydratorContext->getHashHydrator(),
            $hydratorContext->getQueryStringHydrator(),
            $hydratorContext->getPropertyHydrator(),
            $hydratorContext->getListenerHydrator(),
            $hydratorContext->getLoaderHydrator(),
            $hydratorContext->getEmitHydrator(),
            $hydratorContext->getRedirectHydrator()
        ]);
    }

    /**
     * @param Component $component
     * @param array $updates
     * @throws LocalizedException
     * @throws ComponentActionException
     */
    public function processUpdates(Component $component, array $updates): void
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
    }

    public function compose(Component $component, array $data = []): array
    {
        $properties = array_filter((new ReflectionClass($component))->getProperties(), static function ($property) {
            return $property->isPublic() && !$property->isStatic();
        });

        foreach ($properties as $property) {
            $component->{$property->getName()} = $property->isInitialized($component)
                ? $property->getValue($component)
                : $data[$property->getName()] ?? null;
        }

        return $properties;
    }

    /**
     * Runs on every request, after the component is hydrated,
     * but before an action is performed, or the layout block
     * has been rendered.
     *
     * @param Component $component
     * @return void
     * @throws LifecycleException
     */
    public function hydrate(Component $component): void
    {
        foreach ($this->hydrationPool as $hydrator) {
            try {
                $hydrator->hydrate($component, $component->getRequest());
            } catch (Exception $exception) {
                throw new LifecycleException(
                    __('An error occurred while hydrating %1: %2', [get_class($hydrator), $exception->getMessage()])
                );
            }
        }
    }

    /**
     * Runs on every request, before the component is dehydrated,
     * right before the layout block gets rendered.
     *
     * @param Component $component
     * @throws LifecycleException
     */
    public function dehydrate(Component $component): void
    {
        foreach (array_reverse($this->hydrationPool) as $dehydrator) {
            try {
                $dehydrator->dehydrate($component, $component->getResponse());
            } catch (Exception $exception) {
                throw new LifecycleException(
                    __('An error occurred while dehydrating %1: %2', [get_class($dehydrator), $exception->getMessage()])
                );
            }
        }
    }

    /**
     * @param Template $block
     * @param Component $component
     * @param string|null $handle
     * @return Request
     * @throws LocalizedException
     */
    public function createInitialRequest(
        Template $block,
        Component $component,
        string $handle = null
    ): Request {
        $properties = $component->getPublicProperties(false);
        $request = $block->getRequest();
        //$data = array_intersect_key(array_replace($properties, $arguments), $properties);

        /**
         * SHA1 hashing the wire:id value is an idea which can change in the future. I'm still tumbling around the
         * acceptance of just using the block name which has to be unique which is the most important part. I need to
         * look into the security aspect when switching to an un-hashed version of the wire:id attribute.
         */
        $id = $component->id ?? sha1($block->getNameInLayout());

        $name   = $block->getNameInLayout();
        $handle = $handle ?? $request->getFullActionName();
        $locale = $this->localeResolver->getLocale();

        return $this->httpFactory->createRequest([
            'fingerprint' => [
                'id'     => $id,
                'name'   => $name,
                'locale' => $locale,
                'path'   => '/',
                'method' => 'GET',

                // Custom relative to Livewire's core.
                'handle' => $handle,
                'type'   => $component::COMPONENT_TYPE
            ],
            'serverMemo' => [
                'data'   => $properties
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
