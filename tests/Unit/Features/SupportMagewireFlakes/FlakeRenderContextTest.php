<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireFlakes;

use InvalidArgumentException;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition\FlakeDefinition;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Definition\FlakeDefinitionMetadata;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Namespace\FlakeNamespace;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakeAttributeBag;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakePropBag;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\View\FlakeRenderContext;
use PHPUnit\Framework\TestCase;

class FlakeRenderContextTest extends TestCase
{
    public function test_every_occurrence_owns_its_values(): void
    {
        $namespace = new FlakeNamespace('flake', 'magewire_flakes', 'magewire.flakes');
        $definition = new FlakeDefinition(
            $namespace,
            'card',
            Template::class,
            'Vendor_Module::flakes/card.phtml',
            new FlakeDefinitionMetadata()
        );
        $first = new FlakeRenderContext(
            'first',
            $namespace,
            $definition,
            ['request' => 1],
            new FlakePropBag(['tone' => 'quiet']),
            new FlakeAttributeBag(['class' => 'first'])
        );
        $second = new FlakeRenderContext(
            'second',
            $namespace,
            $definition,
            ['request' => 2],
            new FlakePropBag(['tone' => 'loud']),
            new FlakeAttributeBag(['class' => 'second'])
        );

        self::assertNotSame($first, $second);
        self::assertSame(1, $first->data()['request']);
        self::assertSame(2, $second->data()['request']);
        self::assertSame('quiet', $first->props()->get('tone'));
        self::assertSame('loud', $second->props()->get('tone'));
        self::assertSame('first', $first->attributes()->get('class'));
        self::assertSame('second', $second->attributes()->get('class'));
        self::assertSame(0, $first->slots()->count());
        self::assertSame(0, $first->ancestors()->count());
    }

    public function test_a_definition_cannot_cross_namespace_boundaries(): void
    {
        $flake = new FlakeNamespace('flake', 'magewire_flakes', 'magewire.flakes');
        $fixture = new FlakeNamespace('fixture', 'magewire_flakes_fixture', 'magewire.flakes.fixture');
        $definition = new FlakeDefinition(
            $flake,
            'card',
            Template::class,
            null,
            new FlakeDefinitionMetadata()
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not belong to the render namespace');

        new FlakeRenderContext('crossed', $fixture, $definition);
    }
}
