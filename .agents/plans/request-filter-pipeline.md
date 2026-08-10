# Request Filter Pipeline

Feasibility investigation for `.claude/plans/PLAN.md` (Magewire V3 — Request Guard Pipeline),
scoped to `vendor/magewirephp/magewire` on branch `feature/request-filter-pipeline`.

> The original plan calls these "guards". This document uses **filter** throughout — see D8.

# TL;DR

**Feasible.** The framework already has the seam the plan asks for: minimal envelope parsing happens
in `MagewireUpdateRoute::parseRequest()` (checksum verify + `ComponentRequestContext` construction),
and component reconstruction only starts inside the `HandleRequests` mechanism.

All decisions are accepted. The pipeline is **owned by the `HandleRequests` mechanism**, which
registers it from `boot()` as a `before('request')` listener — hooking the same event
`handleUpdate()` already fires ahead of the reconstruct loop, rather than calling the pipeline by
hand. `HandleRequests` is Magewire-owned via a checked-in Portman augmentation, so this is an
ordinary edit plus a `portman build`.

Exception→HTTP-response mapping is already generic (`ExceptionManager::$specificExceptionHandlerPool`,
keyed by exception class per area), so `RequestFilterException` subclasses map to responses purely
through `di.xml`. Ordered third-party registration matches the existing `sortOrder` array-argument
convention (`Controller\Router::$routes`).

Three deviations from the original plan, all accepted: "guard" is renamed to **filter**; rate limiting
**splits** rather than converts (component scope cannot run before reconstruction); and the frontend
keeps the existing response contract — a rejection travels as the body the exception already writes,
marked presentable by a single response header.

# Tasks

- [x] Locate the current request lifecycle and the reconstruction boundary
- [x] Verify what parsing already happens before reconstruction
- [x] Verify exception→response machinery can carry filter rejections
- [x] Verify ordered third-party registration is possible without core changes
- [x] Assess rate-limiting migration feasibility
- [x] Create feature branch
- [x] Resolve D2 / D5 / D6 / D8
- [x] Decide HTTP statuses for non-rate-limit filters — resolved by design: each rejection declares
      its own status through `RequestFilterException::status()`
- [x] Implement `RequestContext` (+ generated `RequestContextFactory`)
- [x] Implement `RequestFilterInterface` + abstract `RequestFilterException`
- [x] Implement `RequestFilterPipeline` with ordered DI registration
- [x] Augment `HandleRequests`: inject pipeline, build context, check filters at top of `handleUpdate()`
- [x] Run `portman build`, verify regenerated `dist/Mechanisms/HandleRequests/HandleRequests.php`
- [x] Add request-scoped attribute bag
- [x] Re-parent `TooManyRequestsException` to `RequestFilterException`
- [x] Implement `RateLimitFilter` (request scope); retain component-scope hook
- [x] Generic frontend filter handling: present the response body when marked by the severity header
- [x] Register filters + exception handlers in `src/etc/frontend/di.xml` and `src/etc/adminhtml/di.xml`
- [x] Verify against real DI: registration, rejection, 429 mapping, template render
- [x] Playwright coverage for filter rejection paths
- [ ] Document the extension API on the docs site

## Delivered

**New — `lib/Magewire/Mechanisms/HandleRequests/`**

| File | Role |
|---|---|
| `RequestContext.php` | Request scoped view: request, components, token, fingerprint, attributes |
| `RequestAttributes.php` | Attribute bag (`set`/`get`/`has`/`unset`/`all`) |
| `RequestFingerprint.php` | Opaque origin identifier — session id, falling back to remote address |
| `Filter/RequestFilterInterface.php` | `check(RequestContext): void` |
| `Filter/RequestFilterPipeline.php` | Ordered execution; validates registrations at construction |
| `Filter/RequestFilterExceptionHandler.php` | Answers any rejection with the exception's status, message and severity header |

The pipeline is registered from `HandleRequests::boot()` as a `before('request')` listener.

**New — elsewhere**

- `lib/Magewire/Exceptions/RequestFilterException.php` — abstract base carrying `status()`,
  `severity()` and the customer-facing message, plus the `MESSAGE_SEVERITY_HEADER` constant.
- `lib/Magewire/Support/Enum/MessageType.php` — how much a customer-facing message weighs, moved out
  of `src/Model/Magewire/Notifier/NotificationTypeEnum.php` and renamed. It belongs with the reusable
  support enums rather than under the notifier, which is only one of the things that can render a
  message. `cssClass()` dropped with the move: presentation is the renderer's business.
- `NotificationTypeEnum` stays at its original path as a deprecated shim, keeping its cases,
  `getType()` and `getCssClass()`, plus a `toMessageType()` bridge. Nothing internal references it.
