<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler;

use InvalidArgumentException;
use Magento\Framework\App\ObjectManager;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext\HandlerTypeConditions;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext\HandlerTypeData;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext\HandlerTypeInteractions;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext\HandlerTypeListeners;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext\HandlerTypeMetaData;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext\HandlerTypeTargets;
use Magewirephp\Magewire\Support\DataScope;

abstract class HandlerType
{
    /** @var array<HandlerTypeContext::class, HandlerTypeContext> */
    private array $extensions = [];

    public function __construct(
        private readonly DataScope $data
    ) {
        //
    }

    public function interact(): HandlerTypeInteractions
    {
        return $this->interactions();
    }

    public function listen(string|null $event = null): HandlerTypeListeners
    {
        if ($event) {
            return $this->listeners()->for($event);
        }

        return $this->listeners();
    }

    public function target(string|null $id = null): HandlerTypeTargets
    {
        return $id ? $this->targets()->id($id) : $this->targets();
    }

    public function flag(): HandlerTypeMetaData
    {
        return $this->metadata();
    }

    public function conditionally(callable|null $statement = null): HandlerTypeConditions
    {
        if ($statement) {
            return $this->conditions()->if($statement);
        }

        return $this->conditions();
    }

    public function data(string|null $path = null, mixed $value = null, string|null $alias = null): HandlerTypeData
    {
        $data = $this->extend(HandlerTypeData::class, ['data' => $this->data]);

        if ($path) {
            return $data->set($path, $value, $alias);
        }

        return $data;
    }

    protected function interactions(): HandlerTypeInteractions
    {
        return $this->extend(HandlerTypeInteractions::class);
    }

    protected function listeners(): HandlerTypeListeners
    {
        return $this->extend(HandlerTypeListeners::class);
    }

    protected function targets(): HandlerTypeTargets
    {
        return $this->extend(HandlerTypeTargets::class);
    }

    protected function metadata(): HandlerTypeMetaData
    {
        return $this->extend(HandlerTypeMetaData::class);
    }

    protected function conditions(): HandlerTypeConditions
    {
        return $this->extend(HandlerTypeConditions::class);
    }

    /**
     * @template T of HandlerTypeContext
     * @param class-string<T> $class
     * @return T
     */
    private function extend(string $class, array|null $arguments = null): HandlerTypeContext
    {
        if (isset($this->extensions[$class])) {
            return $this->extensions[$class];
        }

        if (class_exists($class)) {
            $arguments ??= [];
            $arguments['handler'] = $this;

            /** @var HandlerTypeContext $extension */
            return $this->extensions[$class] = ObjectManager::getInstance()->create($class, $arguments);
        }

        throw new InvalidArgumentException(sprintf('Could not extend handler type with class: %s', $class));
    }
}
