<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Fragment\Component;

use Magento\Framework\App\State as ApplicationState;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Component\FlakeFactory;
use Magewirephp\Magewire\Model\View\Fragment;
use Magewirephp\Magewire\Model\View\Management\SlotsManager;
use Magewirephp\Magewire\Support\Random;
use Psr\Log\LoggerInterface;
use Throwable;

class Flake extends Fragment\Component
{
    // Prevent the slot's captured output from being directly echoed.
    // Slots are buffered internally and registered for later use in the component template.
    protected bool $echo = false;

    public function __construct(
        private readonly FlakeFactory $flakeFactory,
        private readonly ApplicationState $applicationState,
        string $type,
        string $id,
        AbstractBlock $block,
        SlotsManager $slotsManager,
        LoggerInterface $logger,
        Escaper $escaper,
        array $modifiers = []
    ) {
        parent::__construct($type, $block, $slotsManager, $logger, $escaper, $id, $modifiers);
    }

    public function end(): static
    {
        // Finalize fragment buffering to capture all output.
        parent::end();
        // Push the final output as the default slot content.
        $this->slots()->default()->push($this->output);

        try {
            $flake = $this->createFlakeByName($this->id());

            if ($flake === false) {
                throw new ComponentNotFoundException(
                    sprintf('Magewire: Flake "%s" could not be found or doesnt exist', $this->type())
                );
            }

            $this->echo($flake->toHtml());
        } catch (Throwable $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);

            if ($this->applicationState->getMode() !== ApplicationState::MODE_PRODUCTION) {
                echo '<!-- Flake exception: ' . $exception->getMessage() . '. -->';
            }
        }

        return $this;
    }

    protected function createFlakeByName(string $name): AbstractBlock|false
    {
        return $this->flakeFactory->createByName($this->type(), [
            'magewire:id' => Random::alphabetical(10),
            'magewire:name' => $name
        ]);
    }
}
