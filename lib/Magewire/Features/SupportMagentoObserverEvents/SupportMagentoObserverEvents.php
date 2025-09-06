<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagentoObserverEvents;

use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Features\SupportMagentoObserverEvents\DTO\ListenerDataTransferObjectFactory;
use Magewirephp\Magewire\Support\PipelineFactory;
use function Magewirephp\Magewire\on;

class SupportMagentoObserverEvents extends ComponentHook
{
    public function __construct(
        private readonly PipelineFactory $pipelineFactory,
        private readonly ListenerDataTransferObjectFactory $listenerDataTransferObjectFactory,
        private readonly EventManagerInterface $eventManager
    ) {
        //
    }

    public function provide(): void
    {
        /**
         * @todo Refactor events mapping to use dependency injection. Move the events array into the DI container
         *       to enable custom event injection by third-party modules, runtime event registration and modification,
         *       observable pattern implementation for better extensibility, and cleaner separation of concerns between
         *       event definition and handling. This would allow developers to register custom lifecycle events without
         *       modifying core files, improving modularity and testability.
         */
        $events = [
            // Lifecycle events.
            'magewire:construct',
            'magewire:precompile',
            'magewire:compiled',
            'magewire:reconstruct',

            'pre-mount',
            'mount.stub',
            'mount',
            'hydrate',
            'update',
            'call',
            'render',
            'render.placeholder',
            'dehydrate',
            'destroy',

            // Request/Response cycle.
            'request',
            'response',

            // Checksum operations.
            'checksum.generate',
            'checksum.verify',
            'checksum.fail',
            'snapshot-verified',

            // Magic methods.
            '__get',
            '__unset',
            '__call',

            // Utility events.
            'exception',
            'flush-state',
            'profile',
        ];

        foreach ($events as $event) {
            on($event, fn (...$args) => $this->dispatch('magewire_on_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $event), ...$args));
        }
    }

    private function dispatch(string $event, ...$arguments): callable
    {
        $listener = $this->listenerDataTransferObjectFactory->create();
        $this->eventManager->dispatch($event, ['listener' => $listener]);

        $afters = [];

        foreach ($listener->listeners() as $listener) {
            if (is_callable($listener)) {
                $result = $listener(...$arguments);

                if (is_callable($result)) {
                    $afters[] = $result;
                }
            }
        }

        return function (...$args) use ($afters, $event) {
            $pipeline = $this->pipelineFactory->create();

            if ($afters) {
                foreach ($afters as $after) {
                    $pipeline->pipe(fn (array $args, callable $next) => $next($after(...$args)));
                }

                return $pipeline->run($args);
            }

            // Can only have a single return item, just like a regular class method.
            return $args[0] ?? null;
        };
    }
}
