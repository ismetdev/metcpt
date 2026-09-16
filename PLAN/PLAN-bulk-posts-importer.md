# PLAN, Bulk Posts Importer

Implementation plan for [PRD-bulk-wordpress-posts-importer.md](PRD-bulk-wordpress-posts-importer.md).
Written 2026-09-16 against MetCPT v1.6.0. Ships as v1.7.0.

Supersedes the repeater-form approach in the first draft of this plan. The
repeater removed clicks but kept the typing. CSV removes the typing.

## 1. Goal

Upload one CSV, get every post created with title, body, excerpt, slug, author,
category, back-dated publish date, comments closed, featured image, image alt text
and the three Yoast fields already set. Nothing left to edit by hand.

## 2. Scope

**In.** A new admin screen with four steps: upload, preview, import, done. CSV
parsing and per-row validation. Native `post` creation. Category created when
missing. Featured image matched from the Media Library by filename. Alt text
written to the attachment. Yoast meta written. Per-row AJAX import. Per-row
result report. Re-import of the same row skipped, not duplicated.

**Out.** Social media scraping or APIs. Scheduled runs. Events, Tenders or
Careers. Any change to CPT registration, shortcodes, templates or front-end CSS.
Image upload from inside the importer, see 3.4.

## 3. Approach

### 3.1 Four steps, one screen

New submenu under MetCPT Settings, slug `metcpt-bulk-import`, title "Bulk Posts
Importer", registered with `add_submenu_page()`.

```
1. Upload    CSV file, or paste the CSV text. Batch defaults set here
2. Preview   every row validated and shown. Nothing is written yet
3. Import    AJAX loop, one row per request, live progress
4. Done      per-row report with edit and view links
```

Not a tab on the settings page. Every tab there renders inside one
`<form action="options.php">`, so a file input placed in it would post to the
Settings API. A separate screen avoids that and keeps the settings page short.

### 3.2 The CSV

Fixed header. No column-mapping step, because the header is dictated to ChatGPT
by the prompt the screen hands out.

| Column | Blank means | Written to |
|---|---|---|
| `no` | ignored | nothing, operator's own reference |
| `title` | row fails | `post_title` |
| `featured_image` | warn, import continues | matched attachment, `set_post_thumbnail()` |
| `image_alt` | warn | `_wp_attachment_image_alt` on the attachment |
| `date` | row fails | `post_date` and `post_date_gmt` |
| `excerpt` | warn | `post_excerpt` |
| `content` | row fails | `post_content` |
| `focus_keyphrase` | warn | `_yoast_wpseo_focuskw` |
| `seo_title` | warn | `_yoast_wpseo_title` |
| `meta_description` | warn | `_yoast_wpseo_metadesc` |
| `slug` | derived from title | `post_name` |
| `author` | batch default | `post_author` |
| `category` | batch default | term, created if missing |
| `comments` | batch default | `comment_status` |

The last three are optional columns. They are the same for every post in a normal
batch, so they are set once as batch defaults on step 1. A column in the file
overrides the default for that row.

Parsing rules:

- `fgetcsv()`, so quoted fields with commas and line breaks in the body work.
- UTF-8, BOM stripped if present.
- Header matched case-insensitively, order-independent, unknown columns ignored.
- Multiple files can be uploaded, and the same batch can be imported in parts on
  different days.

### 3.3 Step 2, preview and validate

Every row is checked before anything is written. Three outcomes per row:

- **OK.** Everything resolves.
- **Warning.** Imports, but something is missing. Empty SEO field, empty excerpt,
  no image filename, an image filename with no match in the Media Library, a
  category that does not exist yet and will be created.
- **Error.** Does not import. Missing title, missing content, unparseable date,
  an author name that matches no user.

The preview is a table with a row per CSV row and the reason in plain words. The
Import button imports the OK and Warning rows and skips the Errors. Nothing is
written during preview.

### 3.4 Featured images, matched by filename

The operator uploads all the images in one action through **Media > Add New**,
which takes a multi-file drag and drop. The CSV then names each file in the
`featured_image` column.

At import, the filename is matched against `_wp_attached_file` on the basename.
An attachment ID in the column is also accepted, for reuse of an existing image.

Why matching, not uploading inside the importer:

