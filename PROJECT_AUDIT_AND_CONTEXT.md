# MetCPT Plugin — Project Audit & Working Context

> **Purpose of this file:** This is a handoff/context document for continuing work on the MetCPT WordPress plugin (built for IIUM Holdings Sdn Bhd) inside Claude Code. It captures the architecture, the deployment model, recent fixes, known gotchas, and open items. Read this first before making changes.

---

## 1. What MetCPT is

A proprietary WordPress plugin — a "Corporate Content Hub" — that manages Events, Tenders, Careers, and News for IIUM Holdings. It registers four custom post types and exposes shortcodes that are placed on WordPress Pages (built in Elementor). See `README.md` for the full feature list and shortcode reference.

Custom post types (registered in `includes/core/post-types.php`):

| CPT | Slug (single post) | has_archive |
|---|---|---|
| `metcpt_event` | `event` | false |
| `metcpt_tender` | `tender` | false |
| `metcpt_career` | `career` | false |
| `metcpt_company` | (none) | false |

---

## 2. The two-site model (IMPORTANT)

There are two live WordPress sites, with **different update mechanisms**. Do not confuse them.

- **Staging: `v2.iiumholdings.com.my`** — the working/dev site. Updated by **FTP auto-deploy**: every push to `main` triggers a GitHub Action that FTPs the repo to the server. Fast, automatic. This is where all current development happens.
- **Production: `iiumholdings.com.my`** — the real company site. NOT yet running MetCPT at time of writing; it goes live after management approval. Production is updated the **industry-standard way**: publish a versioned GitHub Release → WordPress shows an "update available" button → click to update. No FTP auto-deploy on production.

**Source of truth = the GitHub repo (`github.com/ismetdev/metcpt`, private).** Never edit files directly on either live server except in a genuine emergency (and if you do, immediately commit the same change to the repo).

---

## 3. Deployment & release infrastructure

Two GitHub Actions workflows in `.github/workflows/`:

- **`deploy.yml`** — on every push to `main`, FTP-deploys the repo to the staging server at `/v2/wp-content/plugins/metcpt/`. Uses `actions/checkout@v6` and `SamKirkland/FTP-Deploy-Action@v4.4.0`. FTP credentials are stored as GitHub Actions secrets (`FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_PORT`), never in code.
- **`release-zip.yml`** — on release *publish*, builds a clean, correctly-structured `metcpt.zip` on Linux and attaches it to the release automatically. This is the zip WordPress installs on production.

### Auto-update mechanism (private repo)
The plugin uses the Plugin Update Checker library (`libs/plugin-update-checker/`), configured in `metcpt.php`. Because the repo is **private**, WordPress must authenticate to read releases. This is done via a GitHub fine-grained token (read-only, Contents + Metadata) stored in `wp-config.php` as the constant `METCPT_GITHUB_TOKEN` — **never committed to the repo**. The code reads it conditionally:

```php
if ( defined( 'METCPT_GITHUB_TOKEN' ) && METCPT_GITHUB_TOKEN ) {
    $metcpt_update_checker->setAuthentication( METCPT_GITHUB_TOKEN );
}
```

The current token expires **31 Dec 2026** — set a reminder to rotate it before then, or updates will silently stop.

### Release SOP (for production updates)
1. Bump the version in `metcpt.php` in **two** places: the `Version:` header comment AND the `METCPT_VERSION` constant. They must match the release tag.
2. Commit and push.
3. On GitHub → Releases → Draft new release → create tag (e.g. `v1.0.5`) → **Publish**. **Do NOT attach a zip manually** — `release-zip.yml` builds and attaches it automatically.
4. On production WordPress → the update button appears → click to update.

**NEVER build the release zip with Windows PowerShell `Compress-Archive`.** It writes backslash path separators that corrupt on Linux/WordPress extraction (this caused a live outage — see §5). The `release-zip.yml` workflow builds it correctly on Linux; rely on that.

