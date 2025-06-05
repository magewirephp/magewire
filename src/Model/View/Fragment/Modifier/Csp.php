<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View\Fragment\Modifier;

use Magento\Csp\Model\Collector\DynamicCollector as DynamicCspCollector;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\App\Cache\State as CacheState;
use Magento\Framework\View\LayoutInterface;
use Magento\PageCache\Model\Cache\Type as FPC;
use Magewirephp\Magewire\Model\View\Fragment;

class Csp extends Fragment\Modifier
{
    public function __construct(
        private readonly \Magewirephp\Magewire\Model\Csp $csp,
        private readonly DynamicCspCollector $dynamicCspCollector,
        private readonly LayoutInterface $layout,
        private readonly CacheState $cacheState
    ) {
        //
    }

    public function modify(Fragment $fragment): Fragment
    {
        if (! $fragment instanceof Fragment\Script) {
            return $fragment;
        }
        if (! $this->csp->isCspAvailable()) {
            return $fragment;
        }

        if ($this->useCspHeader()) {
            $this->includeCspHeader($fragment);
        } else {
            $fragment->setAttribute('nonce', $this->csp->getMagentoCspNonceProvider()->generateNonce());
        }

        return $fragment;
    }

    private function includeCspHeader(Fragment\Script $fragment): void
    {
        // Generate a hash based on the DOM node content.
        $hash = $this->csp->generateHash($fragment->getScriptCode());
        $algorithm = $this->csp->getHashAlgorithm();

        $this->dynamicCspCollector->add(
            new FetchPolicy(
                'script-src',
                false,
                [],
                [],
                false,
                false,
                false,
                [],
                [$hash => $algorithm]
            )
        );
    }

    private function useCspHeader(): bool
    {
        return $this->cacheState->isEnabled(FPC::TYPE_IDENTIFIER) && $this->layout->isCacheable();
    }
}
