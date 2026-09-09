# Target ownership

| Concern | Owner | Delivery |
| --- | --- | --- |
| `wire:loading`, delayed loading, offline, dirty, and cloak behavior | Magewire core | Pre-baked Magento CSS asset |
| Notifier layout, presentation, badge geometry, and animation | Magewire core | Original Laravel-inspired vanilla Magento CSS asset |
| Loading icon and developer exception structure | Magewire core | Pre-baked Magento CSS asset |
| Default notification colors and surface | Magewire core | Magewire variables and `data-type` styles |
| Optional notification customization | Active Magento theme | Magewire custom properties and existing `.message.<severity>` hooks |
| Alpine/runtime integration and optional Hyva presentation | `magewire-hyva-theme` | Layout/templates and browser-ready vanilla CSS |
| Consumer component presentation | Consumer module/theme | Its chosen CSS pipeline |
| Optional Tailwind-aware Magewire template/compiler APIs | Magewire core | Public framework capability; no core asset dependency |

# Resulting flow

```text
Magento page layout
  -> loads Magewire's ready-to-serve magewire.css
  -> renders semantic Magewire markup
  -> active theme can customize the default through variables or compatibility hooks
  -> Hyva companion may load a vanilla visual override through Magento layout

No Magewire package
  -> registers in hyva-themes.json
  -> contributes Tailwind source/config
  -> requires a merchant Tailwind rebuild
```

# CSS contract

## Stable hooks

Retain existing namespaced hooks used by behavior and tests, including:

- `.magewire-notifier`
- `.magewire-notifier-item`
- `.magewire-notifier-occurrences`
- `.magewire-notifier-before`
- `.magewire-notifier-body`
- `.magewire-notifier-title`
- `.magewire-notifier-message`
- `.magewire-notifier-after`
- `.magewire-exception`

Retain `.message` and severity classes as interoperability hooks. Replace
utility tokens with new namespaced hooks only where CSS or JavaScript needs an
explicit state, such as the repeated-occurrence animation.

## Override behavior

Core CSS should:

- use namespaced selectors for framework-owned structure;
- provide independently usable Laravel-inspired defaults while exposing custom
  properties for deliberate theme overrides;
- expose CSS custom properties for common layout/color adjustments;
- avoid global resets and unscoped utility classes;
- respect reduced-motion preferences;
- use browser-ready syntax so no preprocessing is required.

# Compatibility sequence

1. Release core with pre-baked CSS and without its Hyva observer.
2. During the transition, old companion releases still register both source
   paths. This is redundant but remains functional because new core markup is
   already styled independently.
3. Release the companion with no Tailwind directory or observer and a minimum
   dependency on the new core version.
4. New installations no longer add either Magewire package to
   `hyva-themes.json` and do not rebuild theme CSS for Magewire.

# Failure boundaries

- If the Magewire CSS asset fails to load, framework behavior remains present
  in markup/JavaScript but directive-state and built-in UI presentation can
  flash or degrade. Browser coverage should detect the missing asset.
- If a theme does not implement `.message.<severity>`, Magewire's vanilla
  fallback still provides a complete notification. The compatibility classes
  remain present for extensions that use them.
- A new companion must never resolve with an old core after removing its own
  notifier CSS; the Composer minimum constraint is the guardrail.
- Tailwind classes in user-authored Magewire components remain the user's theme
  build responsibility. Core decoupling does not attempt to scan arbitrary
  third-party templates.
