<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Haraka — How To / Documentation Page
 *
 * Renders the How To tab inside Haraka Settings.
 * Covers all shortcodes with parameters, code examples,
 * and frontend preview mockups.
 *
 * @package Haraka
 * @subpackage Admin
 * @version 1.0.0
 */

function haraka_render_docs_tab() {
    ?>

    <div class="hrk-docs-wrap">

        <?php /* ── Sidebar navigation ── */ ?>
        <nav class="hrk-docs-sidebar">
            <div class="hrk-docs-sidebar-title">How To</div>

            <div class="hrk-docs-nav-group">
                <div class="hrk-docs-nav-group-title">Introduction</div>
                <a href="#getting-started" class="hrk-docs-nav-link active">Getting Started</a>
                <a href="#installation"    class="hrk-docs-nav-link">Installation</a>
            </div>

            <div class="hrk-docs-nav-group">
                <div class="hrk-docs-nav-group-title">Shortcodes</div>
                <a href="#events"          class="hrk-docs-nav-link">Events List</a>
                <a href="#tenders-list"    class="hrk-docs-nav-link">Tenders List</a>
                <a href="#tenders-preview" class="hrk-docs-nav-link">Tenders Preview</a>
                <a href="#careers-list"    class="hrk-docs-nav-link">Careers List</a>
                <a href="#careers-preview" class="hrk-docs-nav-link">Careers Preview</a>
                <a href="#news-grid"       class="hrk-docs-nav-link">News Grid</a>
                <a href="#category-posts"  class="hrk-docs-nav-link">Category Posts</a>
            </div>

            <div class="hrk-docs-nav-group">
                <div class="hrk-docs-nav-group-title">Reference</div>
                <a href="#page-setup"      class="hrk-docs-nav-link">Page Setup Guide</a>
                <a href="#settings-vs-shortcode" class="hrk-docs-nav-link">Settings vs Shortcode</a>
            </div>
        </nav>

        <?php /* ── Main content ── */ ?>
        <div class="hrk-docs-content">

            <?php /* ═══════════════════════════════════
               SECTION: GETTING STARTED
            ═══════════════════════════════════ */ ?>
            <div class="hrk-docs-section" id="getting-started">
                <div class="hrk-docs-section-eyebrow">Introduction</div>
                <h2 class="hrk-docs-section-title">Getting Started</h2>
                <p class="hrk-docs-section-desc">
                    Haraka is a corporate content management plugin for WordPress. It manages
                    Events, Tenders, Careers, and News across your organisation using simple shortcodes
                    that you place on any page.
                </p>

                <div class="hrk-docs-steps">
                    <div class="hrk-docs-step">
                        <div class="hrk-docs-step-num">1</div>
                        <div>
                            <div class="hrk-docs-step-title">Install and activate</div>
                            <div class="hrk-docs-step-desc">Upload the plugin via Plugins → Add New → Upload Plugin. Activate it. The plugin registers four custom post types automatically.</div>
                        </div>
                    </div>
                    <div class="hrk-docs-step">
                        <div class="hrk-docs-step-num">2</div>
                        <div>
                            <div class="hrk-docs-step-title">Flush permalinks</div>
                            <div class="hrk-docs-step-desc">Go to Settings → Permalinks and click Save Changes. This registers the custom post type URLs so archive pages resolve correctly.</div>
                        </div>
                    </div>
                    <div class="hrk-docs-step">
                        <div class="hrk-docs-step-num">3</div>
                        <div>
                            <div class="hrk-docs-step-title">Configure settings</div>
                            <div class="hrk-docs-step-desc">Go to Haraka Settings and fill in the page URLs, notification email, and content defaults for each module.</div>
                        </div>
                    </div>
                    <div class="hrk-docs-step">
                        <div class="hrk-docs-step-num">4</div>
                        <div>
                            <div class="hrk-docs-step-title">Place shortcodes on your pages</div>
                            <div class="hrk-docs-step-desc">Create pages in WordPress and add the relevant shortcode using the Shortcode block in Gutenberg or the classic editor.</div>
                        </div>
                    </div>
                </div>

                <div class="hrk-docs-callout hrk-docs-callout-tip">
                    <span class="hrk-docs-callout-icon">💡</span>
                    <div>All shortcodes work in any WordPress page builder — Gutenberg, Elementor, or the classic editor. Use the Shortcode block or paste directly into a text widget.</div>
                </div>
            </div>

            <hr class="hrk-docs-divider" id="installation">

            <?php /* ═══════════════════════════════════
               SECTION: EVENTS
            ═══════════════════════════════════ */ ?>
            <div class="hrk-docs-section" id="events">
                <div class="hrk-docs-section-eyebrow">Shortcode</div>
                <h2 class="hrk-docs-section-title">[events_list]</h2>
                <p class="hrk-docs-section-desc">
                    Displays all events in a month-grouped list sorted by date. Each event shows the date,
                    title, venue, excerpt, and status badge. Place this on your dedicated Events page.
                </p>

                <div class="hrk-docs-sub">
                    <div class="hrk-docs-sub-title">Basic usage</div>
                    <div class="hrk-docs-code-wrap">
                        <div class="hrk-docs-code-label">Shortcode</div>
                        <pre class="hrk-docs-code">[events_list]

[events_list filter="upcoming"]

[events_list filter="past" order="DESC"]

[events_list posts_per_page="5" show_excerpt="no"]</pre>
                        <button class="hrk-docs-copy-btn" data-copy='[events_list filter="upcoming"]'>Copy</button>
                    </div>

                    <div class="hrk-docs-table-wrap">
                        <table class="hrk-docs-table">
                            <thead>
                                <tr>
                                    <th class="col-param">Parameter</th>
                                    <th class="col-default">Default</th>
                                    <th class="col-desc">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="hrk-docs-param-name">filter</span></td>
                                    <td><span class="hrk-docs-param-default">all</span></td>
                                    <td>Filter events — <code>all</code>, <code>upcoming</code>, or <code>past</code></td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">posts_per_page</span></td>
                                    <td><span class="hrk-docs-param-default">-1</span></td>
                                    <td>Number of events to show. <code>-1</code> shows all.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">order</span></td>
                                    <td><span class="hrk-docs-param-default">ASC</span></td>
                                    <td><code>ASC</code> = soonest first. <code>DESC</code> = latest first.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">show_excerpt</span></td>
                                    <td><span class="hrk-docs-param-default">yes</span></td>
                                    <td>Show or hide the event excerpt. <code>yes</code> or <code>no</code>.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">show_date</span></td>
                                    <td><span class="hrk-docs-param-default">yes</span></td>
                                    <td>Show or hide the date and time line. <code>yes</code> or <code>no</code>.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">category</span></td>
                                    <td><span class="hrk-docs-param-default">(none)</span></td>
                                    <td>Optional category slug to filter events further.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="hrk-docs-preview">
                        <div class="hrk-docs-preview-label">Frontend preview — [events_list]</div>
                        <div class="hrk-docs-preview-body">
                            <div class="hrk-pv-event-list">
                                <div class="hrk-pv-month-divider">June 2026</div>
                                <div class="hrk-pv-event-row">
                                    <div class="hrk-pv-date-box">
                                        <div class="hrk-pv-date-day">14</div>
                                        <div class="hrk-pv-date-month">Jun</div>
                                    </div>
                                    <div>
                                        <div class="hrk-pv-event-title">IIUM Holdings 25th Anniversary Gala</div>
                                        <div class="hrk-pv-event-venue">Putrajaya International Convention Centre · 7:00 PM</div>
                                    </div>
                                    <span class="hrk-pv-badge hrk-pv-badge-upcoming">Upcoming</span>
                                </div>
                                <div class="hrk-pv-event-row">
                                    <div class="hrk-pv-date-box">
                                        <div class="hrk-pv-date-day">28</div>
                                        <div class="hrk-pv-date-month">Jun</div>
                                    </div>
                                    <div>
                                        <div class="hrk-pv-event-title">Subsidiary CEO Roundtable Q2</div>
                                        <div class="hrk-pv-event-venue">Boardroom, IIUM Holdings HQ · 9:00 AM</div>
                                    </div>
                                    <span class="hrk-pv-badge hrk-pv-badge-upcoming">Upcoming</span>
                                </div>
                                <div class="hrk-pv-month-divider">May 2026</div>
                                <div class="hrk-pv-event-row">
                                    <div class="hrk-pv-date-box">
                                        <div class="hrk-pv-date-day">15</div>
                                        <div class="hrk-pv-date-month">May</div>
                                    </div>
                                    <div>
                                        <div class="hrk-pv-event-title">Staff Excellence Award Ceremony</div>
                                        <div class="hrk-pv-event-venue">Auditorium, IIUM Cultural Centre · 2:00 PM</div>
                                    </div>
                                    <span class="hrk-pv-badge hrk-pv-badge-past">Past</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="hrk-docs-divider" id="tenders-list">

            <?php /* ═══════════════════════════════════
               SECTION: TENDERS LIST
            ═══════════════════════════════════ */ ?>
            <div class="hrk-docs-section">
                <div class="hrk-docs-section-eyebrow">Shortcode</div>
                <h2 class="hrk-docs-section-title">[tenders_list]</h2>
                <p class="hrk-docs-section-desc">
                    Displays all tenders in a full listing. Supports two templates — Template A
                    (searchable table with filter tabs) and Template B (editorial layout with Open/Closed tabs).
                    The template is selected globally in Haraka Settings → Tenders.
                </p>

                <div class="hrk-docs-callout hrk-docs-callout-info">
                    <span class="hrk-docs-callout-icon">ℹ</span>
                    <div>The display template (A or B) is a global setting. You cannot set a different template per shortcode instance. Changing it in Settings applies to all tender shortcodes sitewide.</div>
                </div>

                <div class="hrk-docs-sub">
                    <div class="hrk-docs-sub-title">Basic usage</div>
                    <div class="hrk-docs-code-wrap">
                        <div class="hrk-docs-code-label">Shortcode</div>
                        <pre class="hrk-docs-code">[tenders_list]

[tenders_list filter="open"]

[tenders_list filter="closed" order="DESC"]

[tenders_list posts_per_page="10"]</pre>
                        <button class="hrk-docs-copy-btn" data-copy="[tenders_list]">Copy</button>
                    </div>

                    <div class="hrk-docs-table-wrap">
                        <table class="hrk-docs-table">
                            <thead>
                                <tr>
                                    <th class="col-param">Parameter</th>
                                    <th class="col-default">Default</th>
                                    <th class="col-desc">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="hrk-docs-param-name">filter</span></td>
                                    <td><span class="hrk-docs-param-default">all</span></td>
                                    <td>Pre-filter the display — <code>all</code>, <code>open</code>, or <code>closed</code></td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">posts_per_page</span></td>
                                    <td><span class="hrk-docs-param-default">-1</span></td>
                                    <td>Number of tenders to show. <code>-1</code> shows all.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">order</span></td>
                                    <td><span class="hrk-docs-param-default">ASC</span></td>
                                    <td><code>ASC</code> = soonest closing first. <code>DESC</code> = latest first.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">label</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>Template B only — override the section label text.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">headline</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>Template B only — override the regular headline portion.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">headline_italic</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>Template B only — override the italic headline portion.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="hrk-docs-preview">
                        <div class="hrk-docs-preview-label">Frontend preview — [tenders_list] Template A</div>
                        <div class="hrk-docs-preview-body">
                            <table class="hrk-pv-tender-table">
                                <thead>
                                    <tr>
                                        <th>Ref No.</th>
                                        <th>Tender Title</th>
                                        <th>Issuer</th>
                                        <th>Closing Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="hrk-pv-ref">IIUMH/TDR/2026/001</span></td>
                                        <td>Supply and Installation of CCTV System</td>
                                        <td>IIUM Holdings</td>
                                        <td>14 Jul 2026</td>
                                        <td><span class="hrk-pv-badge hrk-pv-badge-open">Open</span></td>
                                    </tr>
                                    <tr>
                                        <td><span class="hrk-pv-ref">IIUMS/TDR/2026/002</span></td>
                                        <td>Cleaning Services — IIUM Medical Centre</td>
                                        <td>IIUM Medical</td>
                                        <td>20 Jun 2026</td>
                                        <td><span class="hrk-pv-badge hrk-pv-badge-soon">Closing Soon</span></td>
                                    </tr>
                                    <tr>
                                        <td><span class="hrk-pv-ref">IIUMH/TDR/2025/003</span></td>
                                        <td>Supply of Office Furniture — HQ Renovation</td>
                                        <td>IIUM Holdings</td>
                                        <td>15 Mar 2026</td>
                                        <td><span class="hrk-pv-badge hrk-pv-badge-closed">Closed</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="hrk-docs-divider" id="tenders-preview">

            <?php /* ═══════════════════════════════════
               SECTION: TENDERS PREVIEW
            ═══════════════════════════════════ */ ?>
            <div class="hrk-docs-section">
                <div class="hrk-docs-section-eyebrow">Shortcode</div>
                <h2 class="hrk-docs-section-title">[tenders_preview]</h2>
                <p class="hrk-docs-section-desc">
                    A compact strip showing open tenders only. Designed for your homepage or landing page
                    to surface active opportunities. Only shows tenders that are currently open or closing soon.
                </p>

                <div class="hrk-docs-sub">
                    <div class="hrk-docs-sub-title">Basic usage</div>
                    <div class="hrk-docs-code-wrap">
                        <div class="hrk-docs-code-label">Shortcode</div>
                        <pre class="hrk-docs-code">[tenders_preview]

[tenders_preview posts_per_page="6"]

[tenders_preview view_all_url="/tender"]</pre>
                        <button class="hrk-docs-copy-btn" data-copy="[tenders_preview]">Copy</button>
                    </div>

                    <div class="hrk-docs-table-wrap">
                        <table class="hrk-docs-table">
                            <thead>
                                <tr>
                                    <th class="col-param">Parameter</th>
                                    <th class="col-default">Default</th>
                                    <th class="col-desc">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="hrk-docs-param-name">posts_per_page</span></td>
                                    <td><span class="hrk-docs-param-default">4</span></td>
                                    <td>Number of open tenders to show in the strip.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">view_all_url</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>Override the View All Tenders link URL for this instance.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="hrk-docs-preview">
                        <div class="hrk-docs-preview-label">Frontend preview — [tenders_preview]</div>
                        <div class="hrk-docs-preview-body">
                            <div class="hrk-pv-tender-strip">
                                <div class="hrk-pv-strip-header">
                                    <div class="hrk-pv-strip-title">
                                        <span class="hrk-pv-strip-dot"></span>
                                        Open Tenders <span style="font-size:11px;color:#64748b;font-weight:400;margin-left:6px;">4 active</span>
                                    </div>
                                    <a class="hrk-pv-strip-link" href="#">View all tenders →</a>
                                </div>
                                <div class="hrk-pv-strip-row">
                                    <span class="hrk-pv-strip-ref">IIUMH/TDR/2026/001</span>
                                    <span class="hrk-pv-strip-title-text">Supply and Installation of CCTV System</span>
                                    <span class="hrk-pv-badge hrk-pv-badge-open">Open</span>
                                    <span class="hrk-pv-strip-date">Closes 14 Jul 2026</span>
                                </div>
                                <div class="hrk-pv-strip-row">
                                    <span class="hrk-pv-strip-ref">IIUMS/TDR/2026/002</span>
                                    <span class="hrk-pv-strip-title-text">Cleaning Services — IIUM Medical Centre</span>
                                    <span class="hrk-pv-badge hrk-pv-badge-soon">Closing Soon</span>
                                    <span class="hrk-pv-strip-date">Closes 20 Jun 2026</span>
                                </div>
                                <div class="hrk-pv-strip-row">
                                    <span class="hrk-pv-strip-ref">SBS/TDR/2026/003</span>
                                    <span class="hrk-pv-strip-title-text">Construction of New Block — Setiabudi School</span>
                                    <span class="hrk-pv-badge hrk-pv-badge-open">Open</span>
                                    <span class="hrk-pv-strip-date">Closes 30 Aug 2026</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="hrk-docs-divider" id="careers-list">

            <?php /* ═══════════════════════════════════
               SECTION: CAREERS LIST
            ═══════════════════════════════════ */ ?>
            <div class="hrk-docs-section">
                <div class="hrk-docs-section-eyebrow">Shortcode</div>
                <h2 class="hrk-docs-section-title">[careers_list]</h2>
                <p class="hrk-docs-section-desc">
                    Displays job vacancies in a searchable card grid with live filter tabs.
                    Supports filtering by company, department, location, and job type. Place this
                    on your dedicated Careers page.
                </p>

                <div class="hrk-docs-sub">
                    <div class="hrk-docs-sub-title">Basic usage</div>
                    <div class="hrk-docs-code-wrap">
                        <div class="hrk-docs-code-label">Shortcode</div>
                        <pre class="hrk-docs-code">[careers_list]

[careers_list filter="open"]

[careers_list company="daya-bersih"]

[careers_list department="ICT" type="Full Time"]</pre>
                        <button class="hrk-docs-copy-btn" data-copy='[careers_list filter="open"]'>Copy</button>
                    </div>

                    <div class="hrk-docs-callout hrk-docs-callout-warning">
                        <span class="hrk-docs-callout-icon">⚠</span>
                        <div>The <code>company</code> parameter uses the company <strong>post slug</strong>, not the company name. Find it in WordPress Admin → Companies → Edit → look at the URL slug field. For example: <code>company="iium-holdings"</code></div>
                    </div>

                    <div class="hrk-docs-table-wrap">
                        <table class="hrk-docs-table">
                            <thead>
                                <tr>
                                    <th class="col-param">Parameter</th>
                                    <th class="col-default">Default</th>
                                    <th class="col-desc">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="hrk-docs-param-name">filter</span></td>
                                    <td><span class="hrk-docs-param-default">all</span></td>
                                    <td>Filter positions — <code>all</code>, <code>open</code>, or <code>closed</code></td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">company</span></td>
                                    <td><span class="hrk-docs-param-default">(none)</span></td>
                                    <td>Company post slug — show positions from one company only.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">department</span></td>
                                    <td><span class="hrk-docs-param-default">(none)</span></td>
                                    <td>Exact department name — e.g. <code>ICT</code>, <code>Finance</code></td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">location</span></td>
                                    <td><span class="hrk-docs-param-default">(none)</span></td>
                                    <td>Location partial match — e.g. <code>Gombak</code></td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">type</span></td>
                                    <td><span class="hrk-docs-param-default">(none)</span></td>
                                    <td><code>Full Time</code>, <code>Part Time</code>, <code>Contract</code>, or <code>Internship</code></td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">posts_per_page</span></td>
                                    <td><span class="hrk-docs-param-default">-1</span></td>
                                    <td>Number of positions to show. <code>-1</code> shows all.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">order</span></td>
                                    <td><span class="hrk-docs-param-default">ASC</span></td>
                                    <td><code>ASC</code> = soonest closing first. <code>DESC</code> = latest first.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="hrk-docs-preview">
                        <div class="hrk-docs-preview-label">Frontend preview — [careers_list]</div>
                        <div class="hrk-docs-preview-body">
                            <div class="hrk-pv-career-grid">
                                <div class="hrk-pv-career-card">
                                    <div class="hrk-pv-career-top">
                                        <span class="hrk-pv-career-type">Full Time</span>
                                        <span class="hrk-pv-badge hrk-pv-badge-open">Open</span>
                                    </div>
                                    <div class="hrk-pv-career-title">Senior HR Manager</div>
                                    <div class="hrk-pv-career-company">IIUM Holdings Sdn Bhd</div>
                                    <div class="hrk-pv-career-meta">
                                        <div>
                                            <div class="hrk-pv-meta-label">Department</div>
                                            <div class="hrk-pv-meta-value">Human Resource</div>
                                        </div>
                                        <div>
                                            <div class="hrk-pv-meta-label">Location</div>
                                            <div class="hrk-pv-meta-value">Gombak</div>
                                        </div>
                                    </div>
                                    <div class="hrk-pv-career-footer">
                                        <span>Closes 14 Jul 2026</span>
                                        <span>→</span>
                                    </div>
                                </div>
                                <div class="hrk-pv-career-card">
                                    <div class="hrk-pv-career-top">
                                        <span class="hrk-pv-career-type">Contract</span>
                                        <span class="hrk-pv-badge hrk-pv-badge-soon">Closing Soon</span>
                                    </div>
                                    <div class="hrk-pv-career-title">ICT Project Manager</div>
                                    <div class="hrk-pv-career-company">IIUM Holdings Sdn Bhd</div>
                                    <div class="hrk-pv-career-meta">
                                        <div>
                                            <div class="hrk-pv-meta-label">Department</div>
                                            <div class="hrk-pv-meta-value">ICT</div>
                                        </div>
                                        <div>
                                            <div class="hrk-pv-meta-label">Location</div>
                                            <div class="hrk-pv-meta-value">Gombak</div>
                                        </div>
                                    </div>
                                    <div class="hrk-pv-career-footer">
                                        <span>Closes 21 Jun 2026</span>
                                        <span>→</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="hrk-docs-divider" id="careers-preview">

            <?php /* ═══════════════════════════════════
               SECTION: CAREERS PREVIEW
            ═══════════════════════════════════ */ ?>
            <div class="hrk-docs-section">
                <div class="hrk-docs-section-eyebrow">Shortcode</div>
                <h2 class="hrk-docs-section-title">[careers_preview]</h2>
                <p class="hrk-docs-section-desc">
                    A compact strip showing open positions. Designed for your homepage alongside
                    the tenders preview. Only shows positions that are currently open or closing soon.
                </p>

                <div class="hrk-docs-sub">
                    <div class="hrk-docs-sub-title">Basic usage</div>
                    <div class="hrk-docs-code-wrap">
                        <div class="hrk-docs-code-label">Shortcode</div>
                        <pre class="hrk-docs-code">[careers_preview]

[careers_preview posts_per_page="6"]

[careers_preview company="daya-bersih" posts_per_page="3"]</pre>
                        <button class="hrk-docs-copy-btn" data-copy="[careers_preview]">Copy</button>
                    </div>

                    <div class="hrk-docs-table-wrap">
                        <table class="hrk-docs-table">
                            <thead>
                                <tr>
                                    <th class="col-param">Parameter</th>
                                    <th class="col-default">Default</th>
                                    <th class="col-desc">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="hrk-docs-param-name">posts_per_page</span></td>
                                    <td><span class="hrk-docs-param-default">4</span></td>
                                    <td>Number of open positions to show in the strip.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">company</span></td>
                                    <td><span class="hrk-docs-param-default">(none)</span></td>
                                    <td>Company post slug — filter to one company only.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">view_all_url</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>Override the View All Positions link URL for this instance.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <hr class="hrk-docs-divider" id="news-grid">

            <?php /* ═══════════════════════════════════
               SECTION: NEWS GRID
            ═══════════════════════════════════ */ ?>
            <div class="hrk-docs-section">
                <div class="hrk-docs-section-eyebrow">Shortcode</div>
                <h2 class="hrk-docs-section-title">[news_grid]</h2>
                <p class="hrk-docs-section-desc">
                    An editorial news grid using WordPress default posts. Displays one large featured post
                    on the left with three smaller posts in a sidebar column on the right. Uses a cream
                    background with Inter typography for a premium editorial feel.
                </p>

                <div class="hrk-docs-sub">
                    <div class="hrk-docs-sub-title">Basic usage</div>
                    <div class="hrk-docs-code-wrap">
                        <div class="hrk-docs-code-label">Shortcode</div>
                        <pre class="hrk-docs-code">[news_grid]

[news_grid category="csr"]

[news_grid label="Our Stories" headline="Latest from" headline_italic="the group."]

[news_grid show_excerpt="no" posts_per_page="4"]</pre>
                        <button class="hrk-docs-copy-btn" data-copy="[news_grid]">Copy</button>
                    </div>

                    <div class="hrk-docs-table-wrap">
                        <table class="hrk-docs-table">
                            <thead>
                                <tr>
                                    <th class="col-param">Parameter</th>
                                    <th class="col-default">Default</th>
                                    <th class="col-desc">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="hrk-docs-param-name">label</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>Section label above the headline — e.g. <code>Impact &amp; Activities</code></td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">headline</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>Regular-weight headline text.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">headline_italic</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>Italic portion of the headline — appears after the regular text.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">view_all_text</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>View All link label text.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">view_all_url</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>View All link URL.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">category</span></td>
                                    <td><span class="hrk-docs-param-default">Settings</span></td>
                                    <td>Category slug to filter posts — leave blank for all posts.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">posts_per_page</span></td>
                                    <td><span class="hrk-docs-param-default">4</span></td>
                                    <td>Minimum 4. First post is featured, remaining fill the sidebar.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">show_excerpt</span></td>
                                    <td><span class="hrk-docs-param-default">yes</span></td>
                                    <td>Show or hide excerpt on the featured post.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">order</span></td>
                                    <td><span class="hrk-docs-param-default">DESC</span></td>
                                    <td><code>DESC</code> = newest first. <code>ASC</code> = oldest first.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">orderby</span></td>
                                    <td><span class="hrk-docs-param-default">date</span></td>
                                    <td><code>date</code>, <code>title</code>, <code>modified</code>, or <code>rand</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="hrk-docs-preview">
                        <div class="hrk-docs-preview-label">Frontend preview — [news_grid]</div>
                        <div class="hrk-docs-preview-body">
                            <div class="hrk-pv-news-wrap">
                                <div class="hrk-pv-news-header">
                                    <div class="hrk-pv-news-label">— Impact &amp; Activities</div>
                                    <div class="hrk-pv-news-title">News, milestones and <em>community work.</em></div>
                                </div>
                                <div class="hrk-pv-news-grid">
                                    <div class="hrk-pv-news-featured">
                                        <div class="hrk-pv-news-featured-img">Featured Post</div>
                                        <div class="hrk-pv-news-featured-body">
                                            <div class="hrk-pv-news-featured-cat">CSR · 14 Jun 2026</div>
                                            <div class="hrk-pv-news-featured-title">Kasih Ramadan 2026 — IIUM Holdings Distributes 500 Iftar Packs to Gombak Community</div>
                                        </div>
                                    </div>
                                    <div class="hrk-pv-news-sidebar">
                                        <div class="hrk-pv-news-small">
                                            <div class="hrk-pv-news-small-img"></div>
                                            <div class="hrk-pv-news-small-body">
                                                <div class="hrk-pv-news-small-cat">Events · 10 Jun 2026</div>
                                                <div class="hrk-pv-news-small-title">25th Anniversary Gala Planning Underway</div>
                                            </div>
                                        </div>
                                        <div class="hrk-pv-news-small">
                                            <div class="hrk-pv-news-small-img"></div>
                                            <div class="hrk-pv-news-small-body">
                                                <div class="hrk-pv-news-small-cat">Corporate · 08 Jun 2026</div>
                                                <div class="hrk-pv-news-small-title">RISE2030 Mid-Year Review Completed</div>
                                            </div>
                                        </div>
                                        <div class="hrk-pv-news-small">
                                            <div class="hrk-pv-news-small-img"></div>
                                            <div class="hrk-pv-news-small-body">
                                                <div class="hrk-pv-news-small-cat">Education · 05 Jun 2026</div>
                                                <div class="hrk-pv-news-small-title">IIUM Schools Wins National Excellence Award</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="hrk-docs-divider" id="category-posts">

            <?php /* ═══════════════════════════════════
               SECTION: CATEGORY POSTS
            ═══════════════════════════════════ */ ?>
            <div class="hrk-docs-section">
                <div class="hrk-docs-section-eyebrow">Shortcode</div>
                <h2 class="hrk-docs-section-title">[category_posts]</h2>
                <p class="hrk-docs-section-desc">
                    A general-purpose post list filtered by a specific WordPress category slug.
                    Simpler than the news grid — plain list layout suitable for CSR pages, media pages,
                    or any section requiring category-filtered posts.
                </p>

                <div class="hrk-docs-sub">
                    <div class="hrk-docs-sub-title">Basic usage</div>
                    <div class="hrk-docs-code-wrap">
                        <div class="hrk-docs-code-label">Shortcode</div>
                        <pre class="hrk-docs-code">[category_posts category_slug="csr"]

[category_posts category_slug="news" posts_per_page="5"]

[category_posts category_slug="media" show_excerpt="no"]</pre>
                        <button class="hrk-docs-copy-btn" data-copy='[category_posts category_slug="csr"]'>Copy</button>
                    </div>

                    <div class="hrk-docs-callout hrk-docs-callout-warning">
                        <span class="hrk-docs-callout-icon">⚠</span>
                        <div><code>category_slug</code> is required. If omitted the shortcode will return an error message. Find category slugs in WordPress Admin → Posts → Categories.</div>
                    </div>

                    <div class="hrk-docs-table-wrap">
                        <table class="hrk-docs-table">
                            <thead>
                                <tr>
                                    <th class="col-param">Parameter</th>
                                    <th class="col-default">Default</th>
                                    <th class="col-desc">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="hrk-docs-param-name">category_slug</span> <span class="hrk-docs-param-required">required</span></td>
                                    <td><span class="hrk-docs-param-default">(none)</span></td>
                                    <td>WordPress category slug — e.g. <code>csr</code>, <code>news</code>, <code>media</code></td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">posts_per_page</span></td>
                                    <td><span class="hrk-docs-param-default">5</span></td>
                                    <td>Number of posts to display.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">order</span></td>
                                    <td><span class="hrk-docs-param-default">DESC</span></td>
                                    <td><code>DESC</code> = newest first. <code>ASC</code> = oldest first.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">orderby</span></td>
                                    <td><span class="hrk-docs-param-default">date</span></td>
                                    <td><code>date</code>, <code>title</code>, <code>modified</code>, or <code>rand</code></td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">show_excerpt</span></td>
                                    <td><span class="hrk-docs-param-default">yes</span></td>
                                    <td>Show or hide post excerpt.</td>
                                </tr>
                                <tr>
                                    <td><span class="hrk-docs-param-name">show_date</span></td>
                                    <td><span class="hrk-docs-param-default">yes</span></td>
                                    <td>Show or hide the published date.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <hr class="hrk-docs-divider" id="page-setup">

            <?php /* ═══════════════════════════════════
               SECTION: PAGE SETUP GUIDE
            ═══════════════════════════════════ */ ?>
            <div class="hrk-docs-section">
                <div class="hrk-docs-section-eyebrow">Reference</div>
                <h2 class="hrk-docs-section-title">Page Setup Guide</h2>
                <p class="hrk-docs-section-desc">
                    Use this table to decide which shortcode to place on each page of your website.
                    Create these pages in WordPress Admin → Pages → Add New, then add the relevant
                    shortcode using a Shortcode block.
                </p>

                <table class="hrk-docs-setup-table">
                    <thead>
                        <tr>
                            <th style="width:180px;">Page</th>
                            <th style="width:260px;">Recommended Shortcode</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Homepage / Landing</strong></td>
                            <td>
                                <span class="hrk-docs-sc-pill">[news_grid]</span>
                                <span class="hrk-docs-sc-pill">[tenders_preview]</span>
                                <span class="hrk-docs-sc-pill">[careers_preview]</span>
                            </td>
                            <td>Place all three in separate Shortcode blocks. News grid at top, tenders and careers preview below.</td>
                        </tr>
                        <tr>
                            <td><strong>Events Page</strong></td>
                            <td><span class="hrk-docs-sc-pill">[events_list filter="upcoming"]</span></td>
                            <td>Shows upcoming events by default. Add a second <code>[events_list filter="past"]</code> block below for past events.</td>
                        </tr>
                        <tr>
                            <td><strong>Tenders Page</strong></td>
                            <td><span class="hrk-docs-sc-pill">[tenders_list]</span></td>
                            <td>Full listing. Template A or B set in Settings. The filter tabs handle Open/Closed switching on the page.</td>
                        </tr>
                        <tr>
                            <td><strong>Careers Page</strong></td>
                            <td><span class="hrk-docs-sc-pill">[careers_list filter="open"]</span></td>
                            <td>Shows open positions by default. Visitors can switch to Closed via the filter tabs.</td>
                        </tr>
                        <tr>
                            <td><strong>Company Careers</strong></td>
                            <td><span class="hrk-docs-sc-pill">[careers_list company="slug"]</span></td>
                            <td>Replace <code>slug</code> with the actual company post slug from WordPress Admin → Companies.</td>
                        </tr>
                        <tr>
                            <td><strong>News Archive</strong></td>
                            <td><span class="hrk-docs-sc-pill">[news_grid category="news"]</span></td>
                            <td>Or use <code>[category_posts category_slug="news"]</code> for a simpler list layout.</td>
                        </tr>
                        <tr>
                            <td><strong>CSR Page</strong></td>
                            <td><span class="hrk-docs-sc-pill">[category_posts category_slug="csr"]</span></td>
                            <td>Filters WordPress posts to the CSR category. Create the category first in Posts → Categories.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <hr class="hrk-docs-divider" id="settings-vs-shortcode">

            <?php /* ═══════════════════════════════════
               SECTION: SETTINGS VS SHORTCODE
            ═══════════════════════════════════ */ ?>
            <div class="hrk-docs-section">
                <div class="hrk-docs-section-eyebrow">Reference</div>
                <h2 class="hrk-docs-section-title">Settings vs Shortcode Parameters</h2>
                <p class="hrk-docs-section-desc">
                    Haraka Settings act as site-wide defaults. Shortcode parameters override those defaults
                    for that specific instance only.
                </p>

                <div class="hrk-docs-callout hrk-docs-callout-tip">
                    <span class="hrk-docs-callout-icon">💡</span>
                    <div><strong>Settings</strong> = default for every shortcode on the site.<br><strong>Shortcode parameter</strong> = override for that one page only. Settings remain unchanged.</div>
                </div>

                <div class="hrk-docs-callout hrk-docs-callout-info">
                    <span class="hrk-docs-callout-icon">ℹ</span>
                    <div>The <strong>Tenders template</strong> (A or B) is the only setting that cannot be overridden per shortcode. It applies globally to all <code>[tenders_list]</code> and <code>[tenders_preview]</code> instances.</div>
                </div>

                <div class="hrk-docs-callout hrk-docs-callout-warning">
                    <span class="hrk-docs-callout-icon">⚠</span>
                    <div>An empty shortcode parameter falls back to the Settings default. For example <code>[news_grid category=""]</code> is the same as <code>[news_grid]</code> — both use whatever category is set in Haraka Settings → Posts.</div>
                </div>
            </div>

        </div>

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
    (function() {

        // ── Copy buttons ──────────────────────────────────────────────────────
        document.querySelectorAll('.hrk-docs-copy-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var text = btn.getAttribute('data-copy');
                navigator.clipboard.writeText(text)
                    .then(function() {
                        btn.textContent = 'Copied!';
                        btn.classList.add('copied');
                        setTimeout(function() {
                            btn.textContent = 'Copy';
                            btn.classList.remove('copied');
                        }, 2000);
                    })
                    .catch(function() {
                        // Fallback
                        var ta = document.createElement('textarea');
                        ta.value = text;
                        ta.style.position = 'fixed';
                        ta.style.opacity  = '0';
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                        btn.textContent = 'Copied!';
                        btn.classList.add('copied');
                        setTimeout(function() {
                            btn.textContent = 'Copy';
                            btn.classList.remove('copied');
                        }, 2000);
                    });
            });
        });

        // ── Active sidebar link on scroll ─────────────────────────────────────
        var sections = document.querySelectorAll('.hrk-docs-section[id]');
        var navLinks = document.querySelectorAll('.hrk-docs-nav-link');

        window.addEventListener('scroll', function() {
            var scrollTop = window.scrollY || document.documentElement.scrollTop;
            var active    = null;

            sections.forEach(function(section) {
                if (section.getBoundingClientRect().top <= 100) {
                    active = section.id;
                }
            });

            navLinks.forEach(function(link) {
                link.classList.remove('active');
                if (active && link.getAttribute('href') === '#' + active) {
                    link.classList.add('active');
                }
            });
        });

        // ── Smooth scroll on sidebar click ────────────────────────────────────
        navLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                var href = link.getAttribute('href');
                if (href && href.startsWith('#')) {
                    e.preventDefault();
                    var target = document.querySelector(href);
                    if (target) {
                        var top = target.getBoundingClientRect().top + window.scrollY - 80;
                        window.scrollTo({ top: top, behavior: 'smooth' });
                    }
                    navLinks.forEach(function(l) { l.classList.remove('active'); });
                    link.classList.add('active');
                }
            });
        });        

    })();
    }); // DOMContentLoaded
    </script>

    <?php
}