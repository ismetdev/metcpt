<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Helper: tender status ─────────────────────────────────────────────────────
if ( ! function_exists( 'metcpt_get_tender_status' ) ) {
    function metcpt_get_tender_status( $close_date_str ) {
        if ( empty( $close_date_str ) ) return 'open';
        $today      = new DateTime( 'today' );
        $close_date = date_create( $close_date_str );
        if ( ! $close_date ) return 'open';
        if ( $close_date < $today ) return 'closed';
        $threshold = (int) get_option( 'metcpt_closing_soon_days', 7 );
        if ( (int) $today->diff( $close_date )->days <= $threshold ) return 'soon';
        return 'open';
    }
}

// ── Helper: format tender date ────────────────────────────────────────────────
if ( ! function_exists( 'metcpt_format_tender_date' ) ) {
    function metcpt_format_tender_date( $close_date_str ) {
        if ( empty( $close_date_str ) ) return 'TBC';
        $date = date_create( $close_date_str );
        return $date ? $date->format( 'd M Y' ) : esc_html( $close_date_str );
    }
}

// ── Helper: tender initials ───────────────────────────────────────────────────
if ( ! function_exists( 'metcpt_tender_initials' ) ) {
    function metcpt_tender_initials( $name ) {
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

// ── Override single template for metcpt_tender ──────────────────────────────────
if ( ! function_exists( 'metcpt_tender_single_template' ) ) {
    function metcpt_tender_single_template( $template ) {
        if ( is_singular( 'metcpt_tender' ) ) {
            if ( ! defined( 'METCPT_TENDER_TEMPLATE_LOADED' ) ) {
                define( 'METCPT_TENDER_TEMPLATE_LOADED', true );
                return METCPT_PATH . 'templates/tenders/single.php';
            }
        }
        return $template;
    }
    add_filter( 'single_template', 'metcpt_tender_single_template' );
}

// ── Render the single tender page ─────────────────────────────────────────────
if ( ! function_exists( 'metcpt_render_tender_single' ) ) {
    function metcpt_render_tender_single() {
        if ( ! is_singular( 'metcpt_tender' ) ) return;

        global $post;
        $post_id = $post->ID;

        $tender_ref               = get_post_meta( $post_id, 'tender_ref',               true );
        $tender_category          = get_post_meta( $post_id, 'tender_category',          true );
        $tender_issuer            = get_post_meta( $post_id, 'tender_issuer',            true );
        $tender_location          = get_post_meta( $post_id, 'tender_location',          true );
        $tender_close_date        = get_post_meta( $post_id, 'tender_close_date',        true );
        $tender_close_time        = get_post_meta( $post_id, 'tender_close_time',        true );
        $tender_validity          = get_post_meta( $post_id, 'tender_validity',          true );
        $tender_fee               = get_post_meta( $post_id, 'tender_fee',               true );
        $tender_submission_method = get_post_meta( $post_id, 'tender_submission_method', true );
        $tender_submission_address= get_post_meta( $post_id, 'tender_submission_address',true );
        $tender_document_url      = get_post_meta( $post_id, 'tender_document_url',      true );
        $tender_contact_name      = get_post_meta( $post_id, 'tender_contact_name',      true );
        $tender_contact_email     = get_post_meta( $post_id, 'tender_contact_email',     true );
        $tender_contact_phone     = get_post_meta( $post_id, 'tender_contact_phone',     true );

        // ── Status ────────────────────────────────────────────────────────────
        $status       = metcpt_get_tender_status( $tender_close_date );
        $close_fmt    = metcpt_format_tender_date( $tender_close_date );
        $publish_date = get_the_date( 'd M Y', $post_id );
        $excerpt      = get_the_excerpt( $post_id );

        $status_label = 'Open';
        $status_class = 'mcpt-t-status-open';
        if ( $status === 'soon' ) {
            $status_label = 'Closing Soon';
            $status_class = 'mcpt-t-status-soon';
        } elseif ( $status === 'closed' ) {
            $status_label = 'Closed';
            $status_class = 'mcpt-t-status-closed';
        }

        $tenders_archive = get_post_type_archive_link( 'metcpt_tender' );
        ?>

        <?php get_header(); ?>

        <main>
        <article class="mcpt-tender-page">

            <a class="mcpt-t-back"
               href="<?php echo esc_url( $tenders_archive ? $tenders_archive : home_url( '/tenders' ) ); ?>">
                &larr; Back to Tenders
            </a>

            <span class="mcpt-t-status <?php echo esc_attr( $status_class ); ?>">
                <span class="mcpt-t-dot"></span>
                <?php echo esc_html( $status_label ); ?>
            </span>

            <?php if ( ! empty( $tender_category ) ) : ?>
                <div class="mcpt-t-cat"><?php echo esc_html( $tender_category ); ?></div>
            <?php endif; ?>

            <h1 class="mcpt-t-title">
                <?php echo esc_html( get_the_title( $post_id ) ); ?>
            </h1>

            <?php if ( ! empty( $excerpt ) ) : ?>
                <p class="mcpt-t-desc"><?php echo esc_html( $excerpt ); ?></p>
            <?php endif; ?>

            <?php /* ── SECTION 1: KEY INFO GRID ── */ ?>
            <div class="mcpt-t-info-grid">
                <?php if ( ! empty( $tender_ref ) ) : ?>
                <div class="mcpt-t-info-card">
                    <div class="mcpt-t-info-label">Reference No.</div>
                    <div class="mcpt-t-info-value"><?php echo esc_html( $tender_ref ); ?></div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $tender_issuer ) ) : ?>
                <div class="mcpt-t-info-card">
                    <div class="mcpt-t-info-label">Issuing Organisation</div>
                    <div class="mcpt-t-info-value"><?php echo esc_html( $tender_issuer ); ?></div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $tender_location ) ) : ?>
                <div class="mcpt-t-info-card">
                    <div class="mcpt-t-info-label">Location</div>
                    <div class="mcpt-t-info-value"><?php echo esc_html( $tender_location ); ?></div>
                </div>
                <?php endif; ?>
                <div class="mcpt-t-info-card">
                    <div class="mcpt-t-info-label">Published</div>
                    <div class="mcpt-t-info-value"><?php echo esc_html( $publish_date ); ?></div>
                </div>
            </div>

            <?php /* ── SECTION 2: TIMELINES ── */ ?>
            <?php if ( ! empty( $tender_close_date ) || ! empty( $tender_validity ) || ! empty( $tender_fee ) ) : ?>
            <div class="mcpt-t-section">
                <div class="mcpt-t-section-title">Critical Timelines</div>
                <div class="mcpt-t-timeline-grid">
                    <?php if ( ! empty( $tender_close_date ) ) : ?>
                    <div class="mcpt-t-timeline-card mcpt-t-deadline">
                        <div class="mcpt-t-timeline-label">Closing Date</div>
                        <div class="mcpt-t-timeline-date"><?php echo esc_html( $close_fmt ); ?></div>
                        <?php if ( ! empty( $tender_close_time ) ) : ?>
                            <div class="mcpt-t-timeline-sub"><?php echo esc_html( $tender_close_time ); ?> (MYT)</div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $tender_validity ) ) : ?>
                    <div class="mcpt-t-timeline-card">
                        <div class="mcpt-t-timeline-label">Tender Validity</div>
                        <div class="mcpt-t-timeline-date"><?php echo esc_html( $tender_validity ); ?></div>
                        <div class="mcpt-t-timeline-sub">From closing date</div>
                    </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $tender_fee ) ) : ?>
                    <div class="mcpt-t-timeline-card">
                        <div class="mcpt-t-timeline-label">Tender Fee</div>
                        <div class="mcpt-t-timeline-date"><?php echo esc_html( $tender_fee ); ?></div>
                        <div class="mcpt-t-timeline-sub">Non-refundable</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php /* ── SECTION 3: DOCUMENT ── */ ?>
            <?php if ( ! empty( $tender_document_url ) ) : ?>
            <div class="mcpt-t-section">
                <div class="mcpt-t-section-title">Tender Document</div>
                <div class="mcpt-t-doc-box">
                    <div class="mcpt-t-doc-info">
                        <div class="mcpt-t-doc-title">Download Tender Document</div>
                        <?php if ( ! empty( $tender_fee ) && strtolower( $tender_fee ) !== 'free' ) : ?>
                            <div class="mcpt-t-doc-fee">
                                Tender fee: <?php echo esc_html( $tender_fee ); ?> — payable upon collection
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo esc_url( $tender_document_url ); ?>"
                       class="mcpt-t-btn mcpt-t-btn-primary"
                       target="_blank" rel="noopener noreferrer">
                        Download
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php /* ── SECTION 4: SUBMISSION ── */ ?>
            <?php if ( ! empty( $tender_submission_method ) || ! empty( $tender_submission_address ) ) : ?>
            <div class="mcpt-t-section">
                <div class="mcpt-t-section-title">Submission Details</div>
                <div class="mcpt-t-submission-box">
                    <?php if ( ! empty( $tender_submission_method ) ) : ?>
                        <div class="mcpt-t-submission-method">
                            <?php echo esc_html( $tender_submission_method ); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ( ! empty( $tender_submission_address ) ) : ?>
                        <div class="mcpt-t-submission-address">
                            <?php echo nl2br( esc_html( $tender_submission_address ) ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php /* ── SECTION 5: CONTACT ── */ ?>
            <?php if ( ! empty( $tender_contact_name ) ) : ?>
            <div class="mcpt-t-section">
                <div class="mcpt-t-section-title">Contact &amp; Enquiries</div>
                <div class="mcpt-t-contact-card">
                    <div class="mcpt-t-avatar">
                        <?php echo esc_html( metcpt_tender_initials( $tender_contact_name ) ); ?>
                    </div>
                    <div>
                        <div class="mcpt-t-contact-dept">Procurement Unit</div>
                        <div class="mcpt-t-contact-name"><?php echo esc_html( $tender_contact_name ); ?></div>
                        <div style="margin-top:4px;display:flex;gap:16px;flex-wrap:wrap;">
                            <?php if ( ! empty( $tender_contact_email ) ) : ?>
                                <a class="mcpt-t-contact-link"
                                   href="mailto:<?php echo esc_attr( $tender_contact_email ); ?>">
                                    <?php echo esc_html( $tender_contact_email ); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( ! empty( $tender_contact_phone ) ) : ?>
                                <a class="mcpt-t-contact-link"
                                   href="tel:<?php echo esc_attr( $tender_contact_phone ); ?>">
                                    <?php echo esc_html( $tender_contact_phone ); ?>
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

// ── Run renderer when loaded as template ──────────────────────────────────────
if ( defined( 'METCPT_TENDER_TEMPLATE_LOADED' ) ) {
    global $wp_query;
    if ( $wp_query->have_posts() ) {
        $wp_query->the_post();
        metcpt_render_tender_single();
        wp_reset_postdata();
    }
}