# PRD — MetCPT Bulk Wordpress Posts Importer

**Status:** Draft for implementation
**Target plugin:** MetCPT (existing, v1.2.1+)
**Author:** Product spec (to be harmonised by Claude Code against the current MetCPT codebase)
**Type:** New admin feature, additive — must not alter or regress any existing MetCPT module.

---

## 0. Reading instructions for Claude Code

This PRD describes **what** to build and **why**, not the exact file layout or class structure. Before implementing:

1. Read the current MetCPT codebase (`includes/`, `templates/`, `assets/css/`, `metcpt.php`).
2. Decide how this feature fits the existing architecture: where the admin page lives, how it is registered, which existing helpers/patterns to reuse (settings pattern, error-log pattern, nonce/capability/sanitisation conventions, CSS design tokens).
3. Reuse existing conventions rather than inventing new ones. If MetCPT already has a helper for a job this PRD needs, use it.
4. Where this PRD and the existing codebase conventions conflict, **the codebase wins** — flag the conflict in your implementation notes rather than silently overriding.

This feature operates on **standard WordPress posts** (the News module, `[news_grid]`), NOT on any custom post type. It creates ordinary `post` entries so they flow into the existing `[news_grid]` / `[category_posts]` display.

---

## 1. Problem

The corporate site (IIUM Holdings) must publish ~15+ news posts per month, sourced by back-dating content that subsidiary companies already posted on their own Facebook/Instagram pages. Several months (April–September) are backlogged.

For each post the operator currently repeats ~10 manual steps in the WordPress admin:
- Add New post, paste title + body (already prepared, Yoast-optimised via ChatGPT)
- Upload featured image, set its alt text
- Set author, set comment status to closed, set category
- Fill Yoast SEO meta fields (focus keyphrase, SEO title, meta description, slug)
- Insert backlink(s) into the content
- Set the publish date to match the original social-media post date
- Publish

Repeated 15+ times per month across 6 months = 90+ manual cycles. The repetitive half is **data entry into WordPress**, not content creation.

## 2. Goal

Provide a single admin screen inside MetCPT where the operator enters the already-prepared content for **multiple posts at once** and imports them in one action, with every field the manual flow sets — including featured image, image alt text, and Yoast SEO meta — set correctly and automatically.

### Explicit non-goals
- **Not** automating content discovery or generation from Facebook/Instagram. The operator still gathers and writes content externally (ChatGPT). This feature begins at "content is ready".
- **Not** scraping any social platform.
- **Not** a replacement for the normal editor — it is a fast path for bulk backlog entry and recurring monthly batches.

## 3. Users

Single primary user: the site content operator (admin-level). Assume `manage_options` capability. Not a public-facing feature.

## 4. Success criteria

- One import action can create N posts (N up to at least 20 in a batch) with zero further manual editing required for a correctly-filled batch.
- Each created post has, when provided: title, body (HTML), excerpt, slug, author, category, comment status closed, publish date (back-dated), featured image with alt text, and Yoast focus keyphrase / SEO title / meta description.
- The operator can **preview/verify before publish**: batches can be created as **Draft** first, reviewed, then published — OR created as Published directly. This is a per-batch choice.
- A clear per-row result report after import: which rows succeeded (with edit links), which failed and why.
- No regression to Events, Tenders, Careers, News Grid, dashboard widget, or error log.

## 5. Functional requirements

### 5.1 Entry method

Provide a form where the operator supplies multiple posts in one submission. Claude Code chooses the most usable input mechanism given MetCPT's existing admin UI conventions. Two acceptable approaches (pick one, or offer both):

- **(a) Repeatable row UI** — an "add another post" repeater, each block containing the fields in 5.2, with a featured-image picker using the native WP media modal.
- **(b) Structured paste / table** — a textarea or table accepting multiple rows in a defined format, with featured images handled separately (see 5.3).

Recommendation: **(a)** integrates most cleanly with the native media library for featured images and alt text, which is the hardest part to get right via paste. If (b) is chosen, 5.3 must still be fully solved.

The operator has stated their content-prep tool (ChatGPT) already outputs: focus keyphrase, SEO title, excerpt, meta description, slug, and body. The form should make pasting those in as frictionless as possible (field order matching that output is a nice-to-have).

### 5.2 Per-post fields

Each post in a batch must support:

| Field | Required | Notes |
|---|---|---|
| Title | Yes | |
| Body / content | Yes | Accept HTML (Yoast-optimised markup, including inline backlinks already embedded). Do not strip valid post HTML. |
| Excerpt | No | |
| Slug | No | If empty, let WordPress derive from title. If provided, sanitise. |
| Author | Yes | Select from existing users. A batch-level default with per-row override is ideal. |
| Category | Yes | Select from existing categories. Batch-level default with per-row override is ideal. Must map to the category the News Grid / target page displays. |
| Comment status | Yes | Default **closed**. |
| Publish date | Yes | Back-dated datetime matching the original social post. Must set the actual post date, not just display. |
| Post status | Batch-level | Draft or Publish (see 5.4). |
| Featured image | No but expected | See 5.3. |
| Image alt text | No | Applied to the featured image attachment (see 5.3). |
| Yoast: focus keyphrase | No | Post meta. |
| Yoast: SEO title | No | Post meta. |
| Yoast: meta description | No | Post meta. |