- `lib/Magewire/Features/SupportMagewireRateLimiting/Filter/RateLimitFilter.php` — request scope,
  publishes its outcome as the `rate_limit` attribute.
- `src/view/base/templates/magewire-features/support-magewire-request-filters/support-magewire-request-filters.phtml`

**New — Playwright scaffolding** (`/magewire/playwright/exceptions`, dev-only via `MagewireDeveloperAction`)

- `src/Controller/Playwright/Exceptions.php`, `src/view/frontend/layout/magewire_playwright_exceptions.xml`
  and `tests/magewire/playwright/exceptions/basic.phtml`.
- `src/Magewire/Playwright/Exceptions/Basic.php` — every method increments one counter, so a counter
  that never moves proves the rejection landed before reconstruction.
- `src/Magewire/Playwright/Exceptions/PlaywrightRejectionFilter.php` — rejects on demand, deciding
  purely from the pending method call in the parsed envelope. Dormant outside developer mode and
  scoped to this page's block name.
- `src/Magewire/Playwright/Exceptions/PlaywrightRejectionException.php` — carries an arbitrary status
  and severity, so one exception covers the whole range.

**Changed**

- `portman/Livewire/Mechanisms/HandleRequests/HandleRequests.php` — two constructor arguments and a
  `boot()` body registering the listener; `dist/` regenerated via `portman build`.
- `src/MagewireServiceProvider.php:90` — `$boot['containers']` was calling `$this->mechanisms->boot()`.
  Harmless today, since `setup()` fully boots containers first and the duplicate call short-circuits,
  but the key was reporting on the wrong service type and non-persistent containers would silently
  never boot here.
- `UpdateRequestRateLimiter` — added `validateWithRequestContext()`, deprecated
  `validateWithComponentRequestContext()`, keys now include the fingerprint.
- `SupportMagewireRateLimiting` — request-scope branch removed; component scope unchanged.
- `TooManyRequestsException` — re-parented; now carries `status()` (429) and a default message.
- `RateLimiterExceptionHandler` — deprecated in favour of the generic handler; kept for BC.
- `src/etc/frontend/di.xml`, `src/etc/adminhtml/di.xml` — pipeline registration, and the
  `TooManyRequestsException` pool entry repointed to `RequestFilterExceptionHandler`.
- `src/view/base/layout/default.xml` — rate-limiting feature block replaced by the filters block.

**Removed**

- `src/view/base/templates/magewire-features/support-magewire-rate-limiting/` — superseded by the
  generic template. Anything overriding that template in a theme must be repointed.

# Decisions

### D1 — Pipeline runs on the `request` event, as a "before" listener ✅ Accepted

Registered from the mechanism's `boot()` rather than called inline:

```php
// HandleRequests::boot()
before('request', function (array $payload): void {
    $this->requestFilterPipeline->check(
        $this->requestContextFactory->create([
            'components' => $payload,
            'token' => $this->request->getParam('token'),
        ])
    );
});
```

**Reasoning.** `handleUpdate()` already announces the request with `trigger('request', $requestPayload)`,
after the envelope has been parsed and verified and before the reconstruct loop. That is exactly the
moment filters want, so the event is the seam — no reason to hard-wire a call next to it.

`before()` rather than `on()` because `EventBus::trigger()` runs before-listeners ahead of the regular
ones (`dist/EventBus.php:61`), which puts filters in front of anything else listening to the same
event. Mechanisms also boot before features (`src/MagewireServiceProvider.php:90-92`), so the listener
is registered before any feature gets to add its own.

A rejection throws out of the listener, through `handleUpdate()` untouched, and lands in the
`catch (Exception)` of `Update::execute()` (`src/Controller/Magewire/Update.php:63`), which renders it
through the exception handler pool.

**Supersedes** two earlier placements: calling the pipeline from `Update::execute()`, and calling it
inline at the top of `handleUpdate()`. Both worked; both hard-wired a step the event system already
models.

**Still rejected: `MagewireUpdateRoute::match()`** — both `MagewireRoute::match()`
(`src/Controller/MagewireRoute.php:55-59`) and `MagewireUpdateRoute::match()`
(`src/Controller/MagewireUpdateRoute.php:88-92`) swallow exceptions and `return null`, so a filter
rejection would surface as a 404 instead of a 429.

### D2 — Pipeline is owned by the `HandleRequests` mechanism ✅ Accepted

`RequestFilterPipeline` and `RequestContextFactory` are constructor dependencies of `HandleRequests`,
and `boot()` registers the listener.

