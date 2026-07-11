<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Helper: get initials from name ────────────────────────────────────────────
if ( ! function_exists( 'metcpt_get_initials' ) ) {
    function metcpt_get_initials( $name ) {
        $words    = explode( ' ', trim( $name ) );
        $initials = '';
        foreach ( $words as $word ) {
            if ( ! empty( $word ) && ctype_upper( $word[0] ) ) {
                $initials .= strtoupper( $word[0] );
            }
            if ( strlen( $initials ) >= 2 ) break;
        }
        return $initials ? $initials : strtoupper( substr( $name, 0, 2 ) );
    }
}

// ── Helper: VIP role badge class ──────────────────────────────────────────────
if ( ! function_exists( 'metcpt_vip_role_class' ) ) {
    function metcpt_vip_role_class( $role ) {
        $map = array(
            'Guest of Honour'  => 'mcpt-vip-goh',
            'Tazkirah'         => 'mcpt-vip-tazkirah',
            'Notable Attendee' => 'mcpt-vip-notable',
            'Speaker'          => 'mcpt-vip-speaker',
            'MC'               => 'mcpt-vip-mc',
        );
        return isset( $map[ $role ] ) ? $map[ $role ] : 'mcpt-vip-notable';
    }
}

// ── Override single template for metcpt_event ───────────────────────────────────
if ( ! function_exists( 'metcpt_event_single_template' ) ) {
    function metcpt_event_single_template( $template ) {
        if ( is_singular( 'metcpt_event' ) ) {
            if ( ! defined( 'METCPT_EVENT_TEMPLATE_LOADED' ) ) {
                define( 'METCPT_EVENT_TEMPLATE_LOADED', true );
                return METCPT_PATH . 'includes/events/template-single.php';
            }
        }
        return $template;
    }
    add_filter( 'single_template', 'metcpt_event_single_template' );
}

