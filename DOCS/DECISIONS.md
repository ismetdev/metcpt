# DECISIONS

Durable decisions and the reason each was made. Search for the topic, then read
that entry. Read the whole file only when reviewing the design.

New entries go at the bottom. Superseded entries stay, marked SUPERSEDED, so the
history of a reversal is readable.

Reconstructed on 2026-08-01 from git history, `readme.txt`, code comments, and
the Claude Code transcripts held on the office machine. Where a transcript did not
survive, the entry says the reason is the one the code documents.

---

<a id="d1"></a>
## D1. Listings live on WordPress Pages, not CPT archives

**Decision.** `/events/`, `/tenders/` and `/careers/` are ordinary WordPress Pages
built in Elementor, holding `[events_list]`, `[tenders_list template="a"]` and
`[careers_list]`. The plugin serves no plural URL.

**Why.** The listing pages need Elementor layout around them, headers, sections and
surrounding content that a CPT archive template cannot provide without rebuilding
the page shell. Putting the listing in a shortcode lets the editor own the page and
the plugin own the list.

Implemented in the three `shortcode-list.php` files.

---

<a id="d2"></a>
## D2. Keep the dormant `template-archive.php` files

**Decision.** Each module still ships a `template-archive.php` that hooks the
`archive_template` filter and renders a full standalone archive page, even though
`has_archive` is false.

**Why.** It is a working fallback for raw archive URLs such as
`?post_type=metcpt_event`, and it is useful for previewing content before the
Elementor Page exists. It is dormant, not dead. Do not delete it casually.

---

<a id="d3"></a>
## D3. Conditional module loading

**Decision.** [includes/core/class-metcpt.php](../includes/core/class-metcpt.php)
loads meta boxes and admin pages only when `is_admin()`, and shortcodes and
templates only when it is not.

**Why.** Performance. Roughly half the plugin never needs to parse on a given
request. Introduced in commit `f6e4031`, 2026-06-14.

**Watch out.** Core files load unconditionally, and that list includes
`error-log.php`, `dummy-data.php` and `docs-page.php`. Anything added to the core
block runs on every front-end request too.

---

<a id="d4"></a>
## D4. Cache-bust CSS with `filemtime()`, not the plugin version

**Decision.** `MetCPT::asset_version()` returns the stylesheet's last-modified
time, falling back to `METCPT_VERSION` if the file is missing.

**Why.** LiteSpeed served stale CSS for weeks after edits, because the version
query string only changed on a release. `filemtime()` changes the moment the file
does. Commit `5527ab4`, 2026-06-28.

---

<a id="d5"></a>
## D5. `has_archive => false` with singular rewrite slugs

**Decision.** All four CPTs register with `has_archive => false`. Rewrite slugs are
`event`, `tender`, `career`, singular. `metcpt_company` has `rewrite => false`
entirely and is admin only.

**Why.** The CPT archive was claiming the same URL as the Elementor Page, so
`/events/` randomly redirected to the homepage. Singular slugs leave every plural
URL free for the Pages. The Events single template's back button was changed at the
same time from `get_post_type_archive_link()` to the `metcpt_events_archive_url`
setting, matching what Tenders and Careers already did. Commit `4073e2b`,
2026-06-28.

**Consequence.** Hitting `/events/` before the Page exists returns 404. That is
correct behaviour, not a bug.

---

<a id="d6"></a>
## D6. Per-shortcode `template=` attribute for tenders

**Decision.** `[tenders_list]` and `[tenders_preview]` accept `template="a"` or
`template="b"`, which overrides the global Active Template setting.

**Why.** The shortcode rendered template A inside the Elementor editor but template
B on the live page, because the editor did not apply the global setting. Making the
choice explicit on the shortcode removed the mismatch. Commit `7cf9894`,
2026-06-28.

**Rule of thumb.** On any page where the tender layout matters, set `template=`
explicitly rather than relying on the global default.

---

<a id="d7"></a>
## D7. Normalise line endings to LF

**Decision.** [.gitattributes](../.gitattributes) forces LF on every text file and
marks binaries explicitly.

