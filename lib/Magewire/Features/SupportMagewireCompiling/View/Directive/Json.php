<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive;

use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Directive;

class Json extends Directive
{
    private int $encodingOptions = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    /**
     * Compile the JSON statement into valid PHP.
     */
    public function compile(string $expression, string $directive): string
    {
        $arguments = $this->parser(Directive\Parser\ExpressionParserType::FUNCTION_ARGUMENTS)->parse($expression)->arguments();

        $value = $arguments->get('value', $arguments->get('default', []));
        $flags = $arguments->get('flags', $this->encodingOptions);
        $depth = $arguments->get('depth', 512);

        return "<?php echo json_encode($value, $flags, $depth) ?>";
    }
}
