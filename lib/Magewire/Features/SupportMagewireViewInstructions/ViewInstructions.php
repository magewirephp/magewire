<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions;

use Magento\Framework\App\ObjectManager;

class ViewInstructions
{
    private ViewInstructionFactory|null $viewInstructionFactory = null;
    private ViewInstructionMaker|null $viewInstructionMaker = null;

    /**
     * @param array<int, ViewInstruction> $instructions
     */
    public function __construct(
        protected array $instructions = []
    ) {
        //
    }

    /**
     * Insert a new view instruction.
     */
    public function instruct(ViewInstruction|string $instruction): ViewInstruction
    {
        return $this->instructions[] = is_string($instruction) ? $this->create($instruction) : $instruction;
    }

    /**
     * @param array<mixed, ViewInstruction> $instructions
     */
    public function merge(self|array $instructions): static
    {
        $instructions = $instructions instanceof self
            ? $instructions->fetch()
            : $instructions;

        foreach ($instructions as $item) {
            $this->instructions[] = $item;
        }

        return $this;
    }

    public function shift(): ViewInstruction
    {
        return array_shift($this->instructions);
    }

    public function pop(): ViewInstruction
    {
        return array_pop($this->instructions);
    }

    /**
     * Spread items into the current view instructions.
     */
    public function spread(ViewInstructions $instructions): self
    {
        /** @var  $item */
        foreach ($instructions->fetch() as $item) {
            $this->instruct($item);
        }

        return $this;
    }

    /**
     * Returns the amount of batched evaluation results.
     */
    public function count(array $items = null): int
    {
        return count($items ?? $this->instructions);
    }

    /**
     * Clear the current batch from existing evaluation results.
     */
    public function clear(callable $filter = null): self
    {
        $this->instructions = $filter ? $this->filter($filter) : [];

        return $this;
    }

    /**
     * Modify view instructions by name.
     *
     * Applies the walk method to filter and modify instructions using a custom callable.
     */
    public function modify(string $name, callable $modifier): static
    {
        return $this->walk($modifier, fn (ViewInstruction $instruction) => $instruction->getName() === $name);
    }

    /**
     * Filters and returns batch result items that meet the criteria defined by the given callable.
     */
    public function filter(callable $filter): array
    {
        return array_filter($this->instructions, $filter);
    }

    /**
     * Check if the current batch owns a specific evaluation result
     * and optionally executes the callback if so.
     */
    public function owns(callable $filter, ?callable $callback = null): bool
    {
        $result = count($this->filter($filter)) > 0;

        if ($callback) {
            $callback($this);
        }

        return $result;
    }

    /**
     * Check if the current batch misses a specific evaluation result
     * and optionally executes the callback if so.
     */
    public function misses(callable $filter, ?callable $callback = null): bool
    {
        $result = count($this->filter($filter)) === 0;

        if ($callback) {
            $callback($this);
        }

        return $result;
    }

    /**
     * Walk over each evaluation result object.
     */
    public function walk(callable $callback, callable $filter = null): self
    {
        // Emulates the functionality of array_walk while incorporating filtering capabilities.
        foreach (array_filter($this->instructions, $filter ?? fn () => true) as $item) {
            $callback($item);
        }

        return $this;
    }

    /**
     * Returns if the batch doesn't contain any results.
     */
    public function isEmpty(callable $filter = null): bool
    {
        return $this->count($filter ? $this->filter($filter) : $this->instructions) === 0;
    }

    /**
     * Returns if the batch contains more then one result.
     */
    public function isPlural(callable $filter = null): bool
    {
        return $this->count($filter ? $this->filter($filter) : $this->instructions) > 1;
    }

    /**
     * Returns if the batch contains only one result.
     */
    public function isSingular(callable $filter = null): bool
    {
        return $this->count($filter ? $this->filter($filter) : $this->instructions) === 1;
    }

    /**
     * Get the view instruction factory singleton.
     */
    public function factory(): ViewInstructionFactory
    {
        return $this->viewInstructionFactory ??= ObjectManager::getInstance()->get(ViewInstructionFactory::class);
    }

    /**
     * Create an instance of a view instruction object.
     * @throws ViewInstructionDoesNotExistException
     */
    public function create(string $instruction): ViewInstruction
    {
        return $this->factory()->create($instruction);
    }

    /**
     * Get the view instruction maker instance.
     */
    public function make(): ViewInstructionMaker
    {
        return $this->viewInstructionMaker ??= ObjectManager::getInstance()->create(ViewInstructionMaker::class, [
            'viewInstructions' => $this
        ]);
    }

    /**
     * Returns all view instructions
     *
     * @return ViewInstruction[]
     */
    public function fetch(): array
    {
        return $this->instructions;
    }
}
