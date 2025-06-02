<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser;

use Magento\Framework\App\ObjectManager;
use Magewirephp\Magewire\Support\Parser;

/**
 * WIP: This enum currently behaves like a Factory but is intended to represent as an Enum type.
 *      This implementation is experimental and, due to its deep integration with the framework,
 *      it is assumed that most developers won't interact with it directly during development.
 *      As a result, this class is still subject to change.
 */
enum ParserType
{
    case FUNCTION_ARGUMENTS;

    /**
     * Returns a new instance of a parse result.
     *
     * @template T of ParserType
     * @param array $arguments
     * @return Parser
     */
    public function create(array $arguments = []): Parser
    {
        return match ($this) {
            self::FUNCTION_ARGUMENTS => ObjectManager::getInstance()->create($this->getTypeClass(), $arguments)
        };
    }

    public function getTypeClass(): string
    {
        return match ($this) {
            self::FUNCTION_ARGUMENTS => FunctionArgumentsParser::class
        };
    }
}
