<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Mechanisms\HandleRequests;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magento\Controller\MagewireUpdateResult;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Controller\MagewireUpdateRouteFrontend;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\MagewireManager;
use function Magewirephp\Magewire\store;
use function Magewirephp\Magewire\trigger;
use Illuminate\Support\Facades\Route;
use Magewirephp\Magewire\Features\SupportScriptsAndAssets\SupportScriptsAndAssets;
use Magewirephp\Magewire\Mechanisms\Mechanism;
class HandleRequests extends Mechanism
{
    protected $updateRoute;
    public function boot()
    {
        // Overwrite.
    }
    function getUpdateUri()
    {
        return (string) str(route($this->updateRoute->getName(), [], false))->start('/');
    }
    function skipRequestPayloadTamperingMiddleware()
    {
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::skipWhen(function () {
            return $this->isLivewireRequest();
        });
        \Illuminate\Foundation\Http\Middleware\TrimStrings::skipWhen(function () {
            return $this->isLivewireRequest();
        });
    }
    function setUpdateRoute($callback)
    {
        $route = $callback([self::class, 'handleUpdate']);
        // Append `livewire.update` to the existing name, if any.
        if (!str($route->getName())->endsWith('livewire.update')) {
            $route->name('livewire.update');
        }
        $this->updateRoute = $route;
    }
    public function isLivewireRequest()
    {
        return $this->isMagewireRequest();
    }
    function isLivewireRoute()
    {
        // @todo: Rename this back to `isLivewireRequest` once the need for it in tests has been fixed.
        $route = request()->route();
        if (!$route) {
            return false;
        }
        /*
         * Check to see if route name ends with `livewire.update`, as if
         * a custom update route is used and they add a name, then when
         * we call `->name('livewire.update')` on the route it will
         * suffix the existing name with `livewire.update`.
         */
        return $route->named('*livewire.update');
    }
    /**
     * @return MagewireUpdateResult|mixed|null
     * @throws ComponentNotFoundException
     * @throws NoSuchEntityException
     */
    public function handleUpdate()
    {
        /** @var ComponentRequestContext[] $updates */
        $requestPayload = $this->request->getParam('components');

        $finish = trigger('request', $requestPayload);
        $requestPayload = $finish($requestPayload);
        $componentResponses = [];
        foreach ($requestPayload as $componentPayload) {
            $reconstruct = trigger('magewire:reconstruct', $componentPayload);
            $block = $reconstruct();
            $component = $block->getData('magewire');
            if (!$component instanceof Component) {
                throw new ComponentNotFoundException('Something went wrong during block reconstruction');
            }
            /*
             * Marks the component to indicate that it is being updated, distinguishing it from a preceding page load
             * or refresh. This notification is crucial for informing other systems about the context of the operation.
             */
            store($component)->set('magewire:update', $componentPayload);
            /*
             * When the 'toHtml' method is invoked on any block with the 'magewire' argument, it initiates the
             * rendering lifecycle. During initial (in other words: preceding) page renders, this process is
             * automatically managed by the framework. However, on subsequent requests, it becomes necessary to
             * manually trigger this lifecycle for the targeted block.
             */
            [$snapshot, $effects] = $this->magewireManager->render($block, $block->toHtml());
            $componentResponses[] = ['effects' => $effects->toArray(), 'snapshot' => $this->serializer->serialize($snapshot)];
        }
        $responsePayload = ['components' => $componentResponses ?? [], 'assets' => []];
        $finish = trigger('response', $responsePayload);
        return $finish($responsePayload);
    }
    public function __construct(private readonly Http $request, private readonly MagewireManager $magewireManager, private readonly SerializerInterface $serializer)
    {
        //
    }
    public function isMagewireRequest()
    {
        return $this->request->getParam(MagewireUpdateRouteFrontend::PARAM_IS_SUBSEQUENT) ?? false;
    }
}
