<?php

namespace Magewirephp\Magewire\Features\SupportLazyLoading;

use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Attributes\Lazy;
use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Features\SupportLifecycleHooks\SupportLifecycleHooks;
use Magewirephp\Magewire\Mechanisms\ResolveComponents\Management\LayoutManager;
use Magewirephp\Magewire\Support\Factory;
use function Magewirephp\Magewire\on;

class SupportLazyLoading extends \Livewire\Features\SupportLazyLoading\SupportLazyLoading
{
    public static function provide()
    {
        on('flush-state', function () {
            static::$disableWhileTesting = false;
        });
    }

    /**
     * Laravel routing does not exist in Magento; lazy is opted into through the
     * #[Lazy] attribute or the "magewire:component:lazy" layout argument instead.
     */
    public static function registerRouteMacro()
    {
        //
    }

    /**
     * Livewire ferries mount params through a dedicated container component whose
     * snapshot is base64 encoded. Magewire's fromSnapshot is block-bound, so that
     * container is unused here (see resurrectMountParams).
     */
    public function registerContainerComponent()
    {
        //
    }

    public function mount($params)
    {
        // If Magewire::withoutLazyLoading()...
        if (static::$disableWhileTesting) {
            return;
        }

        $arguments = $this->component->magewireResolver()->arguments()->forGroup('component');

        $hasLazyParam = $arguments->has('lazy');
        $lazyParam = $arguments->get('lazy', false);
        $lazyEnabled = $hasLazyParam && ! in_array($lazyParam, [false, 'false', '0', 0, '', null], true);

        $reflectionClass = new \ReflectionClass($this->component);
        $lazyAttribute = $reflectionClass->getAttributes(Lazy::class)[0] ?? null;

        // If `magewire:component:lazy="false"` disable lazy loading...
        if ($hasLazyParam && ! $lazyEnabled) {
            return;
        }
        // If no lazy loading is included at all...
        if (! $lazyEnabled && ! $lazyAttribute) {
            return;
        }

        $isolate = true;

        if ($lazyAttribute) {
            $isolate = $lazyAttribute->newInstance()->isolate;
        }

        $lazyMode = $lazyParam === 'on-load' ? 'on-load' : 'on-intersect';

        $this->component->skipMount();

        $this->storeSet('isLazyLoadMounting', true);
        $this->storeSet('isLazyIsolated', $isolate);

        $this->component->skipRender(
            $this->generatePlaceholderHtml($params, $lazyMode)
        );
    }

    public function generatePlaceholderHtml($params, $lazyMode = 'on-intersect')
    {
        $html = $this->getPlaceholderView($this->component, $params);

        // No params are ferried client-side: on the lazy XHR the block is rebuilt from its
        // layout handles, so the mount arguments are re-derived server-side (see call()).
        //
        // The trigger is a CSP-safe Alpine component (magewireLazyLoad) rather than an
        // inline "$wire.__lazyLoad()" expression: Hyvä's CSP-friendly Alpine build cannot
        // evaluate a method call in an attribute, but a real method inside an Alpine.data
        // component calls $wire directly. Mode travels as a plain data attribute.
        return Utils::insertAttributesIntoHtmlRoot($html, [
            'x-data' => 'magewireLazyLoad',
            'data-magewire-lazy-mode' => $lazyMode,
        ]);
    }

    /**
     * Resolves the placeholder markup. A component's placeholder() method may return
     * either a Magento template id (Vendor_Module::path/to/template.phtml), rendered
     * here as a standalone block, or a raw HTML string. Markup must have a single
     * root element so the lazy trigger and wire:id can be attached to it.
     */
    protected function getPlaceholderView($component, $params)
    {
        $result = method_exists($component, 'placeholder') ? $component->placeholder($params) : null;

        if (! is_string($result) || trim($result) === '') {
            return '<div></div>';
        }

        if (preg_match('/^[A-Za-z0-9_]+::.+\.phtml$/', $result)) {
            /** @var LayoutManager $layoutManager */
            $layoutManager = Factory::get(LayoutManager::class);

            return $layoutManager->singleton()
                ->createBlock(Template::class)
                ->setTemplate($result)
                ->addData($params)
                ->toHtml();
        }

        return $result;
    }

    public function call($method, $params, $returnEarly)
    {
        if ($method !== '__lazyLoad') {
            return;
        }

        // The block was rebuilt from its layout handles for this XHR, so its mount
        // arguments are available again — re-derive them rather than trusting the client.
        $mountParams = $this->component->magewireResolver()->arguments()->forMount()->all();

        $this->callMountLifecycleMethod($mountParams);

        $returnEarly();
    }

    public function callMountLifecycleMethod($params)
    {
        $hook = new SupportLifecycleHooks();

        $hook->setComponent($this->component);
        $hook->mount($params);
    }

    /**
     * Params are re-derived from layout in call(); the client never ferries a snapshot.
     */
    public function resurrectMountParams($encoded)
    {
        return [];
    }
}