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
use Magento\Framework\View\Page\Config;
use Magewirephp\Magewire\Exception\AcceptableException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ComponentHydrationException;
use Magewirephp\Magewire\Model\Context\Hydrator as HydratorContext;

class ComponentManager
{
    protected Resolver $localeResolver;
    protected HttpFactory $httpFactory;
    protected Config $pageConfig;
    protected array $updateActionsPool;
    protected array $hydrationPool;

    public function __construct(
        HydratorContext $hydratorContext,
        Resolver $localeResolver,
        HttpFactory $httpFactory,
        Config $pageConfig,
        array $updateActionsPool = [],
        array $hydrationPool = []
    ) {
        $this->localeResolver = $localeResolver;
        $this->updateActionsPool = $updateActionsPool;
        $this->httpFactory = $httpFactory;
        $this->pageConfig = $pageConfig;

        // Core Hydrate & Dehydrate lifecycle sort order.
        $this->hydrationPool = $this->sortHydrators($hydrationPool, [
            $hydratorContext->getFormKeyHydrator(),
            $hydratorContext->getSecurityHydrator(),
            $hydratorContext->getResolverHydrator(),
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
        ]);
    }

    /**
     * @throws LocalizedException
     */
    public function processUpdates(Component $component, array $updates): Component
    {
        if ($component->hasRequest() === false) {
            throw new LocalizedException(__('No request object found'));
        }

        // Temporary typed update basket.
        $types = [];
        // Key to handle the update request.
        $handle = false;

        foreach ($updates as $key => $update) {
            // Process an inspection before the first update by type runs.
            if ($key === 0 || (isset($updates[$key]) && $updates[$key]['type'] !== $updates[$key - 1]['type'])) {
                // Filter out only those who have an simular update type.
                $types = array_filter($updates, fn ($value) => $value['type'] === $update['type']);
                // Lock update handling until inspect releases it.
                $handle = false;

                if (! empty($types)) {
                    $handle = $this->updateActionsPool[$update['type']]->inspect($component, $types);
                }
            }

            if ($handle) {
                try {
                    $this->updateActionsPool[$update['type']]->handle($component, $update['payload']);
                } catch (AcceptableException $exception) {
                    continue;
                } catch (Exception $exception) {
                    throw new LocalizedException(__($exception->getMessage()));
                }

                // Process an evaluation after the last update by type ran.
                if ((! isset($updates[$key + 1]) || $updates[$key + 1]['type'] !== $update['type'])) {
                    $this->updateActionsPool[$update['type']]->evaluate($component, $types);
                }
            }
        }

        return $component;
    }

    /**
     * Runs on every request, after the component is hydrated,
     * but before an action is performed, or the layout block
     * has been rendered.
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
     */
    public function dehydrate(Component $component): Component
    {
        foreach (array_reverse($this->hydrationPool) as $dehydrator) {
            $dehydrator->dehydrate($component, $component->getResponse());
        }

        return $component;
    }

    /**
     * @throws LocalizedException
     */
    public function createInitialRequest(
        Template $block,
        Component $component,
        array $arguments,
        ?string $handle = null
    ): Request {
        $properties = $component->getPublicProperties();
        $request = $block->getRequest();
        $resolver = $component->getResolver();
        $metadata = $component->getMetaData();

        $data = [
            'fingerprint' => [
                'id' => $component->id,
                'name' => $component->name,
                'locale' => $this->localeResolver->getLocale(),
                'path' => '/',
                'method' => 'GET',
                'resolver' => $resolver->getName(),

                // Custom relative to Livewire's core.
                'handle' => $handle ?? $request->getFullActionName(),
                'type' => $component::COMPONENT_TYPE,
                'layout' => $this->pageConfig->getPageLayout()
            ],

            'serverMemo' => [
                'data' => array_intersect_key(array_replace($properties, $arguments), $properties)
            ]
        ];

        if (! empty($metadata)) {
            $data['serverMemo']['dataMeta'] = $metadata;
        }

        return $this->httpFactory->createRequest($data);
    }

    /**
     * [
     *   "class" => object,
     *   "order" => int
     * ]
     *
     * @see ComponentManager::dehydrate()
     * @see ComponentManager::hydrate()
     */
    protected function sortHydrators(array $hydrators, $systemHydrators): array
    {
        $hydrators = array_merge(
            // Map the system hydrators into a class-order arrayed structure.
            array_map(
                static function ($hydator, $key) {
                    $hydator = [
                        'class' => $hydator,
                        'order' => $key * 50
                    ];

                    return $hydator;
                },

                // Context injected core hydrators.
                $systemHydrators,
                // Natural array key to detirmine the order.
                array_keys($systemHydrators)
            ),

            //  Map injected hydrators handling an arrayed or a object type injection.
            array_map(
                static function ($hydrator) use ($systemHydrators) {
                    /*
                     * Hydrators can be injected in two ways.
                     *
                     * 1. Array: where it is required to at least add a 'class' item ('order' is optional).
                     * 2. Object: where the argument is of type 'object'.
                     */
                    if (is_array($hydrator) && $hydrator['class'] ?? null) {
                        $hydrator['order'] = (int) ($hydrator['order'] ?? count($systemHydrators) * 50);
                    } elseif (is_object($hydrator)) {
                        $hydrator = [
                            'class' => $hydrator,
                            'order' => count($systemHydrators) * 50
                        ];
                    } else {
                        throw new ComponentHydrationException(
                            __('Injected hydrator can only be of type array or object.')
                        );
                    }

                    return $hydrator;
                },

                // Additional constructor injected hydrators.
                $hydrators
            )
        );

        usort($hydrators, static function ($x, $y) {
            return $x['order'] - $y['order'];
        });

        return array_column($hydrators, 'class');
    }
}
