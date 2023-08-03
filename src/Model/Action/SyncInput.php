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
use Magewirephp\Magewire\Exception\ComponentException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ValidationException;
use Magewirephp\Magewire\Helper\Property as PropertyHelper;
use Magewirephp\Magewire\Model\Action;
use Psr\Log\LoggerInterface;

class SyncInput extends Action
{
    public const ACTION = 'syncInput';

    protected PropertyHelper $propertyHelper;
    protected LoggerInterface $logger;

    public function __construct(
        PropertyHelper $propertyHelper,
        LoggerInterface $logger
    ) {
        $this->propertyHelper = $propertyHelper;
        $this->logger = $logger;
    }

    public function inspect(Component $component, array $updates): bool
    {
        foreach ($this->reduceProperty($component, $updates) as $property => $value) {
            $this->assign($component, $property, $this->updating($component, $property, $value));
        };

        return parent::inspect($component, $updates);
    }

    /**
     * @throws ComponentActionException
     */
    public function handle(Component $component, array $payload)
    {
        if (! isset($payload['name'], $payload['value'])) {
            throw new ComponentActionException(__('Invalid update payload'));
        }

        // Grep the required type elements from the action payload.
        $property = $payload['name'];
        $value = $payload['value'];

        try {
            $nested = $this->propertyHelper->containsDots($property);
            $transform = [];

            if ($nested) {
                // Full property value including its new payload value.
                $transform = $this->propertyHelper->transformDots($property, $value, $component);
                // Search for the newly set property value by path.
                $value = $this->propertyHelper->searchViaDots($transform['path'], $transform['data']);
            }

            // Try to run existing pre-assignment methods if they exist.
            $value = $this->updating($component, $property, $value);

            if ($nested) {
                $transform = $this->propertyHelper->transformDots($property, $value, $component);
            }

            // Regular value assignment.
            $this->assign($component, $transform ? $transform['property'] : $property, $transform ? $transform['data'] : $value);

            // Try to run post-assignment methods if they exist.
            $value = $this->updated($component, $property, $value);

            if ($nested) {
                $transform = $this->propertyHelper->transformDots($property, $value, $component);
            }

            // Re-assign the final value onto the component property.
            $this->assign($component, $transform ? $transform['property'] : $property, $transform ? $transform['data'] : $value);
        } catch (Exception $exception) {
            $this->logger->critical(
                __sprintf('Magewire: Something went wrong while syncing property "%s" onto component "%s"', [$property, $component->name]),
                ['exception' => $exception]
            );

            return;
        }
    }

    public function evaluate(Component $component, array $updates)
    {
        foreach ($this->reduceProperty($component, $updates) as $property => $value) {
            $this->assign($component, $property, $this->updated($component, $property, $value));
        };

        return parent::evaluate($component, $updates);
    }

    private function assign(Component $component, string $property, $value): void
    {
        $this->propertyHelper->assign(fn ($component, $property, $value) => $component->{$property} = $value, $component, [$property => $value], false);
    }

    private function updating(Component $component, string $property, $value)
    {
        $methods = ['updating' . str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $property))), 'updating'];

        foreach ($methods as $method) {
            if (method_exists($component, $method)) {
                $value = $component->{$method}($value, $property);
            }
        }

        return $value;
    }

    private function updated(Component $component, string $property, $value)
    {
        $methods = ['updated', 'updated' . str_replace(' ', '', ucwords(str_replace(['-', '_', '.'], ' ', $property)))];

        foreach ($methods as $method) {
            if (method_exists($component, $method)) {
                $value = $component->{$method}($value, $property);
            }
        }

        return $value;
    }

    protected function reduceProperty(Component $component, array $updates): array
    {
        $nested = [];

        foreach ($updates as $update) {
            $property = $update['payload']['name'];
            $value = $update['payload']['value'];

            if ($this->propertyHelper->containsDots($property)) {
                try {
                    $transform = $this->propertyHelper->transformDots($property, $value, $component);
                    $nested[$transform['property']] = $component->{$transform['property']};

                    /** @todo multidimensional array support still needs to be included here. */
                } catch (ComponentException $exception) {
                    $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
                }
            }
        }

        return $nested;
    }
}
