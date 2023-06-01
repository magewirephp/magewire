<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

use Magewirephp\Magewire\Model\Component\ResolverInterface;

trait Resolver
{
    protected ?ResolverInterface $resolver = null;
    protected array $metadata = [];

    public function setResolver(ResolverInterface $resolver): self
    {
        $this->resolver = $resolver;
        return $this;
    }

    public function getResolver(): ?ResolverInterface
    {
        return $this->resolver;
    }

    public function setMetaData(array $data): self
    {
        $this->metadata = $data;
        return $this;
    }

    public function getMetaData(): array
    {
        return $this->metadata;
    }
}
