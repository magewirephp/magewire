# Layout XML & Block Registration

## Bind the Component via the `magewire` Argument

Magewire can be bound to any block class — the component PHP class goes in the `magewire` data argument. The block class can be `Magewirephp\Magewire\Block\Magewire`, a custom block, or any existing Magento block.

```xml
<!-- Using the default Magewire block -->
<block class="Magewirephp\Magewire\Block\Magewire"
       name="checkout.shipping.method"
       template="Vendor_Module::shipping-method.phtml">
    <arguments>
        <argument name="magewire" xsi:type="object">Vendor\Module\Magewire\ShippingMethod</argument>
    </arguments>
</block>

<!-- Binding Magewire to an existing block class -->
<block class="Vendor\Module\Block\ShippingMethod"
       name="checkout.shipping.method"
       template="Vendor_Module::shipping-method.phtml">
    <arguments>
        <argument name="magewire" xsi:type="object">Vendor\Module\Magewire\ShippingMethod</argument>
    </arguments>
</block>

<!-- Wrong — component class used as block class -->
<block class="Vendor\Module\Magewire\ShippingMethod"
       name="checkout.shipping.method"
       template="Vendor_Module::shipping-method.phtml"/>
```

## Pass Configuration via Layout Arguments

Use layout XML arguments to configure a component without hardcoding values. Arguments are passed to `mount()` as the `$params` array.

```xml
<block name="product.reviews"
       template="Vendor_Module::reviews.phtml">
    <arguments>
        <argument name="magewire" xsi:type="object">Vendor\Module\Magewire\Reviews</argument>
        <argument name="product_id" xsi:type="number">0</argument>
        <argument name="page_size" xsi:type="number">10</argument>
        <argument name="sort_order" xsi:type="string">created_at</argument>
    </arguments>
</block>
```

```php
public function mount(int $productId, int $pageSize = 5, string $sortOrder = 'created_at'): void
{
    $this->productId = $productId;
    $this->pageSize = $pageSize;
    $this->reviews = $this->loadReviews($sortOrder);
}
```

## Use Descriptive Block Names

Block names should be `snake_case` with dots as namespace separators, following Magento's convention. Names must be unique across the entire layout.

```xml
<!-- Correct -->
<block name="checkout.shipping.address.form" .../>
<block name="customer.account.wishlist" .../>

<!-- Wrong -->
<block name="shippingAddressForm" .../>
<block name="my-wishlist-block" .../>
```

## Register in the Right Layout Handle

Place component blocks in the layout handle where they're actually needed — not in `default.xml` unless the component appears on every page.

```xml
<!-- Correct — only on checkout page -->
<!-- File: Vendor_Module/view/frontend/layout/checkout_index_index.xml -->
<referenceContainer name="content">
    <block class="Magewirephp\Magewire\Block\Magewire" name="checkout.cart.summary" .../>
</referenceContainer>

<!-- Avoid — in default.xml when only needed on one page -->
<!-- File: Vendor_Module/view/frontend/layout/default.xml -->
<referenceContainer name="content">
    <block class="Magewirephp\Magewire\Block\Magewire" name="checkout.cart.summary" .../>
</referenceContainer>
```

## Extend Magewire Layout Containers for JS

When adding JavaScript that integrates with Magewire's lifecycle, use the appropriate named container. Don't create ad-hoc script blocks in `head` or `before.body.end`.

```xml
<!-- Correct — add a custom Magewire utility -->
<referenceContainer name="magewire.utilities">
    <block name="my.custom.utility"
           template="Vendor_Module::js/magewire/utilities/my-utility.phtml"/>
</referenceContainer>

<!-- Correct — add a custom directive -->
<referenceContainer name="magewire.directives">
    <block name="my.custom.directive"
           template="Vendor_Module::js/magewire/directives/my-directive.phtml"/>
</referenceContainer>
```

See the `magewire-architecture` skill for the full container reference.