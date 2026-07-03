<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Haraka Dummy Data
 *
 * Seeds and clears dummy content for local testing.
 * All dummy posts are tagged with _haraka_dummy = 1
 * so real content is never affected.
 *
 * Only available when enabled in Haraka Settings → General.
 *
 * @package Haraka
 * @subpackage Admin
 * @version 1.0.4
 */

// ── Guard: only run when dummy data is enabled ────────────────────────────────
function haraka_dummy_data_enabled() {
    return (bool) get_option( 'haraka_enable_dummy_data', 0 );
}

// ── Helper: relative date string ──────────────────────────────────────────────
function haraka_dummy_date( $offset_days ) {
    return date( 'Y-m-d', strtotime( $offset_days . ' days' ) );
}

// ── Seed dummy events ─────────────────────────────────────────────────────────
function haraka_seed_dummy_events() {
    $today = date( 'Y-m-d' );

    $events = array(

        // 5 past events
        array(
            'title'     => '[DUMMY] Annual Corporate Dinner 2024',
            'excerpt'   => 'Annual corporate dinner celebrating IIUM Holdings achievements.',
            'date'      => haraka_dummy_date( -120 ),
            'time'      => '7:00 PM - 10:00 PM',
            'venue'     => 'Grand Ballroom, Putrajaya Marriott Hotel',
            'organiser' => 'IIUM Holdings Sdn Bhd',
            'audience'  => 'Staff and Invited Guests',
            'capacity'  => '300',
        ),
        array(
            'title'     => '[DUMMY] RISE2030 Strategy Briefing',
            'excerpt'   => 'Briefing session on the RISE2030 Strategic Blueprint for all subsidiaries.',
            'date'      => haraka_dummy_date( -90 ),
            'time'      => '9:00 AM - 12:00 PM',
            'venue'     => 'Dewan Besar, IIUM Gombak',
            'organiser' => 'IIUM Holdings Sdn Bhd',
            'audience'  => 'All Subsidiary Heads',
            'capacity'  => '150',
        ),
        array(
            'title'     => '[DUMMY] Kasih Ramadan 2025 — Iftar Programme',
            'excerpt'   => 'Annual Ramadan charity programme distributing iftar packs to communities.',
            'date'      => haraka_dummy_date( -60 ),
            'time'      => '5:30 PM - 8:00 PM',
            'venue'     => 'Masjid IIUM, Gombak',
            'organiser' => 'Daya Bersih Sdn Bhd',
            'audience'  => 'Community Members',
            'capacity'  => '500',
        ),
        array(
            'title'     => '[DUMMY] Board of Directors Meeting Q1',
            'excerpt'   => 'Quarterly Board of Directors meeting to review Q1 performance.',
            'date'      => haraka_dummy_date( -45 ),
            'time'      => '10:00 AM - 1:00 PM',
            'venue'     => 'Boardroom, IIUM Holdings HQ',
            'organiser' => 'IIUM Holdings Sdn Bhd',
            'audience'  => 'Board Members Only',
            'capacity'  => '20',
        ),
        array(
            'title'     => '[DUMMY] Staff Excellence Award Ceremony',
            'excerpt'   => 'Recognition ceremony honouring outstanding staff across all subsidiaries.',
            'date'      => haraka_dummy_date( -15 ),
            'time'      => '2:00 PM - 5:00 PM',
            'venue'     => 'Auditorium, IIUM Cultural Centre',
            'organiser' => 'Human Resource Division',
            'audience'  => 'All Staff',
            'capacity'  => '400',
        ),

        // 5 upcoming events
        array(
            'title'     => '[DUMMY] IIUM Holdings 25th Anniversary Gala',
            'excerpt'   => 'Grand gala dinner celebrating 25 years of IIUM Holdings.',
            'date'      => haraka_dummy_date( 14 ),
            'time'      => '7:00 PM - 11:00 PM',
            'venue'     => 'Putrajaya International Convention Centre',
            'organiser' => 'IIUM Holdings Sdn Bhd',
            'audience'  => 'All Staff and VIP Guests',
            'capacity'  => '800',
        ),
        array(
            'title'     => '[DUMMY] Subsidiary CEO Roundtable Q2',
            'excerpt'   => 'Quarterly roundtable discussion among all subsidiary CEOs.',
            'date'      => haraka_dummy_date( 21 ),
            'time'      => '9:00 AM - 11:00 AM',
            'venue'     => 'Boardroom, IIUM Holdings HQ',
            'organiser' => 'IIUM Holdings Sdn Bhd',
            'audience'  => 'CEOs and Senior Management',
            'capacity'  => '30',
        ),
        array(
            'title'     => '[DUMMY] Health and Wellness Day 2026',
            'excerpt'   => 'Annual staff health screening and wellness programme.',
            'date'      => haraka_dummy_date( 30 ),
            'time'      => '8:00 AM - 4:00 PM',
            'venue'     => 'IIUM Medical Centre, Kuantan',
            'organiser' => 'IIUM Medical Specialist Centre',
            'audience'  => 'All Staff',
            'capacity'  => '250',
        ),
        array(
            'title'     => '[DUMMY] Graduate Recruitment Fair 2026',
            'excerpt'   => 'Annual recruitment fair for fresh graduates across all divisions.',
            'date'      => haraka_dummy_date( 45 ),
            'time'      => '9:00 AM - 5:00 PM',
            'venue'     => 'Main Hall, IIUM Gombak',
            'organiser' => 'Human Resource Division',
            'audience'  => 'Fresh Graduates',
            'capacity'  => '600',
        ),
        array(
            'title'     => '[DUMMY] IIUM Holdings Innovation Summit',
            'excerpt'   => 'Annual innovation summit showcasing projects from all subsidiaries.',
            'date'      => haraka_dummy_date( 60 ),
            'time'      => '8:30 AM - 5:30 PM',
            'venue'     => 'IIUM Convention Centre, Gombak',
            'organiser' => 'ICI — IIUM Consultancy & Innovation',
            'audience'  => 'All Staff and External Partners',
            'capacity'  => '350',
        ),
    );

    foreach ( $events as $data ) {
        $post_id = wp_insert_post( array(
            'post_title'   => $data['title'],
            'post_excerpt' => $data['excerpt'],
            'post_status'  => 'publish',
            'post_type'    => 'hrk_event',
        ) );

        if ( is_wp_error( $post_id ) ) continue;

        update_post_meta( $post_id, '_haraka_dummy',        1 );
        update_post_meta( $post_id, 'event_date',           $data['date'] );
        update_post_meta( $post_id, 'event_time',           $data['time'] );
        update_post_meta( $post_id, 'event_venue',          $data['venue'] );
        update_post_meta( $post_id, 'event_organiser',      $data['organiser'] );
        update_post_meta( $post_id, 'event_audience',       $data['audience'] );
        update_post_meta( $post_id, 'event_capacity',       $data['capacity'] );
        update_post_meta( $post_id, 'event_contact_name',   'Ahmad Farid bin Zulkifli' );
        update_post_meta( $post_id, 'event_contact_dept',   'Corporate Affairs' );
        update_post_meta( $post_id, 'event_contact_email',  'events@iiumholdings.com.my' );
        update_post_meta( $post_id, 'event_contact_phone',  '+603-6196 4000' );
        update_post_meta( $post_id, 'event_vips',           wp_json_encode( array(
            array( 'name' => 'YBhg. Dato Dr. Ahmad Razi', 'title' => 'Chairman, IIUM Holdings', 'role' => 'Guest of Honour' ),
            array( 'name' => 'Encik Ismet Fitri',         'title' => 'CEO, IIUM Holdings',     'role' => 'Speaker' ),
        ) ) );
        update_post_meta( $post_id, 'event_itinerary', wp_json_encode( array(
            array( 'time' => '8:00 AM', 'activity' => 'Registration and Arrival',  'pic' => 'Protocol Unit' ),
            array( 'time' => '9:00 AM', 'activity' => 'Opening Remarks',           'pic' => 'Corporate Affairs' ),
            array( 'time' => '10:00 AM','activity' => 'Main Programme',            'pic' => 'Event Committee' ),
            array( 'time' => '12:00 PM','activity' => 'Closing and Networking',    'pic' => 'Protocol Unit' ),
        ) ) );
    }
}

