<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action;

use Magento\Framework\Exception\LocalizedException;
use Magewirephp\Magewire\Exception\ComponentActionException;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Action\Type\Factory as TypeFactory;
use Magewirephp\Magewire\Model\Action\Type\Magic;
use Magewirephp\Magewire\Model\ActionInterface;

class CallMethod implements ActionInterface
{
    public const ACTION = 'callMethod';

    protected TypeFactory $typeFactory;
    private array $uncallableMethods;

    /**
     * CallMethod constructor.
     * @param TypeFactory $typeFactory
     * @param array $uncallableMethods
     */
    public function __construct(
        TypeFactory $typeFactory,
        array $uncallableMethods = []
    ) {
        $this->typeFactory = $typeFactory;
        $this->uncallableMethods = $uncallableMethods;
    }

    /**
     * @inheritdoc
     * @throws ComponentActionException
     * @throws LocalizedException
     */
    public function handle(Component $component, array $payload)
    {
        // Magic or not, it's still a class method who can have no '$' as name prefix.
        $method = ltrim($payload['method'], '$');
        // Let's make sure we have an unpackable array.
        $params = is_array($payload['params']) ? $payload['params'] : [$payload['params']];

        if ($this->isCallable($method, $component)) {
            return $component->{$method}(...$params);
        }

        // Determine the required type class by method in specific order.
        $type = $this->determineType($method);
        // Add the component as a dynamic last method param.
        $params[] = $component;

        if ($this->isCallable($method, $type)) {
            return $type->{$method}(...$params);
        }

        throw new ComponentActionException(__('Method %1 does not exist or can not be called', [$method]));
    }

    /**
     * @param $method
     * @param object $class
     * @return bool
     */
    public function isCallable($method, object $class): bool
    {
        $uncallables = $this->uncallableMethods;

        if ($class instanceof Component) {
            $uncallables = array_merge($class->getUncallables(), $this->uncallableMethods);
        }

        return in_array($method, array_diff(get_class_methods($class), $uncallables), true);
    }

    /**
     * @param string $method
     * @return mixed|string
     * @throws LocalizedException
     */
    public function determineType(string $method)
    {
        // Upload class will be added in the future when this feature will get implemented.
        foreach ([Magic::class] as $type) {
            if (method_exists($type, $method)) {
                return $this->typeFactory->create($type);
            }
        }

        throw new LocalizedException(__('Method %1 does not exist', [$method]));
    }
}
