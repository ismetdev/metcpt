# Haraka Plugin — Project Audit & Working Context

> **Purpose of this file:** This is a handoff/context document for continuing work on the Haraka WordPress plugin (built for IIUM Holdings Sdn Bhd) inside Claude Code. It captures the architecture, the deployment model, recent fixes, known gotchas, and open items. Read this first before making changes.

---

## 1. What Haraka is

A proprietary WordPress plugin — a "Corporate Content Hub" — that manages Events, Tenders, Careers, and News for IIUM Holdings. It registers four custom post types and exposes shortcodes that are placed on WordPress Pages (built in Elementor). See `README.md` for the full feature list and shortcode reference.

Custom post types (registered in `includes/core/post-types.php`):

| CPT | Slug (single post) | has_archive |
|---|---|---|
| `hrk_event` | `event` | false |
| `hrk_tender` | `tender` | false |
| `hrk_career` | `career` | false |
| `hrk_company` | (none) | false |

---

## 2. The two-site model (IMPORTANT)

There are two live WordPress sites, with **different update mechanisms**. Do not confuse them.

- **Staging: `v2.iiumholdings.com.my`** — the working/dev site. Updated by **FTP auto-deploy**: every push to `main` triggers a GitHub Action that FTPs the repo to the server. Fast, automatic. This is where all current development happens.
- **Production: `iiumholdings.com.my`** — the real company site. NOT yet running Haraka at time of writing; it goes live after management approval. Production is updated the **industry-standard way**: publish a versioned GitHub Release → WordPress shows an "update available" button → click to update. No FTP auto-deploy on production.

**Source of truth = the GitHub repo (`github.com/ismetdev/haraka`, private).** Never edit files directly on either live server except in a genuine emergency (and if you do, immediately commit the same change to the repo).

---

## 3. Deployment & release infrastructure

Two GitHub Actions workflows in `.github/workflows/`:

- **`deploy.yml`** — on every push to `main`, FTP-deploys the repo to the staging server at `/v2/wp-content/plugins/haraka/`. Uses `actions/checkout@v6` and `SamKirkland/FTP-Deploy-Action@v4.4.0`. FTP credentials are stored as GitHub Actions secrets (`FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_PORT`), never in code.
- **`release-zip.yml`** — on release *publish*, builds a clean, correctly-structured `haraka.zip` on Linux and attaches it to the release automatically. This is the zip WordPress installs on production.

### Auto-update mechanism (private repo)
The plugin uses the Plugin Update Checker library (`libs/plugin-update-checker/`), configured in `haraka.php`. Because the repo is **private**, WordPress must authenticate to read releases. This is done via a GitHub fine-grained token (read-only, Contents + Metadata) stored in `wp-config.php` as the constant `HARAKA_GITHUB_TOKEN` — **never committed to the repo**. The code reads it conditionally:

```php
if ( defined( 'HARAKA_GITHUB_TOKEN' ) && HARAKA_GITHUB_TOKEN ) {
    $haraka_update_checker->setAuthentication( HARAKA_GITHUB_TOKEN );
}
```

The current token expires **31 Dec 2026** — set a reminder to rotate it before then, or updates will silently stop.

### Release SOP (for production updates)
1. Bump the version in `haraka.php` in **two** places: the `Version:` header comment AND the `HARAKA_VERSION` constant. They must match the release tag.
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
| `/` (homepage) | `[tenders_preview posts_per_page="6" template="b"]`, `[news_grid]`, career preview | teasers |

The CPT archives (`has_archive => false`) are **disabled** so they don't fight the Pages for these URLs. Each module also ships a `template-archive.php` file — these are now **dormant** (archives are off) but kept in place; they are NOT dead code to be deleted casually.

**Back buttons** on single CPT pages point at the configured Page URL via settings (`haraka_events_archive_url`, `haraka_tenders_page_url`, `haraka_careers_page_url`), NOT at the CPT archive.

---

## 5. Recent fixes (this session) — context for why things are the way they are

