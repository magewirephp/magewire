<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Upload\Validation\Rules;

class Required extends \Rakit\Validation\Rules\Required
{
    use \Magewirephp\Magewire\Model\Upload\Validation\Rules\Traits\FileTrait;
}