// ── Seed dummy tenders ────────────────────────────────────────────────────────
function haraka_seed_dummy_tenders() {

    $tenders = array(

        // 5 closed tenders
        array(
            'title'      => '[DUMMY] Supply of Office Furniture — HQ Renovation',
            'excerpt'    => 'Supply and delivery of office furniture for IIUM Holdings HQ renovation project.',
            'ref'        => 'IIUMH/TDR/2025/001',
            'issuer'     => 'IIUM Holdings Sdn Bhd',
            'category'   => 'Goods',
            'location'   => 'Gombak, Selangor',
            'close_date' => haraka_dummy_date( -90 ),
            'close_time' => '4:00 PM',
            'fee'        => 'RM 50.00',
            'validity'   => '90 days',
            'method'     => 'Physical Submission',
        ),
        array(
            'title'      => '[DUMMY] Provision of Security Services — IIUM Schools',
            'excerpt'    => 'Provision of professional security guard services for all IIUM Schools campuses.',
            'ref'        => 'IIUMS/TDR/2025/002',
            'issuer'     => 'IIUM Schools Sdn Bhd',
            'category'   => 'Services',
            'location'   => 'Multiple Locations',
            'close_date' => haraka_dummy_date( -60 ),
            'close_time' => '4:00 PM',
            'fee'        => 'RM 100.00',
            'validity'   => '120 days',
            'method'     => 'Physical Submission',
        ),
        array(
            'title'      => '[DUMMY] Landscaping and Maintenance Works — IIUM Educare',
            'excerpt'    => 'Landscaping, grass cutting and maintenance works for IIUM Educare premises.',
            'ref'        => 'IIUME/TDR/2025/003',
            'issuer'     => 'IIUM Educare Sdn Bhd',
            'category'   => 'Construction',
            'location'   => 'Gombak, Selangor',
            'close_date' => haraka_dummy_date( -45 ),
            'close_time' => '12:00 PM',
            'fee'        => 'RM 50.00',
            'validity'   => '90 days',
            'method'     => 'Physical Submission',
        ),
        array(
            'title'      => '[DUMMY] Supply of ICT Equipment — Batch 1',
            'excerpt'    => 'Supply, delivery and installation of ICT equipment including laptops and network hardware.',
            'ref'        => 'IIUMH/TDR/2025/004',
            'issuer'     => 'IIUM Holdings Sdn Bhd',
            'category'   => 'Goods',
            'location'   => 'Gombak, Selangor',
            'close_date' => haraka_dummy_date( -30 ),
            'close_time' => '4:00 PM',
            'fee'        => 'RM 200.00',
            'validity'   => '120 days',
            'method'     => 'Physical Submission',
        ),
        array(
            'title'      => '[DUMMY] Consultancy for RISE2030 Implementation Review',
            'excerpt'    => 'Appointment of consultancy firm to review and assess RISE2030 KPI implementation.',
            'ref'        => 'ICI/TDR/2025/005',
            'issuer'     => 'ICI — IIUM Consultancy & Innovation',
            'category'   => 'Consultancy',
            'location'   => 'Gombak, Selangor',
            'close_date' => haraka_dummy_date( -14 ),
            'close_time' => '4:00 PM',
            'fee'        => 'RM 300.00',
            'validity'   => '180 days',
            'method'     => 'Physical Submission',
        ),

        // 5 open tenders
        array(
            'title'      => '[DUMMY] Supply and Installation of CCTV System',
            'excerpt'    => 'Supply, delivery and installation of CCTV surveillance system for IIUM Holdings HQ.',
            'ref'        => 'IIUMH/TDR/2026/006',
            'issuer'     => 'IIUM Holdings Sdn Bhd',
            'category'   => 'Goods',
            'location'   => 'Gombak, Selangor',
            'close_date' => haraka_dummy_date( 30 ),
            'close_time' => '4:00 PM',
            'fee'        => 'RM 100.00',
            'validity'   => '90 days',
            'method'     => 'Physical Submission',
        ),
        array(
            'title'      => '[DUMMY] Cleaning Services — IIUM Medical Centre',
            'excerpt'    => 'Provision of professional cleaning and housekeeping services for IIUM Medical Centre.',
            'ref'        => 'IUMSC/TDR/2026/007',
            'issuer'     => 'IIUM Medical Specialist Centre',
            'category'   => 'Services',
            'location'   => 'Kuantan, Pahang',
            'close_date' => haraka_dummy_date( 45 ),
            'close_time' => '4:00 PM',
            'fee'        => 'RM 50.00',
            'validity'   => '120 days',
            'method'     => 'Physical Submission',
        ),
        array(
            'title'      => '[DUMMY] Construction of New Block — Setiabudi School',
            'excerpt'    => 'Construction and completion of new academic block at Setiabudi School Gombak.',
            'ref'        => 'SBS/TDR/2026/008',
            'issuer'     => 'Setiabudi Schools Sdn Bhd',
            'category'   => 'Construction',
            'location'   => 'Gombak, Selangor',
            'close_date' => haraka_dummy_date( 60 ),
            'close_time' => '4:00 PM',
            'fee'        => 'RM 500.00',
            'validity'   => '180 days',
            'method'     => 'Physical Submission',
        ),
        array(
            'title'      => '[DUMMY] Supply of Laboratory Equipment — IIUM Higher Education',
            'excerpt'    => 'Supply and delivery of science laboratory equipment for IIUM Higher Education facilities.',
            'ref'        => 'IUMHE/TDR/2026/009',
            'issuer'     => 'IIUM Higher Education Sdn Bhd',
            'category'   => 'Goods',
            'location'   => 'Gombak, Selangor',
            'close_date' => haraka_dummy_date( 75 ),
            'close_time' => '4:00 PM',
            'fee'        => 'RM 200.00',
            'validity'   => '90 days',
            'method'     => 'Physical Submission',
        ),
        array(
            'title'      => '[DUMMY] Property Valuation Services — IIUM Properties',
            'excerpt'    => 'Appointment of registered valuer for property valuation services across all IIUM Properties assets.',
            'ref'        => 'IUMP/TDR/2026/010',
            'issuer'     => 'IIUM Properties Sdn Bhd',
            'category'   => 'Consultancy',
            'location'   => 'Kuala Lumpur',
            'close_date' => haraka_dummy_date( 90 ),
            'close_time' => '4:00 PM',
            'fee'        => 'RM 150.00',
            'validity'   => '120 days',
            'method'     => 'Physical Submission',
        ),
    );

    foreach ( $tenders as $data ) {
        $post_id = wp_insert_post( array(
            'post_title'   => $data['title'],
            'post_excerpt' => $data['excerpt'],
            'post_status'  => 'publish',
            'post_type'    => 'hrk_tender',
        ) );

        if ( is_wp_error( $post_id ) ) continue;

        update_post_meta( $post_id, '_haraka_dummy',              1 );
        update_post_meta( $post_id, 'tender_ref',                 $data['ref'] );
        update_post_meta( $post_id, 'tender_issuer',              $data['issuer'] );
        update_post_meta( $post_id, 'tender_category',            $data['category'] );
        update_post_meta( $post_id, 'tender_location',            $data['location'] );
        update_post_meta( $post_id, 'tender_close_date',          $data['close_date'] );
        update_post_meta( $post_id, 'tender_close_time',          $data['close_time'] );
        update_post_meta( $post_id, 'tender_fee',                 $data['fee'] );
        update_post_meta( $post_id, 'tender_validity',            $data['validity'] );
        update_post_meta( $post_id, 'tender_submission_method',   $data['method'] );
        update_post_meta( $post_id, 'tender_submission_address',  'Procurement Unit, IIUM Holdings Sdn Bhd, Jalan Gombak, 53100 Kuala Lumpur' );
        update_post_meta( $post_id, 'tender_contact_name',        'Puan Siti Hajar binti Mohd Noor' );
        update_post_meta( $post_id, 'tender_contact_email',       'procurement@iiumholdings.com.my' );
        update_post_meta( $post_id, 'tender_contact_phone',       '+603-6196 4100' );
    }
}

