<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Mechanisms\HandleComponents;

use Exception;
use InvalidArgumentException;
use function Magewirephp\Magewire\config;
use function Magewirephp\Magewire\on;
use function Magewirephp\Magewire\store;
use function Magewirephp\Magewire\trigger;
use function Magewirephp\Magewire\wrap;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Mechanisms\HandleComponents\CorruptComponentPayloadException;
use Magewirephp\Magewire\Drawer\Utils;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Exceptions\MethodNotFoundException;
use ReflectionUnionType;
use Magewirephp\Magewire\Mechanisms\Mechanism;
use Magewirephp\Magewire\Mechanisms\HandleComponents\Synthesizers\Synth;
use Magewirephp\Magewire\Exceptions\PublicPropertyNotFoundException;
use Illuminate\Support\Facades\View;
class HandleComponents extends Mechanism
{
    protected $propertySynthesizers = [Synthesizers\CarbonSynth::class, Synthesizers\CollectionSynth::class, Synthesizers\StringableSynth::class, Synthesizers\EnumSynth::class, Synthesizers\StdClassSynth::class, Synthesizers\ArraySynth::class, Synthesizers\IntSynth::class, Synthesizers\FloatSynth::class];
    public static $renderStack = [];
    public static $componentStack = [];
    public function registerPropertySynthesizer($synth)
    {
        foreach ((array) $synth as $class) {
            array_unshift($this->propertySynthesizers, $class);
        }
    }
    public function mount($name, $params = [], $key = null, ?AbstractBlock $block = null, ?Component $component = null)
    {
        $parent = last(self::$componentStack);
        if ($html = $this->shortCircuitMount($name, $params, $key, $parent)) {
            $shortCircuitHtml = $html;
            return function (AbstractBlock $block, string $html) use ($shortCircuitHtml) {
                return [$block, $shortCircuitHtml];
            };
        }
        if ($block === null) {
            throw new InvalidArgumentException('Argument $block can not be null.');
        }
        $context = $this->componentContextFactory->create(['block' => $block, 'component' => $component, 'mounting' => true]);
        if (config('app.debug')) {
            $start = microtime(true);
        }
        $finish = trigger('mount', $component, $params, $key, $parent);
        if (config('app.debug')) {
            trigger('profile', 'mount', $component->getId(), [$start ?? 0, microtime(true)]);
        }
        $this->pushOntoComponentStack($component);
        $start = config('app.debug') ? microtime(true) : null;
        /*
         * This function is divided based on the original design due to the presence of both pre and post-render
         * mechanisms within Magento. It serves as a form of middleware where the Magewire mechanism is embedded
         * around it, instead of being directly responsible for rendering the component. The standard Block render
         * mechanism within Magewire remains responsible for the actual rendering process and does return HTML.
         */
        return function (AbstractBlock $block, string $html) use ($component, $context, $start, $finish) {
            if (config('app.debug')) {
                $start = microtime(true);
            }
            $html = $this->render($component, '<div></div>', $html);
            if (config('app.debug')) {
                trigger('profile', 'render', $component->getId(), [$start, microtime(true)]);
            }
            if (config('app.debug')) {
                $start = microtime(true);
            }
            trigger('dehydrate', $component, $context);
            $snapshot = $this->snapshot($component, $context);
            if (config('app.debug')) {
                trigger('profile', 'dehydrate', $component->getId(), [$start, microtime(true)]);
            }
            trigger('destroy', $component, $context);
            $html = Utils::insertAttributesIntoHtmlRoot($html, ['wire:snapshot' => $snapshot, 'wire:effects' => $context->getEffects()->toArray()]);
            $this->popOffComponentStack();
            return [$block, $finish($html, $snapshot)];
        };
    }
    protected function shortCircuitMount($name, $params, $key, $parent)
    {
        $newHtml = null;
        trigger('pre-mount', $name, $params, $key, $parent, function ($html) use (&$newHtml) {
            $newHtml = $html;
        });
        return $newHtml;
    }
    /**
     * @throws FileSystemException
     * @throws ComponentNotFoundException
     * @throws MethodNotFoundException
     * @throws RuntimeException
     */
    public function update($snapshot, $updates, $calls, ?AbstractBlock $block = null)
    {
        if ($block === null) {
            throw new InvalidArgumentException('Argument $block can not be of type null.');
        }
        $data = $snapshot['data'];
        $memo = $snapshot['memo'];
        if (config('app.debug')) {
            $start = microtime(true);
        }
        [$component, $context] = $this->fromSnapshot($snapshot, $block);
        $this->pushOntoComponentStack($component);
        trigger('hydrate', $component, $memo, $context);
        $this->updateProperties($component, $updates, $data, $context);
        if (config('app.debug')) {
            trigger('profile', 'hydrate', $component->getId(), [$start ?? 0, microtime(true)]);
        }
        $this->callMethods($component, $calls, $context);
        /*
         * This function is divided based on the original design due to the presence of both pre and post-render
         * mechanisms within Magento. It serves as a form of middleware where the Magewire mechanism is embedded
         * around it, instead of being directly responsible for rendering the component. The standard Block render
         * mechanism within Magewire remains responsible for the actual rendering process.
         */
        return function (AbstractBlock $block, string $html) use ($snapshot, $context, $component) {
            if (config('app.debug')) {
                $start = microtime(true);
            }
            if ($html = $this->render($component, '', $html)) {
                $context->addEffect('html', $html);
                if (config('app.debug')) {
                    trigger('profile', 'render', $component->getId(), [$start ?? 0, microtime(true)]);
                }
            }
            if (config('app.debug')) {
                $start = microtime(true);
            }
            trigger('dehydrate', $component, $context);
            $snapshot = $this->snapshot($component, $context);
            if (config('app.debug')) {
                trigger('profile', 'dehydrate', $component->getId(), [$start ?? 0, microtime(true)]);
            }
            trigger('destroy', $component, $context);
            $this->popOffComponentStack();
            return [$snapshot, $context->effects];
        };
    }
    /**
     * @throws ComponentNotFoundException
     * @throws FileSystemException
     * @throws RuntimeException
     * @throws Exception
     */
    public function fromSnapshot($snapshot, ?AbstractBlock $block = null)
    {
        if (!$block instanceof AbstractBlock) {
            throw new Exception(sprintf('Invalid block type: %s', is_object($block) ? get_class($block) : 'Unknown'));
        } elseif (!$block->getData('magewire') instanceof Component) {
            throw new ComponentNotFoundException(sprintf('Unable to find component: [%s]', $block->getNameInLayout()));
        }
        $component = $block->getData('magewire');
        $this->checksum->verify($snapshot);
        trigger('snapshot-verified', $snapshot, $component);
        /** @var Component $component */
        $component = $block->getData('magewire');
        $context = $this->componentContextFactory->create(['component' => $component, 'block' => $block]);
        $this->hydrateProperties($context->getComponent(), $snapshot['data'], $context);
        return [$component, $context];
    }
    public function snapshot($component, $context = null)
    {
        $context ??= $this->componentContextFactory->create(['component' => $component]);
        $data = $this->dehydrateProperties($component, $context);
        $snapshot = ['data' => $data, 'memo' => ['id' => $component->getId(), 'name' => $component->getName(), ...$context->getMemo()->toArray()]];
        $snapshot['checksum'] = $this->checksum->generate($snapshot);
        return $snapshot;
    }
    protected function dehydrateProperties($component, $context)
    {
        $data = Utils::getPublicPropertiesDefinedOnSubclass($component);
        foreach ($data as $key => $value) {
            $data[$key] = $this->dehydrate($value, $context, $key);
        }
        return $data;
    }
    protected function dehydrate($target, $context, $path)
    {
        if (Utils::isAPrimitive($target)) {
            return $target;
        }
        $synth = $this->propertySynth($target, $context, $path);
        [$data, $meta] = $synth->dehydrate($target, function ($name, $child) use ($context, $path) {
            return $this->dehydrate($child, $context, "{$path}.{$name}");
        });
        $meta['s'] = $synth::getKey();
        return [$data, $meta];
    }
    protected function hydrateProperties($component, $data, $context)
    {
        foreach ($data as $key => $value) {
            if (!property_exists($component, $key)) {
                continue;
            }
            $child = $this->hydrate($value, $context, $key);
            // Typed properties shouldn't be set back to "null". It will throw an error...
            if ((new \ReflectionProperty($component, $key))->getType() && is_null($child)) {
                continue;
            }
            $component->{$key} = $child;
        }
    }
    protected function hydrate($valueOrTuple, $context, $path)
    {
        if (!Utils::isSyntheticTuple($value = $tuple = $valueOrTuple)) {
            return $value;
        }
        [$value, $meta] = $tuple;
        if ($this->isRemoval($value) && str($path)->contains('.')) {
            return $value;
        }
        $synth = $this->propertySynth($meta['s'], $context, $path);
        return $synth->hydrate($value, $meta, function ($name, $child) use ($context, $path) {
            return $this->hydrate($child, $context, "{$path}.{$name}");
        });
    }
    protected function render($component, $default = null, ?string $html = null)
    {
        $replace = store($component)->get('skipRender', false);
        if ($replace) {
            $replace = value(is_string($html) ? $html : $default);
            if (!$replace) {
                return '';
            }
            return Utils::insertAttributesIntoHtmlRoot($html, ['wire:id' => $component->getId()]);
        }
        [$block, $properties] = $this->getView($component);
        return $this->trackInRenderStack($component, function () use ($component, $block, $properties, $html) {
            $finish = trigger('render', $component, $block, $properties);
            $html = Utils::insertAttributesIntoHtmlRoot($html, ['wire:id' => $component->getId()]);
            return $finish($html, function ($newHtml) use (&$html) {
                $html = $newHtml;
            }, $component->block());
        });
    }
    protected function getView($component)
    {
        //        WIP
        //        if (method_exists($component, 'render')) {
        //            $block = wrap($component)->render();
        //
        //            if ($block instanceof AbstractBlock) {
        //                $block->setData($component->getBlock()->getData());
        //                $block->setNameInLayout($component->getBlock()->getNameInLayout());
        //
        //                $component->getResolver()->construct($block);
        //            }
        //        }
        return [$component->block(), Utils::getPublicPropertiesDefinedOnSubclass($component)];
    }
    protected function trackInRenderStack($component, $callback)
    {
        static::$renderStack[] = $component;
        return tap($callback(), function () {
            array_pop(static::$renderStack);
        });
    }
    protected function updateProperties($component, $updates, $data, $context)
    {
        $finishes = [];
        foreach ($updates as $path => $value) {
            $value = $this->hydrateForUpdate($data, $path, $value, $context);
            // We only want to run "updated" hooks after all properties have
            // been updated so that each individual hook has the ability
            // to overwrite the updated states of other properties...
            $finishes[] = $this->updateProperty($component, $path, $value, $context);
        }
        foreach ($finishes as $finish) {
            $finish();
        }
    }
    public function updateProperty($component, $path, $value, $context)
    {
        $segments = explode('.', $path);
        $property = array_shift($segments);
        $finish = trigger('update', $component, $path, $value);
        // Ensure that it's a public property, not on the base class first...
        if (!in_array($property, array_keys(Utils::getPublicPropertiesDefinedOnSubclass($component)))) {
            throw new PublicPropertyNotFoundException($property, $component->getName());
        }
        // If this isn't a "deep" set, set it directly, otherwise we have to
        // recursively get up and set down the value through the synths...
        if (empty($segments)) {
            $this->setComponentPropertyAwareOfTypes($component, $property, $value);
        } else {
            $propertyValue = $component->{$property};
            $this->setComponentPropertyAwareOfTypes($component, $property, $this->recursivelySetValue($property, $propertyValue, $value, $segments, 0, $context));
        }
        return $finish;
    }
    protected function hydrateForUpdate($raw, $path, $value, $context)
    {
        $meta = $this->getMetaForPath($raw, $path);
        // If we have meta data already for this property, let's use that to get a synth...
        if ($meta) {
            return $this->hydrate([$value, $meta], $context, $path);
        }
        // If we don't, let's check to see if it's a typed property and fetch the synth that way...
        $parent = str($path)->contains('.') ? data_get($context->component, str($path)->beforeLast('.')->toString()) : $context->component;
        $childKey = str($path)->afterLast('.')->toString();
        if ($parent && is_object($parent) && property_exists($parent, $childKey) && Utils::propertyIsTyped($parent, $childKey)) {
            $type = Utils::getProperty($parent, $childKey)->getType();
            $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
            foreach ($types as $type) {
                $synth = $this->getSynthesizerByType($type->getName(), $context, $path);
                if ($synth) {
                    return $synth->hydrateFromType($type->getName(), $value);
                }
            }
        }
        return $value;
    }
    protected function getMetaForPath($raw, $path)
    {
        $segments = explode('.', $path);
        $first = array_shift($segments);
        [$data, $meta] = Utils::isSyntheticTuple($raw) ? $raw : [$raw, null];
        if ($path !== '') {
            $value = $data[$first] ?? null;
            return $this->getMetaForPath($value, implode('.', $segments));
        }
        return $meta;
    }
    protected function recursivelySetValue($baseProperty, $target, $leafValue, $segments, $index = 0, $context = null)
    {
        $isLastSegment = count($segments) === $index + 1;
        $property = $segments[$index];
        $path = implode('.', array_slice($segments, 0, $index + 1));
        $synth = $this->propertySynth($target, $context, $path);
        if ($isLastSegment) {
            $toSet = $leafValue;
        } else {
            $propertyTarget = $synth->get($target, $property);
            // "$path" is a dot-notated key. This means we may need to drill
            // down and set a value on a deeply nested object. That object
            // may not exist, so let's find the first one that does...
            // Here's we've determined we're trying to set a deeply nested
            // value on an object/array that doesn't exist, so we need
            // to build up that non-existant nesting structure first.
            if ($propertyTarget === null) {
                $propertyTarget = [];
            }
            $toSet = $this->recursivelySetValue($baseProperty, $propertyTarget, $leafValue, $segments, $index + 1, $context);
        }
        $method = $this->isRemoval($leafValue) && $isLastSegment ? 'unset' : 'set';
        $pathThusFar = collect([$baseProperty, ...$segments])->slice(0, $index + 1)->join('.');
        $fullPath = collect([$baseProperty, ...$segments])->join('.');
        $synth->{$method}($target, $property, $toSet, $pathThusFar, $fullPath);
        return $target;
    }
    protected function setComponentPropertyAwareOfTypes($component, $property, $value)
    {
        try {
            $component->{$property} = $value;
        } catch (\TypeError $e) {
            // If an "int" is being set to empty string, unset the property (making it null).
            // This is common in the case of `wire:model`ing an int to a text field...
            // If a value is being set to "null", do the same...
            if ($value === '' || $value === null) {
                unset($component->{$property});
            } else {
                throw $e;
            }
        }
    }
    /**
     * @throws MethodNotFoundException
     */
    protected function callMethods($root, $calls, $context)
    {
        $returns = [];
        foreach ($calls as $idx => $call) {
            $method = $call['method'];
            $params = $call['params'];
            $earlyReturnCalled = false;
            $earlyReturn = null;
            $returnEarly = function ($return = null) use (&$earlyReturnCalled, &$earlyReturn) {
                $earlyReturnCalled = true;
                $earlyReturn = $return;
            };
            $finish = trigger('call', $root, $method, $params, $context, $returnEarly);
            if ($earlyReturnCalled) {
                $returns[] = $finish($earlyReturn);
                continue;
            }
            $methods = Utils::getPublicMethodsDefinedBySubClass($root);
            // Also remove "render" from the list...
            $methods = array_values(array_diff($methods, ['render']));
            // @todo: put this in a better place:
            $methods[] = '__dispatch';
            if (!in_array($method, $methods)) {
                throw new MethodNotFoundException($method);
            }
            if (config('app.debug')) {
                $start = microtime(true);
            }
            $return = wrap($root)->{$method}(...$params);
            if (config('app.debug')) {
                trigger('profile', 'call' . $idx, $root->getId(), [$start, microtime(true)]);
            }
            $returns[] = $finish($return);
        }
        $context->addEffect('returns', $returns);
    }
    public function findSynth($keyOrTarget, $component): ?Synth
    {
        $context = new ComponentContext($component);
        try {
            return $this->propertySynth($keyOrTarget, $context, null);
        } catch (\Exception $e) {
            return null;
        }
    }
    public function propertySynth($keyOrTarget, $context, $path): Synth
    {
        return is_string($keyOrTarget) ? $this->getSynthesizerByKey($keyOrTarget, $context, $path) : $this->getSynthesizerByTarget($keyOrTarget, $context, $path);
    }
    /**
     * @throws Exception
     */
    protected function getSynthesizerByKey($key, $context, $path)
    {
        foreach ($this->propertySynthesizers as $synth) {
            if ($synth::getKey() === $key) {
                return ObjectManager::getInstance()->create($synth, ['context' => $context, 'path' => $path]);
            }
        }
        throw new Exception('No synthesizer found for key: "' . $key . '"');
    }
    /**
     * @throws Exception
     */
    protected function getSynthesizerByTarget($target, $context, $path)
    {
        foreach ($this->propertySynthesizers as $synth) {
            if ($synth::match($target)) {
                return ObjectManager::getInstance()->create($synth, ['context' => $context, 'path' => $path]);
            }
        }
        throw new Exception('Property type not supported in Magewire for property: [' . json_encode($target) . ']');
    }
    protected function getSynthesizerByType($type, $context, $path)
    {
        foreach ($this->propertySynthesizers as $synth) {
            if ($synth::matchByType($type)) {
                return new $synth($context, $path);
            }
        }
        return null;
    }
    protected function pushOntoComponentStack($component)
    {
        array_push($this::$componentStack, $component);
    }
    protected function popOffComponentStack()
    {
        array_pop($this::$componentStack);
    }
    protected function isRemoval($value)
    {
        return $value === '__rm__';
    }
    public function __construct(private readonly ComponentContextFactory $componentContextFactory, private readonly Checksum $checksum, protected array $synthesizers = [])
    {
        // Improve modularity by injecting property synthesizers via dependency injection.
        $this->propertySynthesizers = $synthesizers;
    }
    public static function provide()
    {
        on('pre-mount', function ($target, $view) {
            return function ($html) use ($view, $target) {
                if (method_exists($target, 'render')) {
                    return $target->render();
                }
                return $html;
            };
        });
    }
    public function boot()
    {
        on('snapshot-verified', function (array $snapshot, Component $component) {
            if ($component->getId() !== $snapshot['memo']['id'] || $component->getName() !== $snapshot['memo']['name']) {
                throw new CorruptComponentPayloadException();
            }
        });
    }
}