**Reasoning.** Filtering the request is core plumbing, not optional behaviour: the mechanism owns the
request lifecycle, and `boot()` is the hook it already has for exactly this. Magewire fully owns the
class body — the constructor, `boot()` and `handleUpdate()` are all defined in the checked-in
augmentation `portman/Livewire/Mechanisms/HandleRequests/HandleRequests.php`, not inherited from
upstream Livewire — so this is an ordinary edit to Magewire's own source.

**Cost, accepted:** `dist/Mechanisms/HandleRequests/HandleRequests.php` must be rebuilt and committed
alongside the change, and a future Livewire sync re-runs the build. Deterministic, since the
augmentation is version-controlled.

**Considered and rejected: a feature.** An intermediate revision put the same `before('request')`
registration in a `SupportMagewireRequestFilters` feature, which avoided touching Portman entirely.
Dropped because the architecture reserves features for what can be removed or replaced, and request
filtering is infrastructure. The ordering it bought was redundant anyway: `before()` already puts the
pipeline ahead of every listener on the event.

**New classes need no Portman involvement.** Composer maps `Magewirephp\Magewire\` to `src/`, `dist/`,
`lib/Magewire/` and `lib/MagewireBc/` (`composer.json:46-53`), so `RequestContext`,
`RequestFilterInterface` and `RequestFilterPipeline` live in `lib/Magewire/Mechanisms/HandleRequests/`
under the same namespace as the generated `HandleRequests` — exactly as `ComponentRequestContext`
already does.

### D3 — Request-scoped context named `RequestContext` ✅ Accepted

`Magewirephp\Magewire\Mechanisms\HandleRequests\RequestContext`, in
`lib/Magewire/Mechanisms/HandleRequests/`, sibling of the existing per-component
`ComponentRequestContext`.

**Reasoning.** `ComponentRequestContext` is per-component and carries only
`Snapshot`/`calls`/`updates` — no attribute bag, no HTTP-level data
(`lib/Magewire/Mechanisms/HandleRequests/ComponentRequestContext.php:16-62`). Different scope;
reusing the name would collide.

`RequestContext` carries: the `Http` request, the CSRF token, the request fingerprint, the array of
`ComponentRequestContext` objects, and the attribute bag. Built by a Magento-generated
`RequestContextFactory` — the same pattern as `ComponentRequestContextFactory`
(`src/Controller/MagewireUpdateRoute.php:47, 140-144`).

### D4 — `RequestFilterException extends \RuntimeException` ✅ Accepted

Abstract, namespace `Magewirephp\Magewire\Exceptions`, file in `lib/Magewire/Exceptions/`.

**Reasoning.** `RuntimeException` is an `\Exception`, so it satisfies
`ExceptionManager::handle(Exception $exception)` (`src/Model/App/ExceptionManager.php:44`) and the
`catch (Exception)` in `Update::execute()`. No core signature changes. Concrete subclasses become the
DI pool keys.

### D5 — Rate limiting splits; `TooManyRequestsException` is kept and re-parented ✅ Accepted

- **Request/isolated scope** → `RateLimitFilter implements RequestFilterInterface`.
- **Component scope** → stays on the `magewire:component:reconstruct` hook.
- **Both** throw `TooManyRequestsException`, which is re-parented to `RequestFilterException`.
- No new `RateLimitException`.

**Reasoning for the split.** `UpdateRequestRateLimiter::validateWithComponent(Component $component)`
keys on `$component->id()` (`lib/.../UpdateRequestRateLimiter.php:43-53, 66-69`) — a constructed
component, which cannot exist before reconstruction. The two scopes are mutually exclusive config
variants (`RateLimiterConfig::canRateLimitRequests()` / `canRateLimitComponents()`,
`lib/.../RateLimiterConfig.php:25-33`), so exactly one path is ever active and the split is
behaviourally clean.

**Re-parenting is safe and cheap.** `TooManyRequestsException` is Magewire's own hand-written class
at `lib/Magewire/Features/SupportMagewireRateLimiting/Exceptions/TooManyRequestsException.php:16` —
it is **not** ported from Livewire (upstream ships no rate limiting; no occurrence in `lib/Livewire/`
or `portman/lib/Livewire/`). So it lives in `lib/`, not `dist/`, and changing its parent needs no
Portman rebuild and cannot drift on a Livewire sync. `Exception` → `RuntimeException` widens the
lineage, so every existing `catch (\Exception)` and the `instanceof TooManyRequestsException` check in
`RateLimiterExceptionHandler.php:23` keeps working unchanged.

**Known semantic wrinkle.** The component-scope path throws a `RequestFilterException` subclass from
the reconstruct hook rather than from a filter. Same rejection, different enforcement point. Accepted
in exchange for keeping one exception type and one 429 handler; worth a sentence in the docs so nobody
reads `RequestFilterException` as "only ever thrown by a filter".

**Side benefit — bug fix.** Today the request-scope hook reads only `$payload[0]`
(`lib/.../SupportMagewireRateLimiting.php:43`), so a multi-component payload is only checked for its
first component. `RateLimitFilter` receives all `ComponentRequestContext` objects via `RequestContext`
and enforces correctly. This is a **behaviour change** on multi-component requests — call it out in
the changelog.

### D6 — Frontend reads the response body; nothing is published into the page ✅ Accepted

New feature template
`src/view/base/templates/magewire-features/support-magewire-request-filters/support-magewire-request-filters.phtml`
registering one `Magewire.hook('request', ({ fail }) => …)` handler that presents the response body
of a rejected request. The response contract is untouched.

**Reasoning.** The `fail` callback already receives the response body: the bundle calls it as
`{ status, content, preventDefault }` (verified in `src/view/base/web/js/magewire.csp.min.js`, the
network-failure path reads `c({status:503,content:null,preventDefault:()=>{}})`). Since the exception
already carries the message and the handler already writes it into the body, the message is available
client-side for free. Rendering a status→message map into every page would duplicate that data into
the DOM for a case that usually never fires.

**Presentability is declared, not guessed.** The handler sets a `X-Magewire-Message-Severity`
response header, and its presence is what makes a failed response presentable. Its value says how
much the message weighs. The frontend reads it through the `respond` callback, which receives the raw
`Response` (`p(v)` with `v = {status, response}` in the bundle), and applies it in `fail`.

**Header naming — the message, never the widget, never the cause.** `Message` names what the body
holds and `Severity` how much it weighs, so the frontend stays free to render it as a toast, a modal,
an inline banner or anything else without the wire format following along. Severity is the
established term — syslog (RFC 5424), structured logging, Sentry's `level` — and `message` matches
vocabulary on both sides: Magento's own `addWarningMessage` / `addErrorMessage`, and the notifier.

Two names were considered and rejected:

- `X-Magewire-Notification` named the notifier addon that renders it today. It would have gone stale
  the moment a rejection surfaced as a modal instead.
- `X-Magewire-Exception-Severity` named the cause, which is accurate for every current caller but
  describes a superset: an unhandled fault is an exception too, and must never mark itself
  presentable. It would also have locked the header to exception-originated messages.

Prefixed to match Livewire's own transport headers (`X-Livewire`, `X-Livewire-Navigate`,
`X-Livewire-Stream`) so the two read as one family. RFC 6648 (BCP 178) discourages the `X-` prefix
and modern hypermedia frameworks drop it (`HX-*` in htmx, `Turbo-*` in Turbo); staying recognisably
Livewire-shaped is judged worth more here. Nothing IANA-registered fits regardless — `Warning` was
deprecated by RFC 9111, and RFC 9457 Problem Details is a body format, which D6 rules out.

**Keeping the header count down.** The value is a structured field token (RFC 8941), so anything this
needs to carry later becomes a parameter on the same header (`warning; dismissible=?1`) rather than a
second header. One header is the budget — the compound name must not become a `X-Magewire-Message-*`
family.

**Values.** `RequestFilterException::severity()` returns a `NotificationTypeEnum`, reused rather than
introducing a parallel type since its cases are exactly the severities in play. For a rejection that
realistically means `error`, `warning` or `info`; `success` remains reachable but has no sensible
meaning under a severity frame.

Consequences:

- **Status is never consulted.** A filter picks whatever status its rejection deserves, including a
  `5xx`, and it still presents correctly. An earlier revision restricted presentation to `4xx` and
  added length/HTML heuristics to avoid showing an error page; the header makes all of that
  unnecessary, since only a response the server deliberately marked is ever shown.
- **Anything unmarked falls through** to Magewire's regular failure handling, so a stack trace or a
  Magento error page can never reach a notification.
- **The body stays exactly the message.** The header is the only wire-level addition; the JSON
  payload shape is untouched.

**Severity is per-rejection, on the exception.** `RequestFilterException::severity()` defaults to
`WARNING`. It lives on the exception rather than on `RequestFilterInterface` because a single filter
may reject for several reasons at different severities, and because status and message already live
there.

**Superseded.** An earlier revision had each filter declare `status()`, `message()` and `type()`
through a `RequestFilterNotificationInterface`, collected into a server-rendered map by a view model.
Both were removed: the exception is a better home for all three, and the map polluted the DOM.

### D7 — Branch `feature/request-filter-pipeline` off `main` ✅ Accepted

Created in `vendor/magewirephp/magewire` (own git repo, `main` at `abe934d chore(main): release 3.4.0`).
Renamed twice while D8 settled (`…-guard-` → `…-policy-` → `…-filter-`); local only, never pushed.

**Note.** `main` carried a pre-existing staged file `.agents/plans/pest-test-runner.md`; it followed
the checkout and is unrelated to this work. Do not include it in commits on this branch.

### D8 — "Guard" is renamed to "Filter" ✅ Accepted

| Original plan | This design |
|---|---|
| `RequestGuardInterface` | `RequestFilterInterface` |
| `RequestGuardPipeline` | `RequestFilterPipeline` |
| `RequestGuardException` | `RequestFilterException` |
| `RateLimitGuard` | `RateLimitFilter` |
| `ReCaptchaGuard` | `ReCaptchaFilter` |
| DI argument `guards` | DI argument `filters` |

The contract keeps the plan's original method name: `check(RequestContext $context): void`. It is a
check, not a transformation — `apply()` or `doFilter()` would overstate what the contract does.

**Reasoning — industry precedent.** For this exact pattern (an ordered chain that runs before request
processing and can abort it), the established names are:

| Term | Established in | Verdict |
|---|---|---|
| Middleware | Laravel, Express, Rack, Django, ASP.NET Core | Most universal; Laravel's rate limiter is `ThrottleRequests` middleware. **Name already taken here** — see below |
| Filter | `javax.servlet.Filter`, JAX-RS `ContainerRequestFilter`, ASP.NET Core MVC filter pipeline | Strong precedent; authorization filters run first and short-circuit — literally this pattern. **Chosen** |
| Guard | NestJS `CanActivate`, Angular route guards | Closest 1:1 to the contract, but rejected on taste |
| Interceptor | Spring `HandlerInterceptor.preHandle()`, NestJS | **Blocked** — Magento owns "Interceptor" for generated plugin classes |
| Policy | Laravel Policies, ASP.NET Core authorization policies, OPA, K8s admission policies | Standard, but almost always means *authorization* specifically; rate limiting is not authorization |

**Collision check.** `Middleware` is unavailable: the `PersistentMiddleware` mechanism
(`dist/Mechanisms/PersistentMiddleware/PersistentMiddleware.php:24`) already carries Laravel route
middleware (`Authenticate`, `Authorize`, `SubstituteBindings`) across Magewire requests, and a
`RequestMiddleware` beside it would be actively confusing. `Interceptor` is unavailable to any Magento
module. `Guard`, `Filter` and `Policy` are all free — no existing classes under `src/`, `lib/` or
`dist/` use those suffixes.

**Superseded.** An earlier revision of this document chose `Policy`. That choice rested on a semantic
argument rather than precedent; on review, `Policy` carries an authorization connotation across every
major framework that uses it, which misdescribes rate limiting.

**Owner:** @willemp

# Investigation

## VERIFIED

**Minimal parsing already exists, exactly as the plan scopes it.**
`MagewireUpdateRoute::parseRequest()` (`src/Controller/MagewireUpdateRoute.php:109-153`) reads
`php://input`, deserializes, verifies each snapshot checksum via `Checksum::verify()`, fires
`trigger('snapshot-verified')`, validates the resolver/handle prerequisite against Magento's layout
handle pattern, builds `Snapshot` and `ComponentRequestContext` objects, and extracts `_token`. No
component instantiation, hydration, or rendering. This is the plan's "allowed before guards" list,
already implemented.

