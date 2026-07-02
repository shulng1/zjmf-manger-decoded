# AGENTS.md - ZJMF Manager (魔方财务系统) v3.5.8

## What is this

Decoded (unencrypted) ThinkPHP 5 / ThinkCMF-based billing panel for IDC/hosting services. Version 3.5.8. All source is PHP; frontend assets live under `public/`.

## Key directories

| Path | Purpose |
|------|---------|
| `app/admin/` | Admin panel (controllers, models, views, config) |
| `app/home/` | Client-facing portal |
| `app/openapi/` | REST API (v1 routes) |
| `app/api/` | Additional API layer |
| `app/common/` | Shared helpers, models, logic, validators, job queue |
| `app/Service/` | Domain services (Auth, Billing, Cart, Product, Upgrade, Upstream, etc.) |
| `app/config/` | App-level config (cache, queue, templates, etc.) |
| `public/` | Web root — `index.php` is the entry point |
| `public/plugins/` | Addons: gateways, sms, mail, certification, addons |
| `public/themes/` | Frontend templates (clientarea) |
| `vendor/` | Composer dependencies (ThinkPHP, TCPDF, Guzzle, PHPMailer, PayPal SDK, etc.) |
| `data/route/` | Route definitions: `admin.php`, `home.php`, `openapi.php`, `api.php` |
| `data/runtime_cli/` | CLI runtime cache |
| `uploads/` | User uploads |
| `downloads/` | Downloadable files (database dumps, ticket files) |

## Entry points

- **Web**: `public/index.php` — sets `CMF_ROOT`, `APP_PATH`, `WEB_ROOT`, loads ThinkPHP
- **CLI**: `think` — CLI runner for cron jobs, queue workers, migrations
- **Admin URL**: configurable via `config("database.admin_application")` (default: `admin`)
- **API base**: `/v1/` prefix (see `data/route/openapi.php`)

## Cron / queue commands

```bash
# Daily cron (host suspension, invoice generation, cleanup)
php think cron

# Queue worker for marketing emails
php think queue:work --queue SendActivationMarketing --daemon --memory 128
```

## Database

- MySQL (utf8mb4). Config template at `public/install/config.php` with placeholders.
- Table prefix: configurable (default SQL uses `shd_` prefix in auth_rule dumps).
- Install SQL: `public/install/thinkcmf.sql`
- No migration tool — schema managed via SQL files in `downloads/database/`.

## Routing

- **Forced routing** enabled (`URL_ROUTE_MUST = true` in `public/index.php`).
- Routes split by module: `data/route/{admin,home,openapi,api}.php`.
- Admin routes use configurable admin app name prefix (default `/admin/`).
- OpenAPI routes: `/v1/` prefix with JWT auth.

## Architecture notes

- **Plugin system**: gateways (payment), sms, mail, certification, addons, servers (modules). Loaded from `public/plugins/` and `modules/` via namespace config in `app/app.php`.
- **Upstream integration**: connects to other ZJMF instances or WHMCS via API (`zjmf_finance_api` table). JWT-based auth with 90-min cache.
- **Hooks system**: `app/home/hooks.php` defines extensible hooks; `HooksController` methods are auto-discovered.
- **Multi-language**: zh-cn default, loaded via `app/common/lang/zh-cn.php`. Template strings use lang keys.
- **Session**: ThinkPHP session with `think` prefix, httponly, no secure flag.
- **JWT**: Firebase JWT library, key in `app/config` (`jwtkey`). Tokens cached 7200s.
- **File uploads**: stored in `uploads/` with configurable size (default 2MB) and extensions.

## Coding conventions

- Controllers follow ThinkPHP naming: `{Name}Controller.php` with action methods.
- Models in `app/common/model/` and per-module `model/` dirs.
- Validation in `validate/` dirs.
- Helper functions in `app/common.php` (9700+ lines) and `app/zjmf.php`.
- No strict typing — loose PHP throughout.
- Chinese strings in code (UI labels, messages) — not all are lang-keyed.

## Things agents often get wrong

- **CMF_ROOT** is `dirname(__DIR__)` from `public/index.php` (project root), NOT `__DIR__`.
- **Admin app name** is configurable — don't hardcode `/admin/` in routes.
- **Table prefix** varies by install — use `Db::name()` (prefix-agnostic) not `Db::table()` with hardcoded prefix.
- **No composer autoload for app code** — files are ThinkPHP-autoloaded by namespace convention.
- **`app/common.php` is huge** (9700+ lines) — contains JWT, cookie, upgrade logic, curl helpers, and domain functions. Search before adding new helpers.
- **Plugin namespaces** map to directories via `root_namespace` config in `app/app.php` — not standard PSR-4.
- **Debug mode**: `APP_DEBUG = true` in CLI (`think`), `false` in web (`public/index.php`).
- **uploads/** is for user content; `public/upload/` is for public-facing downloads. They are different paths.

## Testing

No test suite found. No phpunit.xml, no test directories.

## Formatting

- Prettier configured (`.prettierrc`, `.prettierignore`) for non-PHP assets only.
- No PHP CS Fixer or PHPCodeSniffer config found.
