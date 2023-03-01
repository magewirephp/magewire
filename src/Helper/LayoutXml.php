<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Helper;

class LayoutXml
{
    private array $blockNames = [];

    public function setBlockNames(array $blockNames): void
    {
        $this->blockNames = $blockNames;
    }

    public function blockNameExists(string $blockName): bool
    {
        return isset($this->blockNames[$blockName]);
    }
}
