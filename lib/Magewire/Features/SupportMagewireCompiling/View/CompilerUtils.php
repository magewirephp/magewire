<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

class CompilerUtils
{
    /**
     * Determine if the given expression has the same number of opening and closing parentheses.
     */
    public function hasEvenNumberOfParentheses(string $expression): bool
    {
        $tokens = token_get_all('<?php ' . $expression);

        if (empty($tokens) || end($tokens) !== ')') {
            return false;
        }

        $opening = 0;
        $closing = 0;

        foreach ($tokens as $token) {
            if ($token == ')') {
                $closing++;
            } elseif ($token == '(') {
                $opening++;
            }
        }

        return $opening === $closing;
    }

    /**
     * Get the open and closing PHP tag tokens from the given string.
     */
    public function getOpenAndClosingPhpTokens($contents): array
    {
        $tokens = token_get_all($contents);
        $result = [];

        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], [T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG])) {
                $result[] = $token[0];
            }
        }

        return $result;
    }
}
