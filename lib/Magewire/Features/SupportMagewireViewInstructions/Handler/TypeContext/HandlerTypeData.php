<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext;

use BadMethodCallException;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\HandlerType;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\HandlerTypeContext;
use Magewirephp\Magewire\Support\DataScope;

/**
 * @method set(string $path, mixed $value, string|null $alias = null, bool $plural = true)
 * @method push(string $section, mixed $value, string|null $alias = null, string|null $path = null)
 */
class HandlerTypeData extends HandlerTypeContext
{
    public function __construct(
        HandlerType $handler,
        private readonly DataScope $data
    ) {
        parent::__construct($handler);
    }

    public function __call(string $method, array $arguments)
    {
        if (method_exists($this->data, $method)) {
            return call_user_func_array([$this->data, $method], $arguments);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.',
            DataScope::class,
            $method
        ));
    }
}