**Why.** The live server was accumulating doubled carriage returns, and diffs were
noisy. Commit `c864d38`, 2026-06-28.

---

<a id="d8"></a>
## D8. No FTP auto-deploy. Updates are admin-initiated only

**Decision.** The `deploy.yml` workflow that FTP-uploaded the plugin to staging on
every push to `main` was deleted during the rebrand. Sites update only when an
admin clicks Update on the Plugins or Updates screen.

**Why.** Owner's stated reason, 2026-07-09: pushing to `main` silently overwrote a
live site with no version bump and no Update button. Updates must be deliberate.
Confirmed again when the audit doc was cleaned up: "remove this, in the rebrand I
purposely removed it, I want the update to come from the update page or the plugin
page."

**Consequence.** A push to `main` ships nothing. Only a `v*` tag does.

---

<a id="d9"></a>
## D9. Build the release zip in GitHub Actions on Linux

**Decision.** [.github/workflows/release.yml](../.github/workflows/release.yml)
triggers on a `v*` tag, stages the plugin in a folder named exactly `metcpt`, zips
it with `zip -rq`, verifies `metcpt/metcpt.php` and the update library are present,
then publishes the Release with the zip attached.

**Why.** A zip built with PowerShell `Compress-Archive` wrote backslash path
separators. On extraction the live site got about 150 files literally named
`metcpt\...`, no valid `metcpt.php`, and WordPress reported "Plugin file does not
exist". That was a live outage. Linux `zip` writes forward slashes and cannot
repeat it. Commit `c885324`, 2026-06-28.

**Never** hand-build the zip. Never attach one manually.

---

<a id="d10"></a>
## D10. Rebrand Haraka to MetCPT with a full data migration

**Decision.** Renamed the plugin, folder, main file, class, constants, functions,
text domain and CSS prefixes from Haraka to MetCPT, and shipped a one-time
migration in [includes/core/migrate-from-haraka.php](../includes/core/migrate-from-haraka.php)
that moves post-type slugs (`hrk_*` to `metcpt_*`), options, the post meta key
`haraka_closing_notified`, the error-log table, and both cron hooks.

**Why.** To match the naming convention already used by the sibling MetTranslate
plugin. Renaming the folder makes WordPress see a brand-new plugin, so without the
migration all existing content and settings would have been orphaned. Commit
`c46ece6`, 2026-07-10.

**Ordering constraint that still matters.** The migration runs inline in
[metcpt.php](../metcpt.php) **before** `metcpt_error_log_create_table()`. If the
order flips, an empty `metcpt_error_log` table is created first and the legacy
table can no longer be renamed onto it.

**Also decided.** Rename the GitHub repo rather than create a fresh one, to keep
history, issues, releases and tags. GitHub redirects the old URL.

---

<a id="d11"></a>
## D11. Public repository, no GitHub token

**Decision.** The repo is public. The update checker runs unauthenticated. The
`METCPT_GITHUB_TOKEN` support in [metcpt.php](../metcpt.php) is kept but is a
no-op unless the constant is defined in `wp-config.php`.

**Why.** Owner's decision, 2026-07-09. A private repo needed a token in
`wp-config.php` with a hard expiry of 31 Dec 2026, and its silent failure mode was
that auto-updates would just stop with no error. Making the repo public removed the
token, the expiry and the failure mode.

---

<a id="d12"></a>
## D12. Rethrow after logging in the exception handler

**Decision.** The global handler in
[includes/admin/error-log.php](../includes/admin/error-log.php) calls
`restore_exception_handler()` and rethrows after writing the log row.

**Why.** It used to log and return, which turned a fatal into a blank white screen
with no message. The actual fatal it hid was the bundled update checker failing to
find `Parsedown`, because `.gitignore`'s blanket `vendor/` rule was excluding
`libs/plugin-update-checker/vendor/`. Both halves were fixed together: the ignore
rule was anchored to `/vendor/` so it only matches the repo root, and the handler
now surfaces the error. Commit `10f8c51`, 2026-07-03.

**Do not** make the handler swallow again.

