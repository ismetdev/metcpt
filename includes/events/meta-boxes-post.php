<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Event meta box for native WordPress posts.
 *
 * Lets an editor mark a normal post as an event. Ticking the box turns on the
 * same event fields the metcpt_event CPT screen uses, plus a control for where
 * the front-end summary block appears. The field markup, its repeater JS, and
 * the save logic are shared with the CPT screen via metcpt_event_meta_box_html()
 * and metcpt_save_event_meta_fields() in meta-boxes.php, so the two screens
 * cannot list different fields by accident.
 */

// ── Register the Event meta box on the Post screen ────────────────────────────
function metcpt_events_post_add_meta_box() {
    add_meta_box(
        'metcpt_event_post_details',
        'Event Details',
        'metcpt_event_post_meta_box_html',
        'post',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'metcpt_events_post_add_meta_box' );


// ── Allowed values for the summary placement field ─────────────────────────────
function metcpt_event_summary_position_options() {
    return array(
        ''       => 'Use default (Settings → Events)',
        'top'    => 'Top of the post',
        'bottom' => 'Bottom of the post',
        'both'   => 'Both top and bottom',
        'none'   => 'Do not show on this post',
    );
}


// ── Meta box HTML ──────────────────────────────────────────────────────────────
function metcpt_event_post_meta_box_html( $post ) {

    $is_event = (bool) get_post_meta( $post->ID, 'metcpt_is_event', true );
    $position = get_post_meta( $post->ID, 'metcpt_event_summary_position', true );
    ?>

    <div class="mcpt-meta-wrap">

        <div class="mcpt-meta-row">
            <label for="metcpt_is_event">
                Use this post for MetCPT Events
                <span class="mcpt-hint">
                    Adds this post to the /events/ listing and shows the event
                    summary on the post page.
                </span>
            </label>
            <input type="checkbox" id="metcpt_is_event" name="metcpt_is_event"
                   value="1" <?php checked( $is_event ); ?> />
        </div>

        <div class="mcpt-meta-row" id="mcpt-event-position-row"
             <?php echo $is_event ? '' : 'style="display:none;"'; ?>>
            <label for="metcpt_event_summary_position">
                Event summary position
                <span class="mcpt-hint">
                    Where the date, time, venue and organiser summary appears on
                    this post. Leave on default to follow the site-wide setting
                    in Settings &rarr; Events.
                </span>
            </label>
            <select id="metcpt_event_summary_position" name="metcpt_event_summary_position">
                <?php foreach ( metcpt_event_summary_position_options() as $value => $label ) : ?>
                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $position, $value ); ?>>
                        <?php echo wp_kses( $label, array() ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>

    <div id="mcpt-event-fields-wrap" <?php echo $is_event ? '' : 'style="display:none;"'; ?>>
        <?php
        // Renders the nonce field plus every event field and its repeater JS.
        // Shared unchanged with the metcpt_event CPT screen.
        metcpt_event_meta_box_html( $post );
        ?>
    </div>

    <script>
    (function() {
        var toggle = document.getElementById( 'metcpt_is_event' );
        var wrap   = document.getElementById( 'mcpt-event-fields-wrap' );
        var posRow = document.getElementById( 'mcpt-event-position-row' );
        if ( ! toggle ) {
            return;
        }
        toggle.addEventListener( 'change', function() {
            var show = toggle.checked;
            if ( wrap ) {
                wrap.style.display = show ? '' : 'none';
            }
            if ( posRow ) {
                posRow.style.display = show ? '' : 'none';
            }
        } );
    })();
    </script>

    <?php
}


// ── Save handler — native Post screen ──────────────────────────────────────────
function metcpt_save_event_meta_post( $post_id ) {

    // Same nonce field/action metcpt_event_meta_box_html() renders. The field
    // markup is always output (hidden by CSS, not removed from the DOM) so this
    // is present whether or not the tick box is on.
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

    if ( get_post_type( $post_id ) !== 'post' ) {
        return;
    }

    $is_event = isset( $_POST['metcpt_is_event'] ) && $_POST['metcpt_is_event'] === '1';

    if ( $is_event ) {
        update_post_meta( $post_id, 'metcpt_is_event', '1' );
    } else {
        // Deleted, not stored as '0'. The listing's meta_query checks
        // metcpt_is_event EXISTS, and a stored '0' would still satisfy that.
        delete_post_meta( $post_id, 'metcpt_is_event' );
    }

    if ( isset( $_POST['metcpt_event_summary_position'] ) ) {
        $position = sanitize_text_field( wp_unslash( $_POST['metcpt_event_summary_position'] ) );
        if ( ! array_key_exists( $position, metcpt_event_summary_position_options() ) ) {
            $position = '';
        }
        update_post_meta( $post_id, 'metcpt_event_summary_position', $position );
    }

    // The field inputs stay in the DOM (CSS-hidden, not removed) when the tick
    // box is off, so this always runs. Data survives being unticked and
    // re-ticked later.
    metcpt_save_event_meta_fields( $post_id );

    if ( $is_event ) {
        metcpt_assign_events_category( $post_id );
    }
}
add_action( 'save_post', 'metcpt_save_event_meta_post' );


// ── Assign the Events category, without disturbing other categories ───────────
function metcpt_assign_events_category( $post_id ) {
    $term = get_term_by( 'slug', 'events', 'category' );

    if ( ! $term ) {
        $inserted = wp_insert_term( 'Events', 'category', array( 'slug' => 'events' ) );
        if ( is_wp_error( $inserted ) ) {
            return;
        }
        $term_id = $inserted['term_id'];
    } else {
        $term_id = $term->term_id;
    }

    // Append, do not replace — the third argument true keeps existing categories.
    wp_set_post_terms( $post_id, array( $term_id ), 'category', true );
}
