<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Symfony;

use Symfony\Component\Console\Command\Command;

class MagewireCommand extends Command
{
    public function setName($name)
    {
        return parent::setName('magewire:' . $name);
    }
}
