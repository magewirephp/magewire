<?php

namespace Magewirephp\Magewire\Features\SupportLazyLoading;

use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Attributes\Lazy;
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
        $mode = null;

        if ($lazyAttribute) {
            $attribute = $lazyAttribute->newInstance();

            $isolate = $attribute->isolate;
            $mode = $attribute->mode;
        }

        // A mode named on the layout argument outranks the one on the attribute, so a
        // single component class can be lazied differently per placement.
        if (in_array($lazyParam, ['on-load', 'on-intersect'], true)) {
            $mode = $lazyParam;
        }

        $this->component->skipMount();

        $this->storeSet('isLazyLoadMounting', true);
        $this->storeSet('isLazyIsolated', $isolate);
        $this->storeSet('lazyMode', $mode === 'on-load' ? 'on-load' : 'on-intersect');

        $this->component->skipRender($this->generatePlaceholderHtml($params));
    }

    /**
     * The trigger mode travels through the snapshot memo instead of a root attribute.
     * Livewire injects "x-init"/"x-intersect" into the placeholder root, which Hyvä's
     * CSP-friendly Alpine build cannot evaluate ($wire method calls in an attribute
     * expression), and any injected "x-data" would silently overrule an x-data a
     * developer already put on their own placeholder root.
     */
    public function dehydrate($context)
    {
        if ($this->storeGet('isLazyLoadMounting') === true) {
            $context->addMemo('lazyLoaded', false);
            $context->addMemo('lazyIsolated', $this->storeGet('isLazyIsolated'));
            $context->addMemo('lazyMode', $this->storeGet('lazyMode', 'on-intersect'));
        } elseif ($this->storeGet('isLazyLoadHydrating') === true) {
            $context->addMemo('lazyLoaded', true);
        }
    }

    /**
     * Placeholder markup is handed back untouched; only "wire:id" gets stamped onto its
     * root later on. No params are ferried client-side either: on the lazy XHR the block
     * is rebuilt from its layout handles, so mount arguments are re-derived server-side
     * (see call()).
     */
    public function generatePlaceholderHtml($params, $isDeferred = false)
    {
        return $this->getPlaceholderView($this->component, $params);
    }

    /**
     * Resolves the placeholder markup. A component's placeholder() method may return
     * either a Magento template id (Vendor_Module::path/to/template.phtml), rendered
     * here as a standalone block, or a raw HTML string. Markup must have a single
     * root element so wire:id can be attached to it.
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

            return $layoutManager->singleton()->createBlock(Template::class)->setTemplate($result)->addData($params)->toHtml();
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