- Nothing is sideloaded during an import request, so no image resizing happens
  inside the 30 second limit or the 40 MB `WP_MEMORY_LIMIT` recorded in the site
  health report.
- The Media Library upload path is core code, already handles HEIC from phones,
  already generates every thumbnail size.
- Preview can tell the operator a filename has no match before the import runs.

WordPress rewrites filenames on upload, spaces become dashes and a duplicate name
gains a suffix. Matching therefore compares the sanitised basename, and preview
shows what matched so a mismatch is visible, not silent.

Uploading a zip of images from inside the importer is possible later. It is not in
this version.

### 3.5 Step 3, one AJAX request per row

Rows parsed at step 1 are held in a transient, not a new table and not a file on
disk. The import loop asks for row N, creates it, returns the result, moves on.

This is what makes it survive the production limits:

| Limit | Value | Why this clears it |
|---|---|---|
| PHP time limit | 30 s | One post per request |
| PHP max input vars | 1,000 | The CSV is parsed server side, not posted as fields |
| `WP_MEMORY_LIMIT` | 40 MB | No image processing in the request |

A failed row stops nothing. The loop continues and the report records it.

### 3.6 What one row does, in order

1. Verify nonce `metcpt_bulk_import` and `manage_options`. Fail closed.
2. Sanitise. `sanitize_text_field` for title, SEO title, keyphrase, alt text.
   `sanitize_textarea_field` for meta description and excerpt. `sanitize_title`
   for slug. `absint` plus an existence check for any ID. Body, see 3.8.
3. Skip if already imported, see 3.7.
4. Resolve the author to a user ID, and the category to a term ID, creating the
   term with `wp_insert_term()` if it does not exist.
5. Build `post_date` from the CSV datetime and `post_date_gmt` with
   `get_gmt_from_date()`, so the back-dated post sits correctly in the archive
   under the site's `Asia/Kuala_Lumpur` timezone.
6. `wp_insert_post( ..., true )`. Status from the batch control, `comment_status`
   closed by default, `ping_status` closed.
7. On `WP_Error`, pass it through `metcpt_capture_wp_error()` and report the row
   as failed.
8. `set_post_thumbnail()` for the matched attachment, then write the alt text to
   `_wp_attachment_image_alt` on that attachment.
9. Write the three Yoast meta keys, see 3.9.
10. Write the import hash, see 3.7.
11. Return post ID, title, status, edit link, view link, and any warnings.

### 3.7 Re-import safety

Each imported post gets a meta key `metcpt_import_hash`, an MD5 of the title plus
the date. Before creating a row, the importer looks for that hash. If a post has
it, the row is reported as "already imported" with a link to the existing post,
and nothing is created.

This means the same CSV can be uploaded twice with no duplicates, which is what
happens in practice when a batch is interrupted.

### 3.8 Body HTML

Core applies `wp_kses_post` only to users without `unfiltered_html`. This matches
core: `wp_kses_post` unless `current_user_can( 'unfiltered_html' )`.

Stated deviation from PRD section 6, which says `wp_kses_post` with no exception.
The blanket rule would strip embeds the same operator can paste in the normal
editor.

### 3.9 Yoast meta

| Field | Meta key |
|---|---|
| Focus keyphrase | `_yoast_wpseo_focuskw` |
| SEO title | `_yoast_wpseo_title` |
| Meta description | `_yoast_wpseo_metadesc` |

Written whether or not Yoast is active. Inert without it, picked up if installed
later. If Yoast is not active the screen shows one notice. The installed version
is confirmed before code is written, step 1.

### 3.10 The ChatGPT prompt lives on the screen

Step 1 carries a **Copy prompt** button and a **Download blank template** link.
The prompt is the operator's own writing brief plus the output format rules, so
the CSV comes out correct the first time. Kept in one PHP function, so the column
list on screen and the column list in the prompt cannot drift apart.

### 3.11 Files

