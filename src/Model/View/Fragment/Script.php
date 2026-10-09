<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment;

class Script extends Html
{
    private string|null $code = null;

    public function start(): static
    {
        return parent::start()
            ->withValidator(static fn ($script) => str_starts_with($script, '<script'))
            ->withValidator(static fn ($script) => str_ends_with($script, '</script>'));
    }

    /**
     * Returns the content between the script tags, exactly as it is rendered.
     *
     * A CSP hash of the script is calculated from this code, and the browser
     * only accepts the script when the hash matches its content byte for byte,
     * including the leading and trailing whitespace.
     */
    public function getScriptCode(): string
    {
        if (is_string($this->code)) {
            return $this->code;
        }

        $start = strpos($this->raw, '>');
        $end = strrpos($this->raw, '<');

        if ($start !== false && $end !== false && $start < $end) {
            $this->code = substr($this->raw, $start + 1, $end - $start - 1);
        }

        return $this->code ?? '';
    }
}