---

<a id="d13"></a>
## D13. Delete settings that nothing reads

**Decision.** Removed six settings in 1.2.0: accent colour, organisation name,
events default order, events show-excerpt, default submission address, default
tender fee. Also removed the duplicate news-grid preview from the Posts tab.

**Why.** Each registered a field and saved a value that no template, CSS file or
meta box ever read. Verified three ways before removal: code trace, the live site,
and re-reading the shortcode source, which showed `[events_list]` uses its own
`order` and `show_excerpt` attributes and ignores the settings entirely. Commit
`733226e`, 2026-07-10.

**Kept, because they are alive.** Events archive URL, tenders page URL, careers
page URL, news grid link, news grid default content. All were suspected dead and
all proved to be driving real output.

---

<a id="d14"></a>
## D14. Uninstall preserves data by default

**Decision.** [uninstall.php](../uninstall.php) keeps its full cleanup block
commented out. Only the deliberate path deletes options and the error-log table.

**Why.** Preserve by default. Uninstalling should not destroy a company's content.
The option list inside the block was completed and two name bugs fixed in 1.2.0:
`metcpt_accent_color` was the American spelling of the real
`metcpt_accent_colour`, and `metcpt_events_page_url` was never the real
`metcpt_events_archive_url`. Both would have left rows behind.

---

<a id="d15"></a>
## D15. Version-gated auto-flush of rewrite rules

**Decision.** `metcpt_maybe_flush_rewrite_rules()` in
[includes/core/post-types.php](../includes/core/post-types.php) runs on
`admin_init`, flushes once when `metcpt_rewrite_version` does not match
`METCPT_VERSION`, then stamps the option. `MetCPT::activate()` stamps the same
option after its own flush so a fresh install does not flush twice.

**Why.** WordPress runs the activation hook on activation but not on update, so
after any release that changed CPT or rewrite registration the rules stayed stale
until someone visited Settings, Permalinks, Save. The owner asked for that manual
step to go away. Admin-only was chosen deliberately over `init`: updates are
admin-initiated, so the flush fires in the same session, and the expensive call
never runs on a front-end request. Commit `18882ec`, 2026-07-10.

Mirrors the existing version-gate pattern used by `metcpt_error_log_db_version`.

---

<a id="d16"></a>
## D16. Single templates must not emit their own document shell

**Decision.** The three `template-single.php` files call `get_header()` and
`get_footer()` and nothing else. They do not write a doctype, `<head>` or `<body>`.

**Why.** They used to do both, so every single Event, Tender and Career page
rendered two document shells, a duplicated `<title>` and `wp_head()` twice. Bad
HTML, bad SEO, extra weight. Fixed in 1.3.1, commit `5ac0c80`, verified visually
identical with before and after headless screenshots.

---

<a id="d17"></a>
## D17. Do not refactor the remaining CSS without a design reference

**Decision.** The `.mcpt-v2` design-token pattern in
[assets/style-tokens.css](../assets/style-tokens.css) is applied only to the news
grid and tenders preview template A. Events, Careers, `[tenders_list]` and
template B keep their `body`-prefixed selectors and `!important`.

**Why.** Hello Elementor's reset CSS out-specifies simple selectors, so the old
approach exists for a reason. Replacing it is a visual change with real regression
risk and no way to confirm "done" without a mockup to compare against. Events and
Careers also have only one layout each, so a per-page template override would mean
designing a second layout first, not just wiring up an attribute.

Deliberately deferred, not forgotten. Owner confirmed on 2026-07-09: "keep it".

**Also.** Tokens are scoped under a `.mcpt-v2` class, not bare `:root`, so they
cannot collide with Elementor's own custom properties or leak site-wide.

---

<a id="d18"></a>
## D18. Grep for shortcode tag collisions before adding one

**Decision.** Before registering any new shortcode tag, grep the whole
`wp-content/plugins/` tree for `add_shortcode` with that tag.

