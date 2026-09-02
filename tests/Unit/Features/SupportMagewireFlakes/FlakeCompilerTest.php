<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireFlakes;

use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Compiler\Middleware\Flake;
use PHPUnit\Framework\TestCase;

class FlakeCompilerTest extends TestCase
{
    public function test_default_prefix_keeps_existing_flake_syntax(): void
    {
        $compiled = (new Flake())->compile('<flake:card />');

        self::assertStringContainsString("@magewireComponent(prefix: 'flake'", $compiled);
        self::assertStringContainsString("type: 'card'", $compiled);
        self::assertStringContainsString('@magewireEndComponent', $compiled);
        self::assertStringNotContainsString('<flake:card', $compiled);
    }

    public function test_a_fixture_prefix_uses_the_same_compiler(): void
    {
        $compiled = (new Flake('fixture'))->compile('<fixture:message />');

        self::assertStringContainsString("@magewireComponent(prefix: 'fixture'", $compiled);
        self::assertStringContainsString("type: 'message'", $compiled);
        self::assertStringNotContainsString('<fixture:message', $compiled);
    }
}
