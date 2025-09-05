<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use BadMethodCallException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\HandlesMagewireCompiling;
use Magewirephp\Magewire\Features\SupportAttributes\HandlesAttributes;
use Magewirephp\Magewire\Features\SupportMagentoFlashMessages\HandlesMagewireFlashMessages;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\HandlesComponentBackwardsCompatibility;
use Magewirephp\Magewire\Features\SupportMagewireLoaders\HandlesMagewireLoaders;
use Magewirephp\Magewire\Features\SupportMagentoLayouts\HandlesMagentoLayout;
use Magewirephp\Magewire\Concerns\InteractsWithProperties;
use Magewirephp\Magewire\Exceptions\PropertyNotFoundException;
use Magewirephp\Magewire\Features\SupportEvents\HandlesEvents;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\HandlesMagewireViewInstructions;
use Magewirephp\Magewire\Features\SupportMagewireViewModel\HandlesMagewireViewModel;
use Magewirephp\Magewire\Features\SupportRedirects\HandlesRedirects;
use Magewirephp\Magewire\Features\SupportStreaming\HandlesStreaming;

abstract class Component implements ArgumentInterface
{
    use InteractsWithProperties;
    use HandlesEvents;
    use HandlesRedirects;
    use HandlesStreaming;
    use HandlesAttributes;
    //    use HandlesValidation;
    //    use HandlesFormObjects;
    //    use HandlesJsEvaluation;
    //    use HandlesPageComponents;
    //    use HandlesDisablingBackButtonCache;

    use HandlesMagentoLayout;
    use HandlesMagewireFlashMessages;
    use HandlesMagewireLoaders;
    use HandlesMagewireViewInstructions;
    use HandlesMagewireViewModel;
    use HandlesComponentBackwardsCompatibility;
    use HandlesMagewireCompiling;

    protected $__id;
    protected $__name;

    protected string|null $__alias = null;

    function id()
    {
        return $this->getId();
    }

    function setId($id)
    {
        // Support backwards compatibility.
        $this->id = $id;

        $this->__id = $id;
    }

    function getId()
    {
        return $this->__id;
    }

    function setName($name)
    {
        $this->__name = $name;
    }

    public function getName()
    {
        return $this->__name;
    }

    public function setAlias(string|null$alias): void
    {
        $this->__alias = $alias;
    }

    public function getAlias(): string|null
    {
        return $this->__alias;
    }

    public function hasAlias(): bool
    {
        return $this->__alias !== null;
    }

    public function skipRender($html = null): void
    {
        store($this)->set('skipRender', $html ?: true);
    }

    public function skipMount(): void
    {
        store($this)->set('skipMount', true);
    }

    public function skipHydrate(): void
    {
        store($this)->set('skipHydrate', true);
    }

    public function __isset($property)
    {
        try {
            $value = $this->__get($property);

            if (isset($value)) {
                return true;
            }
        } catch(\Magewirephp\Magewire\Exceptions\PropertyNotFoundException $exception) {}

        return false;
    }

    /**
     * @throws PropertyNotFoundException
     */
    public function __get($property)
    {
        $value = 'noneset';

        $returnValue = function ($newValue) use (&$value) {
            $value = $newValue;
        };

        $finish = trigger('__get', $this, $property, $returnValue);

        $value = $finish($value);

        if ($value === 'noneset') {
            throw new PropertyNotFoundException($property, $this->getName());
        }

        return $value;
    }

    public function __unset($property)
    {
        trigger('__unset', $this, $property);
    }

    public function __call($method, $params)
    {
        $value = 'noneset';

        $returnValue = function ($newValue) use (&$value) {
            $value = $newValue;
        };

        $finish = trigger('__call', $this, $method, $params, $returnValue);

        $value = $finish($value);

        if ($value !== 'noneset') {
            return $value;
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    public function tap($callback): static
    {
        $callback($this);

        return $this;
    }
}
