# Examples

All examples use the same domain — a simple **clipboard** utility, a **poll** addon that drives an Alpine
component, a **copy** directive, and a **sync** feature — so you can see how each piece relates to the others.

---

## Utility

A utility returns a plain object of pure helper functions. No state, no dependencies.

**`src/view/base/templates/js/magewire/utilities/clipboard.phtml`**

```php
<?php

declare(strict_types=1);

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;

/** @var Escaper $escaper */
/** @var Template $block */
/** @var MagewireViewModel $magewireViewModel */

$magewireViewModel = $block->getData('view_model');
$magewireFragment  = $magewireViewModel->utils()->fragment();
?>
<?php $script = $magewireFragment->make()->script()->start() ?>
<script>
    function magewireClipboardUtility() {
        'use strict';

        return {
            copy: async function(text) {
                if (! navigator.clipboard) {
                    return false;
                }

                try {
                    await navigator.clipboard.writeText(text);
                    return true;
                } catch {
                    return false;
                }
            },

            read: async function() {
                if (! navigator.clipboard) {
                    return null;
                }

                try {
                    return await navigator.clipboard.readText();
                } catch {
                    return null;
                }
            }
        }
    }

    <?php /* Register as Magewire utility. */ ?>
    document.addEventListener('alpine:init', () => window.MagewireUtilities.register('clipboard', magewireClipboardUtility), { once: true });
</script>
<?php $script->end() ?>
```

**Layout block** in `src/view/base/layout/default.xml`, inside `magewire.utilities`:

```xml
<block name="magewire.utilities.clipboard"
       template="Magewirephp_Magewire::js/magewire/utilities/clipboard.phtml"
/>
```

---

## Addon

An addon is a stateful, framework-agnostic API. It is Alpine-reactive when the third argument to
`register()` is `true`, allowing its state to drive the DOM directly.

**`src/view/base/templates/js/magewire/addons/poll.phtml`**

```php
<?php

declare(strict_types=1);

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;

/** @var Escaper $escaper */
/** @var Template $block */
/** @var MagewireViewModel $magewireViewModel */

$magewireViewModel = $block->getData('view_model');
$magewireFragment  = $magewireViewModel->utils()->fragment();
?>
<?php $script = $magewireFragment->make()->script()->start() ?>
<script>
    function magewirePollAddon() {
        'use strict';

        return {
            active:   false,
            interval: null,
            count:    0,

            start: function(ms = 5000) {
                if (this.active) {
                    return;
                }

                this.active   = true;
                this.interval = setInterval(() => {
                    this.count++;
                    this.tick();
                }, ms);
            },

            stop: function() {
                clearInterval(this.interval);
                this.active   = false;
                this.interval = null;
            },

            tick: function() {
                if (window.Magewire) {
                    Magewire.triggerAsync('addons.poll.tick', { count: this.count });
                }
            },

            reset: function() {
                this.stop();
                this.count = 0;
            }
        };
    }

    <?php /* Register as Magewire addon. */ ?>
    window.MagewireAddons.register('poll', magewirePollAddon, true);
</script>
<?php $script->end() ?>
```

**Layout block** in `src/view/base/layout/default.xml`, inside `magewire.addons`:

```xml
<block name="magewire.addons.poll"
       template="Magewirephp_Magewire::js/magewire/addons/poll.phtml"
/>
```

---

## Alpine component

An Alpine component is a thin wrapper that exposes addon state and a limited set of methods to HTML templates.
All logic lives in the addon. The component only provides what the template needs.

Magewire ships `magewire.csp.min.js`, an unmodified copy of Livewire's JavaScript bundle, which includes
the Alpine CSP build. HTML attribute expressions can only access properties and methods defined on the
component's returned data object. Closure variables like `const poll = window.MagewireAddons.poll` are
not in scope from HTML — they must be wrapped in component methods. That is the sole reason for the
`START/END` comment markers.

**`src/view/base/templates/js/alpinejs/components/magewire-poll.phtml`**

```php
<?php

declare(strict_types=1);

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;

/** @var Escaper $escaper */
/** @var Template $block */
/** @var MagewireViewModel $magewireViewModel */

$magewireViewModel = $block->getData('view_model');
$magewireFragment  = $magewireViewModel->utils()->fragment();
?>
<?php $script = $magewireFragment->make()->script()->start() ?>
<script>
    function magewirePoll() {
        'use strict';

        const poll = window.MagewireAddons.poll;

        return {
            get active() { return poll.active; },
            get count()  { return poll.count; },

            <?php /* START: Only add those methods that should become CSP compatible. */ ?>
            start: function() {
                poll.start();
            },
            stop: function() {
                poll.stop();
            },
            reset: function() {
                poll.reset();
            },
            <?php /* END */ ?>

            bindings: {
                toggle: function() {
                    return {
                        'x-bind:aria-pressed'() {
                            return poll.active;
                        },
                        'x-on:click'() {
                            poll.active ? poll.stop() : poll.start();
                        }
                    }
                }
            }
        }
    }

    <?php /* Register as Alpine component. */ ?>
    document.addEventListener('alpine:init', () => Alpine.data('magewirePoll', magewirePoll), { once: true });
</script>
<?php $script->end() ?>
```

