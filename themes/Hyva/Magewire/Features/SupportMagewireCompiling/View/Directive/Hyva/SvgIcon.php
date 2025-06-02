<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\MagewireCompatibilityWithHyva\Magewire\Features\SupportMagewireCompiling\View\Directive\Hyva;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\FunctionArguments;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\FunctionArgumentsParser;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ParserType;

class SvgIcon extends Directive
{
    public function compile(string $expression, string $directive): string
    {
        /** @var FunctionArgumentsParser $parsed */
        $parsed = $this->parser(ParserType::FUNCTION_ARGUMENTS)->parse($expression);
        $arguments = $parsed->arguments();

        if ($arguments->isset('type') && method_exists($this, $arguments->get('type'))) {
            return $this->{$arguments->get('type')}($arguments->unset('type')->all());
        }

        return "<?= \$__magewire->action('hyva.svg-icons')->execute('heroicon', {$arguments->renderWithNames()}) ?>";
    }

    protected function heroicon(...$arguments): string
    {
        return "<?php if(\$__magewire->action('hyva.svg-icons')->heroicon()): ?>";
    }
}
