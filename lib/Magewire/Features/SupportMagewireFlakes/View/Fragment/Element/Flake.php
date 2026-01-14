<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Fragment\Element;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Component\FlakeFactory;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\ComponentResolverNotFoundException;
use Magewirephp\Magewire\Model\View\Fragment;
use Magewirephp\Magewire\Model\View\SlotsRegistry;
use Magewirephp\Magewire\Support\Random;
use Psr\Log\LoggerInterface;

class Flake extends Fragment\Element
{
    // Prevent the slot's captured output from being directly echoed.
    // Slots are buffered internally and registered for later use in the component template.
    protected bool $echo = false;

    public function __construct(
        private FlakeFactory $flakeFactory,
        string $variant,
        AbstractBlock $block,
        SlotsRegistry $slotsRegistry,
        LoggerInterface $logger,
        Escaper $escaper,
        array $modifiers = []
    ) {
        parent::__construct($variant, $block, $slotsRegistry, $logger, $escaper, $modifiers);
    }

    public function start(): static
    {
        // Begin tracking a new slot context for this component.
        // This pushes a new layer onto the slot registry stack, allowing named slots to be captured.
        $this->slotsRegistry->track();

        return parent::start();
    }

    public function end(): void
    {
        // Finalize fragment buffering to capture all output.
        parent::end();

        // Register any content outside of named slots as the 'default' slot.
        $this->slotsRegistry->update('default', $this->output);

        try {
            $flake = $this->flakeFactory->createByName($this->variant, [
                'magewire:id' => Random::alphabetical(4),
                'magewire:name' => Random::alphabetical(4),
            ]);

            // Render the final Flake component.
            echo $flake->toHtml();
        } catch (ComponentResolverNotFoundException $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

            echo '<!-- Flake component could not be rendered. -->';
        }

        // End tracking for this component — pop the current slot context from the stack.
        $this->slotsRegistry->untrack();
    }
}