1. **Cache-busting** — CSS/JS enqueues in `includes/core/class-haraka.php` use `filemtime()` (via an `asset_version()` helper) so a changed file always gets a new URL. This fixed a recurring stale-CSS problem where LiteSpeed served old CSS for weeks.
2. **Line endings** — a `.gitattributes` normalizes all text files to LF, so diffs stay clean (previously the live server accumulated doubled `\r\r\n`).
3. **Auto-update on private repo** — token + `setAuthentication()` (see §3). Verified working (HTTP 200 from the private repo, update button installs cleanly).
4. **Automated release zip** — `release-zip.yml` (see §3). Added after PowerShell-built zip corrupted the live plugin folder (~150 files with literal `haraka\...` backslash names, no valid `haraka.php`, causing "Plugin file does not exist"). Recovered by deleting the folder and redeploying clean via FTP.
5. **Slug collision fix** — CPTs changed to `has_archive => false` + singular slugs (`event`/`tender`/`career`); Events single-template back button changed from `get_post_type_archive_link()` to reading the `haraka_events_archive_url` setting (consistent with Tenders/Careers). This stopped Pages randomly redirecting to the homepage.
6. **Per-shortcode template override for Tenders** — `[tenders_list]` and `[tenders_preview]` now accept a `template="a"` (table) or `template="b"` (editorial) attribute that overrides the global "Active Template" setting in Haraka Settings. This fixed an editor-vs-live mismatch where the shortcode rendered Template A in the Elementor editor but Template B on the live page (because the global setting only applied on the front end). **Rule of thumb:** on any page where the tender layout matters, specify `template=` explicitly rather than relying on the global default.
7. **News grid button** — "View newsroom → /newsroom" changed to "View all events → /events/" via Haraka Settings (`haraka_news_view_all_text`, `haraka_news_view_all_url`). No code change — many labels/URLs are settings, not code.

---

## 6. Critical operational gotchas (READ BEFORE CHANGING THINGS)

- **After ANY change to CPT registration** (slugs, `has_archive`, rewrite): you MUST flush permalinks (**Settings → Permalinks → Save Changes**) or the change won't take effect and URLs may break/redirect to homepage.
- **After ANY front-end change** (CSS, templates, shortcode output): purge LiteSpeed (**LiteSpeed Cache → Toolbox → Purge All**) and verify in an **incognito** window. LiteSpeed will otherwise serve stale pages and make you think a fix didn't work.
- **Editor vs live mismatch** on shortcodes usually means the output depends on a global setting that the Elementor editor doesn't apply but the front end does. Fix by making the choice explicit on the shortcode (see §5 item 6).
- **`php -l` / parse-check `haraka.php` and any edited PHP before deploying.** A syntax error in the main plugin file = white screen of death on the live site. There is history of this.
- **Never edit `wp-config.php` without backing it up first** (`cp wp-config.php wp-config.php.backup-YYYYMMDD`).
- **Deleted the server-side `.git` folder** earlier (it was a stale leftover from an original `git clone` deploy and was web-exposed). Deployment is FTP-only now; do not re-clone onto the server.

---

## 7. Server details (staging)

- Plugin path on server: `/home2/iiumhold/v2/wp-content/plugins/haraka/`
- wp-config path: `/home2/iiumhold/v2/wp-config.php`
- Access: cPanel (File Manager + Terminal + LiteSpeed Cache). SSH/terminal available via cPanel. Local dev via LocalWP at `C:\Users\User\Local Sites\ihsb-v2\app\public\wp-content\plugins\haraka` (this is also the git repo working copy).
- Host stack: LiteSpeed + WordPress; Elementor + Hello Elementor theme (its reset CSS out-specifies simple selectors — plugin CSS uses `body`-prefixed selectors and some `!important` to win; this is acknowledged tech debt).

---

## 8. Open items / backlog (not yet done)

1. **P3 — white screen on plugin activation.** A blank page flashed once during activation, then resolved on refresh. Not currently breaking anything. **Start by checking the built-in Error Log tab** (Haraka Settings → Error Log) — the plugin captures its own fatals to a `wp_haraka_error_log` table. If empty, enable `WP_DEBUG_LOG` and reproduce by deactivating/reactivating.
2. **Per-page template override for Events and Careers.** Only Tenders has the `template=` attribute so far. If per-page layout switching is wanted for the other two, replicate the pattern from `includes/tenders/shortcode-list.php`.
3. **CSS `!important` / `body`-prefix tech debt.** Works but brittle. Longer-term: scope plugin output under a single wrapper class (e.g. `.haraka-app`) instead of fighting the theme globally. Low priority — do NOT attempt without being able to visually verify against the live Elementor theme (regression risk).
4. **Cosmetic version strings.** `@version 1.0.0` in docblocks and other non-functional spots are out of sync with the real version. Harmless; only the `haraka.php` header + `HARAKA_VERSION` constant matter functionally.
5. **Menu/footer singular-vs-plural labels** ("Tender"/"Career" vs "Careers") — cosmetic Elementor menu labels, not plugin code.

---

## 9. How to work on this project in Claude Code

- The repo is the source of truth. Make changes here, commit, push. Staging auto-deploys via FTP.
- Always parse-check edited PHP before pushing.
- Remember the two post-deploy rituals when a change involves CPTs or front-end output: **flush permalinks** and **purge LiteSpeed + test incognito**.
- For production releases, follow the Release SOP in §3 — publish a release, let `release-zip.yml` build the zip, never hand-build with PowerShell.
- Many "bugs" are actually settings in Haraka Settings (labels, URLs, template choice) — check there before writing code.
