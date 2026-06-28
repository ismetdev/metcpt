# Haraka — Corporate Content Hub for WordPress

A comprehensive WordPress plugin built for IIUM Holdings Sdn Bhd to manage corporate events, procurement tenders, career vacancies, and news content across multiple subsidiary companies.

## Features

### Core Modules

**Events Management**
- Custom post type for corporate events with 8-section meta box
- Auto status detection (Upcoming/Today/Past)
- VIP attendee tracking with role badges
- Detailed itinerary builder with time/activity/PIC columns
- FAQ section for event queries
- Single event page with Google Calendar integration and WhatsApp sharing
- `[events_list]` shortcode with filter/search capabilities

**Tenders Management**
- Custom post type for procurement opportunities
- Dual template system (Table view + Editorial layout)
- Auto status calculation (Open/Closing Soon/Closed) based on dates
- Email notifications for closing tenders via wp_cron
- Document download links and submission details
- Timeline visualization (Briefing Date → Closing Date)
- `[tenders_list]` and `[tenders_preview]` shortcodes

**Careers Management**
- Custom post type for job vacancies
- Relational company system (hrk_company CPT)
- Controlled department dropdown from settings
- Job type categories (Full Time/Part Time/Contract/Internship)
- Application tracking with closing dates
- Company profiles with logo support
- `[careers_list]` and `[careers_preview]` shortcodes

**News Grid**
- Editorial news template for WordPress default posts
- Asymmetric grid layout (1 large featured + 3 small sidebar posts)
- Cream background with Inter typography
- Category filtering support
- `[news_grid]` shortcode with 10+ parameters

### Admin Features

**Settings Page** (4 tabs)
- Global configuration for all modules
- Template selection for Tenders (A or B)
- Closing notification settings
- Department management for Careers
- News grid header customization

**Dashboard Widget**
- Real-time content health monitoring
- Event/Tender/Career count summaries
- Closing soon alerts with day countdown
- Missing field detection (date/ref/company)
- Transient-cached (5-minute expiry) to reduce DB load

**Automation**
- Daily wp_cron task checks tender closing dates
- Email notifications sent within threshold (default 7 days)
- Deduplication prevents repeated notifications
- Manual trigger available via Settings page

**Error Log**
- Captures PHP errors, uncaught exceptions, fatal errors, and WP_Error events
- Scoped strictly to Haraka plugin files — other plugins ignored
- Stores logs in dedicated `wp_haraka_error_log` database table
- Filterable admin UI — filter by level (error, warning, notice, exception, fatal) and resolved status
- View Details modal shows full stack trace and request context
- Mark Resolved per entry via AJAX — no page reload
- Copy for Claude button generates a pre-formatted diagnostic prompt
- Auto-purges resolved logs older than 30 days via WP-Cron

## Installation

### Requirements
- WordPress 5.8 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Via WordPress Admin
1. Download `haraka.zip` from releases
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the zip file and click **Install Now**
4. Click **Activate Plugin**

### Via FTP/cPanel
1. Extract `haraka.zip` to `/wp-content/plugins/haraka/`
2. Go to **Plugins** in WordPress admin
3. Find **Haraka** and click **Activate**

### Post-Activation Setup
1. Go to **Settings → Permalinks** and click **Save Changes** (flushes rewrite rules)
2. Go to **Haraka Settings** and configure:
   - Tenders Page URL
   - Careers Page URL
   - Notification Email
   - Closing Soon Threshold (days)
3. Create your first Company under **Companies → Add New**
4. Add content to Events, Tenders, or Careers

## Usage

### Shortcodes

#### Events
```
[events_list]
[events_list filter="upcoming"]
[events_list filter="past" order="DESC"]
[events_list posts_per_page="5" show_excerpt="no"]
```

**Parameters:**
- `filter` — all / upcoming / past (default: all)
- `category` — category slug for filtering
- `posts_per_page` — number to display (default: -1 = all)
- `order` — ASC / DESC (default: ASC)
- `show_excerpt` — yes / no (default: yes)
- `show_date` — yes / no (default: yes)

#### Tenders
```
[tenders_list]
[tenders_list filter="open"]
[tenders_preview]
[tenders_preview posts_per_page="6"]
```

**Parameters (tenders_list):**
- `filter` — all / open / closed (default: all)
- `posts_per_page` — number to display (default: -1)
- `order` — ASC / DESC (default: ASC)
- `category` — category slug for filtering
- `label` — override section label (Template B only)
- `headline` — override headline text (Template B only)
- `headline_italic` — override italic portion (Template B only)
- `view_all_text` — override link text (Template B only)

