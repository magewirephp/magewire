<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Magewire\Playwright\LazyLoading;

use Magewirephp\Magewire\Component;

/**
 * Base lazy-loading test component. Carries no #[Lazy] attribute, so on its own it
 * only lazy-loads when a "magewire:component:lazy" layout argument opts it in.
 *
 * mount() sets $mounted so the rendered template can prove the mount lifecycle ran
 * on the follow-up XHR rather than on the initial (placeholder) paint. placeholder()
 * returns a Magento template id, exercising the standalone-block placeholder path.
 */
class Basic extends Component
{
    public bool $mounted = false;

    public function mount(): void
    {
        $this->mounted = true;
    }

    public function placeholder(array $params = []): string
    {
        return 'Magewirephp_Magewire::tests/magewire/playwright/lazy_loading/placeholder.phtml';
    }
}