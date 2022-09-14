<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Hydrator;

use Magento\Framework\App\RequestInterface as AppRequestInterface;
use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Helper\Functions as FunctionsHelper;
use Magewirephp\Magewire\Model\HydratorInterface;
use Magewirephp\Magewire\Model\RequestInterface;
use Magewirephp\Magewire\Model\ResponseInterface;

class QueryString implements HydratorInterface
{
    protected AppRequestInterface $request;
    protected FunctionsHelper $functionsHelper;
    protected UrlInterface $urlBuilder;

    private array $queryParams = [];

    /**
     * @param AppRequestInterface $request
     * @param FunctionsHelper $functionsHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        AppRequestInterface $request,
        FunctionsHelper $functionsHelper,
        UrlInterface $urlBuilder
    ) {
        $this->request = $request;
        $this->functionsHelper = $functionsHelper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritdoc
     */
    public function hydrate(Component $component, RequestInterface $request): void
    {
        if ($request->isPreceding() && $component->hasQueryString()) {
            $properties = $this->getQueryParamsFromComponentProperties($component);
            $aliases = $this->filterQueryStringSetting($component, 'as');

            foreach (array_keys($properties) as $property) {
                $fromQueryString = $this->request->getParam($aliases[$property] ?? $property);

                if ($fromQueryString === null) {
                    continue;
                }

                $decoded = is_array($fromQueryString)
                    ? json_decode(json_encode($fromQueryString), true)
                    : json_decode($fromQueryString, true);

                $component->{$property} = $decoded ?? $fromQueryString;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function dehydrate(Component $component, ResponseInterface $response): void
    {
        // WIP
        if (($referer = $this->request->getHeader('Referer')) && $this->request->getHeader('x-livewire')) {
//                $this->getPathFromReferer($referer, $component, $response);
        } else {
//            if (! $this->shouldSendPath($component)) return;
//
//                $queryParams = $this->mergeComponentPropertiesWithExistingQueryParamsFromOtherComponentsAndTheRequest($component);
//
//                $response->effects['path'] = url()->current().$this->stringifyQueryParams($queryParams);
            $url = $this->urlBuilder->getCurrentUrl();
            $url = $this->urlBuilder->getDirectUrl($url, ['foo' => 'bar']);
        }

        if ($response->getRequest()->isSubsequent()) {
            $wip = true;
        }
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
        return $this->functionsHelper->mapWithKeys(function ($value, $key) use ($component) {
            $key = is_string($key) ? $key : $value;
            return [$key => $component->{$key}];
        }, $component->getQueryString());
    }
}
