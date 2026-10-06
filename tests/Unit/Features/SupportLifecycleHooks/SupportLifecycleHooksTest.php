<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportLifecycleHooks;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Features\SupportLifecycleHooks\DirectlyCallingLifecycleHooksNotAllowedException;
use Magewirephp\Magewire\Features\SupportLifecycleHooks\SupportLifecycleHooks;
use PHPUnit\Framework\TestCase;

class SupportLifecycleHooksTest extends TestCase
{
    public function test_browser_calls_cannot_invoke_application_lifecycle_hooks(): void
    {
        $component = new class extends Component {
            public function boot(): void
            {
            }

            public function bootHandlesRedirects(): void
            {
            }

            public function placeholder(): string
            {
                return '<div></div>';
            }

            public function onSave(): void
            {
            }
        };
        $component->setName('test');

        $hook = new SupportLifecycleHooks();
        $hook->setComponent($component);

        foreach (['boot', 'bootHandlesRedirects', 'placeholder'] as $method) {
            try {
                $hook->call($method, [], static function (): void {});
                self::fail("Lifecycle method {$method} was browser-callable");
            } catch (DirectlyCallingLifecycleHooksNotAllowedException) {
                self::assertTrue(true);
            }
        }

        $hook->call('onSave', [], static function (): void {});
    }
}