**Usage in a theme template:**

```html
<div x-data="magewirePoll">
    <span x-text="count"></span>
    <button x-bind="bindings.toggle()">Toggle</button>
    <button @click="reset()">Reset</button>
</div>
```

**Layout block** in `src/view/base/layout/default.xml`, inside `magewire.alpinejs.components`:

```xml
<block name="magewire.alpinejs.components.magewire-poll"
       template="Magewirephp_Magewire::js/alpinejs/components/magewire-poll.phtml"
/>
```

---

## Directive

A directive adds a declarative HTML attribute. It is self-contained and exports nothing.

**`src/view/base/templates/js/magewire/directives/copy.phtml`**

```php
<?php

declare(strict_types=1);

use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;

/** @var Template $block */
/** @var MagewireViewModel $magewireViewModel */

$magewireViewModel = $block->getData('view_model');
$magewireFragment  = $magewireViewModel->utils()->fragment();
?>
<?php $script = $magewireFragment->make()->script()->start() ?>
<script>
    (() => {
        'use strict';

        document.addEventListener('magewire:initialized', event => {
            Magewire.directive('mage:copy', ({ el, directive, cleanup }) => {
                const source = directive.expression
                    ? document.querySelector(directive.expression)
                    : el;

                const action = async () => {
                    const text = source?.textContent?.trim() ?? '';

                    if (window.MagewireUtilities && window.MagewireUtilities.has('clipboard')) {
                        await window.MagewireUtilities.clipboard.copy(text);
                    }
                };

                el.addEventListener('click', action);

                cleanup(() => el.removeEventListener('click', action));
            });
        });
    })();
</script>
<?php $script->end() ?>
```

**Usage:**

```html
<!-- Copies own text content -->
<button mage:copy>Copy me</button>

<!-- Copies text from a target element -->
<code id="snippet">npm install magewire</code>
<button mage:copy="#snippet">Copy command</button>
```

**Layout block** in `src/view/base/layout/default.xml`, inside `magewire.directives`:

```xml
<block name="magewire.directives.copy"
       template="Magewirephp_Magewire::js/magewire/directives/copy.phtml"
/>
```

---

## Feature

A feature bridges a Magewire PHP Feature's effects to an addon or utility. It reads effects pushed by PHP
and calls the appropriate addon methods.

The PHP side pushes a custom effect in `dehydrate()`:

```php
public function dehydrate(ComponentContext $context): void
{
    $context->pushEffect('poll', ['reset' => true]);
}
```

**`src/view/base/templates/js/magewire/features/support-magewire-poll/support-magewire-poll.phtml`**

```php
<?php

declare(strict_types=1);

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;

/** @var Escaper $escaper */
/** @var Template $block */
/** @var MagewireViewModel $magewireViewModel */

$magewireViewModel = $block->getData('view_model');
$magewireFragment  = $magewireViewModel->utils()->fragment();
?>
<?php $script = $magewireFragment->make()->script()->start() ?>
<script>
    document.addEventListener('magewire:init', event => {
        const addons = window.MagewireAddons;

        Magewire.hook('commit', ({ succeed }) => {
            succeed(({ effects }) => {
                if (! addons.has('poll')) {
                    return;
                }

                const data = effects.poll ?? {};

                if (data.reset === true) {
                    addons.poll.reset();
                }
            });
        });
    });
</script>
<?php $script->end() ?>
```

**Layout block** in `src/view/base/layout/default.xml`, inside `magewire.features`:

```xml
<block name="magewire.features.support-magewire-poll"
       template="Magewirephp_Magewire::js/magewire/features/support-magewire-poll/support-magewire-poll.phtml"
/>
```

---

## Frontend-only component

When a component uses frontend-specific APIs (e.g., Hyva's `hyva.getFormKey()`), it goes in `frontend/` instead of `base/`.
The structure under `js/` is identical — only the view area changes.

**`src/view/frontend/templates/js/alpinejs/components/magewire-form.phtml`**

```php
<?php

declare(strict_types=1);

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\ViewModel\Magewire as MagewireViewModel;

/** @var Escaper $escaper */
/** @var Template $block */
/** @var MagewireViewModel $magewireViewModel */

$magewireViewModel = $block->getData('view_model');
$magewireFragment  = $magewireViewModel->utils()->fragment();
?>
<?php $script = $magewireFragment->make()->script()->start() ?>
<script>
    function magewireForm() {
        'use strict';

        return {
            bindings: {
                root: function() {
                    return {
                        'x-bind:data-csrf'() {
                            return hyva.getFormKey();
                        }
                    }
                }
            }
        }
    }

    document.addEventListener('alpine:init', () => Alpine.data('magewireForm', magewireForm), { once: true });
</script>
<?php $script->end() ?>
```

**Layout block** in `src/view/frontend/layout/default.xml`:

```xml
<block name="magewire.alpinejs.components.magewire-form"
       template="Magewirephp_Magewire::js/alpinejs/components/magewire-form.phtml"
/>
```
