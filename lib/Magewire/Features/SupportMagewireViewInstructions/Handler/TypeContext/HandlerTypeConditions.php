<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\TypeContext;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Customer\Model\Session as CustomerSession;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\HandlerType;
use Magewirephp\Magewire\Features\SupportMagewireViewInstructions\Handler\HandlerTypeContext;
use Magewirephp\Magewire\Support\Random;

class HandlerTypeConditions extends HandlerTypeContext
{
    public function __construct(
        private readonly HandlerType $handler,
        private readonly CustomerSession $customerSession
    ) {
        parent::__construct($handler);
    }

    public function if(callable $condition, string|null $alias = null): static
    {
        $this->handler()->data()->set('_conditions', $condition, $alias ?? Random::string());

        return $this;
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->if(function () use ($customer) {
            return $this->customerSession->getId() === $customer->getId();
        });
    }

    public function forCustomerGroup(CustomerGroup $group): static
    {
        return $this->if(function () use ($group) {
            return $this->customerSession->getCustomerGroupId() === $group->getId();
        });
    }
}
