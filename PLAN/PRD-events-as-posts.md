# PRD: Events move to native WordPress posts

Target release: v1.6.0. Written 2026-09-08.

Not shipped in the release zip. See the exclusion rules in section 0.

## 0. Working rules for this change

- **Model.** Planning and research on the session default model. All coding on
  Sonnet 5, per [../CLAUDE.md](../CLAUDE.md) and
  [../DOCS/DECISIONS.md D20](../DOCS/DECISIONS.md#d20).
- **Local test site:** `http://v2/wp-login.php`, user `admin`. The password is
  not written to this repo, see the warning below.
- **Before editing:** `git pull --ff-only origin main`. Two machines work on
  this repo.

### `PLAN/` must be excluded from the release zip

This folder ships to every live site unless three things change together.
Missing any one of them leaks it.

1. `.gitattributes`, the export-ignore block: add `PLAN/  export-ignore`.
2. `.github/workflows/release.yml`, the `grep -vE` exclusion list: add `PLAN`.
3. `release.yml`, the leak check: add `PLAN/` to the grep. It tests only
   `DOCS/`, `CLAUDE.md`, `composer.json` and `phpcs.xml.dist` today, so it
   would pass while `PLAN/` shipped.

### Credentials warning

The repo is public at `https://github.com/ismetdev/metcpt`. Do not write the
admin password into this file, the DOCS set, a commit message, or any tracked
file. A password committed to git stays in the history after it is deleted.

## 1. Context

Events are a custom post type, `metcpt_event`, with their own single page
template at `templates/events/single.php`. That template renders 16 meta
fields including three repeaters, so an event page looks nothing like an
article on the rest of the site.

The owner is happy with the `/events/` listing. She is not happy with the
single event page. She wants an event to read like a normal blog post, using
the `met-hello-elementor-child` theme's existing single post design.

So the plugin stops owning the event page. Events become native posts. The
plugin keeps owning the event data and the listing.

Outcome: an event is written on the normal Add Post screen. The theme renders
the page. The plugin adds a small summary of event facts and keeps the
listing sorted by event date.

## 2. Goal

Write events as normal posts, keep `/events/` looking exactly as it does
today, and show a short event summary on the single post without touching
the theme.

## 3. Scope

**In**

- Event meta boxes on the native post editor, gated by a tick box.
- A summary block on the single post: event date, time, venue, organiser.
- Placement control for that block, per post, with a global default.
- `[events_list]` reads posts instead of the CPT. Same markup, same CSS.
- schema.org Event JSON-LD on event posts.
- Back link on an event post points at `/events/`.
- A button-triggered migration of existing `metcpt_event` entries into
  posts, with 301 redirects from the old URLs.

**Out**

- Any change to the `/events/` listing design.
- Any change to tenders, careers, companies, the news grid design, the
  error log, the cron or the dashboard widget.
- Rendering the rich fields. VIPs, itinerary, FAQs, guidelines, audience and
  capacity stay editable and stored, and render nothing.
- Any edit to the `met-hello-elementor-child` repo.
- Any filtering of events out of the homepage, the newsroom, search or
  archives. An event is a post and behaves like one. Owner's decision, see
  D4 below.

## 4. Decisions

| # | Decision | Reason |
|---|---|---|
| 1 | An event is a post with the tick box on and an event date set. The category is not part of the trigger. | One source of truth. A term can be renamed or deleted, a meta flag cannot. Also makes the currently dead `category=` attribute on `[events_list]` work as an optional filter, because posts carry the core `category` taxonomy that the CPT never had. |
| 2 | The tick box also assigns the existing **Events** category, slug `events`. | Gives a filter, a tidy admin list, and a fallback if the meta ever goes wrong. It is a convenience, not the trigger. |
| 3 | The summary shows event date, time, venue, organiser. No status badge. | Owner's choice. The listing already groups by month and marks upcoming and past. |
| 4 | Event posts are not hidden from the homepage, newsroom, search or any archive. | Owner's decision. An event is a post, so it behaves like one everywhere. This removes a site-wide query filter and any need to change the theme. |
| 5 | The rich fields stay in the editor and render nothing. | Owner's choice. The programme goes into the post body with the normal editor. |
| 6 | The block is injected with a `the_content` filter. Never a `single_template` filter. | A `single_template` filter for `post` replaces the theme's `single.php` outright and destroys the exact design this change exists to get. |
| 7 | Placement is a select field in the meta box, defaulting to a global setting. | Owner's request, plus a global default so it is set once rather than per post. |
| 8 | Migration runs from a button on Settings > Events, not on update. | A data conversion that runs by itself on a live site has already gone wrong by the time anyone sees it. |
| 9 | Old `/event/<slug>/` URLs 301 to the new post. `metcpt_event` stays registered but hidden from the menu. | Keeps existing links and search results alive, and leaves a rollback path. |
| 10 | Event JSON-LD is added by the plugin. | Yoast does not emit Event schema for plain posts, so nothing else on the site covers it. |

## 5. Approach

### 5.1 Meta boxes on the post editor

New file `includes/events/meta-boxes-post.php`, loaded in the `is_admin()`
branch of `includes/core/class-metcpt.php` alongside the other meta box
files.

One meta box on the `post` screen. The tick box is the first control. When
it is off, the rest is hidden, using the same inline vanilla JS pattern
already in `includes/events/meta-boxes.php`.

New meta keys:

| Key | Values |
|---|---|
| `metcpt_is_event` | `'1'` or absent. The trigger. |
| `metcpt_event_summary_position` | `''` (use global default), `top`, `bottom`, `both`, `none`. |

Existing keys are reused unchanged: `event_date`, `event_time`,
`event_venue`, `event_organiser`, and all the rest.

The save handler copies the shape of `metcpt_save_event_meta()` in
`includes/events/meta-boxes.php`: nonce, autosave guard, capability check,
post type check, then the same per-key sanitisers. Reuse that function's
mapping arrays rather than retyping the key list, so the two screens cannot
drift apart.

When the tick box is saved on, add the `events` category with
`wp_set_post_terms( $post_id, array( $term_id ), 'category', true )`.
Append, do not replace, so other categories survive.

The rich fields (VIPs, itinerary, FAQs, guidelines, audience, capacity)
keep their inputs so the data stays editable, in a collapsed section so the
screen stays short.

### 5.2 The summary block

New file `includes/events/summary-block.php`, loaded in the `! is_admin()`
branch.

- `metcpt_render_event_summary( $post_id )` returns markup for date, time,
  venue, organiser. Empty fields are skipped. Returns an empty string when
  the tick box is off.
- `metcpt_event_summary_content( $content )` on `the_content`, guarded by
  `is_singular( 'post' ) && in_the_loop() && is_main_query()`.
- Position from `metcpt_event_summary_position`, falling back to the new
  option `metcpt_event_summary_position_default`.
- Markup uses the existing `mcpt-` class prefix under one
  `.mcpt-event-summary` root.

The block lands inside `.post-body__inner`, the narrow editorial column in
the theme's `single.php`. Write the CSS for that column width, not full
bleed.

### 5.3 CSS

New `assets/css/style-event-summary.css`, registered and enqueued in
`MetCPT::enqueue_frontend_styles()` behind a gate matching the render
condition: `is_singular( 'post' )` and `metcpt_is_event` present on the
queried post.

It must **not** depend on `metcpt-tokens`. The theme loads its own
`tokens.css` sitewide writing `:root` custom properties, and MetCPT's
tokens sheet also writes `:root`. Both on one page load in undefined order.
Scope any custom properties to `.mcpt-event-summary`, the same way D17
scopes `.mcpt-v2`.

### 5.4 Event schema

In `summary-block.php`, a `wp_head` callback emits Event JSON-LD on event
posts only: name, startDate from `event_date` plus `event_time` when
parseable, location from `event_venue`, organizer from `event_organiser`,
image from the featured image, description from the excerpt, url from the
permalink. Emit nothing when `event_date` is empty, since a date is
required for the schema to be valid.

### 5.5 The listing

`includes/events/shortcode-list.php` changes in one place, the query args:

- `post_type` becomes `post`.
- The meta query gains a clause requiring `metcpt_is_event` to exist,
  alongside the existing `event_date EXISTS` clause.

Everything else is untouched: `orderby => meta_value`, `meta_key =>
event_date`, the month dividers, the `.ev-` markup, the empty states. The
excerpt already renders through `show_excerpt` defaulting to `yes` and
`get_the_excerpt()`, so it keeps working with no change. Posts with no
hand-written excerpt fall back to WordPress's automatic one, which is the
normal behaviour.

The events CSS gate in `class-metcpt.php` swaps `is_singular(
'metcpt_event' )` for the event-post condition and keeps the
`metcpt_page_has_shortcode( 'events_list' )` branch that drives the listing
page.

### 5.6 Back link

The theme's back button reads `met_hello_child_back_link_url()` in
`inc/template-tags.php`, which defaults to `/news-announcement/` and is
filterable. The plugin filters it to the existing
`metcpt_events_archive_url` option, but only when the current post is an
event. No theme edit.

### 5.7 Migration and redirects

New file `includes/admin/migrate-events-to-posts.php`.

- A panel on the Settings > Events tab showing how many `metcpt_event`
  entries exist.
- A dry run listing what would change, before anything is written.
- On confirm, per entry: `wp_update_post` changing `post_type` to `post`,
  set `metcpt_is_event` to `'1'`, store the old slug in
  `metcpt_legacy_event_slug`, and append the `events` category. The post
  ID, meta, featured image, comments and publish date all survive, because
  only the type changes.
- A `template_redirect` handler resolves `/event/<slug>/` against
  `metcpt_legacy_event_slug` and 301s to the new permalink.
- Idempotent and option-guarded, in the style of
  `includes/core/migrate-from-haraka.php`.

`metcpt_event` registration in `includes/core/post-types.php` gains
`show_in_menu => false`, driven by an option so it can be switched back on.
The registration itself stays, so nothing else breaks and the redirect
handler keeps working.

`METCPT_VERSION` must be bumped, which is what fires
`metcpt_maybe_flush_rewrite_rules()` in `post-types.php`.

### 5.8 Left alone

The old CPT single and archive templates in `templates/events/` stay on
disk and stay registered. They become unreachable once the CPT is empty.
Deleting them is a separate cleanup once the migration is proven on
staging.

## 6. Risks

| Risk | What to do |
|---|---|
| Migration is hard to reverse. | Dry run first, run on staging first, take a database backup before production. The old slug is stored, so URLs can be rebuilt. |
| The plugin now renders on `is_singular( 'post' )`, which breaks the documented "no overlap" boundary in `DOCS/STATE.md` and in the theme's own docs. | Update the scope boundary section in both repos' STATE.md as part of this release. Documentation change only. |
| Two `:root` custom property blocks on one page if the summary CSS pulls in `metcpt-tokens`. | Do not declare that dependency. Scope variables to `.mcpt-event-summary`. Stated in 5.3. |
| `metcpt_page_has_shortcode()` reads `global $post` with no `is_singular()` guard. The theme has a hardened version in `inc/listing.php`. | Pre-existing, not caused by this change. Note it, fix in a separate release. |
| Events now appear on the homepage, in the newsroom, in search and in archives. | Accepted by the owner, D4. No code. If it looks wrong once live, the fix is one filter and can be added later. |

## 7. Steps

Each step is testable on its own.

0. Create `PLAN/`, write this PRD into it, then make the three exclusion
   edits listed in section 0 so the folder never ships. Verify by running
   the `release.yml` grep locally against the file list.
1. Add the tick box, the placement field and the event fields to the post
   editor. Verify they save and reload, and that the `events` category is
   appended.
2. Add the summary renderer and the `the_content` filter. Verify it appears
   on a ticked post, in the chosen position, and nowhere else.
3. Add the scoped stylesheet and its enqueue gate. Verify it loads only on
   event posts, and that the theme's own styling is unchanged.
4. Add the Event JSON-LD. Validate with Google's Rich Results Test.
5. Filter the back link to `/events/` on event posts. Verify a normal news
   post still goes back to Newsroom.
6. Switch `[events_list]` to query posts. Verify the listing renders
   identically against a hand-made event post.
7. Add the migration panel with a dry run. Verify counts against the
   database.
8. Run the migration on local. Verify every event kept its meta, image,
   publish date and comments.
9. Add the 301 redirect handler. Verify an old `/event/<slug>/` URL lands
   on the new post.
10. Hide the Events admin menu.
11. Release: bump `METCPT_VERSION` and the `Version:` header, bump `Stable
    tag:` in `readme.txt`, add changelog and upgrade notice, `php -l` every
    changed file, update `DOCS/STATE.md`, `DOCS/DECISIONS.md` and
    `DOCS/PROJECT_LOG.md`, commit, push `main`, then push the `v1.6.0` tag.
    After the Action runs, open the published `metcpt.zip` and confirm it
    contains no `PLAN/` and no `DOCS/`.

## 8. Verification

Run on the local site: `http://v2/wp-login.php`, user `admin`, password
held outside the repo.

- Create a post, tick the event box, fill date, time, venue, organiser.
  Publish. The page is the theme's normal post design plus the summary
  block, and nothing else changed.
- Change the placement field to top, bottom, both and none. Confirm each.
- Publish a normal news post. Confirm no summary block, no MetCPT CSS
  loaded, and the back link still goes to Newsroom.
- View `/events/`. Confirm it is visually identical to before, including
  month dividers, excerpts and thumbnails.
- View page source on an event post. Confirm one `<main id="content">`,
  one document shell, and valid Event JSON-LD.
- Check the loaded stylesheets on an event post, a news post and the
  homepage. Only event posts pull `style-event-summary.css`.
- Run the migration dry run, compare its count to the Events admin list,
  then run it. Spot check three migrated events for meta, featured image,
  publish date and comments.
- Open an old `/event/<slug>/` URL. Confirm a 301 to the new permalink.
- Confirm tenders, careers, the news grid, the dashboard widget, the daily
  cron and the error log all still behave as before.
- `php -l` clean on every changed file, using the PHP binary under
  `C:\Users\IIUM Holdings\AppData\Roaming\Local\lightning-services\php-*\bin\win64\php.exe`.

## 9. Done when

Every check in section 8 passes, and `/events/` is unchanged to the eye.
