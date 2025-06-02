<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions;

use Magento\Framework\App\ObjectManager;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Concern\WithNaming;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Concern\WithStacking;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\HandlerType;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\Type\ComponentHandler;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\Type\DispatchHandler;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\Type\DocumentHandler;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\Type\DomNodeHandler;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\Type\FormHandler;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\Type\FormInputHandler;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\Type\ScheduleHandler;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\Type\WindowHandler;
use Magewirephp\Magewire\Support\DataScope;

abstract class ViewInstruction
{
    use WithNaming;
    use WithStacking;

    /** @var array<int, HandlerType>|HandlerType */
    private array $handlers = [];

    private DataScope|null $data = null;

    abstract public function getType(): string;
    abstract public function getAction(): string;

    /**
     * Handles the view instruction by dispatching it immediately.
     */
    public function dispatch(): DispatchHandler
    {
        return $this->handleWith(DispatchHandler::class, 'dispatch');
    }

    /**
     * Handles the view instruction with a Magewire component.
     */
    public function handleWithComponent(Component|string $component): ComponentHandler
    {
        $id = is_string($component) ? $component : $component->id();

        $handler = $this->handleWith(ComponentHandler::class, 'component');
        $handler->data('args.id', $id);

        return $handler;
    }

    /**
     * Handles the view instruction with a DOM node.
     *
     * @template T of DomNodeHandler
     * @param ?string $id
     * @param class-string<T>|null $handler
     * @return T
     */
    public function handleWithDomNode(string|null $id = null, string|null $handler = null): DomNodeHandler
    {
        return $this->handleWith($handler ?? DomNodeHandler::class, 'dom-node')->target($id)->return();
    }

    /**
     * Handles the view instruction with a form.
     */
    public function handleWithForm(string|null $id = null): FormHandler
    {
        return $this->handleWithDomNode($id, FormHandler::class);
    }

    /**
     * Handles the view instruction with a form input.
     */
    public function handleWithFormInput(string|null $id = null): FormInputHandler
    {
        return $this->handleWithDomNode($id, FormInputHandler::class);
    }

    /**
     * Handles the view instruction with the document.
     */
    public function handleWithDocument(): DocumentHandler
    {
        return $this->handleWith(DocumentHandler::class, 'document');
    }

    /**
     * Handles the view instruction with the window.
     */
    public function handleWithWindow(): WindowHandler
    {
        return $this->handleWith(WindowHandler::class, 'window');
    }

    /**
     * @template T of HandlerType
     * @param class-string<T> $handler
     * @return T
     */
    public function handleWith(string $handler, string $type, array $args = []): HandlerType
    {
        if ($this->handlerExists($handler)) {
            return $this->handlers[$handler];
        }

        $args['compiler'] ??= ObjectManager::getInstance()->create(Data\Compiler\RecursiveArray::class);
        $args['data'] ??= $this->data()->scope('handlers', $type);

        $handler = ObjectManager::getInstance()->create($handler, $args);
        return $this->handlers[$handler::class] = $handler;
    }

    public function data(): DataScope
    {
        if ($this->data) {
            return $this->data;
        }

        return $this->data = ObjectManager::getInstance()->create(DataScope::class);
    }

    /**
     * Returns any public options set via e.g. concerns.
     */
    public function getOptions(): array
    {
        return [
            'name' => $this->getName(),

            'stacking' => [
                'position' => $this->getStackPosition()
            ]
        ];
    }

    protected function handlerExists(string $handler): bool
    {
        return array_key_exists($handler, $this->handlers);
    }
}
