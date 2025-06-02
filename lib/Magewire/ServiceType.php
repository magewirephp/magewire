<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * The ServiceType class provides a structured way to manage and organize different operation types
 * within an application. It handles the instantiation, sorting, and retrieval of these types while
 * ensuring dependencies are resolved and necessary data is injected.
 */
abstract class ServiceType
{
    private bool $booted = false;

    /**
     * @param array<string, string|array> $items
     */
    public function __construct(
        protected array $items = []
    ) {
        //
    }

    public function boot(): self
    {
        if ($this->booted) {
            return $this;
        }

        $this->assemble()->sort();
        $this->booted = true;

        return $this;
    }

    /**
     * Returns an operation type facade which simplifies the interface of a complex type by
     * exposing only the necessary methods, thus defining a clear and concise
     * API for interacting with the type.
     *
     * @throws NotFoundException
     */
    public function facade(string $for)
    {
        $for = preg_replace('/(?<!^)[A-Z]/', '_$0', $for);
        $facade = $this->items[$for]['facade'];

        if (is_string($facade)) {
            $this->items[$for]['facade'] = ObjectManager::getInstance()->get($facade);
        } elseif (! is_object($facade)) {
            throw new NotFoundException(
                __('Operation type facade "%1" could not be found.', $for)
            );
        }

        return $this->items[$for]['facade'];
    }

    /**
     * @throws NotFoundException
     */
    public function item(string $name): object
    {
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $name);

        if (isset($this->items[$name])) {
            return $this->items[$name]['type'];
        }

        throw new NotFoundException(
            __('Operation type item "%1" could not be found.', $name)
        );
    }

    /**
     * @throws NotFoundException
     */
    public function viewModel(string $name): object
    {
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $name);

        // WIP: could maybe be made a little more strict checking ArgumentInterface?
        if (isset($this->items[$name]['view_model'])) {
            return $this->items[$name]['view_model'];
        }

        throw new NotFoundException(
            __('Operation view model "%1" could not be found.', $name)
        );
    }

    private function assemble(): self
    {
        $this->items = array_map(function (string|array $item) {
            if (is_string($item)) {
                $item = ['type' => $item];
            }
            if (is_object($item['type'])) {
                return $item;
            }

            $item['type'] = ObjectManager::getInstance()->get($item['type']);

            // Injects data if a `setData()` method is present in the operation type item class.
            if (method_exists($item['type'], 'setData')) {
                foreach ($item['data'] ?? [] as $key => $value) {
                    $item['type']->setData($key, $value);
                }
            }

            // Ensure the facade key exists, defaulting to null if not set.
            $item['facade'] ??= null;
            // Ensure each item has a numeric sort order, defaulting to 0.
            $item['sort_order'] = (int) ($item['sort_order'] ?? 0);
            // Keep only active sequences (where the value is true).
            $item['sequence'] = array_filter($item['sequence'] ?? [], fn ($item) => $item === true);
            // Ensure the view model key exists, defaulting to null if not set.
            $item['view_model'] ??= null;

            return $item;
        }, array_filter($this->items, fn ($value) => is_string($value) || is_array($value)));

        return $this;
    }

    private function sort(): self
    {
        uasort($this->items, function ($a, $b) {
            if ($a['sort_order'] == $b['sort_order']) {
                if (isset($a['sequence'])) {
                    foreach ($a['sequence'] as $dependency) {
                        if ($dependency == $b['type']) {
                            return 1;  // $a should come after $b
                        }
                    }
                }

                if (isset($b['sequence'])) {
                    foreach ($b['sequence'] as $dependency) {
                        if ($dependency == $a['type']) {
                            return -1; // $a should come before $b
                        }
                    }
                }

                return 0;
            }

            return ($a['sort_order'] ?? 0 < $b['sort_order'] ?? 0) ? -1 : 1;
        });

        return $this;
    }
}
