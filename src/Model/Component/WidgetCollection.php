<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Component;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\ComponentFactory;

class WidgetCollection implements TypeCollectionInterface
{
    /** @var Component[]<string, Component> $mapping */
    protected array $mapping;

    public function __construct(
        array $mapping = []
    ) {
        $this->mapping = $mapping;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->mapping);
    }

    public function get(string $id): Component
    {
        return $this->mapping[$id];
    }
}
