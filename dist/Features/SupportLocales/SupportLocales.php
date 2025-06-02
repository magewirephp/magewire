<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportLocales;

use Magento\Framework\Locale\Resolver as LocaleResolver;
use Magewirephp\Magewire\ComponentHook;
class SupportLocales extends ComponentHook
{
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
    function __construct(private readonly LocaleResolver $localeResolver)
    {
        //
    }
}