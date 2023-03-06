<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Upload\Validation\Rules\Traits;

trait FileTrait
{
    public function isUploadedFile($value): bool
    {
        return $this->isValueFromUploadedFiles($value);
    }
}