### 5.3 Featured image handling (critical — this is the main reason the feature exists)

The operator has the images as **local files downloaded from social media**, renamed, with an intended alt text derived from the focus keyphrase.

Requirements:
- Accept a local image per post via the **native WordPress media modal** (`wp.media`) so images land in the Media Library properly, OR via direct upload within the form.
- The imported/selected image must be set as the post's **featured image** (`set_post_thumbnail`).
- The provided **alt text** must be written to the attachment's alt meta (`_wp_attachment_image_alt`) so it is not lost.
- If sideloading from a file path/upload is used, use WordPress core sideload facilities (`media_handle_sideload` / `media_handle_upload`) — never move files manually — so thumbnails, metadata, and library registration are correct.
- Reusing an already-uploaded Media Library image (by attachment ID) must also be supported, to avoid duplicate uploads.

Do **not** require images to be pre-hosted at a public URL. Local upload / media-library selection is the point.

### 5.4 Draft-first vs publish-direct

- The batch form has a **status control**: "Save as Draft" or "Publish now".
- Draft: posts created as `draft`, retaining the back-dated date, so the operator can open each, proofread, and publish manually or via a bulk action.
- Publish: posts created as `publish` with the back-dated date (they appear in the archive at the correct historical position).

### 5.5 Yoast SEO meta

Set Yoast fields via post meta on the created post:
- Focus keyphrase → `_yoast_wpseo_focuskw`
- SEO title → `_yoast_wpseo_title`
- Meta description → `_yoast_wpseo_metadesc`

(Confirm current Yoast meta keys against the installed Yoast version during implementation; the above are the standard keys. If Yoast is not active, these writes must fail gracefully and be reported, not fatal.)

Slug is native WordPress (`post_name`), not Yoast — set it on the post itself.

### 5.6 Result reporting

After submit, show a per-row outcome:
- Success: post title + **edit link** + **view link**, and status (draft/published).
- Failure: which row, which field/stage failed, and the reason (e.g. "featured image upload failed", "Yoast not active", "invalid date").
- Partial batches are allowed: a failure on row 3 must not prevent rows 1, 2, 4… from importing. Report the mix honestly.

Route unexpected errors into MetCPT's existing **error log** where appropriate, following the current logging convention.

## 6. Non-functional requirements

- **Security:** follow existing MetCPT conventions exactly — nonce verification on submit, `manage_options` (or `publish_posts`) capability check, sanitise every input on save (`sanitize_text_field`, `wp_kses_post` for body, `esc_url_raw` for links, `sanitize_title` for slug, author/category IDs via `absint` + existence check), escape all output.
- **No new database tables.** Use native `wp_posts` / `wp_postmeta`. (The error-log table is the only custom table in MetCPT; do not add another.)
- **Performance:** a batch of ~20 posts with image sideloads may run long. Guard against timeouts — process sensibly (chunking, or a clear per-row loop with feedback) and avoid loading unbounded data. Follow the plugin's lean-query ethos.
- **Additive & reversible:** ships behind the existing plugin, no changes to CPT registration or public shortcodes. Uninstall behaviour unchanged (this feature stores only native posts/meta the site owner keeps).
- **UI consistency:** reuse the MetCPT settings/admin CSS design tokens and layout patterns so the page looks native to the plugin.
- **Localisation:** wrap user-facing strings in the plugin's existing text domain, matching current practice.

## 7. Placement

Claude Code decides, consistent with MetCPT's IA. Candidates: a top-level MetCPT admin subpage ("Bulk News Import"), or a tab within an existing area. It concerns standard posts (News module), so grouping near the Posts settings/News-Grid area is logical — but defer to whatever keeps the admin menu coherent.

## 8. Out of scope / future

- Recurring/scheduled monthly runs (the operator may want this later; design the feature so a future scheduled or CSV-fed variant is not precluded, but do not build it now).
- Importing Events/Tenders/Careers in bulk (this PRD is News/posts only).
- Any social-media API integration.

## 9. Acceptance test (manual, matches operator's verify-before-push flow)

1. Open the Bulk News Import screen as an admin.
2. Enter 3 posts: each with title, HTML body containing an inline backlink, excerpt, slug, a distinct back-dated publish date, author, category, comment status closed, a locally-uploaded featured image with alt text, and Yoast focus keyphrase / SEO title / meta description.
3. Choose "Save as Draft", submit.
4. Verify the result report lists 3 successes with working edit links.
5. Open each draft: confirm every field above is set correctly — including featured image visible, alt text present on the attachment, Yoast fields populated, date back-dated, comments closed, category correct, body HTML and backlink intact.
6. Publish one; confirm it appears in `[news_grid]` at the correct historical date position.
7. Re-run a batch with one deliberately broken row (e.g. missing title); confirm the good rows import and the broken row is reported without aborting the batch.
8. Confirm Events/Tenders/Careers/dashboard/error-log all still function.

## 10. Open questions for implementation

- Confirm the installed Yoast version's meta keys before hardcoding.
- Confirm the target display category slug used by the live News Grid so the default category can be pre-selected.
- Decide input mechanism (5.1a vs 5.1b) after reviewing the existing admin UI — document the choice and reasoning in the implementation notes.
