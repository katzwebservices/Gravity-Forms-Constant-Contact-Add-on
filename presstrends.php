<?php

/**
* PressTrends Plugin API
*/
    function presstrends_GravityFormsConstantContact_plugin() {

        // PressTrends Account API Key
        $api_key = 'mc9ossbhdx30z6l7x4dnchacxpzhp6e054t4';
        $auth    = 'qv13a94w4nzqn7ciregsmchd0kcham5sz';

        // Start of Metrics
        global $wpdb;
        $data = get_transient( 'presstrends_cache_data' );
        if ( !$data || $data == '' ) {
            $api_base = 'http://api.presstrends.io/index.php/api/pluginsites/update/auth/';
            $url      = $api_base . $auth . '/api/' . $api_key . '/';

            $count_posts    = wp_count_posts();
            $count_pages    = wp_count_posts( 'page' );
            $comments_count = wp_count_comments();

            // wp_get_theme was introduced in 3.4, for compatibility with older versions, let's do a workaround for now.
            if ( function_exists( 'wp_get_theme' ) ) {
                $theme_data = wp_get_theme();
                $theme_name = urlencode( $theme_data->Name );
            } else {
                $theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
                $theme_name = $theme_data['Name'];
            }

            $plugin_name = '&';
            foreach ( get_plugins() as $plugin_info ) {
                if(strlen($plugin_name) > 3000) { continue; } // Too long!
                $plugin_name .= $plugin_info['Name'] . '&';
            }
            // CHANGE __FILE__ PATH IF LOCATED OUTSIDE MAIN PLUGIN FILE
            $plugin_data         = get_plugin_data( GFConstantContact::get_file() );
            $posts_with_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='post' AND comment_count > 0" );
            $data                = array(
                'url'             => stripslashes( str_replace( array( 'http://', '/', ':' ), '', site_url() ) ),
                'posts'           => $count_posts->publish,
                'pages'           => $count_pages->publish,
                'comments'        => $comments_count->total_comments,
                'approved'        => $comments_count->approved,
                'spam'            => $comments_count->spam,
                'pingbacks'       => $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback'" ),
                'post_conversion' => ( $count_posts->publish > 0 && $posts_with_comments > 0 ) ? number_format( ( $posts_with_comments / $count_posts->publish ) * 100, 0, '.', '' ) : 0,
                'theme_version'   => $plugin_data['Version'],
                'theme_name'      => $theme_name,
                'site_name'       => str_replace( ' ', '', get_bloginfo( 'name' ) ),
                'plugins'         => count( get_option( 'active_plugins' ) ),
                'plugin'          => urlencode( $plugin_name ),
                'wpversion'       => get_bloginfo( 'version' ),
            );

            foreach ( $data as $k => $v ) {
                $url .= $k . '/' . $v . '/';
            }
            $remote = @wp_remote_get( $url );

            set_transient( 'presstrends_cache_data', $data, 60 * 60 * 24 );
        }
    }

    // Setup Events
    function presstrends_track_event_GravityFormsConstantContact($event_name) {
        // PressTrends Account API Key & Theme/Plugin Unique Auth Code
        $api_key    = 'mc9ossbhdx30z6l7x4dnchacxpzhp6e054t4';
        $auth       = 'qv13a94w4nzqn7ciregsmchd0kcham5sz';
        $api_base   = 'http://api.presstrends.io/index.php/api/events/track/auth/';
        $api_string = $api_base . $auth . '/api/' . $api_key . '/';
        $event_string   = $api_string . 'name/' . urlencode($event_name) . '/';
        @wp_remote_get( $event_string );
    }

add_action('plugins_loaded', 'add_presstrends_GravityFormsConstantContact');
function add_presstrends_GravityFormsConstantContact() {
    // PressTrends WordPress Action
    add_action('admin_init', 'presstrends_GravityFormsConstantContact_plugin');
    add_action('presstrends_event_gfcc', 'presstrends_track_event_GravityFormsConstantContact', 1, 1 );
}
