# Magewire - Content Security Policy (CSP)
When dealing with payments and/or customer data on your website you may want to enforce the [Content Security Policy Framework](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/script-src#unsafe_inline_script.
Magento has this builtins to deal with this. In a nutshell, unverified can no longer be executed without adding a control header and nonce
to your `<script>` tags and `eval` is also forbidden.

## PCI-DSS compliance
From 2025, April 1st sites handling payment data need to have CSP enforced on pages related to payment data. This can be the full checkout process or
if you use payment buttons on product pages.

## Implication for Magewire/Livewire
By default it was possible to add scripts to your existing code which could be loaded side-by-side with your `.phtml` files.
Livewire would parse the new dom and if scripts where added evaluate them inline. This does not work when CSP is enabled.

The solution is to separate your `scripts` (for instance Alpine) and `.phtml` into two files, where the scripts are loaded on page load.
In Alpine there is a container `magewire.plugin.scripts` which will be loaded on the first page load.

## Updating scripts existing scripts and add a nonce
CSP can help you protect against XSS(Cross Site Scripting) by adding a `<script nonce="UNIQUE-PER-REQUEST">` which will change on every request (hence nonce)

In Magewire there is a helper which will to quickly update your existing scripts with a nonce.
This nonce is also added to the `Content-Security-Policy` header. Your browser will verify if the script matches the nonce from the header and only then execute the script.

```diff
-<script>
+<script nonce="<?= /* @noEscape */ $block->helper(\Magewirephp\Magewire\Helper\CspNonceProvider::class)->generateNonce() ?>">
```

Scripts must be seperated from the dynamic `phtml` files. See [#Example]

### Disable inline scripting on specific pages in Magento
To disable inline scripting, eval and event handlers you can add the following rule to your `etc/config.xml`

```xml
<default>
    <!-- disable inline scripts on checkout -->
    <csp>
        <mode>
            <storefront_checkout_index_index>
                <report_only>0</report_only>
            </storefront_checkout_index_index>
        </mode>
        <policies>
            <storefront_checkout_index_index>
                <scripts>
                    <eval>0</eval>
                    <inline>0</inline>
                    <event_handlers>1</event_handlers>
                </scripts>
            </storefront_checkout_index_index>
        </policies>
    </csp>
```
- `storefront_checkout_index_index` determines the controller path
- `eval|inline` is disabled
- `event_handlers` are still allowed
- `report_only` will not break the page but only display an error

## Example
An example on a AlpineJS powered script.

_Read the AlpineJS documentation on [Alpine CSP](https://alpinejs.dev/advanced/csp)_
Make sure to load the `-csp` version of alpine on csp enforced pages. 

### before: allowed with inline scripting and evaluation
`simple_page_controller.xml`
```xml
<referenceContainer name="content">
    <!-- simplified magewire component example -->
    <block template="MyModule::awesome.phtml" />
</referenceContainer>
```

`awesome.phtml`
```html
<div x-data="awesome({visible: $wire.entangle('visible')})">
    <div x-show="visible">
        This text is visible.
    </div>
    <button x-on:click="$wire.set('visible', false)">Hide text</button>
    <button x-on:click="show($wire)">Show text</button>
</div>
<script>
    function awesome(data) {
        return Object.assign(
            data,
            {
                show($wire) {
                    $wire.set('visibile', true)
                }
            }
        )
    }
</script>
```
- data is entangled from the magewire component visible property
- the global `awesome` function is used to add an extra method `show`
- from the buttons, `$wire` is directly injected or set

### after: inline scripting and evaluation is disabled
`simple_page_controller.xml`
```xml
<body>
    <referenceContainer name="content">
        <!-- simplified magewire component example -->
        <block template="MyModule::awesome.phtml" />
    </referenceContainer>

    <!-- global footer js scripts -->
    <referenceContainer name="magewire.plugin.scripts">
        <!-- simplified magewire component example -->
        <block template="MyModule::awesome-js.phtml" />
    </referenceContainer>
</body>
```

`awesome.phtml`
```html
<div x-data="awesome">
    <div x-show="visible">
        This text is visible.
    </div>
    <button x-on:click="hide">Hide text</button>
    <button x-on:click="show">Show text</button>
</div>
```

`awesome-js.phtml`
```php
?>
<script nonce="<?= /* @noEscape */ $block->helper(\Magewirephp\Magewire\Helper\CspNonceProvider::class)->generateNonce() ?>">
    function awesome() {
        const $wire = this.$wire;
        
        return {
            visible: $wire.entangle('visible'),
            hide() {
                $wire.set('visibile', false)
            },
            show() {
                $wire.set('visibile', true)
            }
        }
    }
    (() => {
        const initFn = () => Alpine.data('awesome', awesome);
        window.Alpine ? initFn() : window.addEventListener('alpine:init', initFn, {once: true})
    })()
</script>
```
- no arguments are send to functions, everything is explicit
- `$wire` is loaded via `this.$wire`
- the global `awesome` function is registered via `Alpine.data` to make it available for `x-data`
- from the buttons, `$wire` is used from the defined scripts
- script can be fully validated
- script is loaded globally and not via ajax
