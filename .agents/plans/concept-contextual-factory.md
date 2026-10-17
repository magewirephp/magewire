# Concept: consumer-aware dependency lookup

Status: experimental draft for API and behavior review.

## Problem

`Factory::get(LoggerInterface::class)` applies Magento's current preference, but
does not know that a particular consumer was configured with a different object
for its `$logger` constructor argument. It also cannot recover an instance passed
explicitly to that consumer's constructor.

## Proposed API

```php
use Magewirephp\Magewire\Support\Factory;
use Psr\Log\LoggerInterface;

$factory = Factory::for($component);

$logger = $factory->dependency('logger', LoggerInterface::class);
$sharedOrStoredLogger = $factory->get('logger', LoggerInterface::class);
$freshLogger = $factory->create('logger', LoggerInterface::class, ['channel' => 'checkout']);
```

The consumer is explicit, so no backtrace is necessary. The fallback type is
explicit because a missing property or constructor parameter cannot supply it.
The original `Factory::get()` and `Factory::create()` keep their existing behavior.
This prototype adds no automatic call-site interception or component lifecycle hooks.

## Resolution choices

| Method | Stored instance property | Configured object reference | No configured argument |
| --- | --- | --- | --- |
| `dependency()` | Reuse its value | Follow the configured shared/non-shared lifecycle | Magento `get($type)` |
| `get()` | Reuse its value | Magento `get($configuredType)`, overriding sharing flags | Magento `get($type)` |
| `create()` | Skip it | Magento `create($configuredType, $arguments)`, overriding sharing flags | Magento `create($type, $arguments)` |

For `get()`, an existing injected instance wins even if it was originally transient.
The explicit shared choice only applies when resolution is needed. `dependency()`
remains the automatic option when the constructor argument's lifecycle should win.

Only `create()` accepts constructor arguments. It creates a fresh root object;
that object's own dependencies retain their normal Magento DI configuration.
Creation delegates directly to the Object Manager, avoiding the existing static
factory's special shared-instance shortcut for names ending in `Factory`.

The public methods express the resolution choice. An internal `ResolutionMode`
enum keeps the shared implementation explicit without adding a public mode parameter.

## Lookup contract

1. Find the named instance property on the consumer or its parent classes. If it
   is initialized, return its value, including an intentional `null`. `create()`
   skips this step completely. Validate
   non-null values against the requested type; do not replace incompatible values.
2. If the property is absent or uninitialized, inspect the active Magento
   `ConfigInterface::getArguments($consumerType)` entry for the same argument name.
   Resolve the configured object with the selected lifecycle from the table above.
   `get()` and `dependency()` preserve configured literal objects and `null`.
   `create()` rejects these bindings because there is no DI type name to construct;
   it does not infer a type from an object or silently replace a configured null.
   All methods reject unsupported/scalar argument metadata.
3. If that argument has no configuration, use Magento's normal `get($type)` or
   `create($type, $arguments)` according to the selected method.

Static properties do not count as stored instance dependencies. Resolved values
are returned without modifying the consumer, its properties, or Magento DI.
No extra cache is introduced. `create()` neither clones the stored object nor
derives its type with `get_class()`: it uses the configured DI name, preserving
virtual type constructor configuration. Explicit runtime constructor overrides
cannot be reconstructed from the existing instance.

### Example configuration

```xml
<type name="Vendor\Module\Magewire\Checkout">
    <arguments>
        <argument name="logger" xsi:type="object">Vendor\Module\Logger\Audit</argument>
    </arguments>
</type>
```

With `dependency()` or `get()`, if the component already stores `$logger`, its
actual instance wins, including a runtime constructor override. Otherwise the
named argument selects the audit logger. `create()` selects the audit logger
without reading the property. If there is no such argument configuration, the
explicit interface falls back to the current Magento preference.

This API does not remove the need to inject dependencies initially when exact
instance identity matters. A transient dependency not stored by its consumer
cannot be recovered afterward; the configuration can only create another instance.

## Property access and `invade()`

The property-first approach follows the idea behind Magewire's `invade()` helper.
This prototype uses `ReflectionProperty::getValue()` instead: `invade()` returns
properties by reference and binds access to the runtime class, which is unsuitable
for readonly properties and private properties declared on an interceptor's parent.
The resolver searches the hierarchy and reads the declaring property's value
directly. It does not change the shared helper.

An argument and its storage property need not have the same name. This initial
API uses one name for both lookup stages and does not infer assignments in
constructor bodies. Separate property/argument names could be added after review.

## Consumer identity

The default DI identity is the consumer's concrete class, with Magento-generated
interceptors removed through the existing reflection utility. The active config
already reflects the area loaded by Magento; no XML is reparsed.

Virtual types do not have a distinct PHP class. Pass their original name when
configuration fallback must use that identity:

```php
$logger = Factory::for($component, consumerType: 'checkout.audit')
    ->dependency('logger', LoggerInterface::class);
```

The caller is responsible for supplying the correct DI identity. Automatic
recording of component virtual type names is outside this concept's scope.

## Developer and compiled metadata

The prototype handles developer-mode `instance`/`shared` references and compiled
`_i_` (shared), `_ins_` (non-shared), `_vn_` (null), and `_v_` (literal) entries.
Literal results must still be objects of the requested type or null. Array and
environment-parameter resolution are deliberately outside this object-only API;
unsupported metadata throws instead of silently selecting the default preference.

Compiled DI only retains metadata relevant to real constructor parameters. An
arbitrary `<argument>` with no corresponding parameter must not be relied on as a
new general-purpose binding. A dedicated named binding system would require its
own configuration contract.

## Validation and review questions

Focused tests exercise exact injected identity through an interceptor, readonly
and private parent properties, null, uninitialized properties, lifecycle flags,
fallback preferences, invalid values, and explicit virtual consumer identity.
Additional cases verify that explicit get/create override lifecycle flags, creation
skips incompatible stored values, literal bindings cannot be recreated, constructor
arguments reach the selected virtual type, and stored dependencies remain unchanged.
Container tests use Magento's actual developer and compiled factories with
runtime/compiled configuration fixtures, rather than only mocked routing calls.
They do not replace a full Magento application boot or `setup:di:compile` run.

Before promoting this draft to a supported API, review:

- Whether `dependency()` belongs on `Factory` or the application-container adapter.
- Whether property and constructor argument names need separate parameters.
- Whether initialized null should remain authoritative or allow an explicit opt-in fallback.
- How the component creation path should retain virtual type identity.
- The supported Magento versions and area-specific integration test matrix.

The checkout has no `.claude/skills/` tree and ignores that directory. This tracked
design document records the experimental API alongside the existing `.agents`
plans; published documentation and AI reference material should follow once the
API is accepted.
