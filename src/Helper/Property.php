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
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

/**
 * Class Property
 * @package Magewirephp\Magewire\Helper
 */
class Property
{
    /** @var ArrayManager $arrayManager */
    protected $arrayManager;

    /**
     * Magic constructor.
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
    }

    /**
     * @param string $property
     * @return bool
     */
    public function containsDots(string $property): bool
    {
        return strpos($property, '.') !== false;
    }

    /**
     * @param string $path
     * @param $value
     * @param Component $component
     * @return array
     */
    public function transformDots(string $path, $value, Component $component): array
    {
        $pieces = explode('.', $path);
        $property = $pieces[0];

        array_shift($pieces);
        $value = $this->arrayManager->set(implode('/', $pieces), $component->{$property}, $value);

        return compact('property', 'value');
    }

    /**
     * Use a callback function to assign component property
     * values except default reserved properties.
     *
     * @param callable $callback
     * @param RequestInterface|ResponseInterface $subject (waiting for PHP 8.x support)
     * @param Component $component
     * @return void
     */
    public function assign(callable $callback, $subject, Component $component): void
    {
        $publicProperties = $component->getPublicProperties();

        foreach ($subject->memo['data'] as $property => $value) {
            if (in_array($property, Component::RESERVED_PROPERTIES, true)) {
                continue;
            }
            if (array_key_exists($property, $publicProperties) && ($component->{$property} !== $value)) {
                $callback($component, $subject, $property, $value);
            }
        }
    }
}
