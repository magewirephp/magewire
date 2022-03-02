<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magento\Framework\Message\ManagerInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class FlashMessage implements HydratorInterface
{
    protected ManagerInterface $messageManager;

    /**
     * FlashMessage constructor.
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        ManagerInterface $messageManager
    ) {
        $this->messageManager = $messageManager;
    }

    /**
     * @inheritdoc
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        //
    }

    /**
     * @inheritdoc
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        if ($component->hasFlashMessages()) {
            $messages = array_map(static function ($message) {
                return ['text' => $message->getMessage()->render(), 'type' => $message->getType()];
            }, $component->getFlashMessages());

            if (isset($response->effects['redirect']) || $response->getRequest()->isPreceding()) {
                $this->messageManager->addMessages(array_map(function ($message) {
                    return $this->messageManager->createMessage($message['type'])->setText($message['text']);
                }, $messages));

                return;
            }

            $component->dispatchBrowserEvent('messages-loaded', ['messages' => $messages]);
        }
    }
}
