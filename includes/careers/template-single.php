<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Helper: career initials ───────────────────────────────────────────────────
if ( ! function_exists( 'haraka_career_initials' ) ) {
    function haraka_career_initials( $name ) {
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

// ── Override single template for hrk_career ───────────────────────────────────
if ( ! function_exists( 'haraka_career_single_template' ) ) {
    function haraka_career_single_template( $template ) {
        if ( is_singular( 'hrk_career' ) ) {
            if ( ! defined( 'HARAKA_CAREER_TEMPLATE_LOADED' ) ) {
                define( 'HARAKA_CAREER_TEMPLATE_LOADED', true );
                return HARAKA_PLUGIN_DIR . 'includes/careers/template-single.php';
            }
        }
        return $template;
    }
    add_filter( 'single_template', 'haraka_career_single_template' );
}

// ── Render single career page ─────────────────────────────────────────────────
if ( ! function_exists( 'haraka_render_career_single' ) ) {
    function haraka_render_career_single() {
        if ( ! is_singular( 'hrk_career' ) ) {
            return;
        }

        global $post;
        $post_id = $post->ID;

        // ── Pull all meta ─────────────────────────────────────────────────────
        $company_id    = get_post_meta( $post_id, 'career_company_id',    true );
        $department    = get_post_meta( $post_id, 'career_department',    true );
        $location      = get_post_meta( $post_id, 'career_location',      true );
        $type          = get_post_meta( $post_id, 'career_type',          true );
        $close_date    = get_post_meta( $post_id, 'career_close_date',    true );
        $salary        = get_post_meta( $post_id, 'career_salary',        true );
        $apply_url     = get_post_meta( $post_id, 'career_apply_url',     true );
        $contact_name  = get_post_meta( $post_id, 'career_contact_name',  true );
        $contact_email = get_post_meta( $post_id, 'career_contact_email', true );
        $contact_phone = get_post_meta( $post_id, 'career_contact_phone', true );

        // ── Resolve company data ──────────────────────────────────────────────
        $company_name    = '';
        $company_short   = '';
        $company_website = '';
        $company_desc    = '';
        $company_logo    = '';

        if ( ! empty( $company_id ) ) {
            $cid             = (int) $company_id;
            $company_name    = get_the_title( $cid );
            $company_short   = get_post_meta( $cid, 'company_short_name',  true );
            $company_website = get_post_meta( $cid, 'company_website',     true );
            $company_desc    = get_post_meta( $cid, 'company_description', true );
            $company_logo    = has_post_thumbnail( $cid )
                               ? get_the_post_thumbnail_url( $cid, array( 120, 60 ) )
                               : '';
        }

        $display_company = $company_short ? $company_short : $company_name;

        // ── Status ────────────────────────────────────────────────────────────
        $status       = haraka_get_career_status( $close_date );
        $close_fmt    = haraka_format_career_date( $close_date );
        $publish_date = get_the_date( 'd M Y', $post_id );
        $excerpt      = get_the_excerpt( $post_id );

        $status_label = 'Open';
        $status_class = 'hrk-c-status-open';
        if ( $status === 'soon' ) {
            $status_label = 'Closing Soon';
            $status_class = 'hrk-c-status-soon';
        } elseif ( $status === 'closed' ) {
            $status_label = 'Closed';
            $status_class = 'hrk-c-status-closed';
        }

        // ── Archive link ──────────────────────────────────────────────────────
        $careers_archive = get_option( 'haraka_careers_page_url', '/careers' );

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
        <article class="hrk-career-page">

            <a class="hrk-c-back"
               href="<?php echo esc_url( $careers_archive ); ?>">
                &larr; Back to Careers
            </a>

            <?php /* ── STATUS + TYPE BADGES ── */ ?>
            <div class="hrk-c-badges">
                <span class="hrk-c-status <?php echo esc_attr( $status_class ); ?>">
                    <span class="hrk-c-dot"></span>
                    <?php echo esc_html( $status_label ); ?>
                </span>
                <?php if ( ! empty( $type ) ) : ?>
                    <span class="hrk-c-type-badge">
                        <?php echo esc_html( $type ); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php /* ── TITLE ── */ ?>
            <h1 class="hrk-c-title">
                <?php echo esc_html( get_the_title( $post_id ) ); ?>
            </h1>

            <?php /* ── EXCERPT ── */ ?>
            <?php if ( ! empty( $excerpt ) ) : ?>
                <p class="hrk-c-excerpt"><?php echo esc_html( $excerpt ); ?></p>
            <?php endif; ?>

            <?php /* ── SECTION 1: KEY INFO GRID ── */ ?>
            <div class="hrk-c-info-grid">
                <?php if ( ! empty( $display_company ) ) : ?>
                <div class="hrk-c-info-card">
                    <div class="hrk-c-info-label">Company</div>
                    <div class="hrk-c-info-value">
                        <?php if ( ! empty( $company_logo ) ) : ?>
                            <img src="<?php echo esc_url( $company_logo ); ?>"
                                 alt="<?php echo esc_attr( $display_company ); ?>"
                                 class="hrk-c-company-logo" />
                        <?php else : ?>
                            <?php echo esc_html( $display_company ); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $department ) ) : ?>
                <div class="hrk-c-info-card">
                    <div class="hrk-c-info-label">Department</div>
                    <div class="hrk-c-info-value"><?php echo esc_html( $department ); ?></div>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $location ) ) : ?>
                <div class="hrk-c-info-card">
                    <div class="hrk-c-info-label">Location</div>
                    <div class="hrk-c-info-value"><?php echo esc_html( $location ); ?></div>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $salary ) ) : ?>
                <div class="hrk-c-info-card">
                    <div class="hrk-c-info-label">Salary Range</div>
                    <div class="hrk-c-info-value"><?php echo esc_html( $salary ); ?></div>
                </div>
                <?php endif; ?>

                <div class="hrk-c-info-card">
                    <div class="hrk-c-info-label">Posted</div>
                    <div class="hrk-c-info-value"><?php echo esc_html( $publish_date ); ?></div>
                </div>
            </div>

            <?php /* ── SECTION 2: JOB DESCRIPTION ── */ ?>
            <?php
            $content = get_the_content( null, false, $post_id );
            $content = apply_filters( 'the_content', $content );
            ?>
            <?php if ( ! empty( trim( $content ) ) ) : ?>
            <div class="hrk-c-section">
                <div class="hrk-c-section-title">Job Description &amp; Requirements</div>
                <div class="hrk-c-content">
                    <?php echo wp_kses_post( $content ); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php /* ── SECTION 3: APPLICATION DETAILS ── */ ?>
            <?php if ( ! empty( $close_date ) || ! empty( $apply_url ) ) : ?>
            <div class="hrk-c-section">
                <div class="hrk-c-section-title">How to Apply</div>
                <div class="hrk-c-apply-box">
                    <div class="hrk-c-apply-info">
                        <?php if ( ! empty( $close_date ) ) : ?>
                            <div class="hrk-c-apply-deadline">
                                <span class="hrk-c-apply-label">Application Deadline</span>
                                <span class="hrk-c-apply-date <?php echo $status === 'soon' ? 'hrk-c-date-urgent' : ''; ?>">
                                    <?php echo esc_html( $close_fmt ); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ( ! empty( $apply_url ) && $status !== 'closed' ) : ?>
                        <a href="<?php echo esc_url( $apply_url ); ?>"
                           class="hrk-c-btn hrk-c-btn-primary"
                           target="_blank"
                           rel="noopener noreferrer">
                            Apply Now
                        </a>
                    <?php elseif ( $status === 'closed' ) : ?>
                        <span class="hrk-c-btn hrk-c-btn-disabled">
                            Applications Closed
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php /* ── SECTION 4: ABOUT THE COMPANY ── */ ?>
            <?php if ( ! empty( $company_desc ) || ! empty( $company_website ) ) : ?>
            <div class="hrk-c-section">
                <div class="hrk-c-section-title">
                    About <?php echo esc_html( $display_company ? $display_company : 'the Company' ); ?>
                </div>
                <div class="hrk-c-company-box">
                    <?php if ( ! empty( $company_logo ) ) : ?>
                        <img src="<?php echo esc_url( $company_logo ); ?>"
                             alt="<?php echo esc_attr( $display_company ); ?>"
                             class="hrk-c-company-logo-lg" />
                    <?php endif; ?>
                    <div class="hrk-c-company-info">
                        <?php if ( ! empty( $company_desc ) ) : ?>
                            <p class="hrk-c-company-desc">
                                <?php echo esc_html( $company_desc ); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ( ! empty( $company_website ) ) : ?>
                            <a href="<?php echo esc_url( $company_website ); ?>"
                               class="hrk-c-company-link"
                               target="_blank"
                               rel="noopener noreferrer">
                                Visit company website &rarr;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php /* ── SECTION 5: HR CONTACT ── */ ?>
            <?php if ( ! empty( $contact_name ) ) : ?>
            <div class="hrk-c-section">
                <div class="hrk-c-section-title">HR Contact &amp; Enquiries</div>
                <div class="hrk-c-contact-card">
                    <div class="hrk-c-avatar">
                        <?php echo esc_html( haraka_career_initials( $contact_name ) ); ?>
                    </div>
                    <div>
                        <div class="hrk-c-contact-dept">Human Resource</div>
                        <div class="hrk-c-contact-name">
                            <?php echo esc_html( $contact_name ); ?>
                        </div>
                        <div style="margin-top:4px;display:flex;gap:16px;flex-wrap:wrap;">
                            <?php if ( ! empty( $contact_email ) ) : ?>
                                <a class="hrk-c-contact-link"
                                   href="mailto:<?php echo esc_attr( $contact_email ); ?>">
                                    <?php echo esc_html( $contact_email ); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( ! empty( $contact_phone ) ) : ?>
                                <a class="hrk-c-contact-link"
                                   href="tel:<?php echo esc_attr( $contact_phone ); ?>">
                                    <?php echo esc_html( $contact_phone ); ?>
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

// ── Run renderer when loaded as template ──────────────────────────────────────
if ( defined( 'HARAKA_CAREER_TEMPLATE_LOADED' ) ) {
    global $wp_query;
    if ( $wp_query->have_posts() ) {
        $wp_query->the_post();
        haraka_render_career_single();
        wp_reset_postdata();
    }
}