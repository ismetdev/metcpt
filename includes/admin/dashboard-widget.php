<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Register the dashboard widget ─────────────────────────────────────────────
function metcpt_register_dashboard_widget() {
    wp_add_dashboard_widget(
        'metcpt_dashboard_widget',
        'MetCPT — Content Health',
        'metcpt_dashboard_widget_html'
    );
}
add_action( 'wp_dashboard_setup', 'metcpt_register_dashboard_widget' );


// ── Invalidate cache when any MetCPT post is saved or deleted ─────────────────
function metcpt_invalidate_dashboard_cache( $post_id ) {
    $post_type = get_post_type( $post_id );
    if ( in_array( $post_type, array( 'metcpt_event', 'metcpt_tender', 'metcpt_career' ), true ) ) {
        delete_transient( 'metcpt_dashboard_counts' );
    }
}
add_action( 'save_post',   'metcpt_invalidate_dashboard_cache' );
add_action( 'delete_post', 'metcpt_invalidate_dashboard_cache' );
add_action( 'trash_post',  'metcpt_invalidate_dashboard_cache' );


// ── Compute dashboard counts — cached for 5 minutes ──────────────────────────
function metcpt_get_dashboard_counts() {

    $cached = get_transient( 'metcpt_dashboard_counts' );
    if ( $cached !== false ) {
        return $cached;
    }

    $today     = date( 'Y-m-d' );
    $threshold = (int) get_option( 'metcpt_closing_soon_days', 7 );

    // ── Events ────────────────────────────────────────────────────────────────
    $events_upcoming = new WP_Query( array(
        'post_type'      => 'metcpt_event',
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
        'post_type'      => 'metcpt_event',
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
        'post_type'      => 'metcpt_event',
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
        'post_type'      => 'metcpt_tender',
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
        'post_type'      => 'metcpt_tender',
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
        'post_type'      => 'metcpt_career',
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
        'post_type'      => 'metcpt_career',
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
    set_transient( 'metcpt_dashboard_counts', $counts, 5 * MINUTE_IN_SECONDS );

    return $counts;
}


// ── Widget HTML ───────────────────────────────────────────────────────────────
function metcpt_dashboard_widget_html() {

    $threshold = (int) get_option( 'metcpt_closing_soon_days', 7 );
    $c         = metcpt_get_dashboard_counts();

    $total_issues = $c['events_no_date']
                  + $c['count_no_ref']
                  + $c['count_no_close_date']
                  + $c['count_no_company']
                  + $c['count_no_career_date'];
    ?>

    <style>
        #metcpt_dashboard_widget .inside { margin:0; padding:0; }
        .mcpt-dw-wrap { font-family:-apple-system,'Segoe UI',sans-serif; font-size:13px; }
        .mcpt-dw-section { padding:14px 16px; border-bottom:1px solid #f1f5f9; }
        .mcpt-dw-section:last-child { border-bottom:none; }
        .mcpt-dw-section-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#94a3b8; margin-bottom:10px; }
        .mcpt-dw-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
        .mcpt-dw-stat { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; text-align:center; }
        .mcpt-dw-stat-number { font-size:22px; font-weight:700; color:#0f172a; line-height:1; display:block; margin-bottom:4px; }
        .mcpt-dw-stat-label { font-size:11px; color:#64748b; }
        .mcpt-dw-stat-upcoming .mcpt-dw-stat-number { color:#16a34a; }
        .mcpt-dw-stat-past     .mcpt-dw-stat-number { color:#94a3b8; }
        .mcpt-dw-stat-open     .mcpt-dw-stat-number { color:#16a34a; }
        .mcpt-dw-stat-soon     .mcpt-dw-stat-number { color:#d97706; }
        .mcpt-dw-stat-closed   .mcpt-dw-stat-number { color:#94a3b8; }
        .mcpt-dw-closing-item { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f8fafc; gap:10px; }
        .mcpt-dw-closing-item:last-child { border-bottom:none; }
        .mcpt-dw-closing-title { font-size:12px; font-weight:500; color:#0f172a; flex:1; line-height:1.35; }
        .mcpt-dw-closing-ref { font-size:11px; color:#94a3b8; font-family:monospace; }
        .mcpt-dw-closing-days { font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px; white-space:nowrap; flex-shrink:0; }
        .mcpt-dw-days-urgent  { background:#fee2e2; color:#991b1b; }
        .mcpt-dw-days-warning { background:#fef9c3; color:#a16207; }
        .mcpt-dw-issue-row { display:flex; align-items:center; gap:8px; padding:6px 0; font-size:12px; color:#475569; border-bottom:1px solid #f8fafc; }
        .mcpt-dw-issue-row:last-child { border-bottom:none; }
        .mcpt-dw-issue-icon { width:18px; height:18px; border-radius:50%; background:#fef9c3; color:#a16207; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0; }
        .mcpt-dw-all-good { display:flex; align-items:center; gap:8px; padding:8px 0; font-size:12px; color:#16a34a; font-weight:500; }
        .mcpt-dw-footer { padding:10px 16px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; gap:10px; flex-wrap:wrap; }
        .mcpt-dw-footer a { font-size:12px; font-weight:500; color:#0056b3; text-decoration:none; }
        .mcpt-dw-footer a:hover { text-decoration:underline; }
        .mcpt-dw-footer-sep { color:#e2e8f0; }
        .mcpt-dw-cache-note { font-size:10px; color:#cbd5e1; padding:4px 16px 6px; text-align:right; }
    </style>

    <div class="mcpt-dw-wrap">

        <?php /* ── Events ── */ ?>
        <div class="mcpt-dw-section">
            <div class="mcpt-dw-section-title">Events</div>
            <div class="mcpt-dw-stats">
                <div class="mcpt-dw-stat mcpt-dw-stat-upcoming">
                    <span class="mcpt-dw-stat-number"><?php echo esc_html( $c['events_upcoming'] ); ?></span>
                    <span class="mcpt-dw-stat-label">Upcoming</span>
                </div>
                <div class="mcpt-dw-stat mcpt-dw-stat-past">
                    <span class="mcpt-dw-stat-number"><?php echo esc_html( $c['events_past'] ); ?></span>
                    <span class="mcpt-dw-stat-label">Past</span>
                </div>
                <div class="mcpt-dw-stat">
                    <span class="mcpt-dw-stat-number"><?php echo esc_html( $c['events_upcoming'] + $c['events_past'] ); ?></span>
                    <span class="mcpt-dw-stat-label">Total</span>
                </div>
            </div>
        </div>

        <?php /* ── Tenders ── */ ?>
        <div class="mcpt-dw-section">
            <div class="mcpt-dw-section-title">Tenders</div>
            <div class="mcpt-dw-stats">
                <div class="mcpt-dw-stat mcpt-dw-stat-open">
                    <span class="mcpt-dw-stat-number"><?php echo esc_html( $c['count_t_open'] ); ?></span>
                    <span class="mcpt-dw-stat-label">Open</span>
                </div>
                <div class="mcpt-dw-stat mcpt-dw-stat-soon">
                    <span class="mcpt-dw-stat-number"><?php echo esc_html( $c['count_t_soon'] ); ?></span>
                    <span class="mcpt-dw-stat-label">Closing Soon</span>
                </div>
                <div class="mcpt-dw-stat mcpt-dw-stat-closed">
                    <span class="mcpt-dw-stat-number"><?php echo esc_html( $c['count_t_closed'] ); ?></span>
                    <span class="mcpt-dw-stat-label">Closed</span>
                </div>
            </div>
        </div>

        <?php /* ── Careers ── */ ?>
        <div class="mcpt-dw-section">
            <div class="mcpt-dw-section-title">Careers</div>
            <div class="mcpt-dw-stats">
                <div class="mcpt-dw-stat mcpt-dw-stat-open">
                    <span class="mcpt-dw-stat-number"><?php echo esc_html( $c['count_c_open'] ); ?></span>
                    <span class="mcpt-dw-stat-label">Open</span>
                </div>
                <div class="mcpt-dw-stat mcpt-dw-stat-soon">
                    <span class="mcpt-dw-stat-number"><?php echo esc_html( $c['count_c_soon'] ); ?></span>
                    <span class="mcpt-dw-stat-label">Closing Soon</span>
                </div>
                <div class="mcpt-dw-stat mcpt-dw-stat-closed">
                    <span class="mcpt-dw-stat-number"><?php echo esc_html( $c['count_c_closed'] ); ?></span>
                    <span class="mcpt-dw-stat-label">Closed</span>
                </div>
            </div>
        </div>

        <?php /* ── Closing Soon List ── */ ?>
        <?php if ( ! empty( $c['closing_soon_list'] ) ) : ?>
        <div class="mcpt-dw-section">
            <div class="mcpt-dw-section-title">
                Tenders Closing Within <?php echo esc_html( $threshold ); ?> Days
            </div>
            <?php foreach ( $c['closing_soon_list'] as $item ) : ?>
                <div class="mcpt-dw-closing-item">
                    <div>
                        <div class="mcpt-dw-closing-title">
                            <a href="<?php echo esc_url( $item['edit_url'] ); ?>">
                                <?php echo esc_html( $item['title'] ); ?>
                            </a>
                        </div>
                        <?php if ( ! empty( $item['ref'] ) ) : ?>
                            <div class="mcpt-dw-closing-ref">
                                <?php echo esc_html( $item['ref'] ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="mcpt-dw-closing-days <?php echo $item['days_left'] <= 3 ? 'mcpt-dw-days-urgent' : 'mcpt-dw-days-warning'; ?>">
                        <?php echo esc_html( $item['days_left'] ); ?>d left
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php /* ── Content Health ── */ ?>
        <div class="mcpt-dw-section">
            <div class="mcpt-dw-section-title">Content Health</div>

            <?php if ( $total_issues === 0 ) : ?>
                <div class="mcpt-dw-all-good">
                    &#10003; All content looks good &mdash; no missing fields detected
                </div>
            <?php else : ?>

                <?php if ( $c['events_no_date'] > 0 ) : ?>
                <div class="mcpt-dw-issue-row">
                    <div class="mcpt-dw-issue-icon">!</div>
                    <div>
                        <strong><?php echo esc_html( $c['events_no_date'] ); ?></strong>
                        event<?php echo $c['events_no_date'] > 1 ? 's' : ''; ?> missing an event date
                        &mdash; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=metcpt_event' ) ); ?>">fix now</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $c['count_no_ref'] > 0 ) : ?>
                <div class="mcpt-dw-issue-row">
                    <div class="mcpt-dw-issue-icon">!</div>
                    <div>
                        <strong><?php echo esc_html( $c['count_no_ref'] ); ?></strong>
                        tender<?php echo $c['count_no_ref'] > 1 ? 's' : ''; ?> missing a reference number
                        &mdash; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=metcpt_tender' ) ); ?>">fix now</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $c['count_no_close_date'] > 0 ) : ?>
                <div class="mcpt-dw-issue-row">
                    <div class="mcpt-dw-issue-icon">!</div>
                    <div>
                        <strong><?php echo esc_html( $c['count_no_close_date'] ); ?></strong>
                        tender<?php echo $c['count_no_close_date'] > 1 ? 's' : ''; ?> missing a closing date
                        &mdash; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=metcpt_tender' ) ); ?>">fix now</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $c['count_no_company'] > 0 ) : ?>
                <div class="mcpt-dw-issue-row">
                    <div class="mcpt-dw-issue-icon">!</div>
                    <div>
                        <strong><?php echo esc_html( $c['count_no_company'] ); ?></strong>
                        position<?php echo $c['count_no_company'] > 1 ? 's' : ''; ?> missing a company assignment
                        &mdash; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=metcpt_career' ) ); ?>">fix now</a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $c['count_no_career_date'] > 0 ) : ?>
                <div class="mcpt-dw-issue-row">
                    <div class="mcpt-dw-issue-icon">!</div>
                    <div>
                        <strong><?php echo esc_html( $c['count_no_career_date'] ); ?></strong>
                        position<?php echo $c['count_no_career_date'] > 1 ? 's' : ''; ?> missing a closing date
                        &mdash; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=metcpt_career' ) ); ?>">fix now</a>
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

        <?php /* ── Footer links ── */ ?>
        <div class="mcpt-dw-footer">
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=metcpt_event' ) ); ?>">Events</a>
            <span class="mcpt-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=metcpt_event' ) ); ?>">+ Event</a>
            <span class="mcpt-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=metcpt_tender' ) ); ?>">Tenders</a>
            <span class="mcpt-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=metcpt_tender' ) ); ?>">+ Tender</a>
            <span class="mcpt-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=metcpt_career' ) ); ?>">Careers</a>
            <span class="mcpt-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=metcpt_career' ) ); ?>">+ Career</a>
            <span class="mcpt-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=metcpt_company' ) ); ?>">Companies</a>
            <span class="mcpt-dw-footer-sep">|</span>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=metcpt-settings' ) ); ?>">Settings</a>
        </div>

        <div class="mcpt-dw-cache-note">
            Counts cached &mdash; refreshes every 5 minutes or on post save
        </div>

    </div>

    <?php
}