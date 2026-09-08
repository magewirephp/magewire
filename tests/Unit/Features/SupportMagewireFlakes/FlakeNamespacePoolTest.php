<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireFlakes;

use InvalidArgumentException;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace\FlakeNamespace;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace\FlakeNamespacePool;
use PHPUnit\Framework\TestCase;

class FlakeNamespacePoolTest extends TestCase
{
    public function test_it_selects_a_namespace_by_exact_prefix(): void
    {
        $flake = new FlakeNamespace('flake', ['magewire_flakes'], 'magewire.flakes');
        $fixture = new FlakeNamespace('fixture', ['magewire_flakes_fixture'], 'magewire.flakes.fixture');
        $pool = new FlakeNamespacePool([$flake, $fixture]);

        self::assertSame($flake, $pool->get('flake'));
        self::assertSame($fixture, $pool->get('fixture'));
        self::assertTrue($pool->has('fixture'));
        self::assertFalse($pool->has('missing'));
    }

    public function test_it_rejects_duplicate_prefixes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate Flake namespace prefix "flake"');

        new FlakeNamespacePool([
            new FlakeNamespace('flake', 'magewire_flakes', 'magewire.flakes'),
            new FlakeNamespace('flake', 'other_handle', 'other.root')
        ]);
    }

    public function test_it_rejects_unknown_prefixes(): void
    {
        $pool = new FlakeNamespacePool();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown Flake namespace prefix "missing"');

        $pool->get('missing');
    }

    public function test_it_validates_namespace_configuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Flake namespace prefix');

        new FlakeNamespace('not:valid', 'magewire_flakes', 'magewire.flakes');
    }
}