| File | New or changed | What |
|---|---|---|
| `includes/admin/bulk-import-page.php` | new | Submenu, the four step screens, asset enqueue, the prompt text |
| `includes/admin/bulk-import-csv.php` | new | Parse, validate, the preview model |
| `includes/admin/bulk-import-runner.php` | new | AJAX row handler, image match, term and author resolve, hash check |
| `assets/css/style-bulk-import.css` | new | Screen styles, on the `.mcpt-v2` tokens |
| `assets/js/bulk-import.js` | new | Upload, preview render, import loop, progress, report |
| `includes/core/class-metcpt.php` | changed | Require the three files in the `is_admin()` block, enqueue the two assets on this page only |
| `metcpt.php` | changed | Version bump, two places |
| `readme.txt` | changed | Stable tag, changelog, upgrade notice |
| `DOCS/STATE.md`, `DOCS/DECISIONS.md`, `DOCS/PROJECT_LOG.md` | changed | Surface rows, decision D25, log entry |

`assets/js/` does not exist yet. Every script in the plugin today is inline in the
PHP that prints it. This one is too large for that, so the directory is added and
the script is enqueued properly, with `wp_localize_script()` for the nonce and the
AJAX URL. Stated deviation from existing convention.

Strings stay plain literals, matching every other admin file. See STATE.md open
item 2.

## 4. Steps

Each step ends in something checkable.

1. `git pull --ff-only origin main`. Confirm the site's Yoast version and the
   News Grid category slug in the `metcpt_news_category` option.
2. Add `bulk-import-page.php` with the submenu and the step 1 screen: file input,
   paste box, batch defaults, copy prompt, blank template. Check: the page loads,
   the `manage_options` gate works, no PHP notices.
3. Add `assets/css/style-bulk-import.css` and gate the admin sheets on the new
   page slug in `class-metcpt.php`. Check: the page looks like MetCPT, and the
   sheets do not load on unrelated admin screens.
4. Add `bulk-import-csv.php`, parse only. Check: a three row CSV with a multi-line
   HTML body parses into three rows with the body intact.
5. Add validation and the step 2 preview table. Check: a CSV with one missing
   title, one bad date, one unknown image filename shows one error, one error and
   one warning, and nothing is written to the database.
6. Add `bulk-import-runner.php` through step 7 of 3.6. Check: a one row draft
   import creates a post with the right date, author, category, slug, closed
   comments and body.
7. Add the category creation and author resolve. Check: a CSV naming a category
   that does not exist creates it once and assigns it.
8. Add the image match and alt text, step 8. Check: the draft shows the featured
   image and the attachment carries the alt text.
9. Add the Yoast writes, step 9. Check: all three fields filled in the Yoast box.
10. Add the hash and the skip, 3.7. Check: importing the same CSV twice creates
    nothing the second time and reports every row as already imported.
11. Add the step 3 progress loop and the step 4 report. Check: a 20 row CSV runs
    to completion without a timeout and the report links work.
12. Run the PRD section 9 acceptance test end to end.
13. `php -l` every changed PHP file with the Local PHP binary.
14. Update `DOCS/STATE.md`, add `DECISIONS.md` D25 for the submenu, the CSV
    format, filename image matching and the import hash, add the
    `PROJECT_LOG.md` entry.
15. Version bump, `readme.txt`, commit, push `main`, then push `v1.7.0`.

## 5. Risks

| Risk | What to do |
|---|---|
| ChatGPT emits a malformed CSV | Preview catches it before anything is written. A bad row is one bad row, not a bad file, because CSV is line oriented |
| The operator edits the CSV in Excel and it re-encodes | Documented on the screen: use the file as given. BOM is stripped on parse, and preview shows the parsed body so corruption is visible |
| An image filename does not match what WordPress stored | Preview shows matched and unmatched per row, before import |
| Publishing straight to live with a wrong category or date | Draft is the default status. Publish is a deliberate change |
| Yoast keys differ on the installed version | Confirmed in step 1. Keys live in one helper |
| New surface on a plugin with a site-wide error handler | Nothing hooks outside `is_admin()`. No `single_template`, no `the_content`, no rewrite change. The error handler is not touched |
| The transient expires mid batch | 12 hour lifetime, and the import loop runs in minutes. If it is gone, the screen says so and the file is re-uploaded. Nothing is half written, rows are independent |

## 6. Done when

1. PRD section 9, all eight checks pass on the local site.
2. A 20 row CSV imports in one run with no timeout.
3. The same CSV imported twice creates no duplicates.
4. Events, Tenders, Careers, News Grid, dashboard widget and error log behave as
   before.
5. `php -l` clean on every changed file.
6. No new database table, no new shortcode tag.
