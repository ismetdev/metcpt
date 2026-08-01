# STATE

Where the project stands today. Update when the shipped version, the open work, or
the environment changes.

Last updated: 2026-08-01

## At a glance

| | |
|---|---|
| Shipped version | **1.3.1** |
| Repository | https://github.com/ismetdev/metcpt (public) |
| Branch | `main` |
| Tags | `v1.0.0`, `1.0.3`, `v1.0.4`, `v1.1.0`, `v1.2.0`, `v1.2.1`, `v1.2.2`, `v1.3.0`, `v1.3.1` |
| Type | WordPress plugin, formerly named "Haraka" |
| Requires | WordPress 6.0+ (tested to 6.5), PHP 7.4+ |
| Text domain | `metcpt` |
| License | GPL-2.0-or-later |

Note: `1.0.3` is missing its `v` prefix. It is a historical mistake, left alone.
Every tag from `v1.0.4` onward uses `v`.

## What the plugin does

Registers four custom post types for corporate content, gives each a structured
admin editing screen, and exposes shortcodes that render the listings on ordinary
WordPress Pages. It also ships single-post templates, an error log, a dashboard
widget, a docs page, and a daily cron that emails about closing tenders.

| CPT | Single slug | `has_archive` | `public` |
|---|---|---|---|
| `metcpt_event` | `event` | false | true |
| `metcpt_tender` | `tender` | false | true |
| `metcpt_career` | `career` | false | true |
| `metcpt_company` | none | false | false (admin only) |

Registered in [includes/core/post-types.php](../includes/core/post-types.php).

## Layout

WordPress pins three files at the plugin root and they must not move or be
renamed: [metcpt.php](../metcpt.php) (carries the plugin header, and its path is
stored in the `active_plugins` option and used by the update checker),
[uninstall.php](../uninstall.php), and [readme.txt](../readme.txt).

Everything else is already modular:

```
metcpt.php              bootstrap: constants, updater, migration, MetCPT::instance()
uninstall.php           option and table cleanup, most of it deliberately commented out
readme.txt              WordPress-format readme and changelog
assets/                 9 stylesheets, enqueued from class-metcpt.php
includes/core/          post types, main class, Haraka migration
includes/admin/         settings, docs page, error log, dashboard widget, cron, dummy data
includes/events/        meta boxes, shortcode, single and archive templates
includes/tenders/       meta boxes, shortcodes, template A and B, single and archive templates
includes/careers/       meta boxes for career and company, shortcodes, templates
includes/posts/         shortcodes over native WordPress posts
libs/plugin-update-checker/   third party, YahnisElsts PUC v5.7, do not edit
DOCS/                   this documentation
.github/workflows/      release.yml
```

## Surfaces

