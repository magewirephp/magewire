<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magento\Framework\App\ObjectManager;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\FunctionArgumentsParser;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ParserType;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management\DirectiveManager;
use Magewirephp\Magewire\Support\Parser;

abstract class Directive
{
    private DirectiveUtils|null $utils = null;

    /**
     * Compiles a string-based directive into executable code.
     *
     * This method processes readable string directives and transforms them into functional code,
     * depending on the directive's nature. The output of a directive can vary. Typically, the method
     * checks for underlying functions that can act as child compilers, allowing a single compiler
     * to handle multiple directives.
     *
     * @param string $expression The expression associated with the directive.
     * @param string $directive The name of the directive to compile.
     */
    public function compile(string $expression, string $directive): string
    {
        /** @var FunctionArgumentsParser $parsed */
        $parsed = $this->parser(ParserType::FUNCTION_ARGUMENTS)->parse($expression);

        // TODO: should handle exceptions, logging them and return an empty string when so.
        return method_exists($this, $directive) ? $this->$directive(...$parsed->arguments()->all()) : '';
    }

    protected function utils(): DirectiveUtils
    {
        return $this->utils ??= ObjectManager::getInstance()->get(DirectiveUtils::class);
    }

    protected function manager(): DirectiveManager
    {
        return $this->manager ??= ObjectManager::getInstance()->get(DirectiveManager::class);
    }

    protected function parser(ParserType $parser, array $arguments = []): Parser
    {
        return $parser->create($arguments);
    }

    protected function functionArgumentsParser(): FunctionArgumentsParser
    {
        return ParserType::FUNCTION_ARGUMENTS->create();
    }
}
