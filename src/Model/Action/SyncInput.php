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
use Magewirephp\Magewire\Exception\ComponentException;
use Magewirephp\Magewire\Helper\Property as PropertyHelper;
use Magewirephp\Magewire\Model\ActionInterface;

/**
 * Class SyncInput
 * @package Magewirephp\Magewire\Model\Action
 */
class SyncInput implements ActionInterface
{
    /** @var PropertyHelper $propertyHelper */
    private $propertyHelper;

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
            if ($this->propertyHelper->containsDots($property)) {
                // Full property value including its new payload value.
                $transform = $this->propertyHelper->transformDots($property, $value, $component);
                // Search for the newly set property value by path.
                $value = $this->propertyHelper->searchViaDots($transform['path'], $transform['value']);
                // Process the property value by path.
                $value = $this->processPropertyByLifecycle($component, $transform['realpath'], $value);
                // Re-assign the full processed property value.
                $transform = $this->propertyHelper->transformDots($transform['realpath'], $value, $component);

                $property = $transform['property'];
                $value    = $transform['value'];
            }

            $value = $this->processPropertyByLifecycle($component, $property, $value);
        } catch (Exception $exception) {
            return;
        }

        // Set the property if a lifecycle method succeed or failed.
        $component->{$property} = $value;
    }

    /**
     * Sync regular mixed value
     *
     * @param Component $component
     * @param string $property
     * @param mixed $value
     * @return mixed
     */
    public function processPropertyByLifecycle(
        Component $component,
        string $property,
        $value
    ) {
        $before = 'updating' . str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $property)));
        $after  = str_replace('updating', 'updated', $before);

        $methods = [$before, 'updating', 'updated', $after];

        foreach ($methods as $method) {
            if (method_exists($component, $method)) {
                $value = $component->{$method}(...[$value, $property]);
            }
        }

        return $value;
    }
}
