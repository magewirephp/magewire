<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use Magewirephp\Magewire\Model\View\Fragment\Exceptions\EmptyFragmentException;
use Magewirephp\Magewire\Model\View\Fragment\Exceptions\FragmentValidationException;
use Magewirephp\Magewire\Model\View\Fragment\Modifier;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class Fragment
{
    // Unchanged raw buffer output, if any.
    protected string|bool $raw = false;
    // Indicates whether the fragment is allowed to be modified.
    protected bool $modifiable = true;

    // Flag to indicate whether the fragment is currently buffering output.
    private bool $buffering = false;
    // The current output buffer level, if any.
    private int|null $level = null;

    // The fragment's validation callbacks.
    /** @var array<int, callable> $validators */
    private array $validators = [];

    /**
     * @param array<int|string, Modifier $modifiers
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly array $modifiers = []
    ) {
        //
    }

    /**
     * Begins output buffering for the fragment.
     *
     * Prepares the fragment by calling setup and initiates output buffering
     * so that any output can later be captured and processed.
     */
    public function start(): static
    {
        if ($this->buffering) {
            return $this;
        }

        ob_start();

        $this->buffering = true;
        $this->level = ob_get_level();

        return $this;
    }

    /**
     * Ends output buffering and processes the captured content.
     *
     * Retrieves the buffered output as raw content, applies an optional transformation
     * to produce the final content, and triggers the inspection process.
     */
    public function end(): void
    {
        if (! $this->buffering || ob_get_level() !== $this->level) {
            return;
        }

        // Stores the output to be able to flag buffering as false.
        $this->raw = trim(ob_get_clean());
        $this->buffering = false;

        $output = $this->render();

        echo $output;
    }

    /**
     * Retrieves the raw output content.
     */
    public function getRawOutput(): string
    {
        return $this->raw === false ? '' : $this->raw;
    }

    /**
     * Determines whether the fragment is currently buffering output.
     */
    public function isBuffering(): bool
    {
        return $this->buffering;
    }

    /**
     * Locks the fragment so that it cannot be modified.
     */
    public function lock(): static
    {
        $this->modifiable = false;

        return $this;
    }

    /**
     * @throws FragmentValidationException
     */
    protected function validate(): bool
    {
        if ($this->isBuffering()) {
            return false;
        }

        foreach ($this->validators as $validator) {
            if ($validator($this->raw)) {
                continue;
            }

            throw new FragmentValidationException('Fragment did not pass validation.');
        }

        return true;
    }

    /**
     * Sets a validation callback who can only return true or false.
     */
    protected function withValidator(callable $callback, string|null $name = null): static
    {
        // We can be sure all validator callback keys are integers.
        $key = $name ?? key($this->validators) + 1;
        $this->validators[$key] = $callback;

        return $this;
    }

    protected function handleModifierException(Throwable $exception): static
    {
        $message = 'An unexpected exception occurred while modifying a fragment.';
        $this->logger->critical($message, ['exception' => $exception]);

        return $this;
    }

    protected function handleValidationException(Throwable $exception): string
    {
        if ($exception instanceof EmptyFragmentException) {
            return '';
        }

        $message = 'A validation exception occurred while processing the fragment.';
        $this->logger->critical($message, ['exception' => $exception]);

        return $this->raw;
    }

    protected function render(): string
    {
        try {
            $this->validate();
            $output = $this->raw;

            if ($output) {
                foreach ($this->getModifiers() as $modifier) {
                    try {
                        if ($modifier instanceof Modifier) {
                            $output = $modifier->modify($output, $this);
                        }
                    } catch (Throwable $exception) {
                        $this->handleModifierException($exception);
                    }
                }
            }
        } catch (Throwable $exception) {
            $output = $this->handleValidationException($exception);
        }

        return $output;
    }

    protected function getModifiers(): array
    {
        return $this->modifiable ? $this->modifiers : [];
    }
}
