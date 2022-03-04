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
    protected $name;
    protected $params;
    protected $up;
    protected $self;
    protected $component;

    /**
     * Event constructor.
     * @param $name
     * @param $params
     */
    public function __construct($name, $params)
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
            'params' => $this->params,
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
}
