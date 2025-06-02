<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\View\Layout\ScheduledStructure;

use Magento\Framework\App\State;
use Magento\Framework\View\Layout\ScheduledStructure;
use Magento\Framework\View\Layout\Data\Structure;

class Helper extends ScheduledStructure\Helper
{
    function scheduleElement(ScheduledStructure $scheduledStructure, Structure $structure, $key)
    {
        $row  = $scheduledStructure->getStructureElement($key);
        $data = $scheduledStructure->getStructureElementData($key);

        if (! isset($row[self::SCHEDULED_STRUCTURE_INDEX_TYPE])) {
            $this->logger->critical('Broken reference: missing declaration of the element "{$key}".');

            $scheduledStructure->unsetPathElement($key);
            $scheduledStructure->unsetStructureElement($key);

            return;
        }

        list($type, $alias, $parentName, $siblingName, $isAfter) = $row;

        $name = $this->_createStructuralElement($structure, $key, $type, $parentName . $alias);

        if ($parentName) {
            if ($scheduledStructure->hasStructureElement($parentName)) {
                $this->scheduleElement($scheduledStructure, $structure, $parentName);
            }

            if (! $structure->hasElement($parentName)) {
                /*
                 * Without a fully loaded page, there won't be a wrapping element acting as the root content container.
                 * This leads to a problem where the starting parent element isn't available, causing an error.
                 * Emulation becomes necessary to attach their respective children when this is the case.
                 */
                $structure->createElement($parentName, [
                    'attributes' => [
                        'label' => ucfirst($parentName)
                    ],
                    'type' => \Magento\Framework\View\Layout\Element::TYPE_CONTAINER
                ]);
            }

            $structure->setAsChild($name, $parentName, $alias);
        }

        // Transforming a scheduledStructure into a scheduledElement.
        $scheduledStructure->unsetStructureElement($key);
        $scheduledStructure->setElement($name, [$type, $data]);

        /*
         * Some elements provide info "after" or "before" which sibling they are supposed to go
         * Add element into list of sorting
         */
        if ($siblingName) {
            $scheduledStructure->setElementToSortList($parentName, $name, $siblingName, $isAfter);
        }
    }
}
