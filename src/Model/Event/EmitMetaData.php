<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Event;

use Magento\Framework\DataObject;
use Magewirephp\Magewire\Model\Element\Event;

class EmitMetaData extends DataObject
{
    public function __construct(
        bool $isAncestorsOnly = false,
        bool $isSelfOnly = false,
        ?string $toComponent = null
    ) {
        parent::__construct([
            Event::KEY_ANCESTORS_ONLY => $isAncestorsOnly,
            EVENT::KEY_SELF_ONLY => $isSelfOnly,
            EVENT::KEY_TO => $toComponent
        ]);
    }

    public function isAncestorsOnly(): bool
    {
        return $this->getData(Event::KEY_ANCESTORS_ONLY);
    }

    public function isSelfOnly(): bool
    {
        return $this->getData(Event::KEY_SELF_ONLY);
    }

    public function isToComponent(): bool
    {
        return is_string($this->getData(Event::KEY_TO));
    }

    public function getToComponent(): string
    {
        return $this->getData(Event::KEY_TO) ?? '';
    }
}
