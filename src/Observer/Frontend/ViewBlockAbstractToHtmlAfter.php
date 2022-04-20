<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Observer\Frontend;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\LifecycleException;
use Magewirephp\Magewire\Exception\RootTagMissingFromViewException;
use Magewirephp\Magewire\Model\ResponseInterface;

class ViewBlockAbstractToHtmlAfter extends ViewBlockAbstract implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        /** @var Template $block */
        $block = $observer->getBlock();

        if ($block->hasData('magewire')) {
            try {
                $component = $this->getComponentHelper()->extractComponentFromBlock($block);
                $response  = $component->getResponse();
                $html      = $observer->getTransport()->getHtml();

                if ($response === null) {
                    throw new LifecycleException(__('Component response object not found'));
                }

                // Add previous rendered components as children of the current component.
                $this->registerChildren($block->getNameInLayout(), $component, $html);

                $observer->getTransport()->setHtml(
                    $this->renderToView($response, $component, $html)
                );
            } catch (Exception $exception) {
                $block = $this->transformToExceptionBlock($block, $exception);
                $observer->getTransport()->setHtml($block->toHtml());
            }
        }
    }

    /**
     * @param ResponseInterface $response
     * @param Component $component
     * @param string $html
     * @return string|null
     */
    public function renderToView(ResponseInterface $response, Component $component, string $html): ?string
    {
        // Bind intended HTML onto the Response.
        $response->effects['html'] = $html;

        /**
         * @lifecycle Runs on every subsequent request, before the component is dehydrated,
         * but after it's been rendered.
         */
        $component->dehydrate();

        // Dehydration lifecycle step.
        $this->getComponentManager()->dehydrate($component);

        $id = $response->fingerprint['id'];
        $data = ['id' => $response->fingerprint['id']];

        if ($response->getRequest()->isPreceding()) {
            $data['initial-data'] = $response->toArrayWithoutHtml();
        }

        if ($component->canRender() === false) {
            $response->effects['html'] = null;
        } elseif (is_string($response->effects['html'])) {
            $response->effects['html'] = $this->wrapWithEndingMarker($response->renderWithRootAttribute($data), $id);
        }

        return $response->effects['html'];
    }

    /**
     * Assign children to the component.
     *
     * @param string $nameInLayout
     * @param Component $component
     * @param string $html
     * @throws RootTagMissingFromViewException
     */
    public function registerChildren(string $nameInLayout, Component $component, string $html)
    {
        if ($this->getLayoutRenderLifecycle()->exists($nameInLayout) === false) {
            return;
        }

        // Try to grep first DOM element of the current rendered component.
        preg_match('/(<[a-zA-Z0-9\-]*)/', $html, $matches, PREG_OFFSET_CAPTURE);

        if (count($matches) === 0) {
            throw new RootTagMissingFromViewException();
        }

        $this->getLayoutRenderLifecycle()->setStartTag(trim($matches[0][0], '<'), $nameInLayout);

        if ($this->getLayoutRenderLifecycle()->canStop($nameInLayout)) {
            $children = $this->getLayoutRenderLifecycle()->getViewsWithFilter(
                function ($value, string $key) use ($nameInLayout) {
                    if ((is_string($value) && $key !== $nameInLayout)) {
                        return $value;
                    }

                    return false;
                }
            );

            $this->getLayoutRenderLifecycle()->stop($nameInLayout);

            foreach ($children as $name => $tag) {
                $component->logRenderedChild($name, $tag);
            }
        }
    }

    /**
     * Append an ending marker.
     *
     * @param string $html
     * @param string $id
     * @return string
     */
    protected function wrapWithEndingMarker(string $html, string $id): string
    {
        return $html . '<!-- Magewire Component wire-end:' . $id . ' -->';
    }
}
