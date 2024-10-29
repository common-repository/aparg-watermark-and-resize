<?php
defined('ABSPATH') or die('No script kiddies please!');
if (!defined('WP_UNINSTALL_PLUGIN')){
    exit();
}

if (function_exists( 'is_multisite' ) && is_multisite() ){
    global $wpdb;
    $old_blog =  $wpdb->blogid;
    $blogids =  $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
    foreach ( $blogids as $blog_id ) {
        switch_to_blog($blog_id);
        apwr_delete_all_options();
    }
    switch_to_blog( $old_blog );
}
else{
    apwr_delete_all_options();
}

function apwr_delete_all_options(){
    delete_option('apwr_max_width');
    delete_option('apwr_max_height');
    delete_option('apwr_img_quality');
    delete_option('apwr_resize_enable');
    delete_option('apwr_watermark_position');
    delete_option('apwr_watermark_percentage');
    delete_option('apwr_watermark_margin');
    delete_option('apwr_watermark_enable');
    delete_option('apwr_logo');
    delete_option('apwr_time');
    delete_option('apwr_status');
    delete_option('apwr_interlace_enable');
}
