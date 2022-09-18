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

class BrowserHistory implements HydratorInterface
{
    protected AppRequestInterface $request;
    protected FunctionsHelper $functionsHelper;
    protected UrlInterface $urlBuilder;

    private ?array $mergedQueryParamsFromDehydratedComponents;

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
        if ($response->getRequest()->isSubsequent()) {
            if (! $referer = $this->request->getHeader('Referer')) {
                return;
            }

            $this->getPathFromReferer($referer, $component, $response);
        }

        if ($response->getRequest()->isPreceding()) {
            if (($referer = $this->request->getHeader('Referer')) && $this->request->getHeader('x-livewire')) {
                $this->getPathFromReferer($referer, $component, $response);
            } else {
                if (! $this->shouldSendPath($component)) {
                    return;
                }

                $queryParams = $this->mergeComponentPropertiesWithExistingQueryParamsFromOtherComponentsAndTheRequest($component);
                $response->effects['path'] = $this->urlBuilder->getCurrentUrl() . $this->stringifyQueryParams($queryParams);
            }
        }
    }

    protected function mergeComponentPropertiesWithExistingQueryParamsFromOtherComponentsAndTheRequest($component): array
    {
        if (! $this->mergedQueryParamsFromDehydratedComponents) {
            $this->mergedQueryParamsFromDehydratedComponents = $this->getExistingQueryParams();
        }

        $excepts = $this->getExceptsFromComponent($component);

        $this->mergedQueryParamsFromDehydratedComponents = array_map(static function ($property) {
            return is_bool($property) ? json_encode($property) : $property;
        }, array_filter(array_merge(
            $this->request->getParams(),
            $this->mergedQueryParamsFromDehydratedComponents,
            $this->getQueryParamsFromComponentProperties($component)
        ), static function ($value, $key) use ($excepts) {
            return isset($excepts[$key]) && $excepts[$key] === $value;
        }, ARRAY_FILTER_USE_BOTH));

        return $this->mergedQueryParamsFromDehydratedComponents;
    }

    protected function getExceptsFromComponent(Component $component): array
    {
        $queryStringParams = array_filter($component->getQueryString(), static function ($value) {
            return isset($value['except']);
        });

        return array_map(static function ($value, $key) {
            $key = $value['as'] ?? $key;
            return [$key => $value['except']];
        }, $queryStringParams, array_keys($queryStringParams));
    }

    protected function getRouteFromReferer($referer)
    {
        try {
            // See if we can get the route from the referer.
            return app('router')->getRoutes()->match(
                Request::create($referer, Livewire::originalMethod())
            );
        } catch (NotFoundHttpException|MethodNotAllowedHttpException $e) {
            // If not, use the current route.
            return app('router')->current();
        }
    }

    protected function getPathFromReferer($referer, $component, $response)
    {
        $route = $this->getRouteFromReferer($referer);

        if (! $this->shouldSendPath($component)) {
            return;
        }

        $queryParams = $this->mergeComponentPropertiesWithExistingQueryParamsFromOtherComponentsAndTheRequest($component);

        if ($route && ! $route->getActionName() instanceof \Closure && false !== strpos($route->getActionName(), get_class($component))) {
            $path = $response->effects['path'] = $this->buildPathFromRoute($component, $route, $queryParams);
        } else {
            $path = $this->buildPathFromReferer($referer, $queryParams);
        }

        if ($referer !== $path) {
            $response->effects['path'] = $path;
        }
    }

    protected function shouldSendPath($component): bool
    {
        return count($this->getQueryParamsFromComponentProperties($component)) !== 0;
    }

    public function isDefinitelyMagewireRequest(): bool
    {
        return true;
    }

    public function getExistingQueryParams()
    {
        return $this->isDefinitelyMagewireRequest() ? $this->getQueryParamsFromRefererHeader() : $this->request->getParams();
    }

    public function getQueryParamsFromRefererHeader()
    {
        if (empty($referer = $this->request->getHeader('Referer'))) {
            return [];
        }

        parse_str((string) parse_url($referer, PHP_URL_QUERY), $refererQueryString);
        return $refererQueryString;
    }

    public function buildPathFromReferer($referer, $queryParams) : string
    {
        return str($referer)->before('?').$this->stringifyQueryParams($queryParams);
    }

    public function buildPathFromRoute($component, $route, $queryString)
    {
        $boundParameters = array_merge(
            $route->parametersWithoutNulls(),
            array_intersect_key(
                $component->getPublicPropertiesDefinedBySubClass(),
                array_flip($route->parameterNames())
            )
        );

        return app(UrlGenerator::class)->toRoute($route, $boundParameters + $queryString->toArray(), true);
    }

    public function stringifyQueryParams(array $queryParams): string
    {
        if (empty($queryParams)) {
            return '';
        }

        return '?' . http_build_query($queryParams, '', '&', PHP_QUERY_RFC1738);
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
