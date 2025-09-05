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
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\FunctionExpressionParser;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ExpressionParserType;

class SvgIcon extends Directive
{
    public function compile(string $expression, string $directive): string
    {
        /** @var FunctionExpressionParser $parsed */
        $parsed = $this->parser(ExpressionParserType::FUNCTION_ARGUMENTS)->parse($expression);
        $arguments = $parsed->arguments();

        if ($arguments->isset('type') && method_exists($this, $arguments->get('type'))) {
            return $this->{$arguments->get('type')}($arguments->unset('type')->all());
        }

        return "<?= \$__magewire->action('hyva.svg-icons')->execute('heroicon', {$arguments->renderAsNamed()}) ?>";
    }

    protected function heroicon(): string
    {
        return "<?php if(\$__magewire->action('hyva.svg-icons')->heroicon()): ?>";
    }
}
