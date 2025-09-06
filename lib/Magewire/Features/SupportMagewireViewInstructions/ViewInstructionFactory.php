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

class ViewInstructionFactory
{
    /**
     * @param array<string, ViewInstruction|string> $instructions
     */
    public function __construct(
        private readonly array $instructions = []
    ) {
        //
    }

    /**
     * @template T of ViewInstruction
     * @param class-string<T> $instruction
     * @return T
     * @throws ViewInstructionDoesNotExistException
     */
    public function create(string $instruction): ViewInstruction
    {
        if (array_key_exists($instruction, $this->instructions)) {
            return $this->instructions[$instruction];
        }
        if (class_exists($instruction)) {
            return ObjectManager::getInstance()->create($instruction);
        }

        throw new ViewInstructionDoesNotExistException();
    }
}
