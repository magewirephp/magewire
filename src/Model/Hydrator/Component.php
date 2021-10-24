<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magewirephp\Magewire\Component as MagewireComponent;
use Magewirephp\Magewire\Helper\Component as ComponentHelper;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

/**
 * Class Component.
 */
class Component implements HydratorInterface
{
    /** @var ComponentHelper */
    private $componentHelper;

    /**
     * Component constructor.
     *
     * @param ComponentHelper $componentHelper
     */
    public function __construct(
        ComponentHelper $componentHelper
    ) {
        $this->componentHelper = $componentHelper;
    }

    /**
     * @lifecyclehook boot
     * @lifecyclehook hydrate
     * @lifecyclehook mount
     *
     * @inheritdoc
     */
    public function hydrate(MagewireComponent $component, RequestInterface $request): void
    {
        $data = $this->componentHelper->extractDataFromBlock($component->getParent(), ['request' => $request]);
        $component->boot(...array_values($data));

        if ($request->isSubsequent()) {
            $this->executePropertyLifecycleHook($component, 'hydrate', $request);
            $component->hydrate($request);
        } else {
            $component->mount(...array_values($data));
        }
    }

    /**
     * @lifecyclehook dehydrate
     *
     * @inheritdoc
     */
    public function dehydrate(MagewireComponent $component, ResponseInterface $response): void
    {
        $component->dehydrate($response);
        $this->executePropertyLifecycleHook($component, 'dehydrate', $response);
    }

    /**
     * @param MagewireComponent $component
     * @param string            $type
     * @param object            $object
     */
    public function executePropertyLifecycleHook(MagewireComponent $component, string $type, object $object): void
    {
        foreach ($component->getPublicProperties() as $property => $value) {
            $component->{strtolower($type).ucfirst($property)}($value, $object);
        }
    }
}