**Reconstruction boundary is a single, clean line.** `HandleRequests::handleUpdate()` loops
`trigger('magewire:component:reconstruct', $componentPayload)`
(`portman/Livewire/Mechanisms/HandleRequests/HandleRequests.php:72`, generated to
`dist/Mechanisms/HandleRequests/HandleRequests.php:114`). Everything expensive — block
reconstruction, `store()`, `render()`, `toHtml()` — is downstream of it.

**Magewire owns `HandleRequests` outright.** The augmentation at
`portman/Livewire/Mechanisms/HandleRequests/HandleRequests.php` defines the constructor (lines 26-33)
and the whole of `handleUpdate()` (lines 60-111). Nothing in that method comes from upstream Livewire,
so editing it carries no merge risk on a Livewire sync.

**Rate limiting is entirely Magewire's own.** Upstream Livewire ships no rate-limiting feature —
`TooManyRequestsException`, `RateLimiter`, `UpdateRequestRateLimiter`, `RateLimiterConfig` and
`RateLimiterExceptionHandler` all live under `lib/Magewire/Features/SupportMagewireRateLimiting/`.
Nothing rate-limit-related exists in `lib/Livewire/` or `portman/lib/Livewire/`. This whole feature is
therefore free to restructure without Portman.

**Boot order.** `MagewireUpdateRoute::match()` calls
`$this->magewireServiceProvider->boot(RequestMode::SUBSEQUENT)` at
`src/Controller/MagewireUpdateRoute.php:84`, *before* `parseRequest()`. By the time `handleUpdate()`
runs, all Features and Mechanisms are booted and any DI-registered filter is resolvable.