**Why.** A separate plugin, Category Post Listings, was registering its own
`tenders_preview`, `tenders_list`, `events_list` and `category_posts` under the
identical tags. WordPress silently lets the last registration win, with no warning.
It was deactivated on staging in 2026-06 after confirming it owned no post type,
table or admin page of its own.

---

<a id="d19"></a>
## D19. Writing standard

**Decision.** All writing follows [WRITING_RULES.md](WRITING_RULES.md): no em dash,
not verbose, clear and complete, no bombastic words, simple English. Commit
messages add conventional commit format.

**Why.** Owner's reason, 2026-08-01: long replies waste tokens and time. Set first
in the child theme repo, applied here for consistency across all three projects.

---

<a id="d20"></a>
## D20. Plan on the session model, code on Sonnet 5

**Decision.** Planning, research and review run on whatever model the session
starts with. All coding runs on Sonnet 5. After a plan is approved, stop before
touching any project file and ask the owner to run `/model sonnet`.

**Why.** Owner's decision, 2026-08-01. Enforced from [CLAUDE.md](../CLAUDE.md),
which is the only file Claude Code loads automatically. A rule written only in
`DOCS/` would sit unread.

---

<a id="d21"></a>
## D21. Read the docs partially, by file

**Decision.** [CLAUDE.md](../CLAUDE.md) states how much of each doc to read:
STATE and WRITING_RULES whole, DECISIONS searched by topic, PROJECT_LOG top 40
lines by default. PROJECT_LOG archives past-year entries once it passes about 200
lines.

**Why.** PROJECT_LOG is the one file that grows forever, and usually only the
newest entries matter. DECISIONS grows too, but its old entries still apply, so it
cannot be truncated the same way. Carried over from the child theme repo.

---

<a id="d22"></a>
## D22. Never run scripted bulk find-and-replace over source files

**Decision.** Edit files one change at a time with the editor tool. After any batch
operation, re-verify with a lint pass and a grep for damage.

**Why.** A PowerShell replace script flattened its argument arrays during the child
theme refactor on 2026-08-01 and silently corrupted two files. It was caught and
restored, but only because a verification pass ran afterwards. The Haraka rebrand
here used a mass replacement across 45 files and got away with it. Do not rely on
that.

---

<a id="d23"></a>

## D23. Move to a standard WordPress layout, without hand-splitting the template markup

**Decision.** In 1.4.0: the six `template-single.php`/`template-archive.php`
files moved to `templates/{module}/single.php` and `archive.php`, with a thin
`includes/{module}/templates.php` left behind that `require_once`s both. CSS moved
from `assets/` to `assets/css/`. `metcpt_page_has_shortcode()` moved to
`includes/core/helpers.php`. The update-checker setup in `metcpt.php` was wrapped
in `metcpt_bootstrap_updater()` so it no longer leaves a global variable behind.
Added `phpcs.xml.dist`, `composer.json`, `.editorconfig`, `LICENSE`. `release.yml`
and `.gitattributes` now exclude `DOCS/`, `CLAUDE.md`, `composer.json`,
`phpcs.xml.dist` and `.editorconfig` from the release zip.