// ── Seed dummy careers ────────────────────────────────────────────────────────
function haraka_seed_dummy_careers() {

    // Get first available company or create a dummy one
    $companies = get_posts( array(
        'post_type'      => 'hrk_company',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ) );

    if ( empty( $companies ) ) {
        $company_id = wp_insert_post( array(
            'post_title'  => '[DUMMY] IIUM Holdings Sdn Bhd',
            'post_status' => 'publish',
            'post_type'   => 'hrk_company',
        ) );
        if ( ! is_wp_error( $company_id ) ) {
            update_post_meta( $company_id, '_haraka_dummy',      1 );
            update_post_meta( $company_id, 'company_short_name', 'IIUMH' );
            update_post_meta( $company_id, 'company_full_name',  'IIUM Holdings Sdn Bhd' );
            update_post_meta( $company_id, 'company_website',    'https://iiumholdings.com.my' );
            update_post_meta( $company_id, 'company_description','The commercial arm of the International Islamic University Malaysia.' );
        }
    } else {
        $company_id = $companies[0];
    }

    $careers = array(

        // 5 closed positions
        array(
            'title'      => '[DUMMY] Finance Executive',
            'excerpt'    => 'Responsible for financial reporting, budgeting and treasury management.',
            'department' => 'Finance',
            'location'   => 'Gombak, Selangor',
            'type'       => 'Full Time',
            'salary'     => 'RM 3,500 - RM 5,000',
            'close_date' => haraka_dummy_date( -60 ),
        ),
        array(
            'title'      => '[DUMMY] ICT Support Specialist',
            'excerpt'    => 'Provide technical support and maintain ICT infrastructure across all subsidiaries.',
            'department' => 'ICT',
            'location'   => 'Gombak, Selangor',
            'type'       => 'Full Time',
            'salary'     => 'RM 3,000 - RM 4,500',
            'close_date' => haraka_dummy_date( -45 ),
        ),
        array(
            'title'      => '[DUMMY] Marketing Intern',
            'excerpt'    => 'Assist the Marketing team with digital campaigns, content creation and brand management.',
            'department' => 'Marketing',
            'location'   => 'Gombak, Selangor',
            'type'       => 'Internship',
            'salary'     => 'RM 800 allowance',
            'close_date' => haraka_dummy_date( -30 ),
        ),
        array(
            'title'      => '[DUMMY] Legal Counsel',
            'excerpt'    => 'Provide legal advice on corporate matters, contracts, and compliance.',
            'department' => 'Legal',
            'location'   => 'Kuala Lumpur',
            'type'       => 'Full Time',
            'salary'     => 'RM 6,000 - RM 9,000',
            'close_date' => haraka_dummy_date( -20 ),
        ),
        array(
            'title'      => '[DUMMY] Procurement Officer',
            'excerpt'    => 'Manage procurement processes, vendor relations and contract administration.',
            'department' => 'Procurement',
            'location'   => 'Gombak, Selangor',
            'type'       => 'Contract',
            'salary'     => 'RM 4,000 - RM 5,500',
            'close_date' => haraka_dummy_date( -10 ),
        ),

        // 5 open positions
        array(
            'title'      => '[DUMMY] Senior Human Resource Manager',
            'excerpt'    => 'Lead HR strategy, talent acquisition, and employee development across all subsidiaries.',
            'department' => 'Human Resource',
            'location'   => 'Gombak, Selangor',
            'type'       => 'Full Time',
            'salary'     => 'RM 7,000 - RM 10,000',
            'close_date' => haraka_dummy_date( 30 ),
        ),
        array(
            'title'      => '[DUMMY] Operations Executive',
            'excerpt'    => 'Oversee day-to-day operations and coordinate between subsidiaries.',
            'department' => 'Operations',
            'location'   => 'Gombak, Selangor',
            'type'       => 'Full Time',
            'salary'     => 'RM 3,500 - RM 5,000',
            'close_date' => haraka_dummy_date( 45 ),
        ),
        array(
            'title'      => '[DUMMY] Administration Assistant',
            'excerpt'    => 'Provide administrative support to the Corporate Affairs division.',
            'department' => 'Administration',
            'location'   => 'Gombak, Selangor',
            'type'       => 'Part Time',
            'salary'     => 'RM 1,800 - RM 2,500',
            'close_date' => haraka_dummy_date( 60 ),
        ),
        array(
            'title'      => '[DUMMY] Finance Manager',
            'excerpt'    => 'Lead the finance team and oversee group financial performance and reporting.',
            'department' => 'Finance',
            'location'   => 'Kuala Lumpur',
            'type'       => 'Full Time',
            'salary'     => 'RM 8,000 - RM 12,000',
            'close_date' => haraka_dummy_date( 75 ),
        ),
        array(
            'title'      => '[DUMMY] ICT Project Manager',
            'excerpt'    => 'Manage ICT transformation projects as part of the RISE2030 digital agenda.',
            'department' => 'ICT',
            'location'   => 'Gombak, Selangor',
            'type'       => 'Contract',
            'salary'     => 'RM 6,000 - RM 8,000',
            'close_date' => haraka_dummy_date( 90 ),
        ),
    );

    foreach ( $careers as $data ) {
        $post_id = wp_insert_post( array(
            'post_title'   => $data['title'],
            'post_excerpt' => $data['excerpt'],
            'post_status'  => 'publish',
            'post_type'    => 'hrk_career',
        ) );

        if ( is_wp_error( $post_id ) ) continue;

        update_post_meta( $post_id, '_haraka_dummy',       1 );
        update_post_meta( $post_id, 'career_company_id',   $company_id );
        update_post_meta( $post_id, 'career_department',   $data['department'] );
        update_post_meta( $post_id, 'career_location',     $data['location'] );
        update_post_meta( $post_id, 'career_type',         $data['type'] );
        update_post_meta( $post_id, 'career_salary',       $data['salary'] );
        update_post_meta( $post_id, 'career_close_date',   $data['close_date'] );
        update_post_meta( $post_id, 'career_apply_url',    'https://iiumholdings.com.my/careers/apply' );
        update_post_meta( $post_id, 'career_contact_name', 'Puan Nurul Ain binti Hamzah' );
        update_post_meta( $post_id, 'career_contact_email','hr@iiumholdings.com.my' );
        update_post_meta( $post_id, 'career_contact_phone','+603-6196 4200' );
    }
}

