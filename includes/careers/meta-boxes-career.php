<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Register Career meta box ──────────────────────────────────────────────────
function haraka_career_add_meta_boxes() {
    add_meta_box(
        'haraka_career_details',
        'Career Details',
        'haraka_career_meta_box_html',
        'hrk_career',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'haraka_career_add_meta_boxes' );


// ── Career meta box HTML ──────────────────────────────────────────────────────
function haraka_career_meta_box_html( $post ) {
    wp_nonce_field( 'haraka_career_meta_save', 'haraka_career_nonce' );

    // ── Pull saved values ─────────────────────────────────────────────────────
    $company_id  = get_post_meta( $post->ID, 'career_company_id',  true );
    $department  = get_post_meta( $post->ID, 'career_department',  true );
    $location    = get_post_meta( $post->ID, 'career_location',    true );
    $type        = get_post_meta( $post->ID, 'career_type',        true );
    $close_date  = get_post_meta( $post->ID, 'career_close_date',  true );
    $salary      = get_post_meta( $post->ID, 'career_salary',      true );
    $apply_url   = get_post_meta( $post->ID, 'career_apply_url',   true );
    $contact_name  = get_post_meta( $post->ID, 'career_contact_name',  true );
    $contact_email = get_post_meta( $post->ID, 'career_contact_email', true );
    $contact_phone = get_post_meta( $post->ID, 'career_contact_phone', true );

    // ── Get all published companies for dropdown ───────────────────────────────
    $companies = get_posts( array(
        'post_type'      => 'hrk_company',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );

    // ── Get departments from settings ─────────────────────────────────────────
    $departments_raw = get_option(
        'haraka_career_departments',
        "Finance\nHuman Resource\nICT\nOperations\nProcurement\nLegal\nMarketing\nAdministration"
    );
    $departments = array_filter( array_map( 'trim', explode( "\n", $departments_raw ) ) );

    // ── Job types — fixed options ─────────────────────────────────────────────
    $job_types = array(
        'Full Time',
        'Part Time',
        'Contract',
        'Internship',
    );

    // ── Live status badge ─────────────────────────────────────────────────────
    $status_html = '';
    if ( ! empty( $close_date ) ) {
        $today      = new DateTime( 'today' );
        $close_obj  = date_create( $close_date );
        if ( $close_obj ) {
            if ( $close_obj < $today ) {
                $status_html = '<span class="hrk-admin-badge hrk-badge-past">Closed</span>';
            } elseif ( (int) $today->diff( $close_obj )->days <= 7 ) {
                $status_html = '<span class="hrk-admin-badge hrk-badge-today">Closing Soon</span>';
            } else {
                $status_html = '<span class="hrk-admin-badge hrk-badge-upcoming">Open</span>';
            }
        }
    }
    ?>

    <div class="hrk-meta-wrap">

        <?php /* ── SECTION 1: Company & Role ── */ ?>
        <div class="hrk-meta-section-title">
            Section 1 — Company &amp; Role <?php echo $status_html; ?>
        </div>

        <div class="hrk-meta-row">
            <label for="haraka_career_company_id">
                Hiring Company <span class="hrk-required">*</span>
                <span class="hrk-hint">
                    Select the company offering this position.
                    If not listed, add it first under Companies in the sidebar.
                </span>
            </label>
            <select id="haraka_career_company_id"
                    name="haraka_career_company_id">
                <option value="">-- Select Company --</option>
                <?php foreach ( $companies as $company ) : ?>
                    <option value="<?php echo esc_attr( $company->ID ); ?>"
                        <?php selected( (int) $company_id, $company->ID ); ?>>
                        <?php
                        $short = get_post_meta( $company->ID, 'company_short_name', true );
                        echo esc_html( $short ? $short . ' — ' . $company->post_title : $company->post_title );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ( empty( $companies ) ) : ?>
                <p style="font-size: 11px; color: #d63638; margin: 6px 0 0;">
                    No companies found. Please
                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=hrk_company' ) ); ?>"
                       target="_blank">add a company</a> first.
                </p>
            <?php endif; ?>
        </div>

        <div class="hrk-meta-row">
            <label for="haraka_career_department">
                Department <span class="hrk-required">*</span>
                <span class="hrk-hint">
                    Manage department options under Haraka Settings → Careers
                </span>
            </label>
            <select id="haraka_career_department"
                    name="haraka_career_department">
                <option value="">-- Select Department --</option>
                <?php foreach ( $departments as $dept ) : ?>
                    <option value="<?php echo esc_attr( $dept ); ?>"
                        <?php selected( $department, $dept ); ?>>
                        <?php echo esc_html( $dept ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="hrk-meta-row">
            <label for="haraka_career_type">
                Job Type <span class="hrk-required">*</span>
                <span class="hrk-hint">Employment basis for this position</span>
            </label>
            <select id="haraka_career_type"
                    name="haraka_career_type">
                <option value="">-- Select Job Type --</option>
                <?php foreach ( $job_types as $jtype ) : ?>
                    <option value="<?php echo esc_attr( $jtype ); ?>"
                        <?php selected( $type, $jtype ); ?>>
                        <?php echo esc_html( $jtype ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php /* ── SECTION 2: Location & Compensation ── */ ?>
        <div class="hrk-meta-section-title">Section 2 — Location &amp; Compensation</div>

        <div class="hrk-meta-row">
            <label for="haraka_career_location">
                Work Location
                <span class="hrk-hint">e.g. Gombak, Selangor or Remote</span>
            </label>
            <input type="text"
                   id="haraka_career_location"
                   name="haraka_career_location"
                   value="<?php echo esc_attr( $location ); ?>"
                   placeholder="e.g. Gombak, Selangor" />
        </div>

        <div class="hrk-meta-row">
            <label for="haraka_career_salary">
                Salary Range
                <span class="hrk-hint">Optional — e.g. RM 3,000 – RM 4,500 per month</span>
            </label>
            <input type="text"
                   id="haraka_career_salary"
                   name="haraka_career_salary"
                   value="<?php echo esc_attr( $salary ); ?>"
                   placeholder="e.g. RM 3,000 – RM 4,500 per month" />
        </div>

        <?php /* ── SECTION 3: Application Details ── */ ?>
        <div class="hrk-meta-section-title">Section 3 — Application Details</div>

        <div class="hrk-meta-row">
            <label for="haraka_career_close_date">
                Application Closing Date <span class="hrk-required">*</span>
                <span class="hrk-hint">
                    Plugin auto-determines Open / Closing Soon / Closed from this date
                </span>
            </label>
            <input type="date"
                   id="haraka_career_close_date"
                   name="haraka_career_close_date"
                   value="<?php echo esc_attr( $close_date ); ?>" />
        </div>

        <div class="hrk-meta-row">
            <label for="haraka_career_apply_url">
                Application Link
                <span class="hrk-hint">
                    URL to the application form or email link.
                    e.g. https://forms.google.com/... or mailto:hr@company.com
                </span>
            </label>
            <input type="text"
                   id="haraka_career_apply_url"
                   name="haraka_career_apply_url"
                   value="<?php echo esc_attr( $apply_url ); ?>"
                   placeholder="https://forms.google.com/... or mailto:hr@company.com" />
        </div>

        <?php /* ── SECTION 4: HR Contact ── */ ?>
        <div class="hrk-meta-section-title">Section 4 — HR Contact</div>

        <div class="hrk-meta-row">
            <label for="haraka_career_contact_name">
                Contact Person
                <span class="hrk-hint">HR representative handling this vacancy</span>
            </label>
            <input type="text"
                   id="haraka_career_contact_name"
                   name="haraka_career_contact_name"
                   value="<?php echo esc_attr( $contact_name ); ?>"
                   placeholder="e.g. Puan Aishah" />
        </div>

        <div class="hrk-meta-row">
            <label for="haraka_career_contact_email">
                Contact Email
            </label>
            <input type="text"
                   id="haraka_career_contact_email"
                   name="haraka_career_contact_email"
                   value="<?php echo esc_attr( $contact_email ); ?>"
                   placeholder="e.g. hr@iiumholdings.com.my" />
        </div>

        <div class="hrk-meta-row">
            <label for="haraka_career_contact_phone">
                Contact Phone
            </label>
            <input type="text"
                   id="haraka_career_contact_phone"
                   name="haraka_career_contact_phone"
                   value="<?php echo esc_attr( $contact_phone ); ?>"
                   placeholder="e.g. +603-6421 4331" />
        </div>

    </div>

    <?php
}


// ── Save career meta ──────────────────────────────────────────────────────────
function haraka_save_career_meta( $post_id ) {
    if ( ! isset( $_POST['haraka_career_nonce'] ) ||
         ! wp_verify_nonce( $_POST['haraka_career_nonce'], 'haraka_career_meta_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( get_post_type( $post_id ) !== 'hrk_career' ) {
        return;
    }

    // ── Company ID — stored as integer ────────────────────────────────────────
    if ( isset( $_POST['haraka_career_company_id'] ) ) {
        update_post_meta(
            $post_id,
            'career_company_id',
            absint( $_POST['haraka_career_company_id'] )
        );
    }

    // ── Controlled select fields ──────────────────────────────────────────────
    $select_fields = array(
        'haraka_career_department' => 'career_department',
        'haraka_career_type'       => 'career_type',
    );

    foreach ( $select_fields as $post_key => $meta_key ) {
        if ( isset( $_POST[ $post_key ] ) ) {
            update_post_meta(
                $post_id,
                $meta_key,
                sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) )
            );
        }
    }

    // ── Free text fields ──────────────────────────────────────────────────────
    $text_fields = array(
        'haraka_career_location'      => 'career_location',
        'haraka_career_salary'        => 'career_salary',
        'haraka_career_close_date'    => 'career_close_date',
        'haraka_career_contact_name'  => 'career_contact_name',
        'haraka_career_contact_email' => 'career_contact_email',
        'haraka_career_contact_phone' => 'career_contact_phone',
    );

    foreach ( $text_fields as $post_key => $meta_key ) {
        if ( isset( $_POST[ $post_key ] ) ) {
            update_post_meta(
                $post_id,
                $meta_key,
                sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) )
            );
        }
    }

    // ── URL field — must use esc_url_raw to reject javascript: schemes ────────
    if ( isset( $_POST['haraka_career_apply_url'] ) ) {
        update_post_meta(
            $post_id,
            'career_apply_url',
            esc_url_raw( wp_unslash( $_POST['haraka_career_apply_url'] ) )
        );
    }
}
add_action( 'save_post', 'haraka_save_career_meta' );