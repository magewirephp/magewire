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
    public const KEY_ANCESTORS_ONLY = 'ancestorsOnly';
    public const KEY_SELF_ONLY = 'selfOnly';
    public const KEY_TO = 'to';

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
            $output[self::KEY_ANCESTORS_ONLY] = true;
        }
        if ($this->self) {
            $output[self::KEY_SELF_ONLY] = true;
        }
        if ($this->component) {
            $output[self::KEY_TO] = $this->component;
        }

        return $output;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function isAncestorsOnly(): bool
    {
        return $this->up === true;
    }

    public function isSelfOnly(): bool
    {
        return $this->self === true;
    }

    public function getToComponent(): ?string
    {
        return $this->component;
    }
}
