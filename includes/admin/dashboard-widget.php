<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Register the dashboard widget ─────────────────────────────────────────────
function haraka_register_dashboard_widget() {
    wp_add_dashboard_widget(
        'haraka_dashboard_widget',
        'Haraka — Content Health',
        'haraka_dashboard_widget_html'
    );
}
add_action( 'wp_dashboard_setup', 'haraka_register_dashboard_widget' );


// ── Invalidate cache when any Haraka post is saved or deleted ─────────────────
function haraka_invalidate_dashboard_cache( $post_id ) {
    $post_type = get_post_type( $post_id );
    if ( in_array( $post_type, array( 'hrk_event', 'hrk_tender', 'hrk_career' ), true ) ) {
        delete_transient( 'haraka_dashboard_counts' );
    }
}
add_action( 'save_post',   'haraka_invalidate_dashboard_cache' );
add_action( 'delete_post', 'haraka_invalidate_dashboard_cache' );
add_action( 'trash_post',  'haraka_invalidate_dashboard_cache' );


// ── Compute dashboard counts — cached for 5 minutes ──────────────────────────
function haraka_get_dashboard_counts() {

    $cached = get_transient( 'haraka_dashboard_counts' );
    if ( $cached !== false ) {
        return $cached;
    }

    $today     = date( 'Y-m-d' );
    $threshold = (int) get_option( 'haraka_closing_soon_days', 7 );

    // ── Events ────────────────────────────────────────────────────────────────
    $events_upcoming = new WP_Query( array(
        'post_type'      => 'hrk_event',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => 'event_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ),
        ),
    ) );

    $events_past = new WP_Query( array(
        'post_type'      => 'hrk_event',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => 'event_date',
                'value'   => $today,
                'compare' => '<',
                'type'    => 'DATE',
            ),
        ),
    ) );

    $events_no_date = new WP_Query( array(
        'post_type'      => 'hrk_event',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => 'event_date',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => 'event_date',
                'value'   => '',
                'compare' => '=',
            ),
        ),
    ) );

    // ── Tenders ───────────────────────────────────────────────────────────────
    $all_tenders = new WP_Query( array(
        'post_type'      => 'hrk_tender',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'tender_close_date',
                'compare' => 'EXISTS',
            ),
        ),
    ) );

    $count_t_open        = 0;
    $count_t_soon        = 0;
    $count_t_closed      = 0;
    $count_no_ref        = 0;
    $closing_soon_list   = array();

    if ( $all_tenders->have_posts() ) :
        while ( $all_tenders->have_posts() ) :
            $all_tenders->the_post();
            $post_id    = get_the_ID();
            $close_date = get_post_meta( $post_id, 'tender_close_date', true );
            $ref        = get_post_meta( $post_id, 'tender_ref',        true );

            if ( empty( $ref ) ) {
                $count_no_ref++;
            }

            if ( empty( $close_date ) ) continue;

            $date_obj  = date_create( $close_date );
            if ( ! $date_obj ) continue;

            $today_obj = new DateTime( 'today' );
            $is_past   = $date_obj < $today_obj;
            $days_left = (int) $today_obj->diff( $date_obj )->days;

            if ( $is_past ) {
                $count_t_closed++;
            } elseif ( $days_left <= $threshold ) {
                $count_t_soon++;
                $closing_soon_list[] = array(
                    'title'     => get_the_title(),
                    'ref'       => $ref,
                    'days_left' => $days_left,
                    'close_fmt' => $date_obj->format( 'd M Y' ),
                    'edit_url'  => get_edit_post_link( $post_id ),
                );
            } else {
                $count_t_open++;
            }
        endwhile;
        wp_reset_postdata();
    endif;

    $count_no_close_date = ( new WP_Query( array(
        'post_type'      => 'hrk_tender',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => 'tender_close_date',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => 'tender_close_date',
                'value'   => '',
                'compare' => '=',
            ),
        ),
    ) ) )->found_posts;

    // ── Careers ───────────────────────────────────────────────────────────────
    $all_careers = new WP_Query( array(
        'post_type'      => 'hrk_career',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'career_close_date',
                'compare' => 'EXISTS',
            ),
        ),
    ) );

    $count_c_open       = 0;
    $count_c_soon       = 0;
    $count_c_closed     = 0;
    $count_no_company   = 0;

    if ( $all_careers->have_posts() ) :
        while ( $all_careers->have_posts() ) :
            $all_careers->the_post();
            $post_id    = get_the_ID();
            $close_date = get_post_meta( $post_id, 'career_close_date',  true );
            $company_id = get_post_meta( $post_id, 'career_company_id',  true );

            if ( empty( $company_id ) ) {
                $count_no_company++;
            }

            if ( empty( $close_date ) ) continue;

            $date_obj  = date_create( $close_date );
            if ( ! $date_obj ) continue;

            $today_obj = new DateTime( 'today' );
            $is_past   = $date_obj < $today_obj;
            $days_left = (int) $today_obj->diff( $date_obj )->days;

            if ( $is_past ) {
                $count_c_closed++;
            } elseif ( $days_left <= $threshold ) {
                $count_c_soon++;
            } else {
                $count_c_open++;
            }
        endwhile;
        wp_reset_postdata();
    endif;

    $count_no_career_date = ( new WP_Query( array(
        'post_type'      => 'hrk_career',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => 'career_close_date',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => 'career_close_date',
                'value'   => '',
                'compare' => '=',
            ),
        ),
    ) ) )->found_posts;

    // ── Build counts array ────────────────────────────────────────────────────
    $counts = array(
        'events_upcoming'      => $events_upcoming->found_posts,
        'events_past'          => $events_past->found_posts,
        'events_no_date'       => $events_no_date->found_posts,
        'count_t_open'         => $count_t_open,
        'count_t_soon'         => $count_t_soon,
        'count_t_closed'       => $count_t_closed,
        'count_no_ref'         => $count_no_ref,
        'count_no_close_date'  => $count_no_close_date,
        'closing_soon_list'    => $closing_soon_list,
        'count_c_open'         => $count_c_open,
        'count_c_soon'         => $count_c_soon,
        'count_c_closed'       => $count_c_closed,
        'count_no_company'     => $count_no_company,
        'count_no_career_date' => $count_no_career_date,
    );

    // ── Cache for 5 minutes ───────────────────────────────────────────────────
    set_transient( 'haraka_dashboard_counts', $counts, 5 * MINUTE_IN_SECONDS );

    return $counts;
}


