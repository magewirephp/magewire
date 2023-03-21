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
        if (is_string($response->effects['html'])) {
            $response->effects['html'] = $this->wrapWithEndingMarker($response->renderWithRootAttribute($data, $component->canRender()), $id);
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
    public function registerChildren(string $nameInLayout, Component $component, string $html): void
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
            $views = $this->layoutRenderLifecycle->getViews();
            $position = array_search($nameInLayout, array_keys($views), true);

            if ($position !== false) {
                $children = array_slice($views, $position + 1, count($views), true);

                foreach ($children as $name => $tag) {
                    // When the parent for whatever reason hasn't rendered, the child won't
                    // be renderer also. In that case we don't want to let the component
                    // know if any children because they simply won't be not in the DOM.
                    if (is_string($tag)) {
                        $component->logRenderedChild($name, $tag);
                    }
                }
            }

            $this->getLayoutRenderLifecycle()->stop($nameInLayout);
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
