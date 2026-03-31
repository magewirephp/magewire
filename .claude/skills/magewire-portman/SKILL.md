---
name: magewire-portman
description: >
  Reference for Portman — the dev-only CLI tool that ports Livewire PHP into Magewire by downloading upstream source,
  merging augmentations, transforming namespaces, and writing the result to dist/.
  Use when syncing Magewire with a new Livewire release, writing augmentation files in portman/Livewire/,
  running portman build/watch, or understanding the relationship between lib/Livewire, portman/, and dist/.
---

# Magewire Portman

Portman is a **general-purpose PHP library porting CLI tool** created by Justin van Elst. While its primary use case is porting Livewire into Magewire, it is architecturally framework-agnostic and can be used to port any PHP package between frameworks. Its purpose is to automate the process of pulling upstream PHP source code (from Packagist), transforming it (rename namespaces, rename classes, remove methods), and merging local augmentations.

Portman is a standalone Composer package (`magewirephp/portman`) and is a **dev-only dependency** — Magewire operates fully independently without it. It is only needed when contributing to Magewire or syncing with a new Livewire release.

Nearly everything in Portman is configurable via `portman.config.php`: source directories, output directory, augmentation paths, file glob patterns, ignore rules, namespace/class transformations, method/property removals, file doc blocks, and post-processors.

---

## Why Portman Exists

Magewire's PHP core is a port of Laravel Livewire v3. Rather than forking and diverging, Magewire uses Portman to:

1. Download a specific Livewire release from Packagist
2. Rename all `Livewire\` namespaces to `Magewirephp\Magewire\`
3. Rename specific classes (e.g. `LivewireManager` → `MagewireManager`)
4. Remove Laravel-specific methods that don't apply in Magento
5. Merge Magewire-specific augmentations (additional methods, properties) into the ported classes
6. Output the result into `dist/` ready for use

When Livewire ships a new version, Magewire can re-run Portman against the new release to get the update, with only the augmentation files needing manual review.

---

## Commands

```bash
vendor/bin/portman                  # Show help / list commands
vendor/bin/portman init             # Generate a default portman.config.php
vendor/bin/portman download-source  # Download packages from Packagist into cache
vendor/bin/portman build            # Run the full build (merge + transform → output)
vendor/bin/portman watch            # Watch source/augmentation dirs and rebuild on save
```

`watch` requires `chokidar-cli` installed globally or locally via npm:

```bash
npm install chokidar-cli
```

---

## Configuration: `portman.config.php`

The config file is a PHP array validated as a typed DTO (via Spatie Laravel-Data).

```php
<?php

return [
    'directories' => [

        // Upstream sources to download and transform
        'source' => [
            'lib/Livewire' => [
                'composer' => [
                    'name'         => 'livewire/livewire',
                    'version'      => '~3.6.4',     // Composer constraint
                    'version-lock' => '3.6.4',       // Exact pinned version
                    'base-path'    => 'src',          // Subfolder within the package
                ],
                'glob'   => '**/*.php',              // Which files to process
                'ignore' => [                         // Ant-style exclusions
                    'SomeDirectory/**/*',
                    '!SomeDirectory/KeepThis.php',   // ! negates previous rule
                ],
            ],
        ],

        // Local files that get merged INTO matching source files
        'augmentation' => [
            'portman/Livewire',           // In Magewire: portman/Livewire/
        ],

        // Where the output lands
        'output' => 'dist',
    ],

    'transformations' => [
        'Livewire\\' => [                              // Match a namespace (ends with \\)
            'rename' => 'Magewirephp\\Magewire\\',    // Rename to
            'file-doc-block' => '/** ... */',          // Prepend to all files in namespace
            'children' => [
                'LivewireManager' => [
                    'rename' => 'MagewireManager',     // Rename class
                ],
                'Features\\SupportRedirects\\HandlesRedirects' => [
                    'remove-methods' => [              // Drop these methods
                        'redirectAction',
                        'redirectRoute',
                    ],
                    'remove-properties' => [           // Drop these properties
                        'laravelSpecificProp',
                    ],
                ],
            ],
        ],
    ],

    'post-processors' => [
        'rector'        => false,    // Run Rector after build
        'php-cs-fixer'  => false,    // Run PHP-CS-Fixer after build
    ],
];
```

The config file path can be overridden via the `PORTMAN_CONFIG_FILE` environment variable (set in a `.env` file or shell environment). Defaults to `portman.config.php` in the project root.

---

## How the Build Works

### 1. Download Phase (`download-source`)

- Reads each `composer` entry in `directories.source`
- Downloads the package ZIP from Packagist at the specified version
- Extracts only the `base-path` subdirectory into a local cache
- Applies `glob` and `ignore` filters to select files

### 2. Build Phase (`build`)

For each file that matches a source entry, Portman runs:

**a) Parse** — Both the source file and its matching augmentation file (if any) are parsed into ASTs using `nikic/php-parser`.

**b) Merge** — `ClassMerger` collects all methods, properties, and traits from the augmentation class and injects them into the source class AST. Augmentation wins on conflicts.

**c) Transform** — `Renamer` walks the merged AST and applies namespace renames, class renames, method/property removals, and file doc block insertions according to `transformations`.

**d) Output** — The transformed AST is pretty-printed back to PHP and written to `directories.output`.

**Additional files** are copied directly to output without parsing or merging.

### 3. Post-processing (optional)

If enabled, Rector and/or PHP-CS-Fixer are run over the output directory after the build.

---

## Augmentation Files

An augmentation file is a PHP class file placed at the same relative path as the source file it augments, inside the augmentation directory (in Magewire: `portman/Livewire/`).

Example: to add a method to `lib/Livewire/ComponentHook.php`, create:

```
portman/Livewire/ComponentHook.php
```

```php
<?php

