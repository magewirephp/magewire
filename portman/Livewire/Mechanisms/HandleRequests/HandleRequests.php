<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleRequests;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magewirephp\Magento\Controller\MagewireUpdateResult;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Controller\MagewireUpdateRouteFrontend;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\LivewireManager;
use function Magewirephp\Magewire\store;
use function Magewirephp\Magewire\trigger;

class HandleRequests extends \Livewire\Mechanisms\HandleRequests\HandleRequests
{
    public function __construct(
        private readonly Http $request,
        private readonly LivewireManager $magewireManager,
        private readonly SerializerInterface $serializer
    ) {
        //
    }

    public function boot()
    {
        // Overwrite.
    }

    public function isLivewireRequest()
    {
        return $this->isMagewireRequest();
    }

    public function isMagewireRequest()
    {
        return $this->request->getParam(MagewireUpdateRouteFrontend::PARAM_IS_SUBSEQUENT) ?? false;
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

            if (! $component instanceof Component) {
                throw new ComponentNotFoundException(
                    'Something went wrong during block reconstruction'
                );
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

            $componentResponses[] = [
                'effects' => $effects->toArray(),
                'snapshot' => $this->serializer->serialize($snapshot),
            ];
        }

        $responsePayload = [
            'components' => $componentResponses ?? [],
            'assets' => [] // should be: 'assets' => SupportScriptsAndAssets::getAssets() ( = TODO)
        ];

        $finish = trigger('response', $responsePayload);

        return $finish($responsePayload);
    }
}
