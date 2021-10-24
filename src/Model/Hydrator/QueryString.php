<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magento\Framework\App\RequestInterface as AppRequestInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Helper\Functions as FunctionsHelper;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

/**
 * Class QueryString
 * @package Magewirephp\Magewire\Model\Hydrator
 */
class QueryString implements HydratorInterface
{
    /** @var AppRequestInterface $request */
    private $request;

    /** @var FunctionsHelper $functionsHelper */
    private $functionsHelper;

    /**
     * Property constructor.
     * @param AppRequestInterface $request
     * @param FunctionsHelper $functionsHelper
     */
    public function __construct(
        AppRequestInterface $request,
        FunctionsHelper $functionsHelper
    ) {
        $this->request = $request;
        $this->functionsHelper = $functionsHelper;
    }

    /**
     * @inheritdoc
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if ($request->isPreceding() && $component->hasQueryString()) {
            $properties = $this->getQueryParamsFromComponentProperties($component);
            $aliases = $this->filterQueryStringSetting($component, 'alias');

            foreach (array_keys($properties) as $property) {
                if ($value = $this->request->getParam($aliases[$property] ?? $property)) {
                    $component->{$property} = $value;
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        //
    }

    /**
     * Try to filter out any excepts "property:except-value" pairs.
     *
     * @param Component $component
     * @param string $setting
     * @return array|false
     */
    public function filterQueryStringSetting(Component $component, string $setting = 'except')
    {
        $excepts = $this->functionsHelper->mapWithKeys(static function ($value, $key) use ($setting) {
            return [$key => $value[$setting]];
        }, array_filter($component->getQueryString(), static function ($value) use ($setting) {
            return isset($value[$setting]);
        }, ARRAY_FILTER_USE_BOTH));

        return empty($excepts) ? false : $excepts;
    }

    /**
     * @param Component $component
     * @return array
     */
    public function getQueryParamsFromComponentProperties(Component $component): array
    {
        return $this->functionsHelper->mapWithKeys(function ($value, $key) use ($component, $properties) {
            $key = is_string($key) ? $key : $value;
            return [$key => $component->{$key}];
        }, $component->getQueryString());
    }
}
