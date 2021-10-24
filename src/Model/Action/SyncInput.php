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

        $name  = $payload['name'];
        $value = $payload['value'];

        try {
            $containsDots = $this->propertyHelper->containsDots($name);

            if ($containsDots) {
                $transform = $this->propertyHelper->transformDots($name, $value, $component);

                // Re-assign original method properties.
                $name  = $transform['property'];
                $value = $transform['value'];
            }

            if (!array_key_exists($name, $component->getPublicProperties())) {
                throw new ComponentException(__('Public property %1 does\'nt exist', [$name]));
            }

            $value = is_array($value)
                ? $this->lifecycleSyncArray($component, $name, $value)
                : $this->lifecycleSync($component, $name, $value);
        } catch (Exception $exception) {
            return;
        }

        // Set the property if a lifecycle method succeed or failed.
        $component->{$name} = $value;
    }

    /**
     * Sync regular mixed value
     *
     * @param Component $component
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function lifecycleSync(Component $component, string $name, $value)
    {
        $before = 'updating' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
        $after = str_replace('updating', 'updated', $before);

        $methods = [$before, 'updating', 'updated', $after];
        $clone = $value;

        foreach ($methods as $method) {
            if (method_exists($component, $method)) {
                $clone = $component->{$method}(...[$clone, $name]);
            }

            $component->{$name} = $clone;
        }

        return $value;
    }

    /**
     * Sync value as a dotted value (e.g: my.nested.value).
     *
     * @param Component $component
     * @param string $name
     * @param array $value
     * @return mixed
     */
    public function lifecycleSyncArray(Component $component, string $name, array $value)
    {
        // Get most deep key value pair.
        $value = end($value);
        // Hydrate the value from the key value pair.
        $value = reset($value);

        return $this->lifecycleSync($component, $name, $value);
    }
}
