<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Features\SupportMagewireViewModel;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Text;
use Magewirephp\Magewire\Features\SupportMagewireViewModel\SupportMagewireViewModel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class SupportMagewireViewModelTest extends TestCase
{
    public function test_it_keeps_a_view_model_that_is_not_a_magewire_view_model(): void
    {
        $viewModel = $this->createStub(ArgumentInterface::class);
        $block = $this->createBlock(['view_model' => $viewModel]);

        $this->bindMagewireViewModel($block);

        self::assertSame($viewModel, $block->getData('view_model'));
    }

    private function createBlock(array $data): AbstractBlock
    {
        $block = (new ReflectionClass(Text::class))->newInstanceWithoutConstructor();
        (new ReflectionMethod(DataObject::class, '__construct'))->invoke($block, $data);

        return $block;
    }

    private function bindMagewireViewModel(AbstractBlock $block): void
    {
        $feature = (new ReflectionClass(SupportMagewireViewModel::class))->newInstanceWithoutConstructor();

        (new ReflectionMethod(SupportMagewireViewModel::class, 'bindMagewireViewModel'))->invoke($feature, $block);
    }
}
