<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Helper: career initials ───────────────────────────────────────────────────
if ( ! function_exists( 'metcpt_career_initials' ) ) {
    function metcpt_career_initials( $name ) {
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

// ── Override single template for metcpt_career ───────────────────────────────────
if ( ! function_exists( 'metcpt_career_single_template' ) ) {
    function metcpt_career_single_template( $template ) {
        if ( is_singular( 'metcpt_career' ) ) {
            if ( ! defined( 'METCPT_CAREER_TEMPLATE_LOADED' ) ) {
                define( 'METCPT_CAREER_TEMPLATE_LOADED', true );
                return METCPT_PATH . 'templates/careers/single.php';
            }
        }
        return $template;
    }
    add_filter( 'single_template', 'metcpt_career_single_template' );
}

// ── Render single career page ─────────────────────────────────────────────────
if ( ! function_exists( 'metcpt_render_career_single' ) ) {
    function metcpt_render_career_single() {
        if ( ! is_singular( 'metcpt_career' ) ) {
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
        $status       = metcpt_get_career_status( $close_date );
        $close_fmt    = metcpt_format_career_date( $close_date );
        $publish_date = get_the_date( 'd M Y', $post_id );
        $excerpt      = get_the_excerpt( $post_id );

        $status_label = 'Open';
        $status_class = 'mcpt-c-status-open';
        if ( $status === 'soon' ) {
            $status_label = 'Closing Soon';
            $status_class = 'mcpt-c-status-soon';
        } elseif ( $status === 'closed' ) {
            $status_label = 'Closed';
            $status_class = 'mcpt-c-status-closed';
        }

        // ── Archive link ──────────────────────────────────────────────────────
        $careers_archive = get_option( 'metcpt_careers_page_url', '/careers' );

        ?>
        <?php get_header(); ?>

        <main>
        <article class="mcpt-career-page">

            <a class="mcpt-c-back"
               href="<?php echo esc_url( $careers_archive ); ?>">
                &larr; Back to Careers
            </a>

            <?php /* ── STATUS + TYPE BADGES ── */ ?>
            <div class="mcpt-c-badges">
                <span class="mcpt-c-status <?php echo esc_attr( $status_class ); ?>">
                    <span class="mcpt-c-dot"></span>
                    <?php echo esc_html( $status_label ); ?>
                </span>
                <?php if ( ! empty( $type ) ) : ?>
                    <span class="mcpt-c-type-badge">
                        <?php echo esc_html( $type ); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php /* ── TITLE ── */ ?>
            <h1 class="mcpt-c-title">
                <?php echo esc_html( get_the_title( $post_id ) ); ?>
            </h1>

            <?php /* ── EXCERPT ── */ ?>
            <?php if ( ! empty( $excerpt ) ) : ?>
                <p class="mcpt-c-excerpt"><?php echo esc_html( $excerpt ); ?></p>
            <?php endif; ?>

            <?php /* ── SECTION 1: KEY INFO GRID ── */ ?>
            <div class="mcpt-c-info-grid">
                <?php if ( ! empty( $display_company ) ) : ?>
                <div class="mcpt-c-info-card">
                    <div class="mcpt-c-info-label">Company</div>
                    <div class="mcpt-c-info-value">
                        <?php if ( ! empty( $company_logo ) ) : ?>
                            <img src="<?php echo esc_url( $company_logo ); ?>"
                                 alt="<?php echo esc_attr( $display_company ); ?>"
                                 class="mcpt-c-company-logo" />
                        <?php else : ?>
                            <?php echo esc_html( $display_company ); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $department ) ) : ?>
                <div class="mcpt-c-info-card">
                    <div class="mcpt-c-info-label">Department</div>
                    <div class="mcpt-c-info-value"><?php echo esc_html( $department ); ?></div>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $location ) ) : ?>
                <div class="mcpt-c-info-card">
                    <div class="mcpt-c-info-label">Location</div>
                    <div class="mcpt-c-info-value"><?php echo esc_html( $location ); ?></div>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $salary ) ) : ?>
                <div class="mcpt-c-info-card">
                    <div class="mcpt-c-info-label">Salary Range</div>
                    <div class="mcpt-c-info-value"><?php echo esc_html( $salary ); ?></div>
                </div>
                <?php endif; ?>

                <div class="mcpt-c-info-card">
                    <div class="mcpt-c-info-label">Posted</div>
                    <div class="mcpt-c-info-value"><?php echo esc_html( $publish_date ); ?></div>
                </div>
            </div>

            <?php /* ── SECTION 2: JOB DESCRIPTION ── */ ?>
            <?php
            $content = get_the_content( null, false, $post_id );
            $content = apply_filters( 'the_content', $content );
            ?>
            <?php if ( ! empty( trim( $content ) ) ) : ?>
            <div class="mcpt-c-section">
                <div class="mcpt-c-section-title">Job Description &amp; Requirements</div>
                <div class="mcpt-c-content">
                    <?php echo wp_kses_post( $content ); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php /* ── SECTION 3: APPLICATION DETAILS ── */ ?>
            <?php if ( ! empty( $close_date ) || ! empty( $apply_url ) ) : ?>
            <div class="mcpt-c-section">
                <div class="mcpt-c-section-title">How to Apply</div>
                <div class="mcpt-c-apply-box">
                    <div class="mcpt-c-apply-info">
                        <?php if ( ! empty( $close_date ) ) : ?>
                            <div class="mcpt-c-apply-deadline">
                                <span class="mcpt-c-apply-label">Application Deadline</span>
                                <span class="mcpt-c-apply-date <?php echo $status === 'soon' ? 'mcpt-c-date-urgent' : ''; ?>">
                                    <?php echo esc_html( $close_fmt ); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ( ! empty( $apply_url ) && $status !== 'closed' ) : ?>
                        <a href="<?php echo esc_url( $apply_url ); ?>"
                           class="mcpt-c-btn mcpt-c-btn-primary"
                           target="_blank"
                           rel="noopener noreferrer">
                            Apply Now
                        </a>
                    <?php elseif ( $status === 'closed' ) : ?>
                        <span class="mcpt-c-btn mcpt-c-btn-disabled">
                            Applications Closed
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php /* ── SECTION 4: ABOUT THE COMPANY ── */ ?>
            <?php if ( ! empty( $company_desc ) || ! empty( $company_website ) ) : ?>
            <div class="mcpt-c-section">
                <div class="mcpt-c-section-title">
                    About <?php echo esc_html( $display_company ? $display_company : 'the Company' ); ?>
                </div>
                <div class="mcpt-c-company-box">
                    <?php if ( ! empty( $company_logo ) ) : ?>
                        <img src="<?php echo esc_url( $company_logo ); ?>"
                             alt="<?php echo esc_attr( $display_company ); ?>"
                             class="mcpt-c-company-logo-lg" />
                    <?php endif; ?>
                    <div class="mcpt-c-company-info">
                        <?php if ( ! empty( $company_desc ) ) : ?>
                            <p class="mcpt-c-company-desc">
                                <?php echo esc_html( $company_desc ); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ( ! empty( $company_website ) ) : ?>
                            <a href="<?php echo esc_url( $company_website ); ?>"
                               class="mcpt-c-company-link"
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
            <div class="mcpt-c-section">
                <div class="mcpt-c-section-title">HR Contact &amp; Enquiries</div>
                <div class="mcpt-c-contact-card">
                    <div class="mcpt-c-avatar">
                        <?php echo esc_html( metcpt_career_initials( $contact_name ) ); ?>
                    </div>
                    <div>
                        <div class="mcpt-c-contact-dept">Human Resource</div>
                        <div class="mcpt-c-contact-name">
                            <?php echo esc_html( $contact_name ); ?>
                        </div>
                        <div style="margin-top:4px;display:flex;gap:16px;flex-wrap:wrap;">
                            <?php if ( ! empty( $contact_email ) ) : ?>
                                <a class="mcpt-c-contact-link"
                                   href="mailto:<?php echo esc_attr( $contact_email ); ?>">
                                    <?php echo esc_html( $contact_email ); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ( ! empty( $contact_phone ) ) : ?>
                                <a class="mcpt-c-contact-link"
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

        <?php
    }
}

// ── Run renderer when loaded as template ──────────────────────────────────────
if ( defined( 'METCPT_CAREER_TEMPLATE_LOADED' ) ) {
    global $wp_query;
    if ( $wp_query->have_posts() ) {
        $wp_query->the_post();
        metcpt_render_career_single();
        wp_reset_postdata();
    }
}