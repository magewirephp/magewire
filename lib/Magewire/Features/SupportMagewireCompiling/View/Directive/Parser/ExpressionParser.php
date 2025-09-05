<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser;

use Magewirephp\Magewire\Support\Parser;

abstract class ExpressionParser extends Parser
{
    private Arguments|null $arguments = null;

    public function __construct(
        private readonly ArgumentsFactory $argumentsFactory
    ) {
        //
    }

    public function parse(string $content): self
    {
        $this->arguments()->merge(
            strlen($content) === 0 ? [] : $this->parseArguments($content)
        );

        return $this;
    }

    abstract protected function parseArguments(string $expression): array;

    public function arguments(): Arguments
    {
        return $this->arguments ??= $this->argumentsFactory->create();
    }
}
