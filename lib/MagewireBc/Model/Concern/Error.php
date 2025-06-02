<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Concern;

use Magento\Framework\Phrase;

trait Error
{
    /** @var Phrase[] */
    protected array $errors = [];

    /**
     * Throw an error.
     */
    public function error(string $property, string $message): Phrase
    {
        return $this->errors[$property] = __($message);
    }

    /**
     * @return Phrase[]
     */
    public function getErrors(array $targets = []): array
    {
        if (empty($targets)) {
            return $this->errors;
        }

        foreach ($targets as $key => $value) {
            if (isset($this->errors[$value])) {
                $targets[$value] = $this->errors[$value];
            }

            unset($targets[$key]);
        }

        return $targets;
    }

    public function hasErrors(array $targets = []): bool
    {
        return !empty($this->getErrors($targets));
    }

    public function hasError(string $property): bool
    {
        return isset($this->errors[$property]);
    }

    public function getError(string $property): Phrase
    {
        return $this->hasError($property) ? $this->errors[$property] : __('No %1 error found', [$property]);
    }

    public function clearErrors(): self
    {
        $this->errors = [];
        return $this;
    }
}
