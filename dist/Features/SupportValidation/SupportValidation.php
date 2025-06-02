<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportValidation;

use Magewirephp\Magewire\Drawer\Utils;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\ViewErrorBag;
use Magewirephp\Magewire\ComponentHook;
class SupportValidation extends ComponentHook
{
    function hydrate($memo)
    {
        $this->component->setErrorBag($memo['errors'] ?? []);
    }
    function render($view, $data)
    {
        $errors = (new ViewErrorBag())->put('default', $this->component->getErrorBag());
        $revert = Utils::shareWithViews('errors', $errors);
        return function () use ($revert) {
            // After the component has rendered, let's revert our global
            // sharing of the "errors" variable with blade views...
            $revert();
        };
    }
    function dehydrate($context)
    {
        $errors = $this->component->getErrorBag()->toArray();
        // Only persist errors that were born from properties on the component
        // and not from custom validators (Validator::make) that were run.
        $context->addMemo('errors', collect($errors)->filter(function ($value, $key) {
            return Utils::hasProperty($this->component, $key);
        })->toArray());
    }
    function exception($e, $stopPropagation)
    {
        if (!$e instanceof ValidationException) {
            return;
        }
        $this->component->setErrorBag($e->validator->errors());
        $stopPropagation();
    }
}