namespace Livewire;  // Use the SOURCE namespace, not the output namespace

class ComponentHook
{
    // Only the methods/properties you want to add or override
    public function myMagewireAddition(): void
    {
        // This gets merged into the ported ComponentHook class
    }
}
```

Portman merges the augmentation's members into the source class. The output will use the transformed namespace (`Magewirephp\Magewire\`), not `Livewire\`.

---

## Additional Files

Files placed in `directories.additional` are copied verbatim to the output — no parsing, no merging, no namespace transformation. Use these for Magewire-only classes that have no upstream Livewire counterpart and need to land in `dist/` alongside the ported code.

Note: in Magewire's own setup, Magewire-specific core code (`lib/Magewire/`, `lib/MagewireBc/`, etc.) lives in `lib/` as hand-written source and is NOT routed through Portman. Portman only handles the Livewire-derived code.

---

## Ignore Patterns

The `ignore` array uses Ant-style glob patterns (processed by `webmozart/glob`):

```php
'ignore' => [
    'Console/**/*',           // Exclude everything in Console/
    'Testing/**/*',           // Exclude testing utilities
    '!Testing/Testable.php',  // But keep this one file
]
```

Rules are applied in order. A `!` prefix negates (un-ignores) a previous exclusion.

---

## Typical Workflow

**Initial setup:**

```bash
composer require --dev magewirephp/portman
vendor/bin/portman init
# Edit portman.config.php
vendor/bin/portman download-source
vendor/bin/portman build
```

**Syncing with a new Livewire release:**

1. Update `version` / `version-lock` in `portman.config.php`
2. `vendor/bin/portman download-source` (updates `lib/Livewire/`)
3. `vendor/bin/portman build`
4. Review diff in `dist/` — check `portman/Livewire/` augmentation files for conflicts

**Active development (live rebuild):**

```bash
vendor/bin/portman watch
# Edit files in portman/Livewire/
# dist/ is rebuilt automatically on each save
```

**Adding a new class from upstream:**

- Remove it from `ignore` in `portman.config.php`
- Run `vendor/bin/portman build`

**Overriding an upstream method:**

- Create a matching file in `portman/Livewire/` with just that method
- Run `vendor/bin/portman build`

**Adding a Magewire-only class that belongs in `dist/`:**

- Place the file in the `additional` directory (configured in `portman.config.php`)
- Run `vendor/bin/portman build`
- It is copied as-is, no transformation applied

---

## Key Files in the Portman Package

| File | Purpose |
|------|---------|
| `app/Commands/BuildCommand.php` | Entry point for `build` |
| `app/Commands/DownloadSource.php` | Entry point for `download-source` |
| `app/Commands/WatchCommand.php` | Entry point for `watch` |
| `app/Portman/SourceBuilder.php` | Orchestrates the full build pipeline |
| `app/Portman/ClassMerger.php` | AST node visitor: merges augmentation into source |
| `app/Portman/Renamer.php` | AST node visitor: applies namespace/class renames |
| `app/Portman/TransformerConfiguration.php` | Maps config rules to transformer actions |
| `app/Portman/Configuration/Data/` | Spatie DTO classes for config validation |
| `stubs/portman.config.php` | Template config generated by `init` |

---

## Relationship to Magewire's Directory Structure

The directory paths below are the defaults used in Magewire's `portman.config.php`. All paths (source, augmentation, output) are configurable — these are not hardcoded by Portman.

| Directory | Config key | Role | Edit? |
|-----------|-----------|------|-------|
| `lib/Livewire/` | `directories.source` | Downloaded upstream source (Portman input cache) | No |
| `portman/Livewire/` | `directories.augmentation` | Augmentations — what to change in upstream source | Yes |
| `dist/` | `directories.output` | Portman build output (ported + merged + transformed) | No |
| `lib/Magewire/` | — | Hand-written Magewire core (not Portman-managed) | Yes |
| `lib/MagewireBc/` | — | Hand-written backwards compatibility layer | Yes |
| `lib/Magento/` | — | Hand-written Magento framework integrations | Yes |
| `src/` | — | Magento module structure (etc/, controllers, layout) | Yes |

**Never edit `dist/` or `lib/Livewire/` directly.** `dist/` is overwritten on every `portman build`. `lib/Livewire/` is overwritten on every `portman download-source`.

---

## Troubleshooting

| Problem | Solution |
|---------|---------|
| `dist/` folder missing after `composer install` | Run `../../bin/portman build` from inside the Magewire vendor directory |
| Augmentation warnings during build | Run `../../bin/portman download-source` first, then rebuild |

Note: commands are run as `../../bin/portman` when working from inside `vendor/magewirephp/magewire/`.