**Exception→response mapping is already generic and area-scoped.**
`ExceptionManager::resolveExceptionHandler()` (`src/Model/App/ExceptionManager.php:99-135`) resolves
by exception class within a `subsequent`/`preceding` group, falling back to a default handler.
Registration is pure DI: `src/etc/frontend/di.xml:247-257`, `src/etc/adminhtml/di.xml:244-254`.
`RateLimiterExceptionHandler` returns a `callable(HttpResponseInterface): HttpResponseInterface`
producing a 429 (`lib/.../RateLimiterExceptionHandler.php:21-33`), consumed at
`src/Controller/Magewire/Update.php:65-75`. Adding filter exceptions is config-only.

**Ordered third-party registration has precedent.** `Controller\Router` takes an ordered `routes`
array argument with per-item `sortOrder` (`src/etc/frontend/di.xml:625-630`,
`src/Controller/Router.php:24-28`). The filter pipeline uses the same shape. Registration must be in
`src/etc/frontend/di.xml` / `src/etc/adminhtml/di.xml` — **never** global `src/etc/di.xml`; Magento
merges global into every area and makes per-area override impossible (documented at
`src/etc/frontend/di.xml:120-123`).

**CSRF runs before the mechanism.** `Update` implements `CsrfAwareActionInterface::validateForCsrf()`
(`src/Controller/Magewire/Update.php:96-99`), which Magento's `FrontController` invokes before the
action executes. Filters therefore run *after* CSRF validation — acceptable, and arguably correct: a
forged request is rejected before it can consume rate-limit budget.

