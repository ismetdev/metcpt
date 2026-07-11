<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Register Company meta box ─────────────────────────────────────────────────
function metcpt_company_add_meta_boxes() {
    add_meta_box(
        'metcpt_company_details',
        'Company Details',
        'metcpt_company_meta_box_html',
        'metcpt_company',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'metcpt_company_add_meta_boxes' );


// ── Company meta box HTML ─────────────────────────────────────────────────────
function metcpt_company_meta_box_html( $post ) {
    wp_nonce_field( 'metcpt_company_meta_save', 'metcpt_company_nonce' );

    $full_name   = get_post_meta( $post->ID, 'company_full_name',   true );
    $short_name  = get_post_meta( $post->ID, 'company_short_name',  true );
    $website     = get_post_meta( $post->ID, 'company_website',     true );
    $address     = get_post_meta( $post->ID, 'company_address',     true );
    $description = get_post_meta( $post->ID, 'company_description', true );
    ?>

    <div class="mcpt-meta-wrap">

        <div class="mcpt-meta-section-title">Section 1 — Identity</div>

        <div class="mcpt-meta-row">
            <label for="metcpt_company_full_name">
                Full Legal Name <span class="mcpt-required">*</span>
                <span class="mcpt-hint">e.g. Daya Bersih Sdn Bhd</span>
            </label>
            <input type="text"
                   id="metcpt_company_full_name"
                   name="metcpt_company_full_name"
                   value="<?php echo esc_attr( $full_name ); ?>"
                   placeholder="e.g. Daya Bersih Sdn Bhd" />
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_company_short_name">
                Short / Display Name
                <span class="mcpt-hint">
                    Used in listings and dropdowns e.g. Daya Bersih
                </span>
            </label>
            <input type="text"
                   id="metcpt_company_short_name"
                   name="metcpt_company_short_name"
                   value="<?php echo esc_attr( $short_name ); ?>"
                   placeholder="e.g. Daya Bersih" />
        </div>

        <div class="mcpt-meta-row">
            <label for="metcpt_company_website">
                Company Website
                <span class="mcpt-hint">e.g. https://dayabersih.com.my</span>
            </label>
            <input type="url"
                   id="metcpt_company_website"
                   name="metcpt_company_website"
                   value="<?php echo esc_attr( $website ); ?>"
                   placeholder="https://example.com.my" />
        </div>

        <div class="mcpt-meta-section-title">Section 2 — Location</div>

        <div class="mcpt-meta-row">
            <label for="metcpt_company_address">
                Office Address
                <span class="mcpt-hint">Full address of the company office</span>
            </label>
            <textarea id="metcpt_company_address"
                      name="metcpt_company_address"
                      rows="4"
                      placeholder="e.g. Level 3, Muhammad Abdul Rauf Building&#10;International Islamic University Malaysia&#10;Jalan Gombak, 53100 Kuala Lumpur"><?php echo esc_textarea( $address ); ?></textarea>
        </div>

        <div class="mcpt-meta-section-title">Section 3 — About</div>

        <div class="mcpt-meta-row">
            <label for="metcpt_company_description">
                Company Description
                <span class="mcpt-hint">
                    One short paragraph shown on career listings for this company
                </span>
            </label>
            <textarea id="metcpt_company_description"
                      name="metcpt_company_description"
                      rows="4"
                      placeholder="e.g. Daya Bersih Sdn Bhd is a facilities management subsidiary of IIUM Holdings..."><?php echo esc_textarea( $description ); ?></textarea>
        </div>

        <div class="mcpt-meta-section-title">Section 4 — Logo</div>

        <div class="mcpt-meta-row">
            <label>
                Company Logo
                <span class="mcpt-hint">
                    Set the Featured Image of this post as the company logo.
                    Use the Featured Image panel on the right side of this editor.
                </span>
            </label>
            <div style="padding-top: 6px;">
                <?php
                $thumb_id = get_post_thumbnail_id( $post->ID );
                if ( $thumb_id ) :
                    $thumb_url = wp_get_attachment_image_url( $thumb_id, array( 120, 60 ) );
                    ?>
                    <img src="<?php echo esc_url( $thumb_url ); ?>"
                         style="max-height: 60px; max-width: 180px; border: 1px solid #e2e8f0; border-radius: 4px; padding: 4px;" />
                    <p style="font-size: 11px; color: #64748b; margin: 6px 0 0;">
                        Logo set. To change it use the Featured Image panel.
                    </p>
                <?php else : ?>
                    <p style="font-size: 12px; color: #94a3b8; margin: 0;">
                        No logo set. Add one using the
                        <strong>Featured Image</strong> panel on the right side of this page.
                    </p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <?php
}


// ── Save company meta ─────────────────────────────────────────────────────────
function metcpt_save_company_meta( $post_id ) {
    if ( ! isset( $_POST['metcpt_company_nonce'] ) ||
         ! wp_verify_nonce( $_POST['metcpt_company_nonce'], 'metcpt_company_meta_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    if ( get_post_type( $post_id ) !== 'metcpt_company' ) {
        return;
    }

    $text_fields = array(
        'metcpt_company_full_name'  => 'company_full_name',
        'metcpt_company_short_name' => 'company_short_name',
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

    // URL field
    if ( isset( $_POST['metcpt_company_website'] ) ) {
        update_post_meta(
            $post_id,
            'company_website',
            esc_url_raw( wp_unslash( $_POST['metcpt_company_website'] ) )
        );
    }

    // Textarea fields
    $textarea_fields = array(
        'metcpt_company_address'     => 'company_address',
        'metcpt_company_description' => 'company_description',
    );

    foreach ( $textarea_fields as $post_key => $meta_key ) {
        if ( isset( $_POST[ $post_key ] ) ) {
            update_post_meta(
                $post_id,
                $meta_key,
                sanitize_textarea_field( wp_unslash( $_POST[ $post_key ] ) )
            );
        }
    }
}
add_action( 'save_post', 'metcpt_save_company_meta' );