**Parameters (tenders_preview):**
- `posts_per_page` — number of open tenders (default: 4)
- `view_all_url` — override View All link URL

#### Careers
```
[careers_list]
[careers_list filter="open"]
[careers_list company="daya-bersih"]
[careers_list department="ICT" type="Full Time"]
[careers_preview]
[careers_preview company="iium-holdings" posts_per_page="3"]
```

**Parameters (careers_list):**
- `filter` — all / open / closed (default: all)
- `company` — company post slug
- `department` — exact department name
- `location` — location partial match
- `type` — Full Time / Part Time / Contract / Internship
- `posts_per_page` — number to display (default: -1)
- `order` — ASC / DESC (default: ASC)

**Parameters (careers_preview):**
- `posts_per_page` — number of open positions (default: 4)
- `company` — company post slug
- `view_all_url` — override View All link URL

#### News Grid
```
[news_grid]
[news_grid category="csr"]
[news_grid show_excerpt="no"]
[news_grid label="Our Stories" headline="Latest from" headline_italic="the group."]
```

**Parameters:**
- `label` — section label (default: from Settings)
- `headline` — regular headline text (default: from Settings)
- `headline_italic` — italic headline text (default: from Settings)
- `view_all_text` — link text (default: from Settings)
- `view_all_url` — link URL (default: from Settings)
- `category` — category slug (default: from Settings)
- `posts_per_page` — minimum 4 (default: 4)
- `show_excerpt` — yes / no (default: yes)
- `order` — DESC / ASC (default: DESC)
- `orderby` — date / title / modified / rand (default: date)

#### Category Posts
```
[category_posts category_slug="csr"]
[category_posts category_slug="news" posts_per_page="5"]
[category_posts category_slug="media" show_excerpt="no"]
```

**Parameters:**
- `category_slug` — **required** category slug
- `posts_per_page` — number to display (default: 5)
- `order` — DESC / ASC (default: DESC)
- `orderby` — date / title / modified / rand (default: date)
- `show_excerpt` — yes / no (default: yes)
- `show_date` — yes / no (default: yes)

### Recommended Page Setup

| Page | Shortcode |
|---|---|
| Homepage | `[news_grid]`<br>`[tenders_preview]`<br>`[careers_preview]` |
| Events | `[events_list filter="upcoming"]` |
| Tenders | `[tenders_list]` |
| Careers | `[careers_list filter="open"]` |
| Company-specific Careers | `[careers_list company="company-slug"]` |
| News Archive | `[news_grid category="news"]` |
| CSR Page | `[category_posts category_slug="csr"]` |

### Archive Display

Haraka serves archive listings for Events, Tenders, and Careers through two complementary mechanisms:

**Primary — WordPress pages with shortcodes.** The recommended setup is a standard WordPress page for each module (slugs `events`, `tenders`, `careers`) containing the relevant list shortcode (e.g. `[events_list]`). These pages use the active theme's page template, so they keep the site header, footer, and navigation, and they avoid conflicts with page builders such as Elementor that can intercept custom post type template loading. The archive URL fields under **Haraka Settings** point the single-page "Back" buttons at these pages.

**Fallback — CPT archive templates.** Each module also ships a `template-archive.php` file (in `includes/events/`, `includes/tenders/`, and `includes/careers/`). These hook into WordPress's `template_include` filter and automatically provide a styled archive if a visitor reaches a raw custom post type archive URL directly (for example `?post_type=hrk_event`). They are an active fallback, not dead code — if removed, those raw archive URLs would fall back to the theme's default unstyled archive. The shortcode pages above remain the primary, recommended path.

## Server Cron Setup (Recommended)

WordPress `wp_cron` is traffic-dependent. For reliable tender notifications:

1. Add to `wp-config.php`:
```php
define( 'DISABLE_WP_CRON', true );
```

2. Add cPanel cron job (daily at 8:00 AM):
```bash
wget -q -O /dev/null "https://yoursite.com/wp-cron.php?doing_wp_cron"
```

Or use PHP:
```bash
php /path/to/your/wordpress/wp-cron.php
```

## File Structure