**Why.** Each `template-*.php` file did three jobs in one: defined helpers,
registered a `single_template`/`archive_template` filter, and held 200 to 400
lines of page markup, with WordPress including the same file a second time to
render it (guarded by a `METCPT_*_LOADED` constant). That is surprising but was
working, tested code. Rather than hand-split each render function's markup into a
separate partial, which risks a transcription error across roughly 1,600 lines
under the no-bulk-find-and-replace rule (see [D22](#d22)), the file was relocated
as-is: `git mv` to `templates/`, one self-referencing path string updated per
file, and a loader left in `includes/` so the require order in
[class-metcpt.php](../includes/core/class-metcpt.php) barely changes. This gets
markup out of `includes/` without touching the tested render logic.

**Verification.** `git mv` reported all six files as 98 to 100 percent renames,
confirming content was preserved. Function-name diff against the pre-refactor
commit showed zero functions lost or renamed, one added
(`metcpt_bootstrap_updater`). All PHP files pass `php -l` on 8.2.29. Every
`METCPT_PATH`-relative reference was checked to resolve, including three sample
data literals in `dummy-data.php`'s dummy error log seeder that referenced the old
paths, since fixed. Tested live on `github-test.local`: homepage, all three single
templates, all three archive fallback URLs, the Settings page's four tabs
including the How-To tab's now-enqueued (rather than raw-echoed) stylesheet, the
post editor, and the dashboard, all with no PHP warnings or notices.

**Consequence.** The `templates/*/archive.php` files still emit their own
`<!DOCTYPE>`/`<head>`/`<body>` in addition to calling `get_header()`/`get_footer()`,
the same double-shell pattern fixed for single templates in [D16](#d16). Confirmed
pre-existing (identical behaviour before and after this move, via an A/B test
against the pre-refactor commit), not introduced by this decision, and not fixed
here since these templates are dormant (`has_archive => false`). Recorded as
[STATE.md](STATE.md#open-items) item 3.

---

<a id="d24"></a>
## D24. Events move from a CPT to native posts, single page owned by the theme

**Decision.** In 1.6.0: events are written as ordinary WordPress posts, not
`metcpt_event`. A post becomes an event when its `metcpt_is_event` meta is on
(set from a tick box in a new meta box on the Post screen); an event date is
also required for it to appear in `[events_list]`. Ticking the box also assigns
the post to the (auto-created if missing) `events` category, a convenience, not
the trigger. The plugin injects a short summary (date, time, venue, organiser)
via a `the_content` filter, never a `single_template` filter, so the
`met-hello-elementor-child` theme's own `single.php` keeps rendering everything
else unchanged. `metcpt_event` stays registered, `show_in_menu` gated by a new
option so it can be hidden after migration and shown again if ever needed.

Existing `metcpt_event` posts move over with a button on Settings > Events
(preview first, then run), never automatically on update. Migration changes
only `post_type` and adds meta; ID, content, meta, featured image, comments and
publish date are untouched. Old `/event/<slug>/` URLs 301 to the new post via a
`template_redirect` handler keyed on a stored `metcpt_legacy_event_slug`.

**Why.** Owner's decision, 2026-09-08: the `/events/` listing (an Elementor
Page holding `[events_list]`, see [D1](#d1)) was fine, but the CPT's own single
page (`templates/events/single.php`, 16 meta fields including three JSON
repeaters) looked nothing like the rest of the site. Native posts get the
theme's normal article design for free; the plugin only needs to add the event
facts and keep the listing sorted by date.

**Why `the_content`, never `single_template`.** The theme's `single.php` is a
complete, opinionated template with no content hooks of its own. A
`single_template` filter for `post` would replace it outright, exactly the
design this change exists to keep. `the_content` runs inside the theme's own
markup instead.

**The back-link problem, and why it needed a second filter.** The theme
exposes `met_hello_child_back_link_url()` (filterable, defaults to the
Newsroom archive) for exactly this kind of override, but `single.php` only
calls it when the post has no category — otherwise it links to the post's
primary category archive directly, with no filter around that branch. Every
event post carries the auto-assigned `events` category, so the exposed filter
never fired in testing; the back link went to `/category/events/` instead of
the intended `/events/`. Fixed without a theme edit by adding a second,
narrowly-scoped `term_link` filter: it rewrites the URL only for the `events`
term, only inside the main loop, only on a singular post, only when that post
is an event. Nothing else on the site resolves that term's link during that
window, so the Events category archive is unaffected everywhere else. Found
and confirmed during phase-by-phase verification on `http://v2`, 2026-09-08.

**Also decided, and deliberately not done.**
- Event posts are not filtered out of the homepage, newsroom, search, or any
  archive. An event is a post, so it behaves like one everywhere. Owner's
  choice: removes a site-wide query filter and any need to touch the theme.
- The CPT's rich fields (VIPs, itinerary, FAQs, guidelines, audience,
  capacity) stay editable on the post screen and render nothing on the front
  end. Owner writes the programme into the post body with the normal editor.
- `style-event-summary.css` is a separate sheet from `style-events.css` with
  its own enqueue gate, custom properties scoped to `.mcpt-event-summary`
  rather than `:root` — the theme already writes `:root` custom properties
  sitewide, so a second `:root` block would load in undefined order. Same
  rule as [D17](#d17), applied to a new sheet instead of an existing one.

**Consequence.** This is a deliberate, partial exception to the "no overlap"
boundary between the plugin and the theme recorded in
[STATE.md](STATE.md#scope-boundary): the plugin now renders inside a native
post via two tightly-scoped filters. No theme file was edited or needs a
matching release. `templates/events/single.php` and `archive.php` become
unreachable once the CPT is empty after migration; left on disk, not deleted,
same reasoning as [D2](#d2).

## D25. Bulk Posts Importer: CSV, not a form; own submenu; filename image matching; per-row AJAX

**Decision.** In 1.7.0: a new admin screen, MetCPT > Bulk Posts Importer,
turns a CSV of ready-written posts into native `post` entries. Four steps on
one page (Upload, Preview, Import, Done), all client-side transitions, no
page reload. Full reasoning and the rejected repeater-form design in
[PLAN/PRD-bulk-wordpress-posts-importer.md](../PLAN/PRD-bulk-wordpress-posts-importer.md)
and [PLAN/PLAN-bulk-posts-importer.md](../PLAN/PLAN-bulk-posts-importer.md)
(not shipped in the release zip).

**Why CSV, not a repeatable row form.** The PRD's first draft specced a
repeater: one form block per post, "add another post", a native media-modal
picker per row. Built as a plan, rejected before code, because it does not
remove the operator's actual bottleneck — typing 13 fields per post into a
browser, 15 to 50 times a month. A CSV does: the operator's existing ChatGPT
session already produces every field in one pass; the importer's job is to
turn that output into posts, not to be a second place to type it.

**Why its own submenu, not a Settings tab.** Every tab on the Settings page
renders inside one `<form action="options.php">` (see
[settings-page.php](../includes/admin/settings-page.php)). A file upload
control has no registered setting to post to and does not belong in that
form. `add_submenu_page()` under `metcpt-settings`, reusing the settings
page's header and field-row CSS classes so it reads as part of MetCPT without
adding a second visual language.

**Why images are matched by filename, not uploaded inside the importer.**
The PRD asked for a native media-modal picker per post. Rejected: that still
processes one image per browser action, and any sideload during the import
request would run image resizing inside the production host's limits (see
below). Instead, the operator uploads every image for the batch through the
ordinary Media > Add New screen in one action, and the CSV names the file.
`metcpt_bulk_import_resolve_attachment()` in
[bulk-import-csv.php](../includes/admin/bulk-import-csv.php) matches on the
sanitised basename WordPress stored the file under (also accepts a bare
attachment ID, to reuse an already-uploaded image). The Preview step reports
an unmatched filename before anything is written, so a typo is caught early,
not discovered after 15 drafts exist with no featured image.

**Why one AJAX request per row, not one form POST for the batch.** The
production site health report
([DOCS/CURRENT WEB INFO/ihsb-wordpress-site-health-report.md](CURRENT%20WEB%20INFO/ihsb-wordpress-site-health-report.md))
records a 30 second PHP time limit and a 40 MB `WP_MEMORY_LIMIT`. A batch of
20+ posts, each needing a term lookup, an insert, and postmeta writes, is not
guaranteed to finish in one request under those limits, and a mid-batch
timeout leaves no record of which rows made it. The browser instead drives
the loop: `metcpt_bulk_import_row` (in
[bulk-import-runner.php](../includes/admin/bulk-import-runner.php)) creates
exactly one post per call. A slow or failed row costs one row, not the batch,
and every row's result is visible as it happens rather than only at the end.

**Why a transient, not a new database table.** The PRD explicitly rules out a
second custom table (the error log is the only one, see
[STATE.md](../DOCS/STATE.md)). Parsed rows between Preview and Import live in
one `set_transient()` call, 12 hour TTL, deleted by WordPress on its own once
it expires. At the row counts this screen is built for (tens, not thousands)
this is simpler than a file with a resume cursor and has no cleanup to get
wrong.

**Duplicate safety.** Each imported post carries `metcpt_import_hash`, an MD5
of its title plus its CSV date. Before creating a row, the importer checks
for an existing post with that hash and reports "already imported" instead of
creating a second copy. This makes re-uploading the same CSV, deliberately or
after an interrupted run, safe by default — the same idea WordPress's own WXR
importer uses (a source post ID) to avoid duplicating a re-imported item,
adapted here since a CSV row has no post ID of its own yet.

**Yoast meta keys.** `_yoast_wpseo_focuskw`, `_yoast_wpseo_title`,
`_yoast_wpseo_metadesc`. Confirmed against Yoast SEO 28.5, the version
installed on local, staging, and production as of 2026-09-16. Written
unconditionally; inert if Yoast is not active, picked up if it is installed
later. Kept in one function,
`metcpt_bulk_import_yoast_meta_keys()`, so a future Yoast key change is a
one-line fix.

**Deviation from the PRD, stated per its own rule 4 (codebase wins,
conflict flagged rather than silently overridden).** The PRD asked for
`wp_kses_post` on the body column with no exception. This plugin instead
matches WordPress core's own rule: `wp_kses_post` only for a user without
`unfiltered_html`. An operator who already has `unfiltered_html` in the
normal post editor should not have embeds silently stripped only on this
screen.

**Also decided, and deliberately not done.**
- No column-mapping step. The CSV header is fixed and case-insensitive,
  because the ChatGPT prompt on the Upload step dictates the header the
  operator's own CSV will have. A mapping UI solves a problem this importer
  does not have.
- No image upload or sideload path inside the importer itself, see above.
  Adding one later (a zip upload, sideloaded per row) is not precluded, but
  was cut from this version since Media > Add New already solves it.
- `assets/js/` did not exist before this feature; every other admin script in
  the plugin is inline in the PHP that prints it. `bulk-import.js` is too
  large for that pattern (the whole four-step flow), so it is the first
  properly enqueued script, with `wp_localize_script()` for its nonce and
  AJAX URL rather than an inline `<script>` block.

**Consequence.** No new database table, no new option, no new shortcode tag,
no change to CPT registration, templates, or front-end CSS. All hooks are
`is_admin()`-gated (`admin_menu`, `wp_ajax_*`); nothing touches
`single_template`, `the_content`, or rewrite rules, so this feature cannot
collide with the Events native-post work in [D24](#d24).

**Two bugs found only by testing against a live site, not by review.**
Static review and an isolated logic test (both done before this) missed
both; a real run through wp-admin against the `v2` local site caught them.

- **Import and Done were two separate panels.** Finishing a run hid the
  per-row report behind a bare count, losing the Edit/View links the screen
  exists to produce. Fixed by merging them into one panel (`#mcpt-bi-step-3`
  serves both "3. Import" and "4. Done"); `showStep()` in
  [bulk-import.js](../assets/js/bulk-import.js) keeps it visible for both
  step numbers and only the step chip and a done-summary block toggle.
- **Every imported post's time was 8 hours off.** `strtotime()` reads PHP's
  own `date.timezone` ini setting to interpret a string with no timezone in
  it; `wp_date()` then converts that timestamp into the site's own
  timezone/gmt_offset option to format it. On this host the two settings
  disagree — PHP ini is `UTC`, the site is `Asia/Kuala_Lumpur` (UTC+8) — so
  chaining them applied the site's offset on top of an already-UTC reading.
  Fixed with `metcpt_bulk_import_normalize_date()` in
  [bulk-import-csv.php](../includes/admin/bulk-import-csv.php): parses with
  an explicit UTC `DateTimeZone`, so the literal numbers in the CSV land
  unchanged as the post's local time regardless of the PHP process's own ini
  setting, the same way wp-admin's own Publish date field treats what an
  editor types. `get_gmt_from_date()` (used unchanged, on the result) is
  safe here because it reads the site's own option, not PHP's ini.

Confirmed fixed by opening the created post in the block editor and reading
its own date picker, not only by reading the database back.
