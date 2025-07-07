<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magento\View\Layout;

use Magento\Framework\App\State;
use Magento\Framework\View\Layout\Condition\ConditionFactory;
use Magento\Framework\View\Layout\ScheduledStructure;
use Magewirephp\Magento\View\Layout\ScheduledStructure\Helper as MagewireLayoutScheduledStructureHelper;
use Psr\Log\LoggerInterface;

class GeneratorPool extends \Magento\Framework\View\Layout\GeneratorPool
{
    public function __construct(
        ScheduledStructure\Helper $helper,
        ConditionFactory $conditionFactory,
        LoggerInterface $logger,
        MagewireLayoutScheduledStructureHelper $magewireLayoutScheduledStructureHelper,
        ?array $generators = null,
        ?State $state = null
    ) {
        parent::__construct($helper, $conditionFactory, $logger, $generators, $state);

        /*
         * After extensive research, the decision was made to replace the original layout scheduled structure helper
         * with a custom one. Initially, we attempted to inject a custom Structure object directly into the Layout.
         * However, due to specific typing constraints, this approach proved infeasible. Even utilizing a custom
         * Layout object with a custom Structure object led to various type-specific exceptions.
         */
        $this->helper = $magewireLayoutScheduledStructureHelper;
    }

    protected function addGenerators(array $generators)
    {
        // Limit the generators to just blocks and containers.
        parent::addGenerators(
            array_filter($generators, fn ($generator) => in_array($generator, ['block', 'container']), ARRAY_FILTER_USE_KEY)
        );
    }
}
