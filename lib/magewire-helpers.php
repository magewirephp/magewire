<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\RuntimeException;
use Magewirephp\Magewire\Config as MagewireConfig;
use Psr\Log\LoggerInterface;
use ReflectionClass;

function str($string = null)
{
    if (is_null($string)) {
        return new class() {
            public function __call($method, $params)
            {
                return Str::$method(...$params);
            }
        };
    }

    return Str::of($string);
}

function invade($obj)
{
    return new class($obj) {
        public $obj;
        public $reflected;

        public function __construct($obj)
        {
            $this->obj       = $obj;
            $this->reflected = new ReflectionClass($obj);
        }

        public function &__get($name)
        {
            $getProperty = function &() use ($name) {
                return $this->{$name};
            };

            $getProperty = $getProperty->bindTo($this->obj, get_class($this->obj));

            return $getProperty();
        }

        public function __set($name, $value)
        {
            $setProperty = function () use ($name, &$value) {
                $this->{$name} = $value;
            };

            $setProperty = $setProperty->bindTo($this->obj, get_class($this->obj));

            $setProperty();
        }

        public function __call($name, $params)
        {
            $method = $this->reflected->getMethod($name);

            $method->setAccessible(true);

            return $method->invoke($this->obj, ...$params);
        }
    };
}

function once($fn)
{
    $hasRun = false;

    return function (...$params) use ($fn, &$hasRun) {
        if ($hasRun) {
            return;
        }

        $hasRun = true;

        return $fn(...$params);
    };
}

function of(...$params)
{
    return $params;
}

function revert(&$variable)
{
    $cache = $variable;

    return function () use (&$variable, $cache) {
        $variable = $cache;
    };
}

function wrap($subject)
{
    return ObjectManager::getInstance()->create(Wrapped::class, [
        'target' => $subject
    ]);
}

function pipe($subject)
{
    return ObjectManager::getInstance()->create(Pipe::class, [
        'target' => $subject
    ]);
}

function trigger($name, ...$params)
{
    return ObjectManager::getInstance()->get(EventBus::class)->trigger($name, ...$params);
}

function on($name, $callback)
{
    return ObjectManager::getInstance()->get(EventBus::class)->on($name, $callback);
}

function after($name, $callback)
{
    return ObjectManager::getInstance()->get(EventBus::class)->after($name, $callback);
}

function before($name, $callback)
{
    return ObjectManager::getInstance()->get(EventBus::class)->before($name, $callback);
}

function off($name, $callback)
{
    return ObjectManager::getInstance()->get(EventBus::class)->off($name, $callback);
}

function memoize($target)
{
    static $memo = new \WeakMap();

    return new class($target, $memo) {
        public function __construct(
            protected $target,
            protected &$memo,
        ) {
        }

        public function __call($method, $params)
        {
            $this->memo[$this->target] ??= [];

            $signature = $method . crc32(json_encode($params));

            return $this->memo[$this->target][$signature]
                ??= $this->target->$method(...$params);
        }
    };
}

function store($instance = null)
{
    if (!$instance) {
        $instance = ObjectManager::getInstance()->get(\Magewirephp\Magewire\Mechanisms\DataStore::class);
    }

    return new class($instance) {
        public function __construct(protected $instance)
        {
        }

        public function get($key, $default = null)
        {
            return ObjectManager::getInstance()->get(\Magewirephp\Magewire\Mechanisms\DataStore::class)
                ->get($this->instance, $key, $default);
        }

        public function set($key, $value): void
        {
            ObjectManager::getInstance()->get(\Magewirephp\Magewire\Mechanisms\DataStore::class)
                ->set($this->instance, $key, $value);
        }

        public function push($key, $value, $iKey = null): void
        {
            ObjectManager::getInstance()->get(\Magewirephp\Magewire\Mechanisms\DataStore::class)
                ->push($this->instance, $key, $value, $iKey);
        }

        public function find($key, $iKey = null, $default = null)
        {
            return ObjectManager::getInstance()->get(\Magewirephp\Magewire\Mechanisms\DataStore::class)
                ->find($this->instance, $key, $iKey, $default);
        }

        public function has($key, $iKey = null)
        {
            return ObjectManager::getInstance()->get(\Magewirephp\Magewire\Mechanisms\DataStore::class)
                ->has($this->instance, $key, $iKey);
        }

        public function unset($key, $iKey = null)
        {
            return ObjectManager::getInstance()->get(\Magewirephp\Magewire\Mechanisms\DataStore::class)
                ->unset($this->instance, $key, $iKey);
        }
    };
}

