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

class Flake extends Directive
{
    #[ScopeDirectiveParser(expressionParserType: ExpressionParserType::FUNCTION_ARGUMENTS)]
    public function flake(string $arguments): string
    {
        return <<<DIRECTIVE
<?php
\$decoded = unserialize(base64_decode('$arguments'));

echo \$__magewire->action('magewire.flake')->execute(
    'create',
    flake: \$decoded['flake'],
    content: \$decoded['content'],
    data: \$decoded['data'] ?? []
);

unset(\$decoded, \$fragment);
?>
DIRECTIVE;
    }
}
