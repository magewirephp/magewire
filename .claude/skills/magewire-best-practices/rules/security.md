# Security

## CSRF Protection Is Automatic

Magewire uses Magento's FormKey for CSRF protection. Every AJAX request to `magewire/update` includes the form key automatically. You don't need to add tokens manually — but don't disable or bypass FormKey validation in Magento's configuration.

## Snapshot Checksums Prevent Tampering

Every component snapshot includes an HMAC checksum of the `data` and `memo` fields. The server validates this checksum before processing any update. Never bypass or weaken checksum validation — it's the primary defense against state manipulation.

## Public Methods Are Frontend-Callable

Any `public` method on a component can be invoked from the browser via `wire:click`, `wire:submit`, or JavaScript. Treat every public method as an API endpoint.

```php
// Wrong — admin-level action exposed without authorization
public function deleteAllProducts(): void
{
    $this->productRepository->deleteAll();
}

// Correct — check authorization before executing
public function deleteProduct(int $productId): void
{
    if (!$this->authorizationService->isAllowed('Vendor_Module::delete_product')) {
        $this->magewireNotifications()->make(__('Not authorized'))->asError();
        return;
    }

    $this->productRepository->deleteById($productId);
}
```

## Public Properties Are Visible to the Client

The snapshot is embedded in the HTML as a JSON object. Every public property's value is visible in the page source. Never store secrets, internal IDs that shouldn't be exposed, or PII that the user shouldn't see.

```php
// Wrong
public string $adminSecret = '';
public string $internalApiKey = '';
public string $hashedPassword = '';

// Correct — use private properties or fetch server-side only
private string $adminSecret;

public function processWithSecret(): void
{
    // Fetch the secret server-side, never expose it
    $secret = $this->config->getValue('vendor/module/api_key');
    $this->apiClient->call($secret, $this->publicInput);
}
```

## Escape Output in Templates

Use Magento's `$escaper` for every context. See `rules/templates.md` for the full pattern.

| Context | Escaper | Example |
|---------|---------|---------|
| HTML text | `escapeHtml()` | `<?= $escaper->escapeHtml($magewire->name) ?>` |
| HTML attribute | `escapeHtmlAttr()` | `class="<?= $escaper->escapeHtmlAttr($val) ?>"` |
| URL | `escapeUrl()` | `href="<?= $escaper->escapeUrl($magewire->link) ?>"` |
| JavaScript | `escapeJs()` | `'<?= $escaper->escapeJs($magewire->text) ?>'` |

## Rate Limiting

The `SupportMagewireRateLimiting` feature provides configurable rate limiting for Magewire update requests. It is configured via Magento's admin system configuration and DI — not via PHP attributes on component methods. Enable and configure it through admin settings to prevent abuse of update endpoints.

## Validate Input in Action Methods

Public properties can be set to any value from the frontend. Always validate before using them in business logic — especially before database writes or external API calls.

```php
public function updateQuantity(int $itemId, int $quantity): void
{
    // Validate boundaries
    if ($quantity < 1 || $quantity > 99) {
        $this->magewireNotifications()->make(__('Invalid quantity'))->asError();
        return;
    }

    // Verify the item belongs to the current user
    $item = $this->cartRepository->getItem($itemId);
    if ($item->getCustomerId() !== $this->customerSession->getCustomerId()) {
        $this->magewireNotifications()->make(__('Not authorized'))->asError();
        return;
    }

    $item->setQty($quantity);
    $this->cartRepository->save($item);
}
```

## Don't Trust Method Parameters from the Frontend

Method parameters passed via `wire:click="delete(123)"` come from the HTML — a user can modify them in the browser. Always verify ownership and authorization server-side.

```php
// Wrong — trusts the ID from the frontend
public function delete(int $addressId): void
{
    $this->addressRepository->deleteById($addressId);
}

// Correct — verifies ownership
public function delete(int $addressId): void
{
    $address = $this->addressRepository->getById($addressId);

    if ((int) $address->getCustomerId() !== (int) $this->customerSession->getCustomerId()) {
        return;
    }

    $this->addressRepository->deleteById($addressId);
}
```