/**
 * Get the specified configuration value.
 *
 * @param string $key
 * @param mixed  $default
 *
 * @return mixed|array
 */
function config(string $key, mixed $default = null): mixed
{
    $magewireConfig = ObjectManager::getInstance()->get(MagewireConfig::class);

    try {
        return $magewireConfig->getValue($key) ?? $default;
    } catch (FileSystemException|RuntimeException $exception) {
        $logger = ObjectManager::getInstance()->get(LoggerInterface::class);
        $logger->critical($exception->getMessage(), ['exception' => $exception]);
    }

    return null;
}

/**
 * Get the available container instance.
 *
 * @param null  $abstract
 * @param array $arguments
 *
 * @return mixed
 * @throws NotFoundException
 */
function app($abstract = null, array $arguments = []): mixed
{
    if (is_string($abstract) && class_exists($abstract)) {
        return ObjectManager::getInstance()->get($abstract);
    }
    if (is_null($abstract)) {
        return ObjectManager::getInstance();
    }
    if (is_object($abstract)) {
        return ObjectManager::getInstance()->get($abstract);
    }

    $containers = ObjectManager::getInstance()->get(Containers::class);

    return $containers->item($abstract);
}

/**
 * Get an item from an array or object using "dot" notation.
 *
 * @param mixed                 $target
 * @param string|array|int|null $key
 * @param mixed                 $default
 *
 * @return mixed
 */
function data_get($target, $key, $default = null)
{
    if (is_null($key)) {
        return $target;
    }

    $key = is_array($key) ? $key : explode('.', $key);

    foreach ($key as $i => $segment) {
        unset($key[$i]);

        if (is_null($segment)) {
            return $target;
        }

        if ($segment === '*') {
            if (!is_iterable($target)) {
                return value($default);
            }

            $result = [];

            foreach ($target as $item) {
                $result[] = data_get($item, $key);
            }

            return in_array('*', $key) ? Arr::collapse($result) : $result;
        }

        $segment = match ($segment) {
            '\*' => '*',
            '\{first}' => '{first}',
            '{first}' => array_key_first(is_array($target) ? $target : collect($target)->all()),
            '\{last}' => '{last}',
            '{last}' => array_key_last(is_array($target) ? $target : collect($target)->all()),
            default => $segment,
        };

        if (Arr::accessible($target) && Arr::exists($target, $segment)) {
            $target = $target[$segment];
        } elseif (is_object($target) && isset($target->{$segment})) {
            $target = $target->{$segment};
        } else {
            return value($default);
        }
    }

    return $target;
}

function response($content = '', $status = 200, array $headers = [])
{
    $factory = app(ResponseFactory::class);

    if (func_num_args() === 0) {
        return $factory;
    }

    return $factory->make($content, $status, $headers);
}

function map(callable $callback, array $data): array
{
    $keys  = array_keys($data);
    $items = array_map($callback, $data, $keys);

    return array_combine($keys, $items);
}

function map_with_keys(callable $callback, array $data): array
{
    $result = [];

    foreach ($data as $key => $value) {
        $assoc = $callback($value, $key);

        foreach ($assoc as $mapKey => $mapValue) {
            $result[$mapKey] = $mapValue;
        }
    }

    return $result;
}