**Namespace/directory mapping.** `composer.json:46-53` maps `Magewirephp\Magewire\` across `src/`,
`dist/`, `lib/Magewire/`, `lib/MagewireBc/`. New filter classes go in `lib/Magewire/` under the
`Mechanisms\HandleRequests` namespace without touching `dist/`.

## ASSUMED

- The request fingerprint is not currently computed anywhere; it needs a new small service. Session is
  reachable via standard Magento DI.
- Filters are synchronous and throw-only. No filter mutates the payload — the plan's contract returns
  `void`.
- Admin wants the same pipeline. `adminhtml` currently registers no `MagewireUpdateRoute` (only
  `MagewireUpdateRouteFrontend`, `src/etc/frontend/di.xml:627`), so admin filter registration may be
  moot until an admin update route exists. Worth confirming before duplicating DI.

## UNKNOWN

**Which HTTP statuses non-rate-limit filters claim.** D6 makes status the sole client-side
discriminator, so this has user-visible consequences. Narrowed by spec:

- Rate limiting → **429** with a `Retry-After` header (RFC 6585 §4). Unambiguous; already implemented.
- Verification / captcha → **403** is what is used in practice. No dedicated status exists.
- **428 Precondition Required is ruled out.** RFC 6585 §3 scopes it to conditional-request enforcement
  (`If-Match` and friends), not general pre-processing rejection.

Open: whether every non-rate-limit filter shares 403, or whether distinct statuses are minted so the
frontend can distinguish them.

**Other open items:**

- Whether the attribute bag needs to survive into the component lifecycle (e.g. a `ReCaptchaFilter`
  result readable from a component). If so, bridge it into `DataStore`
  (`dist/Mechanisms/DataStore.php`) rather than keeping it filter-local.
- Whether `RequestContext` has any role on the *preceding* (page-render) path. The plan covers XHR
  updates only; preceding renders have no envelope to parse.

## Found while implementing — not fixed

**Rate limiting shared one global bucket across all visitors.** Keys were built from the literal
prefix `'123@RL'` plus, under an isolated scope, the component id — with nothing identifying the
visitor. Every customer on the store therefore drew from a single budget, so a handful of active
sessions could lock out everyone. Fixed as part of this work: keys are now derived from
`RequestFingerprint`. Existing cached buckets are invalidated by the key change, which is harmless
since they decay within seconds.

**`hit()` ignored the configured decay.** `UpdateRequestRateLimiter` called `hit($key)` without a
decay argument, so entries were stored with the 60 second default while validation filtered by the
configured window. Any decay above 60 seconds silently expired early. Now passes the configured
decay. Fixed as part of this work.

**Two suite failures that are environment, not code.** Both were mistaken for regressions on this
branch and are worth recording so the next person does not re-diagnose them:

- The ten `lazy-loading.spec.js` cases under `server-rendered placeholder` are the only ones using
  Playwright's `request` fixture, and they failed with `unable to verify the first certificate`
  against a local Herd host. Node does not trust the cert; Chromium does, which is why the
  browser-driven cases in the same file passed throughout. Fixed by `ignoreHTTPSErrors: true` in
  `playwright.config.js` — test hosts are developer machines and CI containers, so verifying the
  chain buys nothing.
- The three `multiple-roots.spec.js` cases need `magewire/debug/enable` set. The detection feature
  opens with `if (!config('app.debug')) return;`, and `app.debug` maps to `magewire/debug/enable`
  (`src/etc/frontend/di.xml:558`). With the flag unset the hook returns before counting roots, so no
  exception block renders and the specs fail. Set on this install; worth stating in the Playwright
  README as a prerequisite alongside sample data.

**An exception thrown from a component method answered 500 with a PHP warning.** Fixed. Attempting
to cover this path is what surfaced it, in two parts:

- `ExceptionManager::handleWithBlock()` called `handle()` on a subsequent request and discarded its
  return value. For an exception bound to a response handler that return value *is* the response, so
  a `RequestFilterException` raised inside a component lost its status and severity header, was
  swapped for the inline exception template, and answered 200. It now rethrows when `handle()` yields
  a callable, handing the exception to the controller that knows how to render it. Exceptions with no
  handler are unaffected: `handle()` still throws for those, exactly as before.
- `MagewireManager::render()` read `$this->renderStack[$block->getNameInLayout()]` unconditionally.
  A failed mount or update never stacks a renderer, so the missing key fatalled on top of the
  original failure. It now returns the HTML untouched, keeping the exception manager's recovery
  intact.

Fixed alongside them: `render()` removed its entry with `array_pop()`, which drops the array's last
element rather than the keyed one. Blocks finish rendering innermost first, so that could evict a
sibling or parent still waiting to render. Now `unset()` by key.

Both live in `portman/Livewire/LivewireManager.php` and `src/Model/App/ExceptionManager.php`; `dist/`
regenerated. The restored Playwright case pins the outcome: a component-thrown rejection answers 418
with its message and `error` severity, presented exactly like a filter rejection.

**`LayoutLifecycle::routeForBlock()` can return the wrong type.** `array_search()` returns the array
*key*, so a block registered under a numeric route makes the method return `int` against its
`string|null` declaration, producing a `TypeError` during render. `routeForComponent()` has the same
shape (`lib/Magewire/Mechanisms/ResolveComponents/Layout/LayoutLifecycle.php:298-310`). Unrelated to
this work and left alone; worth a separate issue.

# Tooling

- **Skill** `wpoortman-investigation` — plan document format
- **Skill** `magewire-architecture` — mechanisms/features split, Portman boundary, area-scoped DI rules
- **Vendor source** `vendor/magewirephp/magewire` @ `3.4.0` (`main`, `abe934d`) — primary evidence
- **Portman** `portman.config.php`, `portman/Livewire/**` — augmentation ownership of `HandleRequests`
- **git** — branch state, upstream history
- **grep/find** — hook-usage tracing (`magewire:component:reconstruct`, `trigger('request')`),
  rate-limiting provenance, naming-collision checks, DI registration discovery
- **RFC 6585** — status code selection (429 §4, 428 §3)

# Change log

- 2026-08-07 — Initial feasibility investigation. Verdict: feasible; reconstruction boundary located;
  exception and registration machinery already sufficient.
- 2026-08-07 — Recorded D1–D7. Flagged plan step 6 as only partially achievable: component-scope rate
  limiting cannot run before reconstruction.
- 2026-08-07 — Noted existing `$payload[0]`-only bug in request-scope rate limiting; migration fixes it
  but changes multi-component behaviour.
- 2026-08-07 — Feature branch created off `main`.
- 2026-08-07 — D2 accepted: pipeline owned by the `HandleRequests` mechanism. D1 revised accordingly —
  the pipeline now runs at the top of `handleUpdate()` via the Portman augmentation, and
  `Update::execute()` is untouched. Verified Magewire owns the full `HandleRequests` body, so the edit
  carries no upstream merge risk.
- 2026-08-07 — D6 accepted: response contract frozen; filters contribute message/type through a
  server-rendered status map. Recorded that HTTP status is the only client-side discriminator.
- 2026-08-08 — Verified `TooManyRequestsException` is Magewire-owned, not ported from Livewire
  (upstream has no rate limiting). D5 revised: keep the exception, re-parent it to the pipeline base
  exception, drop the proposed `RateLimitException`.
- 2026-08-08 — D8 first accepted "policy" over "guard" on semantic grounds.
- 2026-08-08 — D8 revised to **filter** after reviewing industry precedent: `Policy` carries an
  authorization connotation that misdescribes rate limiting, `Middleware` collides with the existing
  `PersistentMiddleware` mechanism, and `Interceptor` collides with Magento plugin terminology.
  Renamed across the document; branch and plan file renamed to match.
- 2026-08-08 — Narrowed the HTTP status question with RFC 6585: 429 + `Retry-After` for rate limiting,
  403 in practice for verification, 428 ruled out.
- 2026-08-08 — Implemented. Pipeline, context, attribute bag, fingerprint, exception base, rate limit
  filter and generic frontend handling all in place; `dist/` regenerated by `portman build`. Verified
  against real DI: filter registered and ordered, rejection aborts the pipeline before later filters,
  invalid registrations refused at construction, `TooManyRequestsException` maps to a 429 response,
  and the feature template renders the notification map with a CSP nonce.
- 2026-08-08 — Recorded two rate-limiting defects fixed in passing (global shared bucket, decay
  ignored on `hit()`) and one pre-existing `LayoutLifecycle` type defect left alone.
- 2026-08-09 — D6 reworked: the status→message map is gone. `RequestFilterNotificationInterface` and
  `RequestFilterNotificationsViewModel` deleted, the layout argument dropped, and the frontend now
  reads the response body the exception already produces. `RequestFilterException` gained `status()`,
  a generic `RequestFilterExceptionHandler` replaced the rate-limit-specific one, and
  `TooManyRequestsException` carries its own default message. Re-verified: 429 response body is now
  "Too many requests! Please wait." and the template renders without any embedded data.
- 2026-08-10 — D6 refined again. Presentation is now declared by a response header rather than
  inferred: the 4xx-only rule and the length/HTML heuristics are gone, so
  a filter may reject with any status, `5xx` included. `RequestFilterException::type()` returns a
  `NotificationTypeEnum` (default `WARNING`, all four cases usable). Verified end to end: a rejection
  answers 429, body "Too many requests! Please wait.", header `warning`.
- 2026-08-10 — Playwright coverage added (`tests/Playwright/tests/request-filters.spec.js`, 8 cases).
  The update round-trip is stubbed with `page.route()` rather than provoked through a real filter:
  rate limiting is configuration driven and global, so tripping it for real would rate limit every
  other spec sharing the store, and stubbing covers severities and statuses no shipped filter
  produces yet. Full suite green at 68/68.
- 2026-08-10 — `NotificationTypeEnum` reinstated at its original path as a deprecated shim, so the
  move is no longer a BC break. It cannot extend `MessageType` — PHP enums are implicitly final and
  cannot participate in inheritance — so the cases are redeclared with identical values and bridged
  by `toMessageType()`. Its original `getType()` and `getCssClass()` are kept, since standalone calls
  like `NotificationTypeEnum::WARNING->getType()` were the realistic external usage.
- 2026-08-10 — Pipeline invocation moved off a hand-written call inside `handleUpdate()` and onto a
  `before('request')` listener. Registered from `HandleRequests::boot()`, which is the hook the
  mechanism already has for this; an intermediate revision used a feature instead, dropped because
  features are for what can be removed and filtering is infrastructure. D1 and D2 revised.
- 2026-08-10 — Fixed `MagewireServiceProvider::boot()` booting mechanisms twice under the
  `containers` key. Suite green at 85/85.
- 2026-08-10 — `NotificationTypeEnum` moved to `Support\Enum\MessageType`, dropping `cssClass()`.
  The old name and location tied a general-purpose value to the notifier addon; the enum says how
  much a message weighs and nothing about what draws it.
- 2026-08-10 — Added `ignoreHTTPSErrors: true` to `playwright.config.js`, so the suite runs green on
  a locally-trusted certificate without `NODE_TLS_REJECT_UNAUTHORIZED=0`.
- 2026-08-10 — Fixed the component-thrown rejection path: `handleWithBlock()` now rethrows when the
  exception has a response handler, and `render()` tolerates a missing render-stack entry while
  removing its own by key instead of popping. Component-thrown rejections now answer with their own
  status and severity, so one handler genuinely serves both entry points. Suite green at 85/85.
- 2026-08-10 — Added a dedicated `/magewire/playwright/exceptions` route with its own component,
  test-only filter and exception, plus `tests/Playwright/tests/exceptions.spec.js` (14 cases) driving
  the real server path: filter, exception, handler, status, body, severity header and presentation,
  across all four severities. Full suite green at 82/82.
- 2026-08-10 — Header settled as `X-Magewire-Message-Severity`, `type()` renamed to `severity()`.
  `X-` prefix kept to match Livewire's own transport headers, and the value defined as an RFC 8941
  structured field token so future needs extend the value rather than adding headers. Named for the
  message rather than the notifier addon (only today's UX — a modal must not force a wire format
  change) and rather than the exception behind it (an unhandled fault is an exception too, and must
  never mark itself presentable). Re-verified end to end.
