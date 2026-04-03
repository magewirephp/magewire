<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Mechanisms\ComponentRegistry;
use Magewirephp\Magewire\Facade\HandleComponentsFacade;
use Magewirephp\Magewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Magewirephp\Magewire\Mechanisms\HandleRequests\HandleRequests;
use Magewirephp\Magewire\Mechanisms\HandleComponents\HandleComponents;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Mechanisms\FrontendAssets\FrontendAssets;
use Magewirephp\Magewire\Mechanisms\ExtendBlade\ExtendBlade;
use Magewirephp\Magewire\Features\SupportTesting\Testable;
use Magewirephp\Magewire\Features\SupportTesting\DuskTestable;
use Magewirephp\Magewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;
use Magewirephp\Magewire\Features\SupportLazyLoading\SupportLazyLoading;
class MagewireManager
{
    protected MagewireServiceProvider $provider;
    /**
     * @throws NotFoundException
     */
    public function mount($name, $params = [], $key = null, AbstractBlock|null $block = null, Component|null $component = null): void
    {
        /** @var HandleComponentsFacade $handleComponentsMechanismFacade */
        $handleComponentsMechanismFacade = $this->magewireServiceProvider->getHandleComponentsMechanismFacade();
        $this->renderStack[$block->getNameInLayout()] = $handleComponentsMechanismFacade->mount($name, $params, $block, $component);
    }
    /**
     * @throws FileSystemException
     * @throws RuntimeException
     * @throws NotFoundException
     */
    public function update($snapshot, $diff, $calls, AbstractBlock|null $block = null): void
    {
        /** @var HandleComponentsFacade $handleComponentsMechanismFacade */
        $handleComponentsMechanismFacade = $this->magewireServiceProvider->getHandleComponentsMechanismFacade();
        $this->renderStack[$block->getNameInLayout()] = $handleComponentsMechanismFacade->update($snapshot, $diff, $calls, $block);
    }
    protected $queryParamsForTesting = [];
    protected $cookiesForTesting = [];
    protected $headersForTesting = [];
    function flushState()
    {
        trigger('flush-state');
    }
    public function __construct(private readonly MagewireServiceProvider $magewireServiceProvider, private readonly ComponentRegistry $componentRegistry)
    {
        //
    }
    public function new($name, $id = null)
    {
        return $this->componentRegistry->new($name, $id);
    }
    public function render(AbstractBlock $block, string $html)
    {
        $renderer = $this->renderStack[$block->getNameInLayout()];
        array_pop($this->renderStack);
        return $renderer($block, $html);
    }
    private array $renderStack = [];
}