| Surface | File | Status |
|---|---|---|
| `[events_list]` | [includes/events/shortcode-list.php](../includes/events/shortcode-list.php) | Shipped 1.0.0 |
| `[tenders_list]` | [includes/tenders/shortcode-list.php](../includes/tenders/shortcode-list.php) | Shipped 1.0.0, `template=` attribute added 1.0.4 |
| `[tenders_preview]` | [includes/tenders/shortcode-preview.php](../includes/tenders/shortcode-preview.php) | Redesigned 1.1.0 |
| Tenders template B | [includes/tenders/shortcode-template-b.php](../includes/tenders/shortcode-template-b.php) | Shipped 1.0.4 |
| `[careers_list]`, `[careers_preview]` | [includes/careers/shortcode-list.php](../includes/careers/shortcode-list.php), [shortcode-preview.php](../includes/careers/shortcode-preview.php) | Shipped 1.0.0 |
| `[news_grid]` | [includes/posts/shortcode-news-grid.php](../includes/posts/shortcode-news-grid.php) | Rebuilt to a 3-column grid in 1.1.0 |
| `[category_posts]` | [includes/posts/shortcode-general.php](../includes/posts/shortcode-general.php) | Shipped 1.0.0 |
| Single event, tender, career | `includes/*/template-single.php` | Duplicate document shell removed in 1.3.1 |
| CPT archive fallback | `includes/*/template-archive.php` | Dormant, `has_archive` is false. Not dead code |
| Settings page | [includes/admin/settings-page.php](../includes/admin/settings-page.php), [settings-fields.php](../includes/admin/settings-fields.php) | Redesigned 1.3.0 |
| Docs (How To) page | [includes/admin/docs-page.php](../includes/admin/docs-page.php) | Shipped 1.0.4 |
| Error log | [includes/admin/error-log.php](../includes/admin/error-log.php), [error-log-page.php](../includes/admin/error-log-page.php) | Flood guard and row cap added 1.3.1 |
| Dashboard widget | [includes/admin/dashboard-widget.php](../includes/admin/dashboard-widget.php) | Shipped 1.0.0 |
| Daily tender cron | [includes/admin/cron.php](../includes/admin/cron.php) | Nonce guard fixed 1.2.0, deactivation cleanup fixed 1.3.1 |
| Dummy data seeder | [includes/admin/dummy-data.php](../includes/admin/dummy-data.php) | Made idempotent 1.2.0 |
| Haraka migration | [includes/core/migrate-from-haraka.php](../includes/core/migrate-from-haraka.php) | Shipped 1.1.0, runs once, guarded by an option |
| Auto-update | [metcpt.php](../metcpt.php), [.github/workflows/release.yml](../.github/workflows/release.yml) | Public repo, no token |

## Scope boundary

Two things are most likely to break by accident.

