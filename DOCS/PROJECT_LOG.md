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
