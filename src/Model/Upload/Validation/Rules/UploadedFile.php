<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Upload\Validation\Rules;

class UploadedFile extends \Rakit\Validation\Rules\UploadedFile
{
    use \Magewirephp\Magewire\Model\Upload\Validation\Rules\Traits\FileTrait;
}