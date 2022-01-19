<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Component as MagewireComponent;
use Magewirephp\Magewire\Helper\Component as ComponentHelper;
use Magewirephp\Magewire\Helper\Property as PropertyHelper;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class Property implements HydratorInterface
{
    /** @var PropertyHelper $propertyHelper */
    private $propertyHelper;
    /** @var ComponentHelper $componentHelper */
    private $componentHelper;

    /**
     * @param PropertyHelper $propertyHelper
     * @param ComponentHelper $componentHelper
     */
    public function __construct(
        PropertyHelper $propertyHelper,
        ComponentHelper $componentHelper
    ) {
        $this->propertyHelper = $propertyHelper;
        $this->componentHelper = $componentHelper;
    }

    /**
     * @inheritdoc
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        $data = $this->componentHelper->extractDataFromBlock($component->getParent(), ['request' => $request]);
        $dataValues = array_values($data);

        /** @lifecyclehook boot */
        $component->boot(...$dataValues);

        if ($request->isPreceding()) {
            /** @lifecyclehook mount */
            $component->mount(...$dataValues);
        } else {
            $overwrite = $request->memo['data'];
        }

        // Bind regular properties.
        $this->propertyHelper->assign(function (Component $component, $property, $value) {
            if ($component->{$property} !== $value) {
                $component->{$property} = $value;
            }
        }, $component, $overwrite ?? null);

        if ($request->isSubsequent()) {
            $this->executePropertyLifecycleHook($component, 'hydrate', $request);
            /** @lifecyclehook hydrate */
            $component->hydrate($request);
        } else {
            $request->memo['data'] = array_merge(
                $request->memo['data'],
                array_filter($component->getPublicProperties(true), function ($value) {
                    return $value !== null;
                })
            );
        }

        // Flush properties cache.
        $component->getPublicProperties(true);
        /** @lifecyclehook booted */
        $component->booted(...$dataValues);
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
                } else {
                    $this->processProperty($response, $property);
                }
            }, $component, $response->memo['data']);
        }

        $component->dehydrate($response);
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

    /**
     * @param ResponseInterface $response
     * @param string $property
     */
    public function processProperty(ResponseInterface $response, string $property)
    {
        $response->effects['dirty'][] = $property;
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
