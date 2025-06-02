<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportEvents;

use function Magewirephp\Magewire\app;
use Magewirephp\Magewire\Mechanisms\ComponentRegistry;
class Event
{
    protected $name;
    protected $params;
    protected $self;
    protected $component;
    public function __construct($name, $params)
    {
        $this->name = $name;
        $this->params = $params;
    }
    public function self()
    {
        $this->self = true;
        return $this;
    }
    public function component($name)
    {
        $this->component = $name;
        return $this;
    }
    public function to($name)
    {
        return $this->component($name);
    }
    public function serialize()
    {
        $output = ['name' => $this->name, 'params' => $this->params];
        if ($this->self) {
            $output['self'] = true;
        }
        if ($this->component) {
            $output['to'] = app(ComponentRegistry::class)->getName($this->component);
        }
        return $output;
    }
}