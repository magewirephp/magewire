<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Fragment\Element;

use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Component\FlakeFactory;
use Magewirephp\Magewire\Model\View\Fragment;
use Magewirephp\Magewire\Model\View\SlotsRegistry;
use Magewirephp\Magewire\Support\Random;
use Psr\Log\LoggerInterface;
use Throwable;

class Flake extends Fragment\Element
{
    // Prevent the slot's captured output from being directly echoed.
    // Slots are buffered internally and registered for later use in the component template.
    protected bool $echo = false;

    public function __construct(
        private FlakeFactory $flakeFactory,
        private ApplicationState $applicationState,
        string $variant,
        AbstractBlock $block,
        SlotsRegistry $slotsRegistry,
        LoggerInterface $logger,
        Escaper $escaper,
        array $modifiers = []
    ) {
        parent::__construct($variant, $block, $slotsRegistry, $logger, $escaper, $modifiers);
    }

    public function end(): static
    {
        // Finalize fragment buffering to capture all output.
        parent::end();
        // Register any content outside of named slots as the 'default' slot.
        $this->slotsRegistry->update('default', $this->output);

        try {
            $flake = $this->flakeFactory->createByName($this->variant, [
                'magewire:id'   => Random::alphabetical(4),
                'magewire:name' => Random::alphabetical(4),
            ]);

            if ($flake === false) {
                throw new ComponentNotFoundException(
                    sprintf('Magewire: Flake "%s" could not be found or doesnt exist.', $this->variant)
                );
            }

            // Render the final Flake component.
            echo $flake->toHtml();
        } catch (Throwable $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

            if ($this->applicationState->getMode() !== ApplicationState::MODE_PRODUCTION) {
                echo '<!-- ' . $exception->getMessage() . ' -->';
            }
        }

        return $this;
    }
}
