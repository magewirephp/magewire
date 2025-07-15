<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use Magento\Framework\View\Element\BlockInterface;
use Magewirephp\Magento\Framework\View\BlockRenderingRegistry;
use Magewirephp\Magewire\Model\View\Fragment\Exceptions\EmptyFragmentException;
use Magewirephp\Magewire\Model\View\Fragment\Exceptions\FragmentValidationException;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class Fragment
{
    // Unchanged raw buffer output, if any.
    protected string|bool $raw = false;
    // Indicates whether the fragment is allowed to be modified.
    protected bool $mutable = true;
    // Indicated whether the fragment should skip echoing.
    protected bool $render = true;

    // Flag to indicate whether the fragment is currently buffering output.
    private bool $buffering = false;
    // The current output buffer level, if any.
    private int|null $level = null;

    /** @var array<int, callable> $validators */
    private array $validators = [];

    /**
     * @param array<int|string, FragmentModifier|callable> $modifiers
     */
    public function __construct(
        private readonly LoggerInterface        $logger,
        private readonly BlockRenderingRegistry $renderRegistry,
        private array                           $modifiers = []
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

        try {
            $output = $this->render();
        } catch (EmptyFragmentException $exception) {
            // Unreachable: fragment buffering state verified in method preconditions.
        }

        echo $this->render ? ($output ?? '') : '';
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
        $this->mutable = false;
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
     * Returns the current block.
     */
    public function block(): BlockInterface|null
    {
        return $this->renderRegistry->current();
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

        return $this->raw;
    }

    /**
     * Returns the final fragment output.
     *
     * @throws EmptyFragmentException
     */
    protected function render(): string
    {
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

        if ($this->isBuffering()) {
            throw new EmptyFragmentException(
                'Unclosed output buffer detected. Fragment buffering must be properly terminated.'
            );
        }

        return '';
    }

    /**
     * Runs all injected fragment modifiers.
     */
    protected function modify(): static
    {
        $output = $this->raw;

        if ($this->mutable && $output) {
            foreach ($this->modifiers as $modifier) {
                try {
                    if (is_callable($modifier)) {
                        $modifier($this);
                    } else if ($modifier instanceof FragmentModifier) {
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
