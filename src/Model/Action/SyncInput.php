<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action;

use Exception;
use Magewirephp\Magewire\Exception\ComponentActionException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ValidationException;
use Magewirephp\Magewire\Helper\Property as PropertyHelper;
use Magewirephp\Magewire\Model\ActionInterface;

class SyncInput implements ActionInterface
{
    /** @var PropertyHelper $propertyHelper */
    protected $propertyHelper;

    /**
     * Magic constructor.
     * @param PropertyHelper $propertyHelper
     */
    public function __construct(
        PropertyHelper $propertyHelper
    ) {
        $this->propertyHelper = $propertyHelper;
    }

    /**
     * @inheritdoc
     *
     * @throws ComponentActionException
     */
    public function handle(Component $component, array $payload)
    {
        if (!isset($payload['name'], $payload['value'])) {
            throw new ComponentActionException(__('Invalid update payload'));
        }

        $property = $payload['name'];
        $value    = $payload['value'];

        try {
            $dots = $this->propertyHelper->containsDots($property);

            if ($dots) {
                // Full property value including its new payload value.
                $transform = $this->propertyHelper->transformDots($property, $value, $component);
                // Search for the newly set property value by path.
                $value = $this->propertyHelper->searchViaDots($transform['path'], $transform['data']);
            }

            // Prepare lifecycle methods.
            $before = 'updating' . str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $property)));
            $after  = str_replace('updating', 'updated', $before);
            // Assign 'updating' result in the middle and re-assign the final result at the end.
            $methods = [$before, 'updating', 'assign', 'updated', $after, 'assign'];

            foreach ($methods as $method) {
                if ($method === 'assign') {
                    if ($dots) {
                        $component->{$transform['property']} = $transform['data'];
                    } else {
                        $component->{$property} = $value;
                    }
                } else if (method_exists($component, $method)) {
                    try {
                        $value = $component->{$method}(...[$value, $property]);

                        if ($dots) {
                            $transform = $this->propertyHelper->transformDots($property, $value, $component);
                        }
                    } catch (ValidationException $exception) {
                        // Error was caught and set, but we don't want to end it here.
                    }
                }
            }
        } catch (Exception $exception) {
            return;
        }
    }
}
