<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility;

use Magewirephp\Magewire\Model\Concern\BrowserEvent as BrowserEventConcern;
use Magewirephp\Magewire\Model\Concern\Emit as EmitConcern;
use Magewirephp\Magewire\Model\Concern\Error as ErrorConcern;

trait HandlesComponentBackwardsCompatibility
{
    use ErrorConcern;
    use BrowserEventConcern;
    use EmitConcern;

    /**
     * Component id.
     *
     * @deprecated Has been replaced with a protected $__id. To get the property, the id() or getId()
     *             methods should be used instead.
     * @see static::id(), static::getId()
     * @var string|null
     */
    public $id;
}
