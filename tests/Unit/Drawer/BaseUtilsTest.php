<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Drawer;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Component\Form;
use Magewirephp\Magewire\Concerns\InteractsWithProperties;
use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Features\SupportRedirects\HandlesRedirects;
use Magewirephp\Magewire\Tests\Unit\Fixtures\ActionComponent;
use Magewirephp\Magewire\Tests\Unit\Fixtures\ActionInterceptor;
use Magento\Framework\Interception\Interceptor;
use Magento\Framework\Interception\InterceptorInterface;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\Validator;

require_once __DIR__ . '/../Fixtures/ActionComponent.php';
require_once __DIR__ . '/../Fixtures/ActionInterceptor.php';

class BaseUtilsTest extends TestCase
{
    public function test_only_component_actions_are_exposed(): void
    {
        $component = new ActionComponent();

        $methods = Utils::getPublicMethodsDefinedBySubClass($component);

        self::assertContains('onAction', $methods);
        self::assertNotContains('tap', $methods);
        self::assertNotContains('setId', $methods);
        self::assertNotContains('dispatchMessage', $methods);
    }

    public function test_public_action_can_use_an_inherited_helper(): void
    {
        $component = new class extends Component {
            public string $name = 'original';

            public function clearName(): void
            {
                $this->fill(['name' => '']);
            }
        };

        $methods = Utils::getPublicMethodsDefinedBySubClass($component);

        self::assertContains('clearName', $methods);
        self::assertNotContains('fill', $methods);

        $component->clearName();
        self::assertSame('', $component->name);
    }

    public function test_magento_interceptor_methods_are_not_exposed(): void
    {
        $component = new class extends ActionComponent implements InterceptorInterface {
            use Interceptor;

            public function onAction(): void
            {
            }

            public function tap($callback): static
            {
                return parent::tap($callback);
            }
        };

        $methods = Utils::getPublicMethodsDefinedBySubClass($component);

        self::assertContains('onAction', $methods);
        self::assertNotContains('tap', $methods);
        self::assertNotContains('___callParent', $methods);
        self::assertNotContains('___init', $methods);
    }

    public function test_form_framework_methods_are_not_exposed(): void
    {
        $component = new class(new Validator()) extends Form {
            public function onSave(): string
            {
                return 'saved';
            }
        };

        $methods = Utils::getPublicMethodsDefinedBySubClass($component);

        self::assertSame(['onSave'], array_values($methods));
    }

    public function test_form_methods_remain_hidden_behind_a_magento_interceptor(): void
    {
        $component = new class(new Validator()) extends Form implements InterceptorInterface {
            use Interceptor;

            public function validateOnly(array $rules = [], array $messages = [], ?array $data = null): bool
            {
                return parent::validateOnly($rules, $messages, $data);
            }
        };

        self::assertSame([], array_values(Utils::getPublicMethodsDefinedBySubClass($component)));
    }

    public function test_application_magic_methods_are_not_browser_actions(): void
    {
        $component = new class extends Component {
            public function __construct()
            {
            }

            public function __internal(): void
            {
            }

            public function onAction(): void
            {
            }
        };

        $methods = Utils::getPublicMethodsDefinedBySubClass($component);

        self::assertContains('onAction', $methods);
        self::assertNotContains('__construct', $methods);
        self::assertNotContains('__internal', $methods);
    }

    public function test_reimported_framework_traits_are_not_browser_actions(): void
    {
        $component = new class extends Component {
            use HandlesRedirects;
            use InteractsWithProperties;

            public function onSave(): void
            {
            }
        };

        $methods = Utils::getPublicMethodsDefinedBySubClass($component);

        self::assertSame(['onSave'], array_values($methods));
    }

    public function test_nested_magento_interceptors_do_not_expose_base_methods(): void
    {
        $component = new class extends ActionInterceptor implements InterceptorInterface {
            use Interceptor;

            public function tap($callback): static
            {
                return parent::tap($callback);
            }
        };

        self::assertSame(['onAction'], array_values(Utils::getPublicMethodsDefinedBySubClass($component)));
    }
}