**1. The URL ownership model.** The plural URLs `/events/`, `/tenders/`,
`/careers/` are ordinary WordPress Pages built in Elementor that hold the
shortcodes. The plugin owns only the singular URLs `/event/<slug>/`,
`/tender/<slug>/`, `/career/<slug>/`. That is why every CPT is registered with
`has_archive => false` and a singular rewrite slug. Turning archives back on makes
the CPT archive fight the Page for the same URL, and the Page starts redirecting
to the homepage. See [DECISIONS.md](DECISIONS.md#d5).

**2. The global exception handler.** [includes/admin/error-log.php](../includes/admin/error-log.php)
calls `set_exception_handler()` and `set_error_handler()` on every request, admin
and front end. It only logs errors whose file sits under the plugin directory, but
it is installed site-wide either way. It once turned a fatal error into a silent
white screen. It now rethrows after logging. Do not make it swallow again.

The sibling child theme owns the native post, category, tag and date archives. It
does not read any `metcpt_` option. Checked from both sides on 2026-07-29.

## Environment

- Local dev site: `github-test` (Local by Flywheel), at `https://github-test.local`.
  HTTP fails, use HTTPS. This repo working copy is the live plugin folder of that
  site.
- Staging: `https://v2.iiumholdings.com.my`. Server path
  `/home2/iiumhold/v2/wp-content/plugins/metcpt/`. Access via cPanel only. SSH
  was set up but has never connected, so data-level fixes on staging go through
  wp-admin by hand.
- Production: `https://iiumholdings.com.my`. Not running MetCPT as of 2026-08-01.
- Host stack: LiteSpeed, Elementor, Hello Elementor with the
  `met-hello-elementor-child` child theme.
- Two machines, and Claude Code transcripts do not sync between them. Git author
  names identify which machine did what:
  - **Ismet Home**: v1.0.0 through v1.1.0, and v1.2.2 through v1.3.1.
  - **Ismet Office**: v1.2.0 and v1.2.1, and this documentation.
  - **Ismet Fitri**: commits made through the GitHub web UI.
  Run `git pull --ff-only origin main` before editing on either machine.

## Related projects

Same site, separate repos, separate release cycles.

- **met-hello-elementor-child**, `wp-content/themes/met-hello-elementor-child`.
  Designs native blog posts and their archives. This DOCS set and `CLAUDE.md` are
  copied from that repo's pattern.
- **MetTranslate**, `wp-content/plugins/mettranslate`. English to Malay
  translation. Its build plan states as a standing requirement that it must not
  interfere with the core editor, Hello Elementor, the child theme, or MetCPT
  features. Same release pattern: Plugin Update Checker plus a tag-triggered
  GitHub Action.
- **Category Post Listings**, deactivated on staging in 2026-06. It registered
  `tenders_preview`, `tenders_list`, `events_list` and `category_posts` under the
  same tags as MetCPT. Before adding any new shortcode tag, grep the whole
  `wp-content/plugins/` tree for it first.

## Open items

1. **CSS `!important` and `body`-prefix tech debt.** The `.mcpt-v2` design-token
   pattern in [assets/style-tokens.css](../assets/style-tokens.css) is applied
   only to the news grid and tenders preview template A. Events, Careers,
   `[tenders_list]` and template B still use `body`-prefixed selectors and
   `!important` to beat Hello Elementor's reset. Deliberately deferred: extending
   it needs a design reference to verify against, and Events and Careers have only
   one layout each, so a per-page template override would mean designing a second
   layout first. See [DECISIONS.md](DECISIONS.md#d17).
2. **Stale `@version` docblocks.** Seven files still say `@version 1.0.4` or
   `1.2.1` while the plugin is at 1.3.1. Cosmetic, but misleading.
3. **No coding-standards config.** No `phpcs.xml.dist`, no `composer.json`, no
   `.editorconfig`. The code has never been checked against WordPress Coding
   Standards.
4. **The release zip ships development files.** `release.yml` excludes only
   `.git`, `.github`, `.gitattributes`, `.gitignore`, `.claude`, `node_modules`
   and `build`, so `PROJECT_AUDIT_AND_CONTEXT.md`, `README.md` and now `DOCS/` go
   out to every site.
5. **No `languages/` directory.** The text domain `metcpt` is declared in the
   plugin header but no `.pot` exists, and most admin strings are plain literals
   rather than wrapped in translation functions.
6. **No `LICENSE` file.** The header and `readme.txt` both say GPL-2.0-or-later,
   but the licence text is not in the repo. MetTranslate ships one.
7. **`PROJECT_AUDIT_AND_CONTEXT.md` overlaps this DOCS set.** It was the single
   handoff doc before DOCS existed. Its content is now split across
   [STATE.md](STATE.md), [DECISIONS.md](DECISIONS.md) and
   [PROJECT_LOG.md](PROJECT_LOG.md). Keeping both means two files to update.

## How to cut the next release

1. Bump the version in [metcpt.php](../metcpt.php) in two places: the `Version:`
   header and the `METCPT_VERSION` constant. They must match each other and the
   tag.
2. Bump `Stable tag:` in [readme.txt](../readme.txt) and add a `= X.Y.Z =` block
   to the changelog, plus an upgrade notice.
3. Lint every changed PHP file. PHP CLI is not on PATH, find it at
   `C:\Users\IIUM Holdings\AppData\Roaming\Local\lightning-services\php-*\bin\win64\php.exe`.
4. Commit, `git fetch`, confirm no divergence, push `main` first.
5. `git tag -a vX.Y.Z -m "..."` then `git push origin vX.Y.Z`.
6. `release.yml` builds `metcpt.zip` on Linux inside a folder named exactly
   `metcpt`, checks `metcpt/metcpt.php` and the update library are present, then
   publishes the Release with the zip attached.
7. Never build the zip with PowerShell `Compress-Archive`. It writes backslash
   path separators that corrupt on extraction. That caused a live outage.
   See [DECISIONS.md](DECISIONS.md#d9).
8. On each site: Plugins or Dashboard, Updates, click Update. Then purge LiteSpeed
   and check in an incognito window. Rewrite rules flush themselves since 1.2.1.