// ── Clear all dummy data ──────────────────────────────────────────────────────
function haraka_clear_dummy_data() {
    global $wpdb;

    $post_types = array( 'hrk_event', 'hrk_tender', 'hrk_career', 'hrk_company' );

    foreach ( $post_types as $post_type ) {
        $ids = get_posts( array(
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'   => '_haraka_dummy',
                    'value' => '1',
                ),
            ),
        ) );

        foreach ( $ids as $id ) {
            wp_delete_post( $id, true );
        }
    }
}

// ── AJAX: seed all dummy data ─────────────────────────────────────────────────
function haraka_ajax_seed_dummy_data() {
    check_ajax_referer( 'haraka_dummy_data', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    if ( ! haraka_dummy_data_enabled() ) {
        wp_send_json_error( array( 'message' => 'Dummy data is not enabled.' ) );
    }

    haraka_seed_dummy_events();
    haraka_seed_dummy_tenders();
    haraka_seed_dummy_careers();

    wp_send_json_success( array(
        'message' => 'Dummy data seeded — 10 events, 10 tenders, 10 careers.',
    ) );
}
add_action( 'wp_ajax_haraka_seed_dummy_data', 'haraka_ajax_seed_dummy_data' );

// ── AJAX: clear all dummy data ────────────────────────────────────────────────
function haraka_ajax_clear_dummy_data() {
    check_ajax_referer( 'haraka_dummy_data', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    if ( ! haraka_dummy_data_enabled() ) {
        wp_send_json_error( array( 'message' => 'Dummy data is not enabled.' ) );
    }

    haraka_clear_dummy_data();

    wp_send_json_success( array(
        'message' => 'All dummy data cleared successfully.',
    ) );
}
add_action( 'wp_ajax_haraka_clear_dummy_data', 'haraka_ajax_clear_dummy_data' );

// ── AJAX: seed dummy error log entries (dev only) ─────────────────────────────
function haraka_ajax_seed_dummy_errors() {
    check_ajax_referer( 'haraka_dummy_data', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorised.' ), 403 );
    }

    if ( ! haraka_dummy_data_enabled() ) {
        wp_send_json_error( array( 'message' => 'Dummy data is not enabled.' ) );
    }

    global $wpdb;
    $table = haraka_error_log_table();

    $dummy_errors = array(
        array( 'level' => 'fatal',     'message' => 'Maximum execution time of 30 seconds exceeded',              'source' => 'includes/tenders/shortcode-list.php',     'file' => HARAKA_PLUGIN_DIR . 'includes/tenders/shortcode-list.php',     'function' => 'haraka_tenders_list_shortcode()', 'line' => 45,  'resolved' => 0, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-6 days' ) ) ),
        array( 'level' => 'error',     'message' => 'Call to undefined function haraka_get_tender_meta()',        'source' => 'includes/tenders/template-single.php',    'file' => HARAKA_PLUGIN_DIR . 'includes/tenders/template-single.php',    'function' => 'haraka_render_tender_single()',   'line' => 112, 'resolved' => 0, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-6 days' ) ) ),
        array( 'level' => 'warning',   'message' => 'Undefined index: tender_close_time',                         'source' => 'includes/tenders/meta-boxes.php',         'file' => HARAKA_PLUGIN_DIR . 'includes/tenders/meta-boxes.php',         'function' => 'haraka_tender_meta_box_html()',   'line' => 78,  'resolved' => 1, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-5 days' ) ) ),
        array( 'level' => 'notice',    'message' => 'Undefined variable: event_cal_url',                          'source' => 'includes/events/template-single.php',     'file' => HARAKA_PLUGIN_DIR . 'includes/events/template-single.php',     'function' => 'haraka_render_event_single()',    'line' => 203, 'resolved' => 1, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-5 days' ) ) ),
        array( 'level' => 'exception', 'message' => 'InvalidArgumentException: Invalid date format provided',     'source' => 'includes/admin/cron.php',                 'file' => HARAKA_PLUGIN_DIR . 'includes/admin/cron.php',                 'function' => 'haraka_daily_tender_check()',     'line' => 34,  'resolved' => 0, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-4 days' ) ) ),
        array( 'level' => 'error',     'message' => 'wpdb::prepare was called incorrectly — query does not contain placeholders', 'source' => 'includes/careers/shortcode-list.php', 'file' => HARAKA_PLUGIN_DIR . 'includes/careers/shortcode-list.php', 'function' => 'haraka_careers_list_shortcode()', 'line' => 67, 'resolved' => 0, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-3 days' ) ) ),
        array( 'level' => 'warning',   'message' => 'array_map() expects parameter 2 to be array, null given',   'source' => 'includes/admin/settings-fields.php',      'file' => HARAKA_PLUGIN_DIR . 'includes/admin/settings-fields.php',      'function' => 'haraka_render_tenders_settings()','line' => 91,  'resolved' => 0, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-3 days' ) ) ),
        array( 'level' => 'fatal',     'message' => 'Allowed memory size of 268435456 bytes exhausted',           'source' => 'includes/events/shortcode-list.php',      'file' => HARAKA_PLUGIN_DIR . 'includes/events/shortcode-list.php',      'function' => 'haraka_events_list_shortcode()', 'line' => 89,  'resolved' => 0, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-2 days' ) ) ),
        array( 'level' => 'notice',    'message' => 'Trying to get property of non-object',                       'source' => 'includes/careers/template-single.php',    'file' => HARAKA_PLUGIN_DIR . 'includes/careers/template-single.php',    'function' => 'haraka_render_career_single()',   'line' => 145, 'resolved' => 1, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ) ),
        array( 'level' => 'error',     'message' => 'Cannot redeclare haraka_format_tender_date()',               'source' => 'includes/tenders/shortcode-preview.php',  'file' => HARAKA_PLUGIN_DIR . 'includes/tenders/shortcode-preview.php',  'function' => 'haraka_tenders_preview_shortcode()','line' => 22, 'resolved' => 0, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ) ),
        array( 'level' => 'warning',   'message' => 'Division by zero in dashboard widget count',                 'source' => 'includes/admin/dashboard-widget.php',     'file' => HARAKA_PLUGIN_DIR . 'includes/admin/dashboard-widget.php',     'function' => 'haraka_get_dashboard_counts()',   'line' => 56,  'resolved' => 0, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-4 hours' ) ) ),
        array( 'level' => 'exception', 'message' => 'RuntimeException: Failed to write transient cache',          'source' => 'includes/admin/dashboard-widget.php',     'file' => HARAKA_PLUGIN_DIR . 'includes/admin/dashboard-widget.php',     'function' => 'haraka_get_dashboard_counts()',   'line' => 78,  'resolved' => 0, 'created_at' => date( 'Y-m-d H:i:s', strtotime( '-2 hours' ) ) ),
    );

    $context = wp_json_encode( array( 'url' => '/wp-admin/', 'method' => 'GET', 'user_id' => get_current_user_id(), 'wp_version' => get_bloginfo('version'), 'php_version' => PHP_VERSION ) );
    $trace   = wp_json_encode( array() );

    foreach ( $dummy_errors as $e ) {
        $wpdb->insert( $table, array(
            'created_at' => $e['created_at'],
            'level'      => $e['level'],
            'source'     => $e['source'],
            'message'    => $e['message'],
            'file'       => $e['file'],
            'function'   => $e['function'],
            'line'       => $e['line'],
            'trace'      => $trace,
            'context'    => $context,
            'resolved'   => $e['resolved'],
        ), array( '%s','%s','%s','%s','%s','%s','%d','%s','%s','%d' ) );
    }

    wp_send_json_success( array( 'message' => '12 dummy error log entries seeded across 7 days.' ) );
}
add_action( 'wp_ajax_haraka_seed_dummy_errors', 'haraka_ajax_seed_dummy_errors' );