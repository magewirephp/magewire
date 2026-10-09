<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Support;

use ArrayIterator;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManager\Config\Compiled as CompiledConfig;
use Magento\Framework\ObjectManager\Factory\Compiled as CompiledFactory;
use Magento\Framework\ObjectManager\ObjectManager as NativeObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Support\Concerns\WithFactory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/** A distinct type prevents an existing ArrayIteratorFactory from hiding the regression. */
class WithFactoryIteratorFixture extends ArrayIterator
{
}

/** @mago-expect lint:single-class-per-file */
class WithFactoryTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousObjectManager = ( new ReflectionProperty(ObjectManager::class, '_instance') )->getValue();
    }

    protected function tearDown(): void
    {
        ( new ReflectionProperty(ObjectManager::class, '_instance') )->setValue(null, $this->previousObjectManager);

        parent::tearDown();
    }

    public function test_it_creates_types_with_compiled_di_without_a_generated_factory(): void
    {
        $config = new CompiledConfig([
            'arguments' => [
                WithFactoryIteratorFixture::class => [
                    'array' => ['_vac_' => []],
                    'flags' => ['_v_' => 0]
                ]
            ]
        ]);
        $sharedInstances = [];
        $factory = new CompiledFactory($config, $sharedInstances);
        $objectManager = new NativeObjectManager($factory, $config, $sharedInstances);
        $factory->setObjectManager($objectManager);
        ObjectManager::setInstance($objectManager);

        self::assertFalse(class_exists(WithFactoryIteratorFixture::class . 'Factory'));

        $subject = new class {
            use WithFactory;
        };

        self::assertSame([1, 2], iterator_to_array($subject->newTypeInstance(WithFactoryIteratorFixture::class, ['array' => [1, 2]])));
    }
}
