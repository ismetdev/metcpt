# PROJECT LOG

What happened and when. Newest first.

**How to read this.** The top 40 lines are usually enough. Read further only when
the task is about older history. Once this file passes about 200 lines, move
entries older than the current year into `DOCS/archive/PROJECT_LOG-<year>.md` and
link the archive from here.

**Provenance.** Written on 2026-08-01 from git history with diffstats and full
commit bodies, the `readme.txt` changelog, code comments, the
`PROJECT_AUDIT_AND_CONTEXT.md` handoff doc, and the Claude Code transcripts held
on the office machine. Transcripts do not sync between machines: the sessions that
built v1.2.2, v1.3.0 and v1.3.1 ran on the home machine and are not readable from
here, so those entries come from commit bodies and the changelog, which are
unusually detailed. Dates are commit dates unless marked as an attribution.

---

## 2026-09-16, v1.7.0, Bulk Posts Importer (this machine, Ismet Office)

New admin screen, MetCPT > Bulk Posts Importer: upload a CSV of ready-written
posts and import all of them in one run as native WordPress posts. Built from
[PLAN/PRD-bulk-wordpress-posts-importer.md](../PLAN/PRD-bulk-wordpress-posts-importer.md),
with the input mechanism replaced mid-planning from a repeatable row form to a
CSV upload once it was clear the form only removed clicks, not typing. Full
reasoning in [DECISIONS.md D25](DECISIONS.md#d25).

Four steps on one page (Upload, Preview, Import, Done), no page reload between
them. Preview validates every row before anything is written: missing title,
content, or an unreadable date blocks that row; a missing image, empty SEO
field, or a category that will be created is a warning, not a block. Import
runs one `wp_insert_post()` per AJAX request
([includes/admin/bulk-import-runner.php](../includes/admin/bulk-import-runner.php)),
driven by the browser, so a slow or failed row costs one row and not the
30-second-limited batch. Featured images are matched by filename against the
Media Library rather than uploaded inside the importer, so no image
processing happens inside that same request. Re-uploading the same CSV skips
rows already imported, via an `metcpt_import_hash` postmeta fingerprint.

Also added: `assets/js/` (the plugin's first properly enqueued admin script,
`wp_localize_script()` for its nonce and AJAX URL), and
`assets/css/style-bulk-import.css`, built on the existing
`.metcpt-settings-wrap` tokens.

Yoast SEO meta keys (`_yoast_wpseo_focuskw`, `_yoast_wpseo_title`,
`_yoast_wpseo_metadesc`) confirmed against Yoast 28.5, installed on local,
staging and production. Target category confirmed as `Activity`, matching
production's `metcpt_news_category` setting.

**Two bugs found and fixed during visual verification** against the live
`v2` local site (logged into wp-admin, ran real CSVs through Preview and
Import, read the created posts back via the REST API and the block editor's
own date picker), before this shipped:

- The Import and Done steps were two separate panels. Finishing a run hid the
  per-row report — the Edit/View links the whole screen exists to produce —
  behind a bare count. Fixed by merging them into one panel; the report now
  stays on screen once the run finishes. See `showStep()` in
  [assets/js/bulk-import.js](../assets/js/bulk-import.js).
- Every imported post's time was 8 hours off. Cause: this host's PHP
  `date.timezone` ini is `UTC`, but the site runs `Asia/Kuala_Lumpur`
  (UTC+8); the original code parsed the CSV date with `strtotime()` (reads
  PHP's ini timezone) then formatted it with `wp_date()` (converts to the
  site's timezone), applying the site's offset on top of an already-UTC
  reading. Fixed with `metcpt_bulk_import_normalize_date()` in
  [includes/admin/bulk-import-csv.php](../includes/admin/bulk-import-csv.php),
  which parses with an explicit UTC `DateTimeZone` so the literal numbers in
  the CSV land unchanged as the post's local time — confirmed after the fix
  by opening the block editor's own date picker on a created post, not only
  by reading the database.

Shipped as `v1.7.0`, tag pushed 2026-09-16, `release.yml` build green.

**Acceptance confirmed by the site owner, 2026-09-16**, against `v2` with
real content: the owner's own CSV (from the ChatGPT prompt this screen
hands out) and the owner's own renamed images, uploaded through Media > Add
New first as the importer expects. Every post came in correct against the
ChatGPT source, images linked, no errors. This is the real backlog workflow
working end to end, not a synthetic test batch.

Also confirmed while shipping this release: the site's live WordPress
version is 7.1 and PHP is 8.2.29, both ahead of what `readme.txt` and
`STATE.md` claimed as tested — bumped both to match.

## 2026-09-08, v1.6.0, events move to native posts (this machine, Ismet Office)

Events are now written as ordinary WordPress posts instead of the `metcpt_event`
CPT, so the single page uses the `met-hello-elementor-child` theme's normal post
design instead of the plugin's own template. Full reasoning in
[DECISIONS.md D24](DECISIONS.md#d24). PRD at
[PLAN/PRD-events-as-posts.md](../PLAN/PRD-events-as-posts.md), not shipped in the
release zip.

A tick box in a new meta box on the Post screen
([includes/events/meta-boxes-post.php](../includes/events/meta-boxes-post.php))
marks a post as an event and reveals the same fields the CPT screen uses,
shared through `metcpt_event_meta_box_html()` and
`metcpt_save_event_meta_fields()` in `meta-boxes.php` (refactored to expose
both) so the two screens cannot list different fields by accident.
`[events_list]` now queries `post_type => post` filtered on `metcpt_is_event`
and `event_date`, both required to exist; the listing's own markup, CSS and
sorting are unchanged.

A summary block (date, time, venue, organiser) is injected into event posts via
a `the_content` filter, in
[includes/events/summary-block.php](../includes/events/summary-block.php),
placement configurable per post with a site-wide default in Settings > Events.
The same file adds Event JSON-LD on `wp_head` and points the theme's back link
at the events listing, the latter needing a second, narrowly-scoped `term_link`
filter after testing showed the theme's own filter hook never fires for a post
with a category (see D24 for why). A new stylesheet,
`style-event-summary.css`, has its own enqueue gate and scopes its custom
properties to `.mcpt-event-summary`, never `:root`.

A one-click migration
([includes/admin/migrate-events-to-posts.php](../includes/admin/migrate-events-to-posts.php))
converts existing `metcpt_event` posts in place (post ID, content, meta,
featured image, comments and publish date all preserved), button-triggered
from Settings > Events with a dry-run preview first. Old `/event/<slug>/` URLs
301-redirect to the new post via a `template_redirect` handler. The CPT stays
registered; its admin menu item is hidden by a new option once migration is
run, and can be shown again.

Verified in seven phases on `http://v2`: the post editor and field save/reload,
the single post front end (design, summary block, JSON-LD, back link, no
bleed to normal posts), stylesheet scoping, the `/events/` listing unchanged,
the settings panel, the migration run (data integrity, redirect, listing after
migration), and a regression pass over tenders, careers, the news grid, the
dashboard widget, the error log and the cron. One issue found and fixed during
phase 2 (the back-link filter, see above); everything else passed on first
check. `php -l` clean on every changed file (PHP 8.2.29).

Also added: `PLAN/` as a new development-only folder (this repo's PRDs),
excluded from the release zip the same way `DOCS/` is — matching entries added
to `.gitattributes` and `release.yml`'s copy-exclusion list and leak check.

## 2026-08-08, v1.5.0, conditional CSS and no Google Fonts (this machine, Ismet Office)

Front-end CSS now loads only where used. `enqueue_frontend_styles()` in
[class-metcpt.php](../includes/core/class-metcpt.php) registers all six sheets,
then enqueues each by condition: the module's single post
(`is_singular`), its CPT archive (`is_post_type_archive`), or a page carrying
its shortcode (`metcpt_page_has_shortcode`). The tokens sheet loads only as a
declared dependency of the tenders and posts sheets. Before this, all six loaded
on every page, about 75 KB, including pages with no MetCPT content.

`metcpt_page_has_shortcode()` in [helpers.php](../includes/core/helpers.php) now
also reads `_elementor_data`, because the listing Pages are Elementor built and
hold the shortcode in a widget, not in `post_content`. The archive guard uses the
same `is_post_type_archive()` check the archive templates use, so CSS and template
stay coupled.

Removed the render-blocking Google Fonts `@import` (DM Sans, DM Serif Display)
from [style-events.css](../assets/css/style-events.css). Font declarations keep
their existing fallbacks, so no replacement font ships.

Verified on `http://v2` against cache-busted responses: each single, list Page,
and the two shortcode Pages load only their own sheets; the homepage and other
pages load none. The two shortcode Pages (news_grid, category_posts) had no host
on local, so they were created via an authenticated REST session, checked, then
deleted. CPT archives resolve to the blog listing here because `has_archive` is
false, so no archive template renders and no CSS loads, which is correct. The two
remaining Google Fonts requests on pages are Elementor's own Inter and Roboto,
outside the plugin. `php -l` clean on both changed PHP files (PHP 8.2.29).
Browser automation was not available this session, so checks used cache-busted
server HTML, the same stylesheet set a browser would request.

## 2026-08-01, v1.4.0, layout refactor (this machine, Ismet Office)

Restructured to the standard WordPress layout. Templates moved from
`includes/*/template-single.php` and `template-archive.php` to
`templates/*/single.php` and `archive.php` (relocated as-is via `git mv`, not
hand-split, to avoid a transcription error across roughly 1,600 lines of markup
under the no-bulk-replace rule). CSS moved from `assets/` to `assets/css/`.
`metcpt_page_has_shortcode()` moved to a new `includes/core/helpers.php`. The
update-checker bootstrap in `metcpt.php` wrapped in `metcpt_bootstrap_updater()`
so it stops leaving a global. Added `phpcs.xml.dist`, `composer.json`,
`.editorconfig`, `LICENSE`. `release.yml` and `.gitattributes` now exclude
`DOCS/`, `CLAUDE.md`, `composer.json`, `phpcs.xml.dist` and `.editorconfig` from
the release zip. The How-To tab stylesheet moved from a raw `<link>` echo to a
normal `wp_enqueue_style()` call. Full reasoning in
[DECISIONS.md](DECISIONS.md#d23).

Verified: `php -l` clean on every file (PHP 8.2.29); function-name diff against
the pre-refactor commit showed zero lost or renamed, one added
(`metcpt_bootstrap_updater`); every `METCPT_PATH`-relative reference resolves,
including three sample-data literals in the dummy error log seeder that still
pointed at the old paths, fixed; release zip simulated and confirmed no
development files leak in. Tested live on `github-test.local`, authenticated:
homepage, all three single templates, all three archive fallback URLs, all four
Settings tabs (including the How-To tab CSS), the post editor, and the dashboard.
No PHP warnings or notices anywhere.

Found while moving the archive templates: they still double-wrap their document
shell (`<!DOCTYPE>` plus `get_header()`/`get_footer()`), the same bug fixed for
single templates in 1.3.1 ([D16](DECISIONS.md#d16)). Confirmed pre-existing via an
A/B test against the pre-refactor commit, not a regression. Not fixed, since these
templates are dormant. Recorded as [STATE.md](STATE.md#open-items) item 3.

Closed five open items: stale `@version` docblocks (7 files), no
coding-standards config, release zip shipping development files, no `LICENSE`,
and `PROJECT_AUDIT_AND_CONTEXT.md` overlapping `DOCS/` (replaced with a stub).

## 2026-08-01, docs (this machine, Ismet Office)

Added `DOCS/STATE.md`, `DOCS/DECISIONS.md`, `DOCS/PROJECT_LOG.md`,
`DOCS/WRITING_RULES.md` and `CLAUDE.md`, matching the pattern set in the
`met-hello-elementor-child` repo the day before. Reconstructed from the sources
listed above.

Seven open items recorded in [STATE.md](STATE.md#open-items). The CSS tech debt
was already known. The other six were found while reading: stale `@version`
docblocks, no coding-standards config, the release zip shipping development files,
no `languages/` directory, no `LICENSE` file, and
`PROJECT_AUDIT_AND_CONTEXT.md` now overlapping this DOCS set.

## 2026-07-11, v1.3.1, `5ac0c80` (home machine)

Stability and SEO fixes. The three single templates emitted their own doctype,
head and body **and** called `get_header()` and `get_footer()`, so every single
Event, Tender and Career page rendered two document shells, a duplicated `<title>`
and `wp_head()` twice. Dropped the hand-rolled shell. Verified visually identical
with before and after headless screenshots.

Also: fixed the deactivation hook, which was registered against a path that
resolved outside the plugin, so cron cleanup never ran; cleared both cron hooks;
added a per-request flood guard and dedup to the error logger; capped the
error-log table to the most recent rows, pruned daily; and removed a redundant
`SHOW TABLES` probe on every request.

## 2026-07-11, v1.3.0, `9512c63` (home machine)

Redesigned the admin Settings page into one continuous card with a blue accent,
replacing the earlier paper and gold theme. Contained the Error Log and How-To
pages so they no longer overflow to the right on small screens. Renamed every
leftover `hrk-` CSS class and `--hrk-` token to `mcpt-` across 31 files, markup,
CSS and JS together. The `hrk_*` post-type slugs used by the Haraka migration were
left alone on purpose.

## 2026-07-11, v1.2.2, `34d284c` (home machine)

Rewrote the plugin Description header to a clearer summary and dropped the
client-specific line.

## 2026-07-10, v1.2.1, `18882ec` (office machine)

Rewrite rules now flush themselves once per version on `admin_init`, so the manual
Settings, Permalinks, Save step after an update is gone. Admin-only was chosen
over `init` deliberately, see [DECISIONS.md](DECISIONS.md#d15). Prompted by the
owner asking, after the 1.2.0 release, why the flush could not be automatic.

## 2026-07-10, v1.2.0, `d339412`, `733226e`, `37ebb51` (office machine)

Three commits from a code review session.

`d339412`: fixed an undefined-array-key warning in the manual cron trigger, where
`$_GET['_wpnonce']` was read without an `isset()` guard. Because `cron.php` is in
scope for the plugin's own error handler, every hit self-logged a warning. Made
the dummy-data seeder idempotent so repeat clicks can no longer stack duplicate
posts.

`733226e`: removed six settings that were registered and rendered but never read,
plus the duplicate news-grid preview on the Posts tab. Completed and corrected the
`uninstall.php` option list, which had two wrong option names. Details in
[DECISIONS.md](DECISIONS.md#d13) and [#d14](DECISIONS.md#d14).

`37ebb51`: rewrote `README.md` to a standard structure and aligned the PHP floor.
The header said 7.4 and `readme.txt` said 8.0. No PHP 8.0-only syntax exists in
the code, so 7.4 is the accurate floor and `readme.txt` was corrected down.

Same session: the audit doc was brought back in line with reality. It still
described a two-workflow FTP deploy model that the rebrand had already deleted,
and a private-repo token with a 31 Dec 2026 expiry that a public repo no longer
needs. Both removed on the owner's instruction. See
[DECISIONS.md](DECISIONS.md#d8) and [#d11](DECISIONS.md#d11).

## 2026-07-10, v1.1.0, `c46ece6` (office machine)

Rebranded Haraka to MetCPT: 49 files, about 1,683 insertions and 1,566 deletions.
Folder, main file, class, constants, functions, text domain and CSS prefixes all
renamed, plus a one-time data migration for post-type slugs, options, one post
meta key, the error-log table and both cron hooks. Deleted the FTP `deploy.yml`
and rewrote the release workflow to trigger on a `v*` tag. See
[DECISIONS.md](DECISIONS.md#d10).

The folder rename could not be done by tooling: Windows held a lock on the
directory name while Local and VS Code were running, so the owner did it by hand
with the site stopped. Files inside the folder renamed fine.

## 2026-07-04, `2437e1c`, `8a26e5a` (home machine)

Redesigned the newsroom and tenders preview template A. Introduced
`assets/style-tokens.css`, scoped under a `.mcpt-v2` class rather than bare
`:root`. `[news_grid]` went from an asymmetric "1 featured plus 3 small" layout to
a uniform three-column grid with a floor of 6 cards. `[tenders_preview]` template
A was reskinned onto the same tokens, floor raised to 5, and its internal header
bar removed so rows render as independent cards.

Rows now always link to the tender's own permalink. A non-empty
`tender_document_url` used to override that silently, and three live tenders had
that field filled with unrelated company homepages, so preview rows were sending
visitors offsite. The data was cleared by hand through wp-admin; the code change
makes the shortcode immune to it.

## 2026-07-03, `10f8c51`, `575fa8a` (home machine)

Fixed the white screen on activation and on `wp-admin/update-core.php`. Two causes
at once, both fixed. See [DECISIONS.md](DECISIONS.md#d12).

Added `PROJECT_AUDIT_AND_CONTEXT.md`, the handoff doc that preceded this DOCS set.

## 2026-06-28 (home machine)

A dense day. `5527ab4` added `filemtime()` cache-busting after LiteSpeed served
stale CSS for weeks. `c864d38` added `.gitattributes` and normalised line endings
to LF. `c885324` added the tag-triggered release workflow after a
PowerShell-built zip corrupted the live plugin folder, an outage:
[DECISIONS.md](DECISIONS.md#d9). `4073e2b` fixed the slug collision that made
Pages redirect to the homepage. `7cf9894` added the `template=` attribute for
tenders after an editor versus live mismatch.

## 2026-06-14 and 2026-06-15 (home machine)

The architecture as it stands today was built in these two days: the loader class
and clean entry point (`7b79cdc`), the move to `includes/core/` (`7ffbd7c`),
conditional module and CSS loading (`f6e4031`, `26224eb`), the error log module
with its table, capture handlers, admin UI and cron purge (`c16506a`), the dummy
data seeder (`866ce8e`), the How-To docs tab (`a78abc5`), and the Plugin Update
Checker library (`8d90ade`).

Version numbers were reset from 2.3.0 and 2.4.0 down to 1.0.0 for the production
release (`75e6ebe`), on the reasoning that it was better to start fresh.

`eebea77` is worth remembering: one extra closing brace blanked the whole
WordPress admin. That is why every changed PHP file gets a `php -l` pass.

## 2026-05-13 (GitHub web UI, Ismet Fitri)

Initial commit and early README work, as Haraka.
