<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Helper: get initials from name ────────────────────────────────────────────
if ( ! function_exists( 'haraka_get_initials' ) ) {
    function haraka_get_initials( $name ) {
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
if ( ! function_exists( 'haraka_vip_role_class' ) ) {
    function haraka_vip_role_class( $role ) {
        $map = array(
            'Guest of Honour'  => 'hrk-vip-goh',
            'Tazkirah'         => 'hrk-vip-tazkirah',
            'Notable Attendee' => 'hrk-vip-notable',
            'Speaker'          => 'hrk-vip-speaker',
            'MC'               => 'hrk-vip-mc',
        );
        return isset( $map[ $role ] ) ? $map[ $role ] : 'hrk-vip-notable';
    }
}

// ── Override single template for hrk_event ───────────────────────────────────
if ( ! function_exists( 'haraka_event_single_template' ) ) {
    function haraka_event_single_template( $template ) {
        if ( is_singular( 'hrk_event' ) ) {
            if ( ! defined( 'HARAKA_EVENT_TEMPLATE_LOADED' ) ) {
                define( 'HARAKA_EVENT_TEMPLATE_LOADED', true );
                return HARAKA_PLUGIN_DIR . 'includes/events/template-single.php';
            }
        }
        return $template;
    }
    add_filter( 'single_template', 'haraka_event_single_template' );
}

// ── Render the single event page ──────────────────────────────────────────────
if ( ! function_exists( 'haraka_render_event_single' ) ) {
    function haraka_render_event_single() {
        if ( ! is_singular( 'hrk_event' ) ) {
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
        $status_class = 'hrk-status-upcoming';

        if ( ! empty( $event_date ) ) {
            $today    = new DateTime( 'today' );
            $date_obj = date_create( $event_date );
            if ( $date_obj ) {
                if ( $date_obj->format( 'Y-m-d' ) === $today->format( 'Y-m-d' ) ) {
                    $status_label = 'Today';
                    $status_class = 'hrk-status-today';
                } elseif ( $date_obj < $today ) {
                    $status_label = 'Past Event';
                    $status_class = 'hrk-status-past';
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
        $events_archive = get_post_type_archive_link( 'hrk_event' );

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <?php wp_head(); ?>            
        </head>
        <body <?php body_class(); ?>>
        <?php wp_body_open(); ?>
        <?php get_header(); ?>

        <main>
        <article class="hrk-event-page">

            <a class="hrk-back-link"
               href="<?php echo esc_url( $events_archive ? $events_archive : home_url( '/events' ) ); ?>">
                &larr; Back to Events
            </a>

            <span class="hrk-event-status <?php echo esc_attr( $status_class ); ?>">
                <span class="hrk-status-dot"></span>
                <?php echo esc_html( $status_label ); ?>
            </span>

            <h1 class="hrk-event-title">
                <?php echo esc_html( get_the_title( $post_id ) ); ?>
            </h1>

            <?php $excerpt = get_the_excerpt( $post_id ); ?>
            <?php if ( ! empty( $excerpt ) ) : ?>
                <p class="hrk-event-excerpt"><?php echo esc_html( $excerpt ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $thumb_url ) ) : ?>
                <img class="hrk-event-banner"
                     src="<?php echo esc_url( $thumb_url ); ?>"
                     alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" />
            <?php else : ?>
                <div class="hrk-banner-placeholder">No banner image uploaded</div>
            <?php endif; ?>

            <div class="hrk-info-grid">
                <?php if ( ! empty( $date_long ) ) : ?>
                <div class="hrk-info-card">
                    <div class="hrk-info-label">Date</div>
                    <div class="hrk-info-value"><?php echo esc_html( $date_long ); ?></div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $event_time ) ) : ?>
                <div class="hrk-info-card">
                    <div class="hrk-info-label">Time</div>
                    <div class="hrk-info-value"><?php echo esc_html( $event_time ); ?></div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $event_venue ) ) : ?>
                <div class="hrk-info-card">
                    <div class="hrk-info-label">Venue</div>
                    <div class="hrk-info-value"><?php echo esc_html( $event_venue ); ?></div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $event_organiser ) ) : ?>
                <div class="hrk-info-card">
                    <div class="hrk-info-label">Organiser</div>
                    <div class="hrk-info-value"><?php echo esc_html( $event_organiser ); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $vips ) ) : ?>
            <div class="hrk-section">
                <div class="hrk-section-title">VIPs &amp; Key Figures</div>
                <?php foreach ( $vips as $vip ) : ?>
                    <?php if ( empty( $vip['name'] ) ) continue; ?>
                    <div class="hrk-vip-row">
                        <div class="hrk-avatar">
                            <?php echo esc_html( haraka_get_initials( $vip['name'] ) ); ?>
                        </div>
                        <div>
                            <div class="hrk-vip-name"><?php echo esc_html( $vip['name'] ); ?></div>
                            <?php if ( ! empty( $vip['title'] ) ) : ?>
                                <div class="hrk-vip-title"><?php echo esc_html( $vip['title'] ); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ( ! empty( $vip['role'] ) ) : ?>
                            <span class="hrk-vip-tag <?php echo esc_attr( haraka_vip_role_class( $vip['role'] ) ); ?>">
                                <?php echo esc_html( $vip['role'] ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $itinerary ) ) : ?>
            <div class="hrk-section">
                <div class="hrk-section-title">Programme Itinerary</div>
                <div class="hrk-table-wrap">
                    <table class="hrk-itinerary-table">
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
                                    <td><div class="hrk-itin-time"><?php echo esc_html( $item['time'] ); ?></div></td>
                                    <td><div class="hrk-itin-activity"><?php echo esc_html( $item['activity'] ); ?></div></td>
                                    <td><div class="hrk-itin-pic"><?php echo esc_html( $item['pic'] ); ?></div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $event_audience ) || ! empty( $event_capacity ) ) : ?>
            <div class="hrk-section">
                <div class="hrk-section-title">Attendance &amp; Capacity</div>
                <div class="hrk-attendance-grid">
                    <?php if ( ! empty( $event_audience ) ) : ?>
                    <div class="hrk-info-card">
                        <div class="hrk-info-label">Who Should Attend</div>
                        <div class="hrk-info-value" style="font-weight:400;font-size:13px;margin-top:4px;">
                            <?php echo esc_html( $event_audience ); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $event_capacity ) ) : ?>
                    <div class="hrk-info-card">
                        <div class="hrk-info-label">Capacity</div>
                        <div class="hrk-info-value"><?php echo esc_html( $event_capacity ); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $faqs ) ) : ?>
            <div class="hrk-section">
                <div class="hrk-section-title">Frequently Asked Questions</div>
                <?php foreach ( $faqs as $faq ) : ?>
                    <?php if ( empty( $faq['question'] ) ) continue; ?>
                    <div class="hrk-faq-item">
                        <div class="hrk-faq-q"><?php echo esc_html( $faq['question'] ); ?></div>
                        <?php if ( ! empty( $faq['answer'] ) ) : ?>
                            <div class="hrk-faq-a"><?php echo nl2br( esc_html( $faq['answer'] ) ); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $event_guidelines ) ) : ?>
            <div class="hrk-section">
                <div class="hrk-section-title">Guidelines &amp; Important Notes</div>
                <?php
                $lines = array_filter( array_map( 'trim', explode( "\n", $event_guidelines ) ) );
                foreach ( $lines as $line ) :
                ?>
                    <div class="hrk-guideline-item">
                        <div class="hrk-guide-dot"></div>
                        <div><?php echo esc_html( $line ); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $event_rsvp_url ) || ! empty( $event_cal_url ) ) : ?>
            <div class="hrk-section">
                <div class="hrk-section-title">Register &amp; Save the Date</div>
                <div class="hrk-cta-row">
                    <?php if ( ! empty( $event_rsvp_url ) ) : ?>
                        <a href="<?php echo esc_url( $event_rsvp_url ); ?>"
                           class="hrk-btn hrk-btn-primary"
                           target="_blank" rel="noopener noreferrer">
                            Confirm Attendance
                        </a>
                    <?php endif; ?>
                    <?php if ( ! empty( $event_cal_url ) ) : ?>
                        <a href="<?php echo esc_url( $event_cal_url ); ?>"
                           class="hrk-btn hrk-btn-secondary"
                           target="_blank" rel="noopener noreferrer">
                            Add to Google Calendar
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $whatsapp_url ); ?>"
                       class="hrk-btn hrk-btn-secondary"
                       target="_blank" rel="noopener noreferrer">
                        Share via WhatsApp
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $event_contact_name ) ) : ?>
            <div class="hrk-section">
                <div class="hrk-section-title">Contact &amp; Secretariat</div>
                <div class="hrk-contact-card">
                    <div class="hrk-avatar" style="width:50px;height:50px;font-size:15px;flex-shrink:0;">
                        <?php echo esc_html( haraka_get_initials( $event_contact_name ) ); ?>
                    </div>
                    <div>
                        <?php if ( ! empty( $event_contact_dept ) ) : ?>
                            <div class="hrk-contact-dept"><?php echo esc_html( $event_contact_dept ); ?></div>
                        <?php endif; ?>
                        <div class="hrk-contact-name"><?php echo esc_html( $event_contact_name ); ?></div>
                        <div style="margin-top:4px;display:flex;gap:16px;flex-wrap:wrap;">
                            <?php if ( ! empty( $event_contact_email ) ) : ?>
                                <a class="hrk-contact-detail"
                                   href="mailto:<?php echo esc_attr( $event_contact_email ); ?>">
                                    <?php echo esc_html( $event_contact_email ); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( ! empty( $event_contact_phone ) ) : ?>
                                <a class="hrk-contact-detail"
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
        <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
}

// ── Run the renderer when this file is loaded as the template ─────────────────
if ( defined( 'HARAKA_EVENT_TEMPLATE_LOADED' ) ) {
    global $wp_query;
    if ( $wp_query->have_posts() ) {
        $wp_query->the_post();
        haraka_render_event_single();
        wp_reset_postdata();
    }
}