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
            $setter = 'set' . ucfirst($property);
            (method_exists($component,$setter))?$component->$setter($value):$component->{$property} = $value;
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

            $this->propertyHelper->assign(function (Component $component, $response, $property) {
                // A lot have could happen to the property, so lets set it one more time
                $response->memo['data'][$property] = $component->{$property};
                // The property can be seen as changed and dirty data, who needs a refresh
                $response->effects['dirty'][] = $property;
            }, $response, $component);
        }
    }
}
