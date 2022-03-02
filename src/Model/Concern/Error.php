<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

use Magento\Framework\Phrase;

trait Error
{
    /** @var Phrase[] $errors */
    protected array $errors = [];

    /**
     * Throw an error.
     *
     * @param string $property
     * @param string $message
     * @return Phrase
     */
    public function error(string $property, string $message): Phrase
    {
        return $this->errors[$property] = __($message);
    }

    /**
     * @return Error[]
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

    /**
     * @param array $targets
     * @return bool
     */
    public function hasErrors(array $targets = []): bool
    {
        return !empty($this->getErrors($targets));
    }

    /**
     * @param string $property
     * @return bool
     */
    public function hasError(string $property): bool
    {
        return isset($this->errors[$property]);
    }

    /**
     * @param string $property
     * @return Phrase
     */
    public function getError(string $property): Phrase
    {
        return $this->hasError($property) ? $this->errors[$property] : __('No %1 error found', [$property]);
    }
}
