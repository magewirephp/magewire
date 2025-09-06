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
use Psr\Log\LoggerInterface;
use Throwable;

abstract class Fragment
{
    // Unchanged raw buffer output, if any.
    protected string|bool $raw = false;
    // Flag to indicate whether the fragment is currently buffering output.
    protected bool $buffering = false;
    // Indicates whether the fragment is allowed to be modified.
    protected bool $mutable = true;
    // Indicated whether the fragment should skip echoing.
    protected bool $render = true;
    // The current output buffer level, if any.
    protected int|null $level = null;
    // Fragment alias for better identification.
    protected string|null $alias = null;

    /** @var array<int, callable> $validators */
    private array $validators = [];

    /**
     * @param array<int|string, FragmentModifier|callable> $modifiers
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private array $modifiers = []
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
        $this->level = $this->level ?? ob_get_level();

        return $this;
    }

    /**
     * Ends output buffering, processes and echos the captured content.
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

        try {
            $output = $this->render();
        } catch (EmptyFragmentException $exception) {
            // Unreachable: fragment buffering state verified in method preconditions.
        }

        echo $this->render ? ($output ?? '') : '';
    }

    /**
     * Locks the fragment so that it cannot be modified.
     */
    public function lock(): static
    {
        $this->mutable = false;
        return $this;
    }

    /**
     * Flag the fragment with a alias for better identification.
     */
    public function withAlias(string $alias): static
    {
        // Silently avoid an alias is set on certain circumstances.
        if (! $this->mutable || $this->buffering || is_string($this->alias) || is_string($this->raw)) {
            return $this;
        }

        $this->alias = $alias;
        return $this;
    }

    /**
     * Avoid output echoing.
     */
    public function mute(): static
    {
        $this->render = false;
        return $this;
    }

    /**
     * Retrieve the raw output content.
     */
    protected function getRawOutput(): string
    {
        return $this->raw === false ? '' : $this->raw;
    }

    /**
     * Retrieve the fragment alias (if set).
     */
    protected function getAlias(): string|null
    {
        return $this->alias;
    }

    /**
     * @throws FragmentValidationException
     */
    protected function validate(): bool
    {
        if ($this->buffering) {
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

    /**
     * Set a fragment modifier.
     */
    protected function withModifier(FragmentModifier|callable $modifier): static
    {
        $this->modifiers[] = $modifier;

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

        return $this->raw ?? '';
    }

    protected function handleRenderException(Throwable $exception): string
    {
        $message = 'A render exception occurred while processing the fragment.';
        $this->logger->critical($message, ['exception' => $exception]);

        return '';
    }

    /**
     * Returns the final fragment output.
     */
    protected function render(): string
    {
        try {
            $output = $this->raw;

            if ($output) {
                try {
                    if ($this->validate()) {
                        $this->modify();
                    }
                } catch (Throwable $exception) {
                    $output = $this->handleValidationException($exception);
                }

                return $output;
            }

            if ($this->buffering) {
                throw new EmptyFragmentException(
                    'Unclosed output buffer detected. Fragment buffering must be properly terminated.'
                );
            }
        } catch (Throwable $exception) {
            return $this->handleRenderException($exception);
        }

        return '';
    }

    /**
     * Runs all injected fragment modifiers.
     */
    private function modify(): static
    {
        $output = $this->raw;

        if ($this->mutable && $output) {
            foreach ($this->modifiers as $modifier) {
                try {
                    if (is_callable($modifier)) {
                        $modifier($this);
                    } elseif ($modifier instanceof FragmentModifier) {
                        $modifier->modify($this);
                    }
                } catch (Throwable $exception) {
                    $this->handleModifierException($exception);
                }
            }
        }

        return $this;
    }
}
