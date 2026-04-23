<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility;

use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Model\Concern\BrowserEvent as BrowserEventConcern;
use Magewirephp\Magewire\Model\Concern\Emit as EmitConcern;
use Magewirephp\Magewire\Model\Concern\Error as ErrorConcern;
use Magewirephp\Magewire\Model\Concern\Request as RequestConcern;
use Magewirephp\Magewire\Model\Concern\View;

trait HandlesComponentBackwardsCompatibility
{
    use ErrorConcern;
    use BrowserEventConcern;
    use EmitConcern;
    use RequestConcern;
    use View;

    /**
     * Component id.
     *
     * @deprecated Has been replaced with a protected $__id. To get the property, the id() or getId()
     *             methods should be used instead.
     * @see static::id(), static::getId()
     * @var string|null
     */
    public $id;

    /** @deprecated */
    public const RESERVED_PROPERTIES = ['id', 'name'];

    /** @deprecated Cache backing for getPublicProperties(). */
    private array|null $__publicProperties = null;

    /**
     * @deprecated Use all() instead, which is the v2 equivalent.
     * @mago-expect lint:no-boolean-flag-parameter
     */
    public function getPublicProperties(bool $refresh = false, bool $origin = false): array
    {
        if ($origin) {
            return $this->magewireResolvePublicProperties();
        }

        if ($refresh || $this->__publicProperties === null) {
            $this->__publicProperties = $this->magewireResolvePublicProperties();
        }

        return $this->__publicProperties;
    }

    private function magewireResolvePublicProperties(): array
    {
        return array_diff_key(Utils::getPublicProperties($this), array_flip(self::RESERVED_PROPERTIES));
    }
}