// ── Widget HTML ───────────────────────────────────────────────────────────────
function haraka_dashboard_widget_html() {

    $threshold = (int) get_option( 'haraka_closing_soon_days', 7 );
    $c         = haraka_get_dashboard_counts();

    $total_issues = $c['events_no_date']
                  + $c['count_no_ref']
                  + $c['count_no_close_date']
                  + $c['count_no_company']
                  + $c['count_no_career_date'];
    ?>

    <style>
        #haraka_dashboard_widget .inside { margin:0; padding:0; }
        .hrk-dw-wrap { font-family:-apple-system,'Segoe UI',sans-serif; font-size:13px; }
        .hrk-dw-section { padding:14px 16px; border-bottom:1px solid #f1f5f9; }
        .hrk-dw-section:last-child { border-bottom:none; }
        .hrk-dw-section-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#94a3b8; margin-bottom:10px; }
        .hrk-dw-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
        .hrk-dw-stat { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; text-align:center; }
        .hrk-dw-stat-number { font-size:22px; font-weight:700; color:#0f172a; line-height:1; display:block; margin-bottom:4px; }
        .hrk-dw-stat-label { font-size:11px; color:#64748b; }
        .hrk-dw-stat-upcoming .hrk-dw-stat-number { color:#16a34a; }
        .hrk-dw-stat-past     .hrk-dw-stat-number { color:#94a3b8; }
        .hrk-dw-stat-open     .hrk-dw-stat-number { color:#16a34a; }
        .hrk-dw-stat-soon     .hrk-dw-stat-number { color:#d97706; }
        .hrk-dw-stat-closed   .hrk-dw-stat-number { color:#94a3b8; }
        .hrk-dw-closing-item { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f8fafc; gap:10px; }
        .hrk-dw-closing-item:last-child { border-bottom:none; }
        .hrk-dw-closing-title { font-size:12px; font-weight:500; color:#0f172a; flex:1; line-height:1.35; }
        .hrk-dw-closing-ref { font-size:11px; color:#94a3b8; font-family:monospace; }
        .hrk-dw-closing-days { font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px; white-space:nowrap; flex-shrink:0; }
        .hrk-dw-days-urgent  { background:#fee2e2; color:#991b1b; }
        .hrk-dw-days-warning { background:#fef9c3; color:#a16207; }
        .hrk-dw-issue-row { display:flex; align-items:center; gap:8px; padding:6px 0; font-size:12px; color:#475569; border-bottom:1px solid #f8fafc; }
        .hrk-dw-issue-row:last-child { border-bottom:none; }
        .hrk-dw-issue-icon { width:18px; height:18px; border-radius:50%; background:#fef9c3; color:#a16207; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0; }
        .hrk-dw-all-good { display:flex; align-items:center; gap:8px; padding:8px 0; font-size:12px; color:#16a34a; font-weight:500; }
        .hrk-dw-footer { padding:10px 16px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; gap:10px; flex-wrap:wrap; }
        .hrk-dw-footer a { font-size:12px; font-weight:500; color:#0056b3; text-decoration:none; }
        .hrk-dw-footer a:hover { text-decoration:underline; }
        .hrk-dw-footer-sep { color:#e2e8f0; }
        .hrk-dw-cache-note { font-size:10px; color:#cbd5e1; padding:4px 16px 6px; text-align:right; }
    </style>

    <div class="hrk-dw-wrap">

        <?php /* ── Events ── */ ?>
        <div class="hrk-dw-section">
            <div class="hrk-dw-section-title">Events</div>
            <div class="hrk-dw-stats">
                <div class="hrk-dw-stat hrk-dw-stat-upcoming">
                    <span class="hrk-dw-stat-number"><?php echo esc_html( $c['events_upcoming'] ); ?></span>
                    <span class="hrk-dw-stat-label">Upcoming</span>
                </div>
                <div class="hrk-dw-stat hrk-dw-stat-past">
                    <span class="hrk-dw-stat-number"><?php echo esc_html( $c['events_past'] ); ?></span>
                    <span class="hrk-dw-stat-label">Past</span>
                </div>
                <div class="hrk-dw-stat">
                    <span class="hrk-dw-stat-number"><?php echo esc_html( $c['events_upcoming'] + $c['events_past'] ); ?></span>
                    <span class="hrk-dw-stat-label">Total</span>
                </div>
            </div>
        </div>

        <?php /* ── Tenders ── */ ?>
        <div class="hrk-dw-section">
            <div class="hrk-dw-section-title">Tenders</div>
            <div class="hrk-dw-stats">
                <div class="hrk-dw-stat hrk-dw-stat-open">
                    <span class="hrk-dw-stat-number"><?php echo esc_html( $c['count_t_open'] ); ?></span>
                    <span class="hrk-dw-stat-label">Open</span>
                </div>
                <div class="hrk-dw-stat hrk-dw-stat-soon">
                    <span class="hrk-dw-stat-number"><?php echo esc_html( $c['count_t_soon'] ); ?></span>
                    <span class="hrk-dw-stat-label">Closing Soon</span>
                </div>
                <div class="hrk-dw-stat hrk-dw-stat-closed">
                    <span class="hrk-dw-stat-number"><?php echo esc_html( $c['count_t_closed'] ); ?></span>
                    <span class="hrk-dw-stat-label">Closed</span>
                </div>
            </div>
        </div>

        <?php /* ── Careers ── */ ?>
        <div class="hrk-dw-section">
            <div class="hrk-dw-section-title">Careers</div>
            <div class="hrk-dw-stats">
                <div class="hrk-dw-stat hrk-dw-stat-open">
                    <span class="hrk-dw-stat-number"><?php echo esc_html( $c['count_c_open'] ); ?></span>
                    <span class="hrk-dw-stat-label">Open</span>
                </div>
                <div class="hrk-dw-stat hrk-dw-stat-soon">
                    <span class="hrk-dw-stat-number"><?php echo esc_html( $c['count_c_soon'] ); ?></span>
                    <span class="hrk-dw-stat-label">Closing Soon</span>
                </div>
                <div class="hrk-dw-stat hrk-dw-stat-closed">
                    <span class="hrk-dw-stat-number"><?php echo esc_html( $c['count_c_closed'] ); ?></span>
                    <span class="hrk-dw-stat-label">Closed</span>
                </div>
            </div>
        </div>

        <?php /* ── Closing Soon List ── */ ?>
        <?php if ( ! empty( $c['closing_soon_list'] ) ) : ?>
        <div class="hrk-dw-section">
            <div class="hrk-dw-section-title">
                Tenders Closing Within <?php echo esc_html( $threshold ); ?> Days
            </div>
            <?php foreach ( $c['closing_soon_list'] as $item ) : ?>
                <div class="hrk-dw-closing-item">
                    <div>
                        <div class="hrk-dw-closing-title">
                            <a href="<?php echo esc_url( $item['edit_url'] ); ?>">
                                <?php echo esc_html( $item['title'] ); ?>
                            </a>
                        </div>
                        <?php if ( ! empty( $item['ref'] ) ) : ?>
                            <div class="hrk-dw-closing-ref">
                                <?php echo esc_html( $item['ref'] ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="hrk-dw-closing-days <?php echo $item['days_left'] <= 3 ? 'hrk-dw-days-urgent' : 'hrk-dw-days-warning'; ?>">
                        <?php echo esc_html( $item['days_left'] ); ?>d left
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php /* ── Content Health ── */ ?>
        <div class="hrk-dw-section">
            <div class="hrk-dw-section-title">Content Health</div>

            <?php if ( $total_issues === 0 ) : ?>
                <div class="hrk-dw-all-good">
                    &#10003; All content looks good &mdash; no missing fields detected
                </div>
            <?php else : ?>

                <?php if ( $c['events_no_date'] > 0 ) : ?>
                <div class="hrk-dw-issue-row">
                    <div class="hrk-dw-issue-icon">!</div>
                    <div>
                        <strong><?php echo esc_html( $c['events_no_date'] ); ?></strong>
                        event<?php echo $c['events_no_date'] > 1 ? 's' : ''; ?> missing an event date
                        &mdash; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hrk_event' ) ); ?>">fix now</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $c['count_no_ref'] > 0 ) : ?>
                <div class="hrk-dw-issue-row">
                    <div class="hrk-dw-issue-icon">!</div>
                    <div>
                        <strong><?php echo esc_html( $c['count_no_ref'] ); ?></strong>
                        tender<?php echo $c['count_no_ref'] > 1 ? 's' : ''; ?> missing a reference number
                        &mdash; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hrk_tender' ) ); ?>">fix now</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $c['count_no_close_date'] > 0 ) : ?>
                <div class="hrk-dw-issue-row">
                    <div class="hrk-dw-issue-icon">!</div>
                    <div>
                        <strong><?php echo esc_html( $c['count_no_close_date'] ); ?></strong>
                        tender<?php echo $c['count_no_close_date'] > 1 ? 's' : ''; ?> missing a closing date
                        &mdash; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hrk_tender' ) ); ?>">fix now</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $c['count_no_company'] > 0 ) : ?>
                <div class="hrk-dw-issue-row">
                    <div class="hrk-dw-issue-icon">!</div>
                    <div>
                        <strong><?php echo esc_html( $c['count_no_company'] ); ?></strong>
                        position<?php echo $c['count_no_company'] > 1 ? 's' : ''; ?> missing a company assignment
                        &mdash; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hrk_career' ) ); ?>">fix now</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $c['count_no_career_date'] > 0 ) : ?>
                <div class="hrk-dw-issue-row">
                    <div class="hrk-dw-issue-icon">!</div>
                    <div>
                        <strong><?php echo esc_html( $c['count_no_career_date'] ); ?></strong>
                        position<?php echo $c['count_no_career_date'] > 1 ? 's' : ''; ?> missing a closing date
                        &mdash; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hrk_career' ) ); ?>">fix now</a>
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

        <?php /* ── Footer links ── */ ?>
        <div class="hrk-dw-footer">
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hrk_event' ) ); ?>">Events</a>
            <span class="hrk-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=hrk_event' ) ); ?>">+ Event</a>
            <span class="hrk-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hrk_tender' ) ); ?>">Tenders</a>
            <span class="hrk-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=hrk_tender' ) ); ?>">+ Tender</a>
            <span class="hrk-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hrk_career' ) ); ?>">Careers</a>
            <span class="hrk-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=hrk_career' ) ); ?>">+ Career</a>
            <span class="hrk-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=hrk_company' ) ); ?>">Companies</a>
            <span class="hrk-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=haraka-settings' ) ); ?>">Settings</a>
        </div>

        <div class="hrk-dw-cache-note">
            Counts cached &mdash; refreshes every 5 minutes or on post save
        </div>

    </div>

    <?php
}