<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Register the Events meta box ──────────────────────────────────────────────
function metcpt_events_add_meta_boxes() {
    add_meta_box(
        'metcpt_event_details',
        'Event Details',
        'metcpt_event_meta_box_html',
        'metcpt_event',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'metcpt_events_add_meta_boxes' );


// ── Meta box HTML — all 8 sections ────────────────────────────────────────────
function metcpt_event_meta_box_html( $post ) {
    wp_nonce_field( 'metcpt_event_meta_save', 'metcpt_event_nonce' );

    $event_date          = get_post_meta( $post->ID, 'event_date',          true );
    $event_time          = get_post_meta( $post->ID, 'event_time',          true );
    $event_venue         = get_post_meta( $post->ID, 'event_venue',         true );
    $event_organiser     = get_post_meta( $post->ID, 'event_organiser',     true );
    $event_audience      = get_post_meta( $post->ID, 'event_audience',      true );
    $event_capacity      = get_post_meta( $post->ID, 'event_capacity',      true );
    $event_rsvp_url      = get_post_meta( $post->ID, 'event_rsvp_url',      true );
    $event_contact_name  = get_post_meta( $post->ID, 'event_contact_name',  true );
    $event_contact_dept  = get_post_meta( $post->ID, 'event_contact_dept',  true );
    $event_contact_email = get_post_meta( $post->ID, 'event_contact_email', true );
    $event_contact_phone = get_post_meta( $post->ID, 'event_contact_phone', true );
    $event_cal_url       = get_post_meta( $post->ID, 'event_cal_url',       true );
    $event_guidelines    = get_post_meta( $post->ID, 'event_guidelines',    true );

    $vips_raw      = get_post_meta( $post->ID, 'event_vips',      true );
    $itinerary_raw = get_post_meta( $post->ID, 'event_itinerary', true );
    $faqs_raw      = get_post_meta( $post->ID, 'event_faqs',      true );

    $vips      = array();
    $itinerary = array();
    $faqs      = array();

    if ( ! empty( $vips_raw ) ) {
        $decoded = json_decode( $vips_raw, true );
        $vips    = ( is_array( $decoded ) && ! empty( $decoded ) ) ? $decoded : array();
    }
    if ( empty( $vips ) ) {
        $vips = array( array( 'name' => '', 'title' => '', 'role' => '' ) );
    }

    if ( ! empty( $itinerary_raw ) ) {
        $decoded   = json_decode( $itinerary_raw, true );
        $itinerary = ( is_array( $decoded ) && ! empty( $decoded ) ) ? $decoded : array();
    }
    if ( empty( $itinerary ) ) {
        $itinerary = array( array( 'time' => '', 'activity' => '', 'pic' => '' ) );
    }

    if ( ! empty( $faqs_raw ) ) {
        $decoded = json_decode( $faqs_raw, true );
        $faqs    = ( is_array( $decoded ) && ! empty( $decoded ) ) ? $decoded : array();
    }
    if ( empty( $faqs ) ) {
        $faqs = array( array( 'question' => '', 'answer' => '' ) );
    }

    $status_html = '';
    if ( ! empty( $event_date ) ) {
        $today    = new DateTime( 'today' );
        $date_obj = date_create( $event_date );
        if ( $date_obj ) {
            if ( $date_obj < $today ) {
                $status_html = '<span class="hrk-admin-badge hrk-badge-past">Past</span>';
            } elseif ( $date_obj->format( 'Y-m-d' ) === $today->format( 'Y-m-d' ) ) {
                $status_html = '<span class="hrk-admin-badge hrk-badge-today">Today</span>';
            } else {
                $status_html = '<span class="hrk-admin-badge hrk-badge-upcoming">Upcoming</span>';
            }
        }
    }
    ?>

    <div class="hrk-meta-wrap">

        <div class="hrk-meta-section-title">
            Section 1 — Event Schedule <?php echo $status_html; ?>
        </div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_date">
                Event Date <span class="hrk-required">*</span>
                <span class="hrk-hint">When does this event take place?</span>
            </label>
            <input type="date" id="metcpt_event_date" name="metcpt_event_date"
                   value="<?php echo esc_attr( $event_date ); ?>" />
        </div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_time">
                Event Time
                <span class="hrk-hint">e.g. 9:00 AM - 5:00 PM</span>
            </label>
            <input type="text" id="metcpt_event_time" name="metcpt_event_time"
                   value="<?php echo esc_attr( $event_time ); ?>"
                   placeholder="e.g. 9:00 AM - 5:00 PM" />
        </div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_venue">
                Venue / Location
                <span class="hrk-hint">e.g. Dewan Besar, IIUM Gombak</span>
            </label>
            <input type="text" id="metcpt_event_venue" name="metcpt_event_venue"
                   value="<?php echo esc_attr( $event_venue ); ?>"
                   placeholder="e.g. Dewan Besar, IIUM Gombak" />
        </div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_organiser">
                Organiser
                <span class="hrk-hint">e.g. IIUM Holdings Sdn Bhd</span>
            </label>
            <input type="text" id="metcpt_event_organiser" name="metcpt_event_organiser"
                   value="<?php echo esc_attr( $event_organiser ); ?>"
                   placeholder="e.g. IIUM Holdings Sdn Bhd" />
        </div>

        <div class="hrk-meta-section-title">Section 2 — VIPs &amp; Key Figures</div>

        <div id="metcpt-vips-wrap">
            <?php foreach ( $vips as $i => $vip ) : ?>
            <div class="hrk-repeatable-row" data-type="vip">
                <div class="hrk-repeatable-fields">
                    <input type="text"
                           name="metcpt_vips[<?php echo $i; ?>][name]"
                           value="<?php echo esc_attr( $vip['name'] ); ?>"
                           placeholder="Full name (e.g. YBhg. Dato Dr. Ahmad)" />
                    <input type="text"
                           name="metcpt_vips[<?php echo $i; ?>][title]"
                           value="<?php echo esc_attr( $vip['title'] ); ?>"
                           placeholder="Title / Position (e.g. Chairman, IIUM Holdings)" />
                    <?php
                        $vip_roles_raw = get_option( 'metcpt_vip_roles', "Guest of Honour\nTazkirah\nNotable Attendee\nSpeaker\nMC" );
                        $vip_roles     = array_filter( array_map( 'trim', explode( "\n", $vip_roles_raw ) ) );
                        ?>

                        <select name="metcpt_vips[<?php echo $i; ?>][role]">
                            <option value="">-- Select Role --</option>
                            <?php foreach ( $vip_roles as $role ) : ?>
                                <option value="<?php echo esc_attr( $role ); ?>" <?php selected( $vip['role'], $role ); ?>>
                                    <?php echo esc_html( $role ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                </div>
                <button type="button" class="hrk-remove-row button">Remove</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="hrk-add-row button"
                data-target="metcpt-vips-wrap" data-type="vip">
            + Add VIP / Key Figure
        </button>

        <div class="hrk-meta-section-title">Section 3 — Programme Itinerary</div>

        <div class="hrk-itinerary-header">
            <span>Time</span>
            <span>Activity</span>
            <span>Person in Charge</span>
            <span></span>
        </div>

        <div id="metcpt-itinerary-wrap">
            <?php foreach ( $itinerary as $i => $item ) : ?>
            <div class="hrk-repeatable-row hrk-itinerary-row" data-type="itinerary">
                <div class="hrk-repeatable-fields hrk-itinerary-fields">
                    <input type="text"
                           name="metcpt_itinerary[<?php echo $i; ?>][time]"
                           value="<?php echo esc_attr( $item['time'] ); ?>"
                           placeholder="e.g. 9:00 AM" />
                    <input type="text"
                           name="metcpt_itinerary[<?php echo $i; ?>][activity]"
                           value="<?php echo esc_attr( $item['activity'] ); ?>"
                           placeholder="e.g. Arrival and Registration" />
                    <input type="text"
                           name="metcpt_itinerary[<?php echo $i; ?>][pic]"
                           value="<?php echo esc_attr( $item['pic'] ); ?>"
                           placeholder="e.g. Protocol Unit" />
                </div>
                <button type="button" class="hrk-remove-row button">Remove</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="hrk-add-row button"
                data-target="metcpt-itinerary-wrap" data-type="itinerary">
            + Add Itinerary Row
        </button>

        <div class="hrk-meta-section-title">Section 4 — Attendance &amp; Capacity</div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_audience">
                Who Should Attend
                <span class="hrk-hint">e.g. All IIUM Holdings staff and subsidiary representatives</span>
            </label>
            <input type="text" id="metcpt_event_audience" name="metcpt_event_audience"
                   value="<?php echo esc_attr( $event_audience ); ?>"
                   placeholder="e.g. All staff and invited shareholders" />
        </div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_capacity">
                Capacity
                <span class="hrk-hint">e.g. 300 pax</span>
            </label>
            <input type="text" id="metcpt_event_capacity" name="metcpt_event_capacity"
                   value="<?php echo esc_attr( $event_capacity ); ?>"
                   placeholder="e.g. 300 pax" />
        </div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_rsvp_url">
                RSVP / Registration Link
                <span class="hrk-hint">Paste the URL to your RSVP form or Google Form</span>
            </label>
            <input type="url" id="metcpt_event_rsvp_url" name="metcpt_event_rsvp_url"
                   value="<?php echo esc_attr( $event_rsvp_url ); ?>"
                   placeholder="https://forms.google.com/..." />
        </div>

        <div class="hrk-meta-section-title">Section 5 — Frequently Asked Questions</div>

        <div id="metcpt-faqs-wrap">
            <?php foreach ( $faqs as $i => $faq ) : ?>
            <div class="hrk-repeatable-row hrk-faq-row" data-type="faq">
                <div class="hrk-repeatable-fields hrk-faq-fields">
                    <input type="text"
                           name="metcpt_faqs[<?php echo $i; ?>][question]"
                           value="<?php echo esc_attr( $faq['question'] ); ?>"
                           placeholder="e.g. Is parking available?" />
                    <textarea name="metcpt_faqs[<?php echo $i; ?>][answer]"
                              placeholder="e.g. Yes. Staff may park at Car Park B..."
                              rows="2"><?php echo esc_textarea( $faq['answer'] ); ?></textarea>
                </div>
                <button type="button" class="hrk-remove-row button">Remove</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="hrk-add-row button"
                data-target="metcpt-faqs-wrap" data-type="faq">
            + Add FAQ
        </button>

        <div class="hrk-meta-section-title">Section 6 — Guidelines &amp; Important Notes</div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_guidelines">
                Guidelines
                <span class="hrk-hint">One guideline per line. Each line becomes a bullet point.</span>
            </label>
            <textarea id="metcpt_event_guidelines" name="metcpt_event_guidelines"
                      rows="5"
                      placeholder="Smart casual attire. No shorts or sleeveless tops.
Please arrive by 10:30 AM.
Please stand when the Guest of Honour arrives.
Photography is permitted during the event."><?php echo esc_textarea( $event_guidelines ); ?></textarea>
        </div>

        <div class="hrk-meta-section-title">Section 7 — Call to Action</div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_cal_url">
                Add to Calendar URL
                <span class="hrk-hint">Paste a Google Calendar event link. Leave blank to auto-generate.</span>
            </label>
            <input type="url" id="metcpt_event_cal_url" name="metcpt_event_cal_url"
                   value="<?php echo esc_attr( $event_cal_url ); ?>"
                   placeholder="https://calendar.google.com/..." />
        </div>

        <div class="hrk-meta-section-title">Section 8 — Contact &amp; Secretariat</div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_contact_name">
                PIC Name
                <span class="hrk-hint">Person in charge for this event</span>
            </label>
            <input type="text" id="metcpt_event_contact_name" name="metcpt_event_contact_name"
                   value="<?php echo esc_attr( $event_contact_name ); ?>"
                   placeholder="e.g. Puan Siti Nabilah" />
        </div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_contact_dept">
                Department
                <span class="hrk-hint">e.g. Corporate Affairs Unit</span>
            </label>
            <input type="text" id="metcpt_event_contact_dept" name="metcpt_event_contact_dept"
                   value="<?php echo esc_attr( $event_contact_dept ); ?>"
                   placeholder="e.g. Corporate Affairs Unit" />
        </div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_contact_email">
                Email
            </label>
            <input type="text" id="metcpt_event_contact_email" name="metcpt_event_contact_email"
                   value="<?php echo esc_attr( $event_contact_email ); ?>"
                   placeholder="e.g. events@iiumholdings.com.my" />
        </div>

        <div class="hrk-meta-row">
            <label for="metcpt_event_contact_phone">
                Phone
            </label>
            <input type="text" id="metcpt_event_contact_phone" name="metcpt_event_contact_phone"
                   value="<?php echo esc_attr( $event_contact_phone ); ?>"
                   placeholder="e.g. +603-6421 4331" />
        </div>

    </div>

    <script>
    (function() {
        function reindexRows( wrap ) {
            var rows = wrap.querySelectorAll( '.hrk-repeatable-row' );
            rows.forEach( function( row, i ) {
                row.querySelectorAll( 'input, textarea, select' ).forEach( function( el ) {
                    if ( el.name ) {
                        el.name = el.name.replace( /\[\d+\]/, '[' + i + ']' );
                    }
                } );
            } );
        }

        function makeRemovable( row, wrap ) {
            var btn = row.querySelector( '.hrk-remove-row' );
            if ( btn ) {
                btn.addEventListener( 'click', function() {
                    row.remove();
                    reindexRows( wrap );
                } );
            }
        }

        document.querySelectorAll( '.hrk-repeatable-row' ).forEach( function( row ) {
            var wrap = row.closest( '[id$="-wrap"]' );
            if ( wrap ) {
                makeRemovable( row, wrap );
            }
        } );

        document.querySelectorAll( '.hrk-add-row' ).forEach( function( btn ) {
            btn.addEventListener( 'click', function() {
                var wrapId = btn.getAttribute( 'data-target' );
                var type   = btn.getAttribute( 'data-type' );
                var wrap   = document.getElementById( wrapId );
                var count  = wrap.querySelectorAll( '.hrk-repeatable-row' ).length;
                var row    = document.createElement( 'div' );
                row.className = 'hrk-repeatable-row';

                var fields = '';

                if ( type === 'vip' ) {
                    fields = '<div class="hrk-repeatable-fields">'
                        + '<input type="text" name="metcpt_vips[' + count + '][name]" placeholder="Full name" />'
                        + '<input type="text" name="metcpt_vips[' + count + '][title]" placeholder="Title / Position" />'
                        + '<select name="metcpt_vips[' + count + '][role]">'
                        + '<option value="">-- Select Role --</option>'
                        <?php foreach ( $vip_roles as $role ) : ?>
                        + '<option value="<?php echo esc_js( $role ); ?>"><?php echo esc_js( $role ); ?></option>'
                        <?php endforeach; ?>
                        + '</select>'
                        + '</div>';
                } else if ( type === 'itinerary' ) {
                    fields = '<div class="hrk-repeatable-fields hrk-itinerary-fields">'
                        + '<input type="text" name="metcpt_itinerary[' + count + '][time]" placeholder="e.g. 9:00 AM" />'
                        + '<input type="text" name="metcpt_itinerary[' + count + '][activity]" placeholder="e.g. Arrival and Registration" />'
                        + '<input type="text" name="metcpt_itinerary[' + count + '][pic]" placeholder="e.g. Protocol Unit" />'
                        + '</div>';
                } else if ( type === 'faq' ) {
                    fields = '<div class="hrk-repeatable-fields hrk-faq-fields">'
                        + '<input type="text" name="metcpt_faqs[' + count + '][question]" placeholder="e.g. Is parking available?" />'
                        + '<textarea name="metcpt_faqs[' + count + '][answer]" placeholder="Answer..." rows="2"></textarea>'
                        + '</div>';
                }

                row.innerHTML = fields
                    + '<button type="button" class="hrk-remove-row button">Remove</button>';

                wrap.appendChild( row );
                makeRemovable( row, wrap );
            } );
        } );
    })();
    </script>

    <?php
}


// ── Save all event meta ───────────────────────────────────────────────────────
function metcpt_save_event_meta( $post_id ) {

    if ( ! isset( $_POST['metcpt_event_nonce'] ) ||
         ! wp_verify_nonce( $_POST['metcpt_event_nonce'], 'metcpt_event_meta_save' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( get_post_type( $post_id ) !== 'metcpt_event' ) {
        return;
    }

    // ── Simple text fields ────────────────────────────────────────────────────
    $text_fields = array(
        'metcpt_event_date'          => 'event_date',
        'metcpt_event_time'          => 'event_time',
        'metcpt_event_venue'         => 'event_venue',
        'metcpt_event_organiser'     => 'event_organiser',
        'metcpt_event_audience'      => 'event_audience',
        'metcpt_event_capacity'      => 'event_capacity',
        'metcpt_event_contact_name'  => 'event_contact_name',
        'metcpt_event_contact_dept'  => 'event_contact_dept',
        'metcpt_event_contact_email' => 'event_contact_email',
        'metcpt_event_contact_phone' => 'event_contact_phone',
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

    // ── URL fields ────────────────────────────────────────────────────────────
    $url_fields = array(
        'metcpt_event_rsvp_url' => 'event_rsvp_url',
        'metcpt_event_cal_url'  => 'event_cal_url',
    );

    foreach ( $url_fields as $post_key => $meta_key ) {
        if ( isset( $_POST[ $post_key ] ) ) {
            update_post_meta(
                $post_id,
                $meta_key,
                esc_url_raw( wp_unslash( $_POST[ $post_key ] ) )
            );
        }
    }

    // ── Textarea fields ───────────────────────────────────────────────────────
    if ( isset( $_POST['metcpt_event_guidelines'] ) ) {
        update_post_meta(
            $post_id,
            'event_guidelines',
            sanitize_textarea_field( wp_unslash( $_POST['metcpt_event_guidelines'] ) )
        );
    }

    // ── Repeatable: VIPs ─────────────────────────────────────────────────────
    if ( isset( $_POST['metcpt_vips'] ) && is_array( $_POST['metcpt_vips'] ) ) {
        $vips = array();
        foreach ( $_POST['metcpt_vips'] as $vip ) {
            $name = sanitize_text_field( wp_unslash( isset( $vip['name'] ) ? $vip['name'] : '' ) );
            if ( ! empty( $name ) ) {
                $vips[] = array(
                    'name'  => $name,
                    'title' => sanitize_text_field( wp_unslash( isset( $vip['title'] ) ? $vip['title'] : '' ) ),
                    'role'  => sanitize_text_field( wp_unslash( isset( $vip['role'] )  ? $vip['role']  : '' ) ),
                );
            }
        }
        update_post_meta( $post_id, 'event_vips', wp_json_encode( $vips ) );
    }

    // ── Repeatable: Itinerary ─────────────────────────────────────────────────
    if ( isset( $_POST['metcpt_itinerary'] ) && is_array( $_POST['metcpt_itinerary'] ) ) {
        $itinerary = array();
        foreach ( $_POST['metcpt_itinerary'] as $item ) {
            $activity = sanitize_text_field( wp_unslash( isset( $item['activity'] ) ? $item['activity'] : '' ) );
            if ( ! empty( $activity ) ) {
                $itinerary[] = array(
                    'time'     => sanitize_text_field( wp_unslash( isset( $item['time'] ) ? $item['time'] : '' ) ),
                    'activity' => $activity,
                    'pic'      => sanitize_text_field( wp_unslash( isset( $item['pic'] )  ? $item['pic']  : '' ) ),
                );
            }
        }
        update_post_meta( $post_id, 'event_itinerary', wp_json_encode( $itinerary ) );
    }

    // ── Repeatable: FAQs ─────────────────────────────────────────────────────
    if ( isset( $_POST['metcpt_faqs'] ) && is_array( $_POST['metcpt_faqs'] ) ) {
        $faqs = array();
        foreach ( $_POST['metcpt_faqs'] as $faq ) {
            $question = sanitize_text_field( wp_unslash( isset( $faq['question'] ) ? $faq['question'] : '' ) );
            if ( ! empty( $question ) ) {
                $faqs[] = array(
                    'question' => $question,
                    'answer'   => sanitize_textarea_field( wp_unslash( isset( $faq['answer'] ) ? $faq['answer'] : '' ) ),
                );
            }
        }
        update_post_meta( $post_id, 'event_faqs', wp_json_encode( $faqs ) );
    }
}
add_action( 'save_post', 'metcpt_save_event_meta' );