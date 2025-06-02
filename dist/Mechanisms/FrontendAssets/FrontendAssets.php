<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Mechanisms\FrontendAssets;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Asset\Repository as AssetsRepository;
use Magewirephp\Magewire\Concerns\AsDataObject;
use function Magewirephp\Magewire\on;
use Magewirephp\Magewire\Drawer\Utils;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;
use Magewirephp\Magewire\Mechanisms\Mechanism;
class FrontendAssets extends Mechanism
{
    use AsDataObject;
    public $hasRenderedScripts = false;
    public $hasRenderedStyles = false;
    public $javaScriptRoute;
    public $scriptTagAttributes = [];
    function boot()
    {
        $this->setScriptRoute(fn() => $this->returnJavaScriptAsFile());
        on('flush-state', function () {
            $this->hasRenderedScripts = false;
            $this->hasRenderedStyles = false;
        });
    }
    function useScriptTagAttributes($attributes)
    {
        $this->scriptTagAttributes = array_merge($this->scriptTagAttributes, $attributes);
    }
    function setScriptRoute($callback)
    {
        $route = $callback([self::class, 'returnJavaScriptAsFile']);
        $this->javaScriptRoute = $route;
    }
    public static function livewireScripts($expression)
    {
        return '{!! \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(' . $expression . ') !!}';
    }
    public static function livewireScriptConfig($expression)
    {
        return '{!! \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scriptConfig(' . $expression . ') !!}';
    }
    public static function livewireStyles($expression)
    {
        return '{!! \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(' . $expression . ') !!}';
    }
    function returnJavaScriptAsFile()
    {
        $url = $this->assetsRepository->getUrlWithParams($this->getDataByPath('script.file_path'), ['_secure' => $this->request->isSecure()]);
        $queryParams = $this->getDataByPath('script.query_params');
        if (is_array($queryParams)) {
            return $url . '?' . http_build_query($queryParams);
        }
        return $url;
    }
    public function maps()
    {
        return Utils::pretendResponseIsFile(__DIR__ . '/../../../dist/livewire.min.js.map');
    }
    public static function styles($options = [])
    {
        app(static::class)->hasRenderedStyles = true;
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\" data-livewire-style" : '';
        $progressBarColor = config('livewire.navigate.progress_bar_color', '#2299dd');
        // Note: the attribute selectors are "doubled" so that they don't get overriden when Tailwind's CDN loads a script tag
        // BELOW the one Livewire injects...
        $html = <<<HTML
        <!-- Livewire Styles -->
        <style {$nonce}>
            [wire\\:loading][wire\\:loading], [wire\\:loading\\.delay][wire\\:loading\\.delay], [wire\\:loading\\.inline-block][wire\\:loading\\.inline-block], [wire\\:loading\\.inline][wire\\:loading\\.inline], [wire\\:loading\\.block][wire\\:loading\\.block], [wire\\:loading\\.flex][wire\\:loading\\.flex], [wire\\:loading\\.table][wire\\:loading\\.table], [wire\\:loading\\.grid][wire\\:loading\\.grid], [wire\\:loading\\.inline-flex][wire\\:loading\\.inline-flex] {
                display: none;
            }
        
            [wire\\:loading\\.delay\\.none][wire\\:loading\\.delay\\.none], [wire\\:loading\\.delay\\.shortest][wire\\:loading\\.delay\\.shortest], [wire\\:loading\\.delay\\.shorter][wire\\:loading\\.delay\\.shorter], [wire\\:loading\\.delay\\.short][wire\\:loading\\.delay\\.short], [wire\\:loading\\.delay\\.default][wire\\:loading\\.delay\\.default], [wire\\:loading\\.delay\\.long][wire\\:loading\\.delay\\.long], [wire\\:loading\\.delay\\.longer][wire\\:loading\\.delay\\.longer], [wire\\:loading\\.delay\\.longest][wire\\:loading\\.delay\\.longest] {
                display: none;
            }
        
            [wire\\:offline][wire\\:offline] {
                display: none;
            }
        
            [wire\\:dirty]:not(textarea):not(input):not(select) {
                display: none;
            }
        
            :root {
                --livewire-progress-bar-color: {$progressBarColor};
            }
        
            [x-cloak] {
                display: none !important;
            }
        
            [wire\\:cloak] {
                display: none !important;
            }
        </style>
        HTML;
        return static::minify($html);
    }
    public static function scripts($options = [])
    {
        app(static::class)->hasRenderedScripts = true;
        $debug = config('app.debug');
        $scripts = static::js($options);
        // HTML Label.
        $html = $debug ? ['<!-- Livewire Scripts -->'] : [];
        $html[] = $scripts;
        return implode("\n", $html);
    }
    public static function js($options)
    {
        // Use the default endpoint...
        $url = app(static::class)->javaScriptRoute->uri;
        // Use the configured one...
        $url = config('livewire.asset_url') ?: $url;
        // Use the legacy passed in one...
        $url = $options['asset_url'] ?? $url;
        // Use the new passed in one...
        $url = $options['url'] ?? $url;
        $url = rtrim($url, '/');
        $url = (string) str($url)->when(!str($url)->isUrl(), fn($url) => $url->start('/'));
        // Add the build manifest hash to it...
        $manifest = json_decode(file_get_contents(__DIR__ . '/../../../dist/manifest.json'), true);
        $versionHash = $manifest['/livewire.js'];
        $url = "{$url}?id={$versionHash}";
        $token = app()->has('session.store') ? csrf_token() : '';
        $assetWarning = null;
        $nonce = isset($options['nonce']) ? "nonce=\"{$options['nonce']}\"" : '';
        [$url, $assetWarning] = static::usePublishedAssetsIfAvailable($url, $manifest, $nonce);
        $progressBar = config('livewire.navigate.show_progress_bar', true) ? '' : 'data-no-progress-bar';
        $updateUri = app('livewire')->getUpdateUri();
        $extraAttributes = Utils::stringifyHtmlAttributes(app(static::class)->scriptTagAttributes);
        return <<<HTML
        {$assetWarning}<script src="{$url}" {$nonce} {$progressBar} data-csrf="{$token}" data-update-uri="{$updateUri}" {$extraAttributes}></script>
        HTML;
    }
    public static function scriptConfig($options = [])
    {
        app(static::class)->hasRenderedScripts = true;
        $nonce = isset($options['nonce']) ? " nonce=\"{$options['nonce']}\"" : '';
        $progressBar = config('livewire.navigate.show_progress_bar', true) ? '' : 'data-no-progress-bar';
        $attributes = json_encode(['csrf' => app()->has('session.store') ? csrf_token() : '', 'uri' => app('livewire')->getUpdateUri(), 'progressBar' => $progressBar, 'nonce' => isset($options['nonce']) ? $options['nonce'] : '']);
        return <<<HTML
        <script{$nonce} data-navigate-once="true">window.livewireScriptConfig = {$attributes};</script>
        HTML;
    }
    protected static function usePublishedAssetsIfAvailable($url, $manifest, $nonce)
    {
        $assetWarning = null;
        // Check to see if static assets have been published...
        if (!file_exists(public_path('vendor/livewire/manifest.json'))) {
            return [$url, $assetWarning];
        }
        $publishedManifest = json_decode(file_get_contents(public_path('vendor/livewire/manifest.json')), true);
        $versionedFileName = $publishedManifest['/livewire.js'];
        $fileName = config('app.debug') ? '/livewire.js' : '/livewire.min.js';
        $versionedFileName = "{$fileName}?id={$versionedFileName}";
        $assertUrl = config('livewire.asset_url') ?? (app('livewire')->isRunningServerless() ? rtrim(config('app.asset_url'), '/') . "/vendor/livewire{$versionedFileName}" : url("vendor/livewire{$versionedFileName}"));
        $url = $assertUrl;
        if ($manifest !== $publishedManifest) {
            $assetWarning = <<<HTML
            <script {$nonce}>
                console.warn('Livewire: The published Livewire assets are out of date\\n See: https://livewire.laravel.com/docs/installation#publishing-livewires-frontend-assets')
            </script>
            
            HTML;
        }
        return [$url, $assetWarning];
    }
    protected static function minify($subject)
    {
        return preg_replace('~(\v|\t|\s{2,})~m', '', $subject);
    }
    function __construct(private readonly AssetsRepository $assetsRepository, private readonly RequestInterface $request)
    {
        //
    }
}