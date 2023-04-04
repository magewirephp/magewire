<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Upload\Validation\Rules;

class Mimes extends \Rakit\Validation\Rules\Mimes
{
    use \Magewirephp\Magewire\Model\Upload\Validation\Rules\Traits\FileTrait;
}