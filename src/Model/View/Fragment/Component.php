<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Model\View\Management\SlotsManager;
use Magewirephp\Magewire\Model\View\SlotsRegistry;
use Magewirephp\Magewire\Support\DataCollection;
use Magewirephp\Magewire\Support\Factory;
use Psr\Log\LoggerInterface;

abstract class Component extends Html
{
    // Flag to make the component be aware of its surrounding and vice versa.
    protected bool $trackable = true;

    private DataCollection|null $dictionary = null;

    public function __construct(
        private readonly string $type,
        private readonly AbstractBlock $block,
        private readonly SlotsManager $slotsManager,
        LoggerInterface $logger,
        Escaper $escaper,
        string $id,
        array $modifiers = []
    ) {
        parent::__construct($logger, $escaper, $modifiers, $id);
    }

    public function dictionary(): DataCollection
    {
        return $this->dictionary ??= Factory::create(DataCollection::class);
    }

    /**
     * Public component properties entry point.
     */
    public function props(): DataCollection
    {
        return $this->properties();
    }

    /**
     * Public component attributes entry point.
     */
    public function attrs(): DataCollection
    {
        return $this->attributes();
    }

    /**
     * Public component property entry point.
     */
    public function prop(string $name, mixed $default = null): mixed
    {
        return $this->properties()->get($name, $default);
    }

    /**
     * Fan the compiler-emitted bag into the three target collections —
     * attributes (DOM-bound), properties (component settings), magewire
     * (framework metadata). Called from the directive preamble emitted by
     * AbstractTagCompiler. Each sub-bag is optional; missing keys are
     * tolerated as empty arrays.
     */
    public function distribute(array $data): static
    {
        foreach ($data as $name => $value) {
            if (is_array($value)) {
                $this->properties()->target($name)->fill($value);
            }
        }

        return $this;
    }

    public function track(): static
    {
        $this->slots()->register($this);
        return $this;
    }

    public function untrack(): static
    {
        $this->slots()->unregister();
        return $this;
    }

    /**
     * Sink for finalized component output.
     *
     * Two effects, both intentional:
     *
     *   1. Append `$output` to this area's `default` slot. Components that
     *      author markup never read this back — Flake::end pushes the body
     *      content into the default slot BEFORE its template renders. The
     *      append here keeps the slot in sync with what was emitted, useful
     *      for diagnostics and any consumer that inspects the area after
     *      the render pass.
     *
     *   2. Echo `$output` to the surrounding output buffer. When this
     *      component is nested, the enclosing component's ob_start captures
     *      the echo and folds it into its own buffered body. When the
     *      component is top-level (no enclosing area), the echo reaches
     *      Magento's surrounding render buffer directly.
     *
     * Inter-component nesting still relies on PHP ob_start chains — this
     * does NOT replace the buffer with a pure slot-bubble model.
     */
    protected function echo(string $output): void
    {
        $this->slots()->default()->append($output);

        echo $output;
    }

    protected function slots(): SlotsRegistry
    {
        return $this->slotsManager->registry();
    }

    protected function slotsManager(): SlotsManager
    {
        return $this->slotsManager;
    }

    protected function type(): string
    {
        return $this->type;
    }

    protected function block(): AbstractBlock
    {
        return $this->block;
    }

    protected function properties(): DataCollection
    {
        $properties = parent::properties();
        $properties->subset('attributes');

        return $properties;
    }
}
