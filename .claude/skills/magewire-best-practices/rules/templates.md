# Templates & Views

## Single Root Element

Every Magewire component template must have exactly one root HTML element. Magewire attaches its `wire:snapshot` and `wire:effects` attributes to this element and uses it as the morph target.

```html
<!-- Correct -->
<div>
    <h2><?= $escaper->escapeHtml($magewire->title) ?></h2>
    <p><?= $escaper->escapeHtml($magewire->description) ?></p>
</div>

<!-- Wrong — multiple root elements -->
<h2><?= $escaper->escapeHtml($magewire->title) ?></h2>
<p><?= $escaper->escapeHtml($magewire->description) ?></p>
```

## Escape All Dynamic Output

Magento provides purpose-specific escapers. Use the right one for the context — never output raw user data.

```php
<!-- Text content -->
<?= $escaper->escapeHtml($magewire->name) ?>

<!-- URL attribute -->
<a href="<?= $escaper->escapeUrl($magewire->profileUrl) ?>">

<!-- HTML attribute value -->
<div class="<?= $escaper->escapeHtmlAttr($magewire->cssClass) ?>">

<!-- Inside JavaScript -->
<div x-data="{ name: '<?= $escaper->escapeJs($magewire->name) ?>' }">

<!-- Translation -->
<?= $escaper->escapeHtml(__('Add to Cart')) ?>
```

Never use `htmlspecialchars()` or `strip_tags()` directly — Magento's `$escaper` handles encoding correctly for each context.

## Keep Templates Thin

Templates should render state, not compute it. Move logic into the component class — methods, computed properties, or lifecycle hooks.

```php
<!-- Wrong — business logic in template -->
<?php
$subtotal = 0;
foreach ($magewire->items as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
?>
<span><?= $escaper->escapeHtml(number_format($subtotal, 2)) ?></span>

<!-- Correct — computed property in component -->
<span><?= $escaper->escapeHtml(number_format($magewire->subtotal, 2)) ?></span>
```

## Access Component State via `$magewire`

The `$magewire` variable is the component instance. Access properties and call methods on it directly.

```html
<div>
    <!-- Property access -->
    <span><?= $escaper->escapeHtml($magewire->count) ?></span>

    <!-- Computed property -->
    <span><?= $escaper->escapeHtml($magewire->fullName) ?></span>

    <!-- Conditional rendering -->
    <?php if ($magewire->isActive): ?>
        <span>Active</span>
    <?php endif ?>
</div>
```

## Use `$block` for Magento Integration

The `$block` variable is the Magento block instance (`Magewirephp\Magewire\Block\Magewire`). Use it for Magento-specific functionality like child blocks, URLs, and view file paths.

```html
<!-- Render a nested Magento block -->
<?= $block->getChildHtml('sidebar') ?>

<!-- Get a static asset URL -->
<img src="<?= $escaper->escapeUrl($block->getViewFileUrl('images/logo.svg')) ?>">

<!-- Get a store URL -->
<a href="<?= $escaper->escapeUrl($block->getUrl('customer/account')) ?>">
```

## Use `wire:key` for Dynamic Lists

When rendering lists that can change order or length, add `wire:key` to help the DOM differ track elements correctly.

```html
<?php foreach ($magewire->items as $item): ?>
    <div wire:key="item-<?= $escaper->escapeHtmlAttr($item['id']) ?>">
        <?= $escaper->escapeHtml($item['name']) ?>
    </div>
<?php endforeach ?>
```

## Loading States for User Feedback

Use `wire:loading` directives to show feedback during server roundtrips — don't implement custom polling or spinners.

```html
<button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</button>

<!-- Target specific actions -->
<div wire:loading wire:target="search">Searching...</div>
```