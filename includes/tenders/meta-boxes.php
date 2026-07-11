<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Register the Tenders meta box ─────────────────────────────────────────────
function metcpt_tenders_add_meta_boxes() {
    add_meta_box(
        'metcpt_tender_details',
        'Tender Details',
        'metcpt_tender_meta_box_html',
        'metcpt_tender',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'metcpt_tenders_add_meta_boxes' );


// ── Meta box HTML ─────────────────────────────────────────────────────────────
function metcpt_tender_meta_box_html( $post ) {
    wp_nonce_field( 'metcpt_tender_meta_save', 'metcpt_tender_nonce' );

    $tender_ref               = get_post_meta( $post->ID, 'tender_ref',               true );
    $tender_category          = get_post_meta( $post->ID, 'tender_category',          true );
    $tender_issuer            = get_post_meta( $post->ID, 'tender_issuer',            true );
    $tender_location          = get_post_meta( $post->ID, 'tender_location',          true );
    $tender_close_date        = get_post_meta( $post->ID, 'tender_close_date',        true );
    $tender_close_time        = get_post_meta( $post->ID, 'tender_close_time',        true );
    $tender_validity          = get_post_meta( $post->ID, 'tender_validity',          true );
    $tender_fee               = get_post_meta( $post->ID, 'tender_fee',               true );
    $tender_submission_method = get_post_meta( $post->ID, 'tender_submission_method', true );
    $tender_submission_address= get_post_meta( $post->ID, 'tender_submission_address',true );
    $tender_document_url      = get_post_meta( $post->ID, 'tender_document_url',      true );
    $tender_contact_name      = get_post_meta( $post->ID, 'tender_contact_name',      true );
    $tender_contact_email     = get_post_meta( $post->ID, 'tender_contact_email',     true );
    $tender_contact_phone     = get_post_meta( $post->ID, 'tender_contact_phone',     true );

    // ── Live status badge ─────────────────────────────────────────────────────
    $status_html = '';
    if ( ! empty( $tender_close_date ) ) {
        $today      = new DateTime( 'today' );
        $close_date = date_create( $tender_close_date );
        $threshold = (int) get_option( 'metcpt_closing_soon_days', 7 );
        if ( $close_date ) {
            if ( $close_date < $today ) {
                $status_html = '<span class="mcpt-admin-badge mcpt-badge-past">Closed</span>';
            } elseif ( (int) $today->diff( $close_date )->days <= $threshold ) {
                $status_html = '<span class="mcpt-admin-badge mcpt-badge-today">Closing Soon</span>';
            } else {
                $status_html = '<span class="mcpt-admin-badge mcpt-badge-upcoming">Open</span>';
            }
        }
    }

    $categories_raw = get_option( 'metcpt_tender_categories', "Goods\nServices\nConstruction\nConsultancy\nOthers" );
    $categories     = array_filter( array_map( 'trim', explode( "\n", $categories_raw ) ) );
    ?>

    <div class="mcpt-meta-wrap">

        <div class="mcpt-meta-section-title">
            Section 1 — Tender Identity <?php echo $status_html; ?>
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_ref">
                Reference No. <span class="mcpt-required">*</span>
                <span class="mcpt-hint">e.g. IIUM-TDR-001/2026</span>
            </label>
            <input type="text" id="metcpt_tender_ref" name="metcpt_tender_ref"
                   value="<?php echo esc_attr( $tender_ref ); ?>"
                   placeholder="e.g. IIUM-TDR-001/2026" />
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_category">
                Category <span class="mcpt-required">*</span>
                <span class="mcpt-hint">Select the tender category</span>
            </label>
            <select id="metcpt_tender_category" name="metcpt_tender_category">
                <option value="">-- Select Category --</option>
                <?php foreach ( $categories as $cat ) : ?>
                    <option value="<?php echo esc_attr( $cat ); ?>"
                        <?php selected( $tender_category, $cat ); ?>>
                        <?php echo esc_html( $cat ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_issuer">
                Issuing Organisation
                <span class="mcpt-hint">e.g. Daya Bersih Sdn Bhd</span>
            </label>
            <input type="text" id="metcpt_tender_issuer" name="metcpt_tender_issuer"
                   value="<?php echo esc_attr( $tender_issuer ); ?>"
                   placeholder="e.g. Daya Bersih Sdn Bhd" />
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_location">
                Location
                <span class="mcpt-hint">e.g. Gombak, Selangor</span>
            </label>
            <input type="text" id="metcpt_tender_location" name="metcpt_tender_location"
                   value="<?php echo esc_attr( $tender_location ); ?>"
                   placeholder="e.g. Gombak, Selangor" />
        </div>

        <div class="mcpt-meta-section-title">Section 2 — Critical Timelines</div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_close_date">
                Closing Date <span class="mcpt-required">*</span>
                <span class="mcpt-hint">Plugin auto-determines Open / Closing Soon / Closed from this date</span>
            </label>
            <input type="date" id="metcpt_tender_close_date" name="metcpt_tender_close_date"
                   value="<?php echo esc_attr( $tender_close_date ); ?>" />
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_close_time">
                Closing Time
                <span class="mcpt-hint">e.g. 4:00 PM</span>
            </label>
            <input type="text" id="metcpt_tender_close_time" name="metcpt_tender_close_time"
                   value="<?php echo esc_attr( $tender_close_time ); ?>"
                   placeholder="e.g. 4:00 PM" />
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_validity">
                Tender Validity Period
                <span class="mcpt-hint">e.g. 90 days</span>
            </label>
            <input type="text" id="metcpt_tender_validity" name="metcpt_tender_validity"
                   value="<?php echo esc_attr( $tender_validity ); ?>"
                   placeholder="e.g. 90 days" />
        </div>

        <div class="mcpt-meta-section-title">Section 3 — Document &amp; Fee</div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_document_url">
                Document URL
                <span class="mcpt-hint">Paste the external link to the tender document or PDF</span>
            </label>
            <input type="url" id="metcpt_tender_document_url" name="metcpt_tender_document_url"
                   value="<?php echo esc_attr( $tender_document_url ); ?>"
                   placeholder="https://example.com/tender-document.pdf" />
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_fee">
                Tender Fee
                <span class="mcpt-hint">e.g. RM 50 or Free</span>
            </label>
            <input type="text" id="metcpt_tender_fee" name="metcpt_tender_fee"
                   value="<?php echo esc_attr( $tender_fee ); ?>"
                   placeholder="e.g. RM 50 or Free" />
        </div>

        <div class="mcpt-meta-section-title">Section 4 — Submission Details</div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_submission_method">
                Submission Method
                <span class="mcpt-hint">e.g. Physical Submission / Email / Online Portal</span>
            </label>
            <input type="text" id="metcpt_tender_submission_method"
                   name="metcpt_tender_submission_method"
                   value="<?php echo esc_attr( $tender_submission_method ); ?>"
                   placeholder="e.g. Physical Submission" />
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_submission_address">
                Submission Address / URL
                <span class="mcpt-hint">Full address or portal URL where bids are submitted</span>
            </label>
            <textarea id="metcpt_tender_submission_address"
                      name="metcpt_tender_submission_address"
                      rows="4"
                      placeholder="e.g. Procurement Unit, IIUM Holdings Sdn Bhd&#10;Level 3, Muhammad Abdul Rauf Building&#10;Jalan Gombak, 53100 Kuala Lumpur"><?php echo esc_textarea( $tender_submission_address ); ?></textarea>
        </div>

        <div class="mcpt-meta-section-title">Section 5 — Contact &amp; Enquiries</div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_contact_name">
                Contact Person
                <span class="mcpt-hint">PIC name for tender enquiries</span>
            </label>
            <input type="text" id="metcpt_tender_contact_name" name="metcpt_tender_contact_name"
                   value="<?php echo esc_attr( $tender_contact_name ); ?>"
                   placeholder="e.g. Puan Siti Nabilah" />
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_contact_email">
                Contact Email
            </label>
            <input type="text" id="metcpt_tender_contact_email" name="metcpt_tender_contact_email"
                   value="<?php echo esc_attr( $tender_contact_email ); ?>"
                   placeholder="e.g. tender@iiumholdings.com.my" />
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_tender_contact_phone">
                Contact Phone
            </label>
            <input type="text" id="metcpt_tender_contact_phone" name="metcpt_tender_contact_phone"
                   value="<?php echo esc_attr( $tender_contact_phone ); ?>"
                   placeholder="e.g. +603-6421 4331" />
        </div>

    </div>

    <?php
}


// ── Save all tender meta ──────────────────────────────────────────────────────
function metcpt_save_tender_meta( $post_id ) {
    if ( ! isset( $_POST['metcpt_tender_nonce'] ) ||
         ! wp_verify_nonce( $_POST['metcpt_tender_nonce'], 'metcpt_tender_meta_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( get_post_type( $post_id ) !== 'metcpt_tender' ) {
        return;
    }

    $text_fields = array(
        'metcpt_tender_ref'               => 'tender_ref',
        'metcpt_tender_category'          => 'tender_category',
        'metcpt_tender_issuer'            => 'tender_issuer',
        'metcpt_tender_location'          => 'tender_location',
        'metcpt_tender_close_date'        => 'tender_close_date',
        'metcpt_tender_close_time'        => 'tender_close_time',
        'metcpt_tender_validity'          => 'tender_validity',
        'metcpt_tender_fee'               => 'tender_fee',
        'metcpt_tender_submission_method' => 'tender_submission_method',
        'metcpt_tender_contact_name'      => 'tender_contact_name',
        'metcpt_tender_contact_email'     => 'tender_contact_email',
        'metcpt_tender_contact_phone'     => 'tender_contact_phone',
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

    // Textarea fields
    if ( isset( $_POST['metcpt_tender_submission_address'] ) ) {
        update_post_meta(
            $post_id,
            'tender_submission_address',
            sanitize_textarea_field( wp_unslash( $_POST['metcpt_tender_submission_address'] ) )
        );
    }

    // URL field
    if ( isset( $_POST['metcpt_tender_document_url'] ) ) {
        update_post_meta(
            $post_id,
            'tender_document_url',
            esc_url_raw( wp_unslash( $_POST['metcpt_tender_document_url'] ) )
        );
    }
}
add_action( 'save_post', 'metcpt_save_tender_meta' );