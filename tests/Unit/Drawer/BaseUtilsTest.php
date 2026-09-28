<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Drawer;

use Magewirephp\Magewire\Component\Form;
use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Magewire\Playwright\Events\Basic;
use Magento\Framework\Interception\Interceptor;
use Magento\Framework\Interception\InterceptorInterface;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\Validator;

class BaseUtilsTest extends TestCase
{
    public function test_only_component_actions_are_exposed(): void
    {
        $methods = Utils::getPublicMethodsDefinedBySubClass(new Basic());

        self::assertContains('onClassKept', $methods);
        self::assertNotContains('tap', $methods);
        self::assertNotContains('setId', $methods);
        self::assertNotContains('dispatchMessage', $methods);
    }

    public function test_magento_interceptor_methods_are_not_exposed(): void
    {
        $component = new class extends Basic implements InterceptorInterface {
            use Interceptor;

            public function onClassKept(): void
            {
                parent::onClassKept();
            }

            public function tap($callback): static
            {
                return parent::tap($callback);
            }
        };

        $methods = Utils::getPublicMethodsDefinedBySubClass($component);

        self::assertContains('onClassKept', $methods);
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
        $component = new class extends Basic {
            public function __construct()
            {
                $this->result = 'constructed';
            }

            public function __internal(): void
            {
                $this->result = 'internal';
            }
        };

        $methods = Utils::getPublicMethodsDefinedBySubClass($component);

        self::assertContains('onClassKept', $methods);
        self::assertNotContains('__construct', $methods);
        self::assertNotContains('__internal', $methods);
    }
}
