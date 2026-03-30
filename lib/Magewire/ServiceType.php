<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magewirephp\Magewire\Enums\ServiceTypeItemBootMode;
use Magewirephp\Magewire\Support\Factory;

/**
 * The ServiceType class provides a structured way to manage and organize different operation types
 * within an application. It handles the instantiation, sorting, and retrieval of these types while
 * ensuring dependencies are resolved and necessary data is injected.
 */
abstract class ServiceType
{
    protected bool $assembled = false;
    protected bool $sorted = false;

    private int $sortOrder = 0;

    /** @var ServiceTypeBooter|null $booter */
    private ServiceTypeBooter|null $booter = null;

    /**
     * @param array<string, string|array> $items
     */
    public function __construct(
        private array $items = []
    ) {
    }

    /**
     * Returns true when fully booted, false when only partially booted.
     */
    public function boot(ServiceTypeItemBootMode|null $mode = null): bool
    {
        // Short circuit when the service is fully booted.
        if ($this->items()->booted()) {
            return true;
        }

        return $this->items()->boot($mode, $this->callback())->booted();
    }

    public function booter(): ServiceTypeBooter
    {
        return $this->booter ??= Factory::create(ServiceTypeBooter::class);
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
            $this->items[$for]['facade'] = Factory::get($facade);
        } elseif (! is_object($facade)) {
            throw new NotFoundException(__('Operation type facade "%1" could not be found.', $for));
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

        throw new NotFoundException(__('Operation type item "%1" could not be found.', $name));
    }

    /**
     * @throws NotFoundException
     */
    public function viewModel(string $name): ArgumentInterface
    {
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $name);

        // @todo Could maybe be made a little more strict checking ArgumentInterface?
        if (isset($this->items[$name]['view_model'])) {
            return $this->items[$name]['view_model'];
        }

        throw new NotFoundException(__('Operation view model "%1" could not be found.', $name));
    }

    private function assemble(): static
    {
        if ($this->assembled) {
            return $this;
        }

        $assembled = [];

        foreach (array_filter($this->items, static fn ($value) => is_string($value) || is_array($value)) as $key => $item) {
            if (is_string($item)) {
                $item = ['type' => $item];
            }
            if (is_object($item['type'])) {
                $assembled[$key] = $item;
                continue;
            }

            $item['type'] = Factory::get($item['type']);

            // Injects data if a `setData()` method is present in the operation type item class.
            if (method_exists($item['type'], 'setData')) {
                foreach ($item['data'] ?? [] as $dataKey => $value) {
                    $item['type']->setData($dataKey, $value);
                }
            }

            if ($item['sort_order'] ?? false) {
                $this->sortOrder = (int) $item['sort_order'];
            } else {
                $this->sortOrder++;
                $item['sort_order'] = $this->sortOrder;
            }

            // Ensure the facade key exists, defaulting to null if not set.
            $item['facade'] ??= null;
            // Keep only active sequences (where the value is true).
            $item['sequence'] = array_filter($item['sequence'] ?? [], static fn ($item) => $item === true);
            // Ensure the view model key exists, defaulting to null if not set.
            $item['view_model'] ??= null;
            // Ensure the config key exists, defaulting to null if not set.
            $item['config'] ??= null;
            // Use the array key as the item name if not explicitly defined.
            $item['name'] ??= $key;

            // Ensure the boot mode exists, or set the default if not set.
            $item['boot_mode'] = ServiceTypeItemBootMode::try($item['boot_mode'] ?? null, ServiceTypeItemBootMode::lowest());

            $assembled[$key] = $item;
        }

        $this->items = $assembled;

        $this->assembled = true;
        return $this;
    }

    private function sort(): array
    {
        if ($this->sorted) {
            return $this->items;
        }

        uasort($this->items, static function ($a, $b) {
            if ($a['sort_order'] == $b['sort_order']) {
                if (isset($a['sequence'])) {
                    foreach ($a['sequence'] as $dependency) {
                        if ($dependency == $b['type']) {
                            return 1; // $a should come after $b
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

            return ( $a['sort_order'] ?? 0 ) < ( $b['sort_order'] ?? 0 ) ? -1 : 1;
        });

        $this->sorted = true;
        return $this->items;
    }

    protected function items(): ServiceTypeBooter
    {
        return $this->booter()->setup($this->assemble()->sort());
    }

    /**
     * Callback ran during a booting process of a service type item.
     */
    abstract protected function callback(): callable;
}
