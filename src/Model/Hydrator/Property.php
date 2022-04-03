<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Component as MagewireComponent;
use Magewirephp\Magewire\Helper\Component as ComponentHelper;
use Magewirephp\Magewire\Helper\Property as PropertyHelper;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;
use Magewirephp\Magewire\Model\WireableInterface;
use ReflectionClass;
use ReflectionException;

class Property implements HydratorInterface
{
    protected PropertyHelper $propertyHelper;
    protected ComponentHelper $componentHelper;
    protected SerializerInterface $serializer;

    /**
     * @param PropertyHelper $propertyHelper
     * @param ComponentHelper $componentHelper
     * @param SerializerInterface $serializer
     */
    public function __construct(
        PropertyHelper $propertyHelper,
        ComponentHelper $componentHelper,
        SerializerInterface $serializer
    ) {
        $this->propertyHelper = $propertyHelper;
        $this->componentHelper = $componentHelper;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     * @throws ReflectionException
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if ($request->isSubsequent()) {
            $overwrite = $request->memo['data'];
        }

        /** @var array $wireables */
        $meta['wireables'] = $this->propertyHelper->searchViaDots('dataMeta.wireables', $request->memo, []);

        $this->propertyHelper->assign(function (Component $component, string $property, $value) use ($meta) {
            if (in_array($property, $meta['wireables'])) {
                $component->{$property} = $component->{$property}->unwire($value);
            } elseif (is_array($component->{$property}) && is_array($value)) {
                $a = $this->serializer->serialize($component->{$property});
                $b = $this->serializer->serialize($value);

                if ($a !== $b) {
                    $component->{$property} = $value;
                }
            } elseif ($component->{$property} !== $value) {
                $component->{$property} = $value;
            }
        }, $component, $overwrite ?? null);

        if ($request->isSubsequent()) {
            $this->executePropertyLifecycleHook($component, 'hydrate', $request);
            $this->executeLifecycleHook('hydrate', $component);
        } else {
            $request->memo['data'] = array_merge(
                $request->memo['data'],
                array_filter(
                    $component->getPublicProperties(true),
                    function ($value) {
                        return $value !== null;
                    }
                )
            );
        }

        $component->getPublicProperties(true);
    }

    /**
     * @inheritdoc
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        if ($response->getRequest()->isSubsequent()) {
            $response->effects['dirty'] = [];

            $this->propertyHelper->assign(function (Component $component, $property, $value) use ($response) {
                // A lot have could happen to the property, so lets set it one more time.
                $response->memo['data'][$property] = $component->{$property};

                // The property can be seen as changed and dirty data, who needs a refresh.
                if (is_array($component->{$property})) {
                    $this->processArrayProperty($response, $property);
                } elseif ($component->{$property} instanceof WireableInterface && version_compare(PHP_VERSION, '7.4', '>=')) {
                    $this->processWireableproperty($response, $property);
                } else {
                    $this->processProperty($response, $property);
                }
            }, $component, $response->memo['data']);
        } else {
            array_walk($response->memo['data'], function ($value, $key) use ($component, $response) {
                if ($value instanceof WireableInterface) {
                    $response->memo['dataMeta']['wireables'][] = $key;
                    $response->memo['data'][$key] = $value->wire();
                }
            });
        }

        $this->executeLifecycleHook('dehydrate', $component);
        $this->executePropertyLifecycleHook($component, 'dehydrate', $response);
    }

    /**
     * @param ResponseInterface $response
     * @param string $property
     */
    public function processArrayProperty(ResponseInterface $response, string $property): void
    {
        $request = $response->getRequest();

        $updates = array_filter($request->getUpdates(), function ($update) {
            return $update['type'] === 'syncInput' && $this->propertyHelper->containsDots($update['payload']['name']);
        });

        if (count($updates)) {
            foreach ($updates as $update) {
                $a = $this->propertyHelper->searchViaDots($update['payload']['name'], $request->getServerMemo('data'));
                $b = $this->propertyHelper->searchViaDots($update['payload']['name'], $response->getServerMemo('data'));

                if ($a != $b) {
                    $response->effects['dirty'][] = $update['payload']['name'];
                }
            }
        }
    }

    public function processWireableProperty(ResponseInterface $response, string $property)
    {
        $response->effects['dirty'][] = $property;
    }

    /**
     * @param ResponseInterface $response
     * @param string $property
     */
    public function processProperty(ResponseInterface $response, string $property)
    {
        $response->effects['dirty'][] = $property;
    }

    /**
     * @param string $method
     * @param MagewireComponent $component
     * @param array $params
     */
    public function executeLifecycleHook(string $method, Component $component, array $params = [])
    {
        $component->{$method}(...$params);
    }

    /**
     * @param MagewireComponent $component
     * @param string $type
     * @param object $object
     */
    public function executePropertyLifecycleHook(MagewireComponent $component, string $type, object $object): void
    {
        foreach ($component->getPublicProperties() as $property => $value) {
            $component->{strtolower($type) . ucfirst($property)}($value, $object);
        }
    }
}