```
haraka/
├── haraka.php                              # Plugin entry point
├── uninstall.php                           # Cleanup on deletion
├── libs/
│   └── plugin-update-checker/          # Auto-update library (YahnisElsts)
├── includes/
│   ├── core/
│   │   ├── class-haraka.php               # Main loader class
│   │   └── post-types.php                 # CPT registration
│   ├── admin/
│   │   ├── settings-page.php              # Settings UI
│   │   ├── settings-fields.php            # Field renderers
│   │   ├── cron.php                       # Email notifications
│   │   ├── dashboard-widget.php           # Admin widget
│   │   ├── error-log.php                  # Error capture, DB, AJAX, cron
│   │   ├── error-log-page.php             # Error Log tab UI
│   │   ├── docs-page.php                  # How To tab UI
│   │   └── dummy-data.php                 # Seed and clear dummy data
│   ├── events/
│   │   ├── meta-boxes.php                 # Event fields
│   │   ├── shortcode-list.php             # [events_list]
│   │   ├── template-single.php            # Single event page
│   │   └── template-archive.php           # Event archive page
│   ├── tenders/
│   │   ├── meta-boxes.php                 # Tender fields
│   │   ├── shortcode-list.php             # [tenders_list]
│   │   ├── shortcode-preview.php          # [tenders_preview]
│   │   ├── shortcode-template-b.php       # Template B renderer
│   │   ├── template-single.php            # Single tender page
│   │   └── template-archive.php           # Tender archive page
│   ├── careers/
│   │   ├── meta-boxes-company.php         # Company fields
│   │   ├── meta-boxes-career.php          # Career fields
│   │   ├── shortcode-list.php             # [careers_list]
│   │   ├── shortcode-preview.php          # [careers_preview]
│   │   ├── template-single.php            # Single career page
│   │   └── template-archive.php           # Career archive page
│   └── posts/
│       ├── shortcode-general.php          # [category_posts]
│       └── shortcode-news-grid.php        # [news_grid]
└── assets/
    ├── style-general.css                  # Base styles
    ├── style-events.css                   # Events styles + archive
    ├── style-tenders.css                  # Tenders styles (both templates + archive)
    ├── style-careers.css                  # Careers styles + archive
    ├── style-posts.css                    # News grid styles
    ├── style-admin.css                    # Admin styles
    └── style-docs.css                     # How To tab styles
```

## Custom Post Types

| CPT | Slug | Purpose |
|---|---|---|
| Events | `hrk_event` | Corporate events |
| Tenders | `hrk_tender` | Procurement opportunities |
| Careers | `hrk_career` | Job vacancies |
| Companies | `hrk_company` | Subsidiary company profiles |

## Database Tables

Haraka uses native WordPress tables:
- `wp_posts` — all CPT records
- `wp_postmeta` — all custom field values
- `wp_options` — plugin settings

No custom tables are created.

## Security Features

- Nonce verification on all meta box saves
- Capability checks (`edit_post`, `manage_options`)
- URL sanitization with `esc_url_raw()` to reject `javascript:` schemes
- Output escaping with `esc_html()`, `esc_attr()`, `esc_url()`
- No inline styles (CSP-compliant)
- Transient caching prevents dashboard DB overload

## Performance Optimizations

- Dashboard widget cached for 5 minutes
- Transient invalidation on post save/delete
- CSS versioning for cache busting
- Minimal queries in shortcodes
- Optional category filtering reduces query load
- Automatic update notifications via GitHub releases

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile responsive design

## Changelog

### 1.0.0 (2026-06-14)
- Production release — version reset for live deployment
- Added polished archive pages for Events, Tenders, and Careers
- Added safe uninstall handler (data preserved by default)
- All inline styles extracted to CSS files (CSP compliant)
- Fixed XSS vector in career_apply_url (esc_url_raw)
- Added dashboard widget transient caching (5-minute expiry)
- Refactored all CPT queries (removed WordPress post category dependency)
- Added Template B for Tenders (editorial layout)
- Added [news_grid] shortcode for WordPress default posts
- Added Careers module with hrk_company relational CPT
- Events and Tenders modules
- Dashboard widget with content health monitoring
- Email notifications for closing tenders
- Server cron configured for reliable scheduling

## Support

For issues, feature requests, or questions:
- **Internal:** Contact IIUM Holdings Web Development Team
- **GitHub Issues:** [github.com/ismetdev/haraka/issues](https://github.com/ismetdev/haraka/issues)

## License

Proprietary — Internal use only by IIUM Holdings Sdn Bhd and its subsidiaries.

## Credits

Developed for IIUM Holdings Sdn Bhd  
Version 1.0.0 | June 2026