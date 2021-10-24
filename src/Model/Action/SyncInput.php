<?php

declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ComponentActionException;
use Magewirephp\Magewire\Helper\Property as PropertyHelper;
use Magewirephp\Magewire\Model\ActionInterface;

/**
 * Class SyncInput.
 */
class SyncInput implements ActionInterface
{
    /** @var PropertyHelper */
    private $propertyHelper;

    /**
     * Magic constructor.
     *
     * @param PropertyHelper $propertyHelper
     */
    public function __construct(
        PropertyHelper $propertyHelper
    ) {
        $this->propertyHelper = $propertyHelper;
    }

    /**
     * @inheritdoc
     *
     * @throws ComponentActionException
     */
    public function handle(Component $component, array $payload)
    {
        if (!isset($payload['name'], $payload['value'])) {
            throw new ComponentActionException(__('Invalid update payload'));
        }

        if ($this->propertyHelper->containsDots($payload['name'])) {
            $transform = $this->propertyHelper->transformDots($payload['name'], $payload['value'], $component);

            // Define a new method since it's a nested property
            $method = preg_replace_callback('/[-_.](.?)/', static function ($matches) {
                return ucfirst($matches[1]);
            }, $payload['name']);

            // Re-assign original method properties
            $payload['name'] = $transform['property'];
            $payload['value'] = $transform['value'];
        }

        $component->assign($payload['name'], $payload['value'], false, $method ?? null);
    }
}
