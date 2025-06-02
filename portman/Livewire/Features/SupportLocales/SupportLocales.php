<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportLocales;

use Magento\Framework\Locale\Resolver as LocaleResolver;

class SupportLocales extends \Livewire\Features\SupportLocales\SupportLocales
{
    function __construct(
        private readonly LocaleResolver $localeResolver
    ) {
        //
    }

    function hydrate($memo)
    {
        //
    }

    function dehydrate($context)
    {
        $code = $this->localeResolver->getLocale();
        $locale = strstr($code, '_', true);

        $context->addMemo('locale', $locale);
    }
}
