<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions;

use Magento\Framework\Phrase;

use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Instructions\Action as ActionInstruction;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Instructions\Magento\FlashMessage as MagentoFlashMessage;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Instructions\Notification as NotificationInstruction;

/**
 * @todo Refactor the view instruction maker to accept an array-typed dependency that
 *       allows additional instruction types to be injected using a key-value pair.
 *       These types should be accessible via the __call() method, enabling developers
 *       to map custom instruction types dynamically without requiring direct
 *       knowledge of specific view instruction type classes.
 */
class ViewInstructionMaker
{
    public function __construct(
        private readonly ViewInstructions $viewInstructions
    ) {
        //
    }

    /**
     * @template T of ViewInstruction
     * @return T
     */
    public function notification(Phrase|null $message = null, string|null $name = null): NotificationInstruction
    {
        $instruction = $this->viewInstructions->factory()->create(NotificationInstruction::class);

        if ($name) {
            $instruction->withName($name);
        }
        if ($message) {
            $instruction->withMessage($message);
        }

        return $this->viewInstructions->instruct($instruction);
    }

    /**
     * @template T of ViewInstruction
     * @return T
     */
    public function action(string|null $action = null, string|null $name = null): ActionInstruction
    {
        $instruction = $this->viewInstructions->factory()->create(ActionInstruction::class);

        if ($name) {
            $instruction->withName($name);
        }
        if ($action) {
            $instruction->execute($action);
        }

        return $this->viewInstructions->instruct($instruction);
    }

    /**
     * @template T of ViewInstruction
     * @return T
     */
    public function magentoFlashMessage(Phrase|null $message = null, string|null $name = null): MagentoFlashMessage
    {
        $instruction = $this->viewInstructions->factory()->create(MagentoFlashMessage::class);

        if ($name) {
            $instruction->withName($name);
        }
        if ($message) {
            $instruction->withMessage($message);
        }

        return $this->viewInstructions->instruct($instruction);
    }
}
