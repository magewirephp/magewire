<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Element;

class Event
{
    protected string $name;
    protected array $params;
    protected bool $up = false;
    protected bool $self = false;
    protected ?string $component = null;

    /**
     * Event constructor.
     * @param string $name
     * @param array $params
     */
    public function __construct(string $name, array $params = [])
    {
        $this->name = $name;
        $this->params = $params;
    }

    /**
     * @return $this
     */
    public function up(): Event
    {
        $this->up = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function self(): Event
    {
        $this->self = true;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function component(string $name): Event
    {
        $this->component = $name;
        return $this;
    }

    /**
     * @return $this
     */
    public function to(): Event
    {
        return $this;
    }

    /**
     * @return array
     */
    public function serialize(): array
    {
        $output = [
            'event'  => $this->name,
            'params' => array_values($this->params),
        ];

        if ($this->up) {
            $output['ancestorsOnly'] = true;
        }
        if ($this->self) {
            $output['selfOnly'] = true;
        }
        if ($this->component) {
            $output['to'] = $this->component;
        }

        return $output;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
