<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Support;

use ArrayIterator;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Support\Concerns\WithFactory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class WithFactoryTest extends TestCase
{
    private ObjectManagerInterface|null $previousObjectManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousObjectManager = (new ReflectionProperty(ObjectManager::class, '_instance'))->getValue();
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(ObjectManager::class, '_instance'))->setValue(null, $this->previousObjectManager);

        parent::tearDown();
    }

    public function test_it_creates_types_without_a_generated_factory(): void
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects(self::never())->method('get');
        $objectManager
            ->expects(self::once())
            ->method('create')
            ->with(ArrayIterator::class, ['array' => [1, 2]])
            ->willReturnCallback(static fn (string $type, array $arguments) => new ArrayIterator($arguments['array']));
        ObjectManager::setInstance($objectManager);

        $subject = new class {
            use WithFactory;
        };

        self::assertSame([1, 2], iterator_to_array($subject->newTypeInstance(ArrayIterator::class, ['array' => [1, 2]])));
    }
}
