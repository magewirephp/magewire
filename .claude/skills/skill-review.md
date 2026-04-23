# Skill Review Report

**Date**: 2026-04-17
**Scope**: all skills under `vendor/magewirephp/magewire/.claude/skills/`
**Skills reviewed**: 6 — `magewire`, `magewire-architecture`, `magewire-backwards-compatibility`, `magewire-best-practices`, `magewire-javascript`, `magewire-portman`

## Overall Assessment

**PASS** — 0 findings across all six skills after cleanup.

| Stage | Total | High | Medium | Low |
|---|---|---|---|---|
| Initial review | 44 | 0 | 35 | 9 |
| After cleanup | **0** | 0 | 0 | 0 |

## What Was Fixed

1. **Descriptions (5 skills)** — converted YAML folded scalars (`description: >`) to single-line double-quoted form so the review parser reads them correctly. Eliminated 5 false-positive `description-short` findings.
2. **`magewire-best-practices` description** — reworded to lead with "Use when ..." and include "Trigger phrases include:" so the review regex detects trigger guidance.
3. **`requires` frontmatter** — added dependency declarations:
   - `magewire` → `requires: magewire-portman`
   - `magewire-best-practices` → `requires: magewire, magewire-architecture, magewire-javascript`
4. **Content duplication (22 findings)** — trimmed duplicated XML/PHP boilerplate from four rule files in `magewire-best-practices`, replacing with pointers into `magewire-architecture` and `magewire-javascript`:
   - `references/di.md` — removed the Feature DI registration XML and synthesizer DI XML
   - `references/features.md` — removed the `ComponentHook` class boilerplate
   - `references/synthesizers.md` — removed the synthesizer DI XML
   - `references/javascript.md` — removed the PHP fragment-utility boilerplate
5. **Directory conventions** —
   - `magewire-best-practices/rules/` → `magewire-best-practices/references/` (with all internal links updated)
   - `magewire-javascript/examples.md` → `magewire-javascript/references/examples.md`
   - `magewire-javascript/reference.md` → `magewire-javascript/references/reference.md`
6. **`$listeners` vs `#[On]` inconsistency** — `magewire/SKILL.md` now explicitly labels `$listeners` as legacy/discouraged, aligning with `magewire-best-practices/references/events.md`.
7. **Unusual directive removed** — dropped the "Always use a sub-agent to read rule files" line from `magewire-best-practices/SKILL.md` (behavior was not enforceable from memory/preferences).

## What Was Not Changed

- **Facades "Experimental" label in `magewire-architecture`** — left in place; whoever owns the 3.0 release decides whether to drop it.
- **Tutorial sections inside `magewire-architecture`** ("Adding a Custom Feature", "Custom Resolvers") — could be split into dedicated reference files, but that's a refactor, not a defect.
- **Content of 9 other rules files** in `magewire-best-practices/references/` — components, properties, lifecycle, templates, layout, security, events, performance, style — none of them triggered duplication findings and all contain distinct, opinion-focused content. Left intact.

## Verification

Final run of `node scripts/skill-review.mjs --all --repo .claude` produced:

```json
{
  "totalSkills": 6,
  "totalFindings": 0,
  "bySeverity": { "high": 0, "medium": 0, "low": 0 }
}
```