---

## 4. Architecture: Pages + shortcodes (the URL ownership model)

Listings are served by **WordPress Pages** (Elementor-built) containing shortcodes — NOT by CPT archives. This is deliberate.

| URL | Page contains | Notes |
|---|---|---|
| `/events/` | `[events_list ...]` | Elementor page |
| `/tenders/` | `[tenders_list template="a"]` | table layout, explicit template |
| `/careers/` | `[careers_list ...]` | table layout |
| `/` (homepage) | `[tenders_preview]` (Template A), `[news_grid]`, career preview | teasers — see §5 for the redesigned Template A/news grid look |

The CPT archives (`has_archive => false`) are **disabled** so they don't fight the Pages for these URLs. Each module also ships a `template-archive.php` file — these are now **dormant** (archives are off) but kept in place; they are NOT dead code to be deleted casually.

**Back buttons** on single CPT pages point at the configured Page URL via settings (`metcpt_events_archive_url`, `metcpt_tenders_page_url`, `metcpt_careers_page_url`), NOT at the CPT archive.

---

## 5. Recent fixes — context for why things are the way they are

1. **Cache-busting** — CSS/JS enqueues in `includes/core/class-metcpt.php` use `filemtime()` (via an `asset_version()` helper) so a changed file always gets a new URL. This fixed a recurring stale-CSS problem where LiteSpeed served old CSS for weeks.
2. **Line endings** — a `.gitattributes` normalizes all text files to LF, so diffs stay clean (previously the live server accumulated doubled `\r\r\n`).
3. **Auto-update on private repo** — token + `setAuthentication()` (see §3). Verified working (HTTP 200 from the private repo, update button installs cleanly).
4. **Automated release zip** — `release-zip.yml` (see §3). Added after PowerShell-built zip corrupted the live plugin folder (~150 files with literal `metcpt\...` backslash names, no valid `metcpt.php`, causing "Plugin file does not exist"). Recovered by deleting the folder and redeploying clean via FTP.
5. **Slug collision fix** — CPTs changed to `has_archive => false` + singular slugs (`event`/`tender`/`career`); Events single-template back button changed from `get_post_type_archive_link()` to reading the `metcpt_events_archive_url` setting (consistent with Tenders/Careers). This stopped Pages randomly redirecting to the homepage.
6. **Per-shortcode template override for Tenders** — `[tenders_list]` and `[tenders_preview]` accept a `template="a"` (table/strip) or `template="b"` (editorial) attribute that overrides the global "Active Template" setting in MetCPT Settings. This fixed an editor-vs-live mismatch where the shortcode rendered Template A in the Elementor editor but Template B on the live page. **Rule of thumb:** on any page where the tender layout matters, specify `template=` explicitly rather than relying on the global default.
7. **News grid button** — "View newsroom → /newsroom" changed to "View all events → /events/" via MetCPT Settings (`metcpt_news_view_all_text`, `metcpt_news_view_all_url`). No code change — many labels/URLs are settings, not code.
8. **P3 white-screen fix (activation / `wp-admin/update-core.php`)** — root cause was two-fold: (a) `.gitignore`'s blanket `vendor/` rule was excluding `libs/plugin-update-checker/vendor/Parsedown.php` and `PucReadmeParser.php` from the repo, so the bundled update-checker library crashed with a "Class not found" fatal whenever it tried to render a GitHub release changelog (which happens on activation and on `update-core.php` specifically); (b) MetCPT's own global `set_exception_handler()` in `includes/admin/error-log.php` silently swallowed that fatal (logged it, returned with no output) instead of surfacing an error, producing a blank white screen instead of a real error message. Fixed by un-ignoring the vendor files and making the exception handler `restore_exception_handler()` + rethrow after logging, so any future uncaught exception shows a real error instead of a silent blank page.
9. **Newsroom + Tenders-preview redesign** — introduced `assets/style-tokens.css`, a small shared design-token stylesheet scoped under a `.hrk-v2` class (not bare `:root`, to avoid colliding with Elementor's own custom properties or leaking globally). `[news_grid]` was rebuilt from an asymmetric "1 featured + 3 small" layout into a uniform `repeat(3, 1fr)` grid (6 cards minimum). `[tenders_preview]` Template A was reskinned onto the same tokens, floor raised to 5 items minimum, and later simplified further: the internal header bar (status dot, "Open Tenders" title, count, "View all tenders" link) was removed entirely so rows render as plain independent shadowed cards with no wrapping panel; every row now always links to the tender's own permalink (previously a non-empty `tender_document_url` meta value silently overrode this — see item 11). Template B, `[tenders_list]`, and Events/Careers were deliberately left untouched (no design reference exists for them yet — see §8 item 1).
10. **Duplicate shortcode registration removed** — a second, unrelated-looking active plugin, **Category Post Listings** (`category-post-listings`, same author, "Display WordPress posts by category using shortcodes" — almost certainly the predecessor MetCPT was renamed/restructured from), was registering its **own** `tenders_preview`, `tenders_list`, `events_list`, and `category_posts` shortcodes under the identical tag names as MetCPT's. It registered no custom post type, database table, or admin page of its own (confirmed via repo-wide grep before removal) — purely shortcode handlers filtering regular WordPress posts by category, so deactivating it was safe and changed no page's rendered output (MetCPT's own shortcodes already won by load order). Deactivated on staging; a local backup of the plugin folder was taken via CoreFTP before removal in case it's ever needed for reference.
11. **Stray tender document-URL data cleared** — 3 live tenders (`DBSB-IIUM-001/2026`, `IKOP/IH/PHARMA/2026/001`, `IMSC/IH/CTSCAN/2026/001`) had their `tender_document_url` meta field filled with unrelated company homepage links instead of being empty, which caused `[tenders_preview]` rows to redirect to those external homepages instead of the tender's own page. Cleared manually via wp-admin (Tenders → All Tenders → edit each → clear the Document URL field). The code-level fix in item 9 (rows always link to `get_permalink()` now) makes this shortcode immune to this class of bad data going forward, but the single tender page / full tenders list may still surface a document-download link from this field, so keep it accurate going forward.

---

## 6. Critical operational gotchas (READ BEFORE CHANGING THINGS)

- **After ANY change to CPT registration** (slugs, `has_archive`, rewrite): you MUST flush permalinks (**Settings → Permalinks → Save Changes**) or the change won't take effect and URLs may break/redirect to homepage.
- **After ANY front-end change** (CSS, templates, shortcode output): purge LiteSpeed (**LiteSpeed Cache → Toolbox → Purge All**) and verify in an **incognito** window. LiteSpeed will otherwise serve stale pages and make you think a fix didn't work.
- **Editor vs live mismatch** on shortcodes usually means the output depends on a global setting that the Elementor editor doesn't apply but the front end does. Fix by making the choice explicit on the shortcode (see §5 item 6).
- **`php -l` / parse-check `metcpt.php` and any edited PHP before deploying.** A syntax error in the main plugin file = white screen of death on the live site. There is history of this (see §5 item 8 for a *different* white-screen cause that wasn't a syntax error at all — don't assume every blank page is a parse error).
- **Never edit `wp-config.php` without backing it up first** (`cp wp-config.php wp-config.php.backup-YYYYMMDD`).
- **Deleted the server-side `.git` folder** earlier (it was a stale leftover from an original `git clone` deploy and was web-exposed). Deployment is FTP-only now; do not re-clone onto the server.
- **A global `set_exception_handler()`/`set_error_handler()` runs on every request** (`includes/admin/error-log.php`, loaded unconditionally). It's scoped to only *log* exceptions whose file lives under the plugin directory, but it affects the whole site's exception handling either way. If a future silent white screen shows up again, check `wp_metcpt_error_log` (MetCPT Settings → Error Log) before assuming it's a plain PHP fatal.
- **Shortcode name collisions across plugins are a real risk on this site** — see §5 item 10. Before adding any new shortcode tag, grep the whole `wp-content/plugins/` tree for `add_shortcode` with that tag name first; WordPress silently lets the last-loaded registration win with no warning.
- **Design-token pattern exists now, but only for two modules** — `assets/style-tokens.css` (`.hrk-v2` class) is the intended foundation for de-brittling this plugin's CSS (see §7), but it's only applied to Newsroom (`style-posts.css`) and Tenders-preview Template A (`style-tenders.css`'s `.tdp-*` block) so far. Don't assume it covers Events/Careers/Tenders-list/Template B — check before touching those.

---

## 7. Server details (staging)

- Plugin path on server: `/home2/iiumhold/v2/wp-content/plugins/metcpt/`
- wp-config path: `/home2/iiumhold/v2/wp-config.php`
- Access: cPanel (File Manager + Terminal + LiteSpeed Cache). SSH access exists in principle via an SSH key generated through cPanel → Security → SSH Access, but has not successfully connected in practice (port 22 refused, several common alternate ports timed out) — data-level fixes on staging currently have to go through wp-admin by hand, not a script. Local dev via LocalWP at `C:\Users\User\Local Sites\ihsb-v2\app\public\wp-content\plugins\metcpt` (this is also the git repo working copy).
- Host stack: LiteSpeed + WordPress; Elementor + Hello Elementor theme (its reset CSS out-specifies simple selectors — plugin CSS historically used `body`-prefixed selectors and `!important` to win this fight; **partially superseded** by the `.hrk-v2` design-token pattern introduced in §5 item 9, but only for the two modules mentioned there — the rest of the plugin still relies on the old `body`-prefix/`!important` approach and it's still legitimate tech debt, see §8 item 1).

---

## 8. Open items / backlog (not yet done)

1. **CSS `!important` / `body`-prefix tech debt + per-page template override, for Events/Careers/Tenders-list/Template B.** The `.hrk-v2` token pattern (§5 item 9) proved out the fix for Newsroom + Tenders-preview Template A, but extending it further needs the same thing that made that possible: a mockup/design reference to build toward and visually verify against. Events and Careers currently only have **one** layout each implemented (no "Template B" equivalent), so a per-page template override for them isn't just wiring up an attribute — it requires designing and building a second layout first. **Deliberately deferred** — do not attempt a speculative CSS refactor across these modules without a design reference; regression risk is real and there's no way to visually confirm "done" without one.
2. **Menu/footer singular-vs-plural labels** ("Tender"/"Career" vs "Careers") — cosmetic Elementor menu labels, not plugin code, confirmed via full-repo grep (no plugin PHP registers or filters any front-end nav/footer menu). **Explicitly deprioritized** — low value for the effort, not worth fixing right now.

---

## 9. How to work on this project in Claude Code

- The repo is the source of truth. Make changes here, commit, push. Staging auto-deploys via FTP.
- Always parse-check edited PHP before pushing.
- Remember the two post-deploy rituals when a change involves CPTs or front-end output: **flush permalinks** and **purge LiteSpeed + test incognito**.
- For production releases, follow the Release SOP in §3 — publish a release, let `release-zip.yml` build the zip, never hand-build with PowerShell.
- Many "bugs" are actually settings in MetCPT Settings (labels, URLs, template choice) — check there before writing code.
- For data-only fixes on staging (postmeta, options), there's currently no working SSH/DB access — walk the user through wp-admin manually rather than assuming a script can reach the staging database.
- When redesigning a module against a mockup, follow the pattern established for Newsroom/Tenders-preview: introduce/extend `.hrk-v2` tokens in `assets/style-tokens.css`, verify locally via LocalWP (headless-Chrome screenshots work well for this — see git history around the Newsroom/Tenders redesign commits for the approach), then commit and push.
