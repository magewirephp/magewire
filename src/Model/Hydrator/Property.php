<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Helper\Property as PropertyHelper;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

/**
 * Class Property
 * @package Magewirephp\Magewire\Model\Hydrator
 */
class Property implements HydratorInterface
{
    /** @var PropertyHelper $propertyHelper */
    private $propertyHelper;

    /**
     * Property constructor.
     * @param PropertyHelper $propertyHelper
     */
    public function __construct(
        PropertyHelper $propertyHelper
    ) {
        $this->propertyHelper = $propertyHelper;
    }

    /**
     * @inheritdoc
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if ($request->isPreceding()) {
            /**
             * There is one problem with array in the case. When an empty array is assigned
             * inside the component, it will never be possible to overwrite it with a layout
             * data array. This is mainly because the merge is not recursive. To temporary fix this,
             * you should not set an empty array as the default property value, just leave it as null.
             */
            $request->memo['data'] = array_merge(
                $request->memo['data'],
                array_filter($component->getPublicProperties(true), function ($value) {
                    return $value !== null;
                })
            );
        }

        // Bind regular properties.
        $this->propertyHelper->assign(function (Component $component, $request, $property, $value) {
            $component->{$property} = $value;
        }, $request, $component);

        // Flush properties cache.
        $component->getPublicProperties(true);
    }

    /**
     * @inheritdoc
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        if ($response->getRequest()->isSubsequent()) {
            $response->effects['dirty'] = [];

            $this->propertyHelper->assign(function (Component $component, ResponseInterface $response, $property) {
                // A lot have could happen to the property, so lets set it one more time.
                $response->memo['data'][$property] = $component->{$property};

                // The property can be seen as changed and dirty data, who needs a refresh.
                if (is_array($component->{$property})) {
                    $this->processArrayProperty($response, $property);
                } else {
                    $this->processProperty($response, $property);
                }
            }, $response, $component);
        }
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
}
