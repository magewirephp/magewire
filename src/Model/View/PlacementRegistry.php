<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use BadMethodCallException;
use Magewirephp\Magewire\Support\Factory;
use Stringable;

/**
 * @method PlacementBuffer script(string $name, string|Stringable $content)
 */
class PlacementRegistry
{
    private const MARKER_PREFIX = 'MAGEWIRE_PLACEMENT';

    /** @var array<string, PlacementBuffer> */
    private array $buffers = [];

    /** @var array<string, array{scope: string, name: string}> */
    private array $placeholders = [];

    /**
     * @param array<string, PlacementProxyInterface> $proxies
     */
    public function __construct(
        private array $proxies = []
    ) {
    }

    public function add(string $scope, string $name, string|Stringable $content): PlacementBuffer
    {
        if (! $content instanceof PlacementEntry) {
            $content = Factory::create(PlacementEntry::class, [
                'content' => (string) $content,
                'scope' => $scope,
                'name' => $name
            ]);
        }

        return $this->buffer($scope, $name)->add($content);
    }

    public function has(string $scope, string $name): bool
    {
        $key = $this->key($scope, $name);

        return array_key_exists($key, $this->buffers) && ! $this->buffers[$key]->isEmpty();
    }

    public function render(string $scope, string $name): string
    {
        return $this->placeholder($scope, $name);
    }

    public function resolve(string $html): string
    {
        foreach ($this->placeholders as $placeholder => $target) {
            $html = str_replace($placeholder, (string) $this->buffer($target['scope'], $target['name']), $html);
        }

        return $html;
    }

    private function buffer(string $scope, string $name): PlacementBuffer
    {
        $key = $this->key($scope, $name);

        return $this->buffers[$key] ??= Factory::create(PlacementBuffer::class, [
            'scope' => $scope,
            'name' => $name
        ]);
    }

    private function placeholder(string $scope, string $name): string
    {
        $placeholder = sprintf('<!-- %s:%s -->', self::MARKER_PREFIX, hash('xxh3', $this->key($scope, $name)));
        $this->placeholders[$placeholder] = ['scope' => $scope, 'name' => $name];

        return $placeholder;
    }

    private function key(string $scope, string $name): string
    {
        return $scope . ':' . $name;
    }

    /**
     * @throws BadMethodCallException
     */
    public function __call(string $proxy, array $arguments = []): mixed
    {
        if (array_key_exists($proxy, $this->proxies)) {
            return ($this->proxies[$proxy])($this, ...$arguments);
        }

        throw new BadMethodCallException(sprintf('Placement proxy "%s" was called but does not exist.', $proxy));
    }
}