// ── Render the single event page ──────────────────────────────────────────────
if ( ! function_exists( 'metcpt_render_event_single' ) ) {
    function metcpt_render_event_single() {
        if ( ! is_singular( 'metcpt_event' ) ) {
            return;
        }

        global $post;
        $post_id = $post->ID;

        $event_date          = get_post_meta( $post_id, 'event_date',          true );
        $event_time          = get_post_meta( $post_id, 'event_time',          true );
        $event_venue         = get_post_meta( $post_id, 'event_venue',         true );
        $event_organiser     = get_post_meta( $post_id, 'event_organiser',     true );
        $event_audience      = get_post_meta( $post_id, 'event_audience',      true );
        $event_capacity      = get_post_meta( $post_id, 'event_capacity',      true );
        $event_rsvp_url      = get_post_meta( $post_id, 'event_rsvp_url',      true );
        $event_contact_name  = get_post_meta( $post_id, 'event_contact_name',  true );
        $event_contact_dept  = get_post_meta( $post_id, 'event_contact_dept',  true );
        $event_contact_email = get_post_meta( $post_id, 'event_contact_email', true );
        $event_contact_phone = get_post_meta( $post_id, 'event_contact_phone', true );
        $event_cal_url       = get_post_meta( $post_id, 'event_cal_url',       true );
        $event_guidelines    = get_post_meta( $post_id, 'event_guidelines',    true );

        $vips_raw      = get_post_meta( $post_id, 'event_vips',      true );
        $itinerary_raw = get_post_meta( $post_id, 'event_itinerary', true );
        $faqs_raw      = get_post_meta( $post_id, 'event_faqs',      true );

        $vips      = ! empty( $vips_raw )      ? json_decode( $vips_raw,      true ) : array();
        $itinerary = ! empty( $itinerary_raw ) ? json_decode( $itinerary_raw, true ) : array();
        $faqs      = ! empty( $faqs_raw )      ? json_decode( $faqs_raw,      true ) : array();

        if ( ! is_array( $vips ) )      $vips      = array();
        if ( ! is_array( $itinerary ) ) $itinerary = array();
        if ( ! is_array( $faqs ) )      $faqs      = array();

        // ── Status ────────────────────────────────────────────────────────────
        $status_label = 'Upcoming';
        $status_class = 'mcpt-status-upcoming';

        if ( ! empty( $event_date ) ) {
            $today    = new DateTime( 'today' );
            $date_obj = date_create( $event_date );
            if ( $date_obj ) {
                if ( $date_obj->format( 'Y-m-d' ) === $today->format( 'Y-m-d' ) ) {
                    $status_label = 'Today';
                    $status_class = 'mcpt-status-today';
                } elseif ( $date_obj < $today ) {
                    $status_label = 'Past Event';
                    $status_class = 'mcpt-status-past';
                }
            }
        }

        // ── Format date ───────────────────────────────────────────────────────
        $date_long = '';
        if ( ! empty( $event_date ) ) {
            $date_obj = date_create( $event_date );
            if ( $date_obj ) {
                $date_long = $date_obj->format( 'l, d F Y' );
            }
        }

        // ── Auto Google Calendar URL ──────────────────────────────────────────
        if ( empty( $event_cal_url ) && ! empty( $event_date ) ) {
            $cal_date      = str_replace( '-', '', $event_date );
            $cal_title     = urlencode( get_the_title( $post_id ) );
            $cal_venue     = urlencode( $event_venue );
            $event_cal_url = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
                . '&text=' . $cal_title
                . '&dates=' . $cal_date . '/' . $cal_date
                . '&location=' . $cal_venue;
        }

        // ── WhatsApp share ────────────────────────────────────────────────────
        $whatsapp_url = 'https://wa.me/?text=' . urlencode(
            get_the_title( $post_id ) . ' — ' . $date_long . ' | ' . get_permalink( $post_id )
        );

        // ── Thumbnail ─────────────────────────────────────────────────────────
        $thumb_url = has_post_thumbnail( $post_id )
            ? get_the_post_thumbnail_url( $post_id, 'full' )
            : '';

        // ── Back link ─────────────────────────────────────────────────────────
        // Points at the configured Events page (set in MetCPT Settings), matching
        // how Tenders and Careers resolve their back links. The CPT archive is
        // disabled (has_archive => false) so the page owns the /events/ URL.
        $events_archive = get_option( 'metcpt_events_archive_url', '/events' );

        ?>
        <?php get_header(); ?>

        <main>
        <article class="mcpt-event-page">

            <a class="mcpt-back-link"
               href="<?php echo esc_url( $events_archive ? $events_archive : home_url( '/events' ) ); ?>">
                &larr; Back to Events
            </a>

            <span class="mcpt-event-status <?php echo esc_attr( $status_class ); ?>">
                <span class="mcpt-status-dot"></span>
                <?php echo esc_html( $status_label ); ?>
            </span>

            <h1 class="mcpt-event-title">
                <?php echo esc_html( get_the_title( $post_id ) ); ?>
            </h1>

            <?php $excerpt = get_the_excerpt( $post_id ); ?>
            <?php if ( ! empty( $excerpt ) ) : ?>
                <p class="mcpt-event-excerpt"><?php echo esc_html( $excerpt ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $thumb_url ) ) : ?>
                <img class="mcpt-event-banner"
                     src="<?php echo esc_url( $thumb_url ); ?>"
                     alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" />
            <?php else : ?>
                <div class="mcpt-banner-placeholder">No banner image uploaded</div>
            <?php endif; ?>

            <div class="mcpt-info-grid">
                <?php if ( ! empty( $date_long ) ) : ?>
                <div class="mcpt-info-card">
                    <div class="mcpt-info-label">Date</div>
                    <div class="mcpt-info-value"><?php echo esc_html( $date_long ); ?></div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $event_time ) ) : ?>
                <div class="mcpt-info-card">
                    <div class="mcpt-info-label">Time</div>
                    <div class="mcpt-info-value"><?php echo esc_html( $event_time ); ?></div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $event_venue ) ) : ?>
                <div class="mcpt-info-card">
                    <div class="mcpt-info-label">Venue</div>
                    <div class="mcpt-info-value"><?php echo esc_html( $event_venue ); ?></div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $event_organiser ) ) : ?>
                <div class="mcpt-info-card">
                    <div class="mcpt-info-label">Organiser</div>
                    <div class="mcpt-info-value"><?php echo esc_html( $event_organiser ); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $vips ) ) : ?>
            <div class="mcpt-section">
                <div class="mcpt-section-title">VIPs &amp; Key Figures</div>
                <?php foreach ( $vips as $vip ) : ?>
                    <?php if ( empty( $vip['name'] ) ) continue; ?>
                    <div class="mcpt-vip-row">
                        <div class="mcpt-avatar">
                            <?php echo esc_html( metcpt_get_initials( $vip['name'] ) ); ?>
                        </div>
                        <div>
                            <div class="mcpt-vip-name"><?php echo esc_html( $vip['name'] ); ?></div>
                            <?php if ( ! empty( $vip['title'] ) ) : ?>
                                <div class="mcpt-vip-title"><?php echo esc_html( $vip['title'] ); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $vip['role'] ) ) : ?>
                            <span class="mcpt-vip-tag <?php echo esc_attr( metcpt_vip_role_class( $vip['role'] ) ); ?>">
                                <?php echo esc_html( $vip['role'] ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $itinerary ) ) : ?>
            <div class="mcpt-section">
                <div class="mcpt-section-title">Programme Itinerary</div>
                <div class="mcpt-table-wrap">
                    <table class="mcpt-itinerary-table">
                        <thead>
                            <tr>
                                <th style="width:110px">Time</th>
                                <th>Activity</th>
                                <th style="width:180px">Person in Charge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $itinerary as $item ) : ?>
                                <?php if ( empty( $item['activity'] ) ) continue; ?>
                                <tr>
                                    <td><div class="mcpt-itin-time"><?php echo esc_html( $item['time'] ); ?></div></td>
                                    <td><div class="mcpt-itin-activity"><?php echo esc_html( $item['activity'] ); ?></div></td>
                                    <td><div class="mcpt-itin-pic"><?php echo esc_html( $item['pic'] ); ?></div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $event_audience ) || ! empty( $event_capacity ) ) : ?>
            <div class="mcpt-section">
                <div class="mcpt-section-title">Attendance &amp; Capacity</div>
                <div class="mcpt-attendance-grid">
                    <?php if ( ! empty( $event_audience ) ) : ?>
                    <div class="mcpt-info-card">
                        <div class="mcpt-info-label">Who Should Attend</div>
                        <div class="mcpt-info-value" style="font-weight:400;font-size:13px;margin-top:4px;">
                            <?php echo esc_html( $event_audience ); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $event_capacity ) ) : ?>
                    <div class="mcpt-info-card">
                        <div class="mcpt-info-label">Capacity</div>
                        <div class="mcpt-info-value"><?php echo esc_html( $event_capacity ); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $faqs ) ) : ?>
            <div class="mcpt-section">
                <div class="mcpt-section-title">Frequently Asked Questions</div>
                <?php foreach ( $faqs as $faq ) : ?>
                    <?php if ( empty( $faq['question'] ) ) continue; ?>
                    <div class="mcpt-faq-item">
                        <div class="mcpt-faq-q"><?php echo esc_html( $faq['question'] ); ?></div>
                        <?php if ( ! empty( $faq['answer'] ) ) : ?>
                            <div class="mcpt-faq-a"><?php echo nl2br( esc_html( $faq['answer'] ) ); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $event_guidelines ) ) : ?>
            <div class="mcpt-section">
                <div class="mcpt-section-title">Guidelines &amp; Important Notes</div>
                <?php
                $lines = array_filter( array_map( 'trim', explode( "\n", $event_guidelines ) ) );
                foreach ( $lines as $line ) :
                ?>
                    <div class="mcpt-guideline-item">
                        <div class="mcpt-guide-dot"></div>
                        <div><?php echo esc_html( $line ); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $event_rsvp_url ) || ! empty( $event_cal_url ) ) : ?>
            <div class="mcpt-section">
                <div class="mcpt-section-title">Register &amp; Save the Date</div>
                <div class="mcpt-cta-row">
                    <?php if ( ! empty( $event_rsvp_url ) ) : ?>
                        <a href="<?php echo esc_url( $event_rsvp_url ); ?>"
                           class="mcpt-btn mcpt-btn-primary"
                           target="_blank" rel="noopener noreferrer">
                            Confirm Attendance
                        </a>
                    <?php endif; ?>
                    <?php if ( ! empty( $event_cal_url ) ) : ?>
                        <a href="<?php echo esc_url( $event_cal_url ); ?>"
                           class="mcpt-btn mcpt-btn-secondary"
                           target="_blank" rel="noopener noreferrer">
                            Add to Google Calendar
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $whatsapp_url ); ?>"
                       class="mcpt-btn mcpt-btn-secondary"
                       target="_blank" rel="noopener noreferrer">
                        Share via WhatsApp
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $event_contact_name ) ) : ?>
            <div class="mcpt-section">
                <div class="mcpt-section-title">Contact &amp; Secretariat</div>
                <div class="mcpt-contact-card">
                    <div class="mcpt-avatar" style="width:50px;height:50px;font-size:15px;flex-shrink:0;">
                        <?php echo esc_html( metcpt_get_initials( $event_contact_name ) ); ?>
                    </div>
                    <div>
                        <?php if ( ! empty( $event_contact_dept ) ) : ?>
                            <div class="mcpt-contact-dept"><?php echo esc_html( $event_contact_dept ); ?></div>
                        <?php endif; ?>
                        <div class="mcpt-contact-name"><?php echo esc_html( $event_contact_name ); ?></div>
                        <div style="margin-top:4px;display:flex;gap:16px;flex-wrap:wrap;">
                            <?php if ( ! empty( $event_contact_email ) ) : ?>
                                <a class="mcpt-contact-detail"
                                   href="mailto:<?php echo esc_attr( $event_contact_email ); ?>">
                                    <?php echo esc_html( $event_contact_email ); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( ! empty( $event_contact_phone ) ) : ?>
                                <a class="mcpt-contact-detail"
                                   href="tel:<?php echo esc_attr( $event_contact_phone ); ?>">
                                    <?php echo esc_html( $event_contact_phone ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </article>
        </main>

        <?php get_footer(); ?>
        <?php
    }
}

// ── Run the renderer when this file is loaded as the template ─────────────────
if ( defined( 'METCPT_EVENT_TEMPLATE_LOADED' ) ) {
    global $wp_query;
    if ( $wp_query->have_posts() ) {
        $wp_query->the_post();
        metcpt_render_event_single();
        wp_reset_postdata();
    }
}