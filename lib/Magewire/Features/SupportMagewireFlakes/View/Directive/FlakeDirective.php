<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Directive;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive\Parser\ExpressionParserType;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ScopeDirectiveParser;

class FlakeDirective extends Directive
{
    #[ScopeDirectiveParser(expressionParserType: ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function flake(string $arguments): string
    {
        return <<<DIRECTIVE
<?php
\$decoded = unserialize(base64_decode('$arguments'));
\$variables = get_defined_vars();

echo \$__magewire->action('magewire.flake')->execute(
    'create',
    flake: \$decoded['flake'],
    data: \$decoded['data'] ?? [],
    metadata: \$decoded['metadata'] ?? [],
    variables: \$variables
);

unset(\$decoded, \$fragment, \$variables);
?>
DIRECTIVE;
    }
}
