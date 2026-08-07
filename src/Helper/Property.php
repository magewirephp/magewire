<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Helper;

use Magento\Framework\Stdlib\ArrayManager;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ComponentException;

class Property
{
    protected ArrayManager $arrayManager;

    public function __construct(
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
    }

    public function containsDots(string $property): bool
    {
        return strpos($property, '.') !== false;
    }

    /**
     * @throws ComponentException
     */
    public function transformDots(string $path, $value, Component $component): array
    {
        $property = strstr($path, '.', true);
        $realpath = $path;

        if (!array_key_exists($property, $component->getPublicProperties())) {
            throw new ComponentException(__('Public property %1 doesn\'t exist', [$property]));
        }

        $target = $path;
        $lastDotPosition = strrpos($realpath, '.');

        if ($lastDotPosition !== false) {
            $target = substr($realpath, $lastDotPosition + 1);
        }

        $path = substr(strstr($path, '.'), 1);
        $data = $this->arrayManager->set($path, $component->{$property}, $value, '.');

        return compact('property', 'data', 'realpath', 'path', 'target');
    }

    public function assignViaDots(string $path, $value, array $subject)
    {
        return $this->detachReferences($this->arrayManager->set($path, $subject, $value, '.'));
    }

    /**
     * Returns a copy in which no element is a PHP reference.
     *
     * ArrayManager::set() walks the path by reference and leaves one behind on the element it
     * touched, so a component property such as `fields` ends up holding `&array` at the index
     * that was written. References are invisible to json_encode() and var_export(), which makes
     * the consequences look impossible:
     *
     *   - Rakit's wildcard resolution cannot read through a reference, so a rule like
     *     `fields.*.sku` reports "required" against a value that is plainly set.
     *   - Worse, Rakit writes back through it, nulling the value in the component's own property.
     *     The next dehydration then drops the key entirely, so the field clears in the UI too.
     *
     * Reproducible without Magento or Magewire:
     *
     *   $data = ['fields' => [['sku' => 'ABC']]];
     *   $ref  = &$data['fields'][0];
     *   (new Validator())->make($data, ['fields.*.sku' => 'required'])->validate();  // fails
     *
     * Copying by value on the way out keeps the reference inside ArrayManager, where it belongs.
     *
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function detachReferences(array $data): array
    {
        $copy = [];

        foreach ($data as $key => $value) {
            $copy[$key] = is_array($value) ? $this->detachReferences($value) : $value;
        }

        return $copy;
    }

    public function searchViaDots(string $path, array $value)
    {
        return $this->arrayManager->get($path, $value, null, '.');
    }

    /**
     * Use a callback function to assign component property
     * values except default reserved properties.
     */
    public function assign(callable $callback, Component $component, ?array $data = null, bool $merge = true): void
    {
        $publicProperties = $component->getPublicProperties(true);
        $data = $data === null ? $publicProperties : ($merge ? array_merge($publicProperties, $data) : $data);

        foreach ($data as $property => $value) {
            if (in_array($property, Component::RESERVED_PROPERTIES, true)) {
                continue;
            }

            if (array_key_exists($property, $publicProperties)) {
                $callback($component, $property, $value);
            }
        }
    }

    public function resyncPropsWithRequestData(Component $component): Component
    {
        return $component;
    }
}
