<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Exceptions;

class MethodNotFoundException extends \Exception
{
    use BypassViewHandler;
    private string $componentClass = '';
    private string $componentFile = '';
    private ?int $componentLine = null;
    public function __construct($method, $component = null)
    {
        if ($component !== null) {
            $this->componentClass = get_class($component);
            try {
                $ref = new \ReflectionClass($component);
                $this->componentFile = $ref->getFileName() ?: '';
                $this->componentLine = $ref->getStartLine() ?: null;
            } catch (\ReflectionException $e) {
                // fall through
            }
        }
        parent::__construct("Unable to call component method. Public method [{$method}] not found on component");
    }
    public function getComponentClass(): string
    {
        return $this->componentClass;
    }
    public function getComponentFile(): string
    {
        return $this->componentFile;
    }
    public function getComponentLine(): ?int
    {
        return $this->componentLine;
    }
}