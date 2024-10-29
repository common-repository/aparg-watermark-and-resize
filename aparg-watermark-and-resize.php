<?php
/*
  Plugin Name: Aparg Watermark and Resize
  Description: Aparg Watermark and Resize is design to automatically resize and add watermark to images as they are uploaded to WordPress media library. Also you can do both actions to all existing images.
  Version: 1.2
  Author: Aparg
  Author URI:  http://aparg.com/
  License: GPL2
  Text Domain: aparg-watermark-and-resize
  Domain Path: /languages/

  This plugin is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as active by
  the Free Software Foundation, either version 2 of the License, or
  any later version.
  
  This plugin is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.
  
  You should have received a copy of the GNU General Public License
  along with this plugin. If not, see https://wordpress.org/about/gpl/.
 */

defined('ABSPATH') or die('No script kiddies please!');
include 'aparg-wtm-resize-settings-page.php';

$apwr_memory_limit = 512;
$apwr_time_limit = 60;

/**
 * Load text domain
 */
add_action('init', 'apwr_text_domain');

function apwr_text_domain() {
    load_plugin_textdomain('aparg-watermark-and-resize', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR , basename(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR);
}

/**
 * Including JS and CSS to Admin Side
 */
add_action('admin_enqueue_scripts', 'apwr_admin_enqueue');

function apwr_admin_enqueue($hook) {
    if ("toplevel_page_watermark-and-resize" != $hook) {
        return;
    }
    wp_register_script('aparg_img_opt_js', plugins_url('js'. DIRECTORY_SEPARATOR .'aparg-wtm-resize.js', __FILE__), array('jquery'));
    wp_enqueue_script('aparg_img_opt_js');
    wp_register_style('aparg_img_opt_css', plugins_url('css'. DIRECTORY_SEPARATOR .'aparg-wtm-resize.css', __FILE__));
    wp_enqueue_style('aparg_img_opt_css');
    $apwr_gd_enable = function_exists('gd_info') ? true : false;
    $nonce = sanitize_text_field(wp_create_nonce('aparg-wtm-resize'));
    $apwr_localize_array = array(
        'url' => admin_url('admin-ajax.php'),
        'wtmEnable' => $apwr_gd_enable,
        'wtmEnableMessage' => __('Please make sure to enable your GD library', 'aparg-watermark-and-resize'),
        'errPathList' => __('Failed to proceed', 'aparg-watermark-and-resize'),
        'nonce' => $nonce,
    );
    wp_localize_script('aparg_img_opt_js', 'apwr_optimizer', $apwr_localize_array);
}

/**
 *  resize upload picture 
 */
add_action('add_attachment', 'apwr_resize_attachment');

function apwr_resize_attachment($attachment_id, $width = null, $height = null, $quality = null, $canResize = null, $interlace = null){
    $fullsize_path = get_attached_file($attachment_id);
    if(!file_exists($fullsize_path))
        return false;
    if (!$canResize)
        $canResize = get_option('apwr_resize_enable');
    if ($canResize == 1) {
        global $apwr_memory_limit;
        global $apwr_time_limit;
        if(intval(ini_get('memory_limit')) < $apwr_memory_limit)
            ini_set('memory_limit', $apwr_memory_limit.'M');
        if(intval(ini_get('max_execution_time') < $apwr_time_limit))
            set_time_limit($apwr_time_limit);
        $state  = true;
        if (!$width && !$height && !$quality && !$interlace) {
            $width = get_option('apwr_max_width') ? get_option('apwr_max_width') : null;
            $height = get_option('apwr_max_height') ? get_option('apwr_max_height') : null;
            $quality = get_option('apwr_img_quality') ? get_option('apwr_img_quality') : 90;
            $interlace = intval(get_option('apwr_interlace_enable'));
        }
        if(!apwr_resize_attachment_by_path($fullsize_path, $fullsize_path, $width, $height, $quality, $interlace))
            $state = false;
        list($newwidth, $newheight) = getimagesize($fullsize_path);
        $image_meta = get_post_meta($attachment_id, '_wp_attachment_metadata', true);
        $image_meta['height'] = intval($newheight);
        $image_meta['width'] = intval($newwidth);
        if(!$state)
            return $state;
        if (!update_post_meta($attachment_id, '_wp_attachment_metadata', $image_meta))
            $state = false;
        return $state;
    }    
}

/*
 *  function resize by path
 */
function apwr_resize_attachment_by_path($fullsize_path, $new_path, $width, $height, $quality, $interlace){
    $pathinfo = pathinfo($fullsize_path);
    $ext = strtolower($pathinfo['extension']);
    if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png') {
        $state  = true;
        list($w, $h) = getimagesize($fullsize_path);
        $r = $w / $h;
        $newwidth = $w;
        $newheight = $h;
        if($width == null && $h > $height){
            $newwidth = $height*$r;
            $newheight = $height;
        }
        if($height == null && $w > $width){
            $newheight = $width/$r;
            $newwidth = $width;
        }
        if($width && $height){
            if($w > $width || $h > $height){
                if ($width/$height > $r) {
                    $newwidth = $height*$r;
                    $newheight = $height;
                } else {
                    $newheight = $width/$r;
                    $newwidth = $width;
                }
            }
        }
        if($ext == 'jpg' || $ext == 'jpeg'){
            $img = imagecreatefromjpeg($fullsize_path);
            $newImg = imagecreatetruecolor($newwidth, $newheight);
            imageinterlace($newImg, $interlace);
            if(!imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newwidth, $newheight, $w, $h))
                $state = false;
            if(!imagejpeg($newImg, $new_path, $quality))
                $state = false;
        }
        if($ext == 'png'){
            $img = imagecreatefrompng($fullsize_path);
            $newImg = imagecreatetruecolor($newwidth, $newheight);
            imageinterlace($newImg, $interlace);
            imagealphablending($newImg , false);
            imagesavealpha($newImg , true);
            if(!imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newwidth, $newheight, $w, $h))
                $state = false;
            if(!imagepng($newImg, $new_path))
                $state = false;
        }
        imagedestroy($img);
        imagedestroy($newImg);
        return $state;
    }
}

/*
 *  adding watermark on fly
 */
add_filter('wp_generate_attachment_metadata', 'apwr_watermark_onfly', 10, 2);

function apwr_watermark_onfly($image_data, $attach_id) {
    $type = get_post_mime_type($attach_id);
    if ($type == 'image/jpeg' || $type == 'image/png') {
        if (get_option('apwr_watermark_enable') == 1) {
            $target = get_attached_file($attach_id);
            $logo_position = get_option('apwr_watermark_position');
            $logo_file = get_option('apwr_logo');
            $logo_path = $logo_file['file'];
            $wtm_prc = get_option('apwr_watermark_percentage');
            $quality = get_option('apwr_img_quality') ? get_option('apwr_img_quality') : 90;
            $wtm_margin = get_option('apwr_watermark_margin') != '' ? get_option('apwr_watermark_margin') : 1;
            $interlace = intval(get_option('apwr_interlace_enable'));
            if(!apwr_add_watermark($target, $logo_path, $logo_position, $wtm_prc, $quality, $wtm_margin, $interlace, $attach_id, $image_data))
                return false;
        }
    }
    return $image_data;
}

/*
 * Function for adding watermark 
 */

function apwr_add_watermark($target, $wtrmrk_file, $wtm_position, $wtm_prc, $quality, $wtm_margin, $interlace, $target_id = null, $target_data = null){
    if(!file_exists($target) || !file_exists($wtrmrk_file))
        return false;
    global $apwr_memory_limit;
    global $apwr_time_limit;
    if(intval(ini_get('memory_limit')) < $apwr_memory_limit)
        ini_set('memory_limit', $apwr_memory_limit.'M');
    if(intval(ini_get('max_execution_time') < $apwr_time_limit))
        set_time_limit($apwr_time_limit);
    $tareget_pathinfo = pathinfo($target);
    $tareget_ext = strtolower($tareget_pathinfo['extension']);
    $wtrmrk_pathinfo = pathinfo($wtrmrk_file);
    $wtrmrk_ext = strtolower($wtrmrk_pathinfo['extension']);
    $newcopyPath = $target;
    $state = true;
    list($w, $h) = getimagesize($target);
    $logowidth = ($w * $wtm_prc) / 100;
    $logoheight = ($h * $wtm_prc) / 100;
    $wtm_path = plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR  . 'temp' . DIRECTORY_SEPARATOR  . 'new-logo.' . $wtrmrk_ext; 
    if(!apwr_resize_attachment_by_path($wtrmrk_file, $wtm_path, $logowidth, $logoheight, 100, 0))
            $state = false;
    
    /*
     * Checkng target image 
     */
    
    if ($tareget_ext == 'jpg' || $tareget_ext == 'jpeg') {
        $img = imagecreatefromjpeg($target);
    } elseif ($tareget_ext == 'png') {
        
        $img = imagecreatefrompng($target);
        $newImg = imagecreatetruecolor($w, $h);
        imagealphablending($newImg , false);
        imagesavealpha($newImg , true);
        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $w, $h, $w, $h);
        imagepng($newImg, $target);
        imagedestroy($img);
        imagedestroy($newImg);
        
        $img = imagecreatefrompng($target);
        imagealphablending($img, true);
        imagesavealpha($img, true);
    }
    if (!$img)
        $state = false;
    
    /*
     * Checking watermark image
     */
    if ($wtrmrk_ext == 'jpg' || $wtrmrk_ext == 'jpeg') {
        $watermark = imagecreatefromjpeg($wtm_path);
    } elseif ($wtrmrk_ext == 'png') {
        $watermark = imagecreatefrompng($wtm_path);
        imagealphablending($watermark, false);
        imagesavealpha($watermark, false);
    }
    if (!$watermark)
        $state = false;
    $img_w = imagesx($img);
    $img_h = imagesy($img);
    $wtrmrk_w = imagesx($watermark);
    $wtrmrk_h = imagesy($watermark);
    switch ($wtm_position) {
        case 'Center':
            $dst_x = ($img_w / 2) - ($wtrmrk_w / 2);
            $dst_y = ($img_h / 2) - ($wtrmrk_h / 2);
            $imgcopy = imagecopy($img, $watermark, $dst_x, $dst_y, 0, 0, $wtrmrk_w, $wtrmrk_h);
            if (!$imgcopy)
                $state = false;
            break;
        case 'Top Right':
            $dst_x = (100-$wtm_margin)/100 * $img_w - $wtrmrk_w;
            $dst_y = $wtm_margin/100 * $img_h;
            $imgcopy = imagecopy($img, $watermark, $dst_x, $dst_y, 0, 0, $wtrmrk_w, $wtrmrk_h);
            if (!$imgcopy)
                $state = false;
            break;
        case 'Top Left':
            $dst_x = $wtm_margin/100 * $img_w;
            $dst_y = $wtm_margin/100 * $img_h;
            $imgcopy = imagecopy($img, $watermark, $dst_x, $dst_y, 0, 0, $wtrmrk_w, $wtrmrk_h);
            if (!$imgcopy)
                $state = false;
            break;
        case 'Bottom Left':
            $dst_x = $wtm_margin/100 * $img_w;
            $dst_y = (100-$wtm_margin)/100 * $img_h - $wtrmrk_h;
            $imgcopy = imagecopy($img, $watermark, $dst_x, $dst_y, 0, 0, $wtrmrk_w, $wtrmrk_h);
            if (!$imgcopy)
                $state = false;
            break;
        case 'Bottom Right':
            $dst_x = (100-$wtm_margin)/100 * $img_w - $wtrmrk_w;
            $dst_y = (100-$wtm_margin)/100 * $img_h - $wtrmrk_h;
            $imgcopy = imagecopy($img, $watermark, $dst_x, $dst_y, 0, 0, $wtrmrk_w, $wtrmrk_h);
            if (!$imgcopy)
                $state = false;
            break;
    }
    if ($tareget_ext == "jpg" || $tareget_ext == "jpeg") {
        imageinterlace($img, $interlace);
        imagejpeg($img, $newcopyPath, $quality);
    } elseif ($tareget_ext == "png") {
        imageinterlace($img, $interlace);
        imagepng($img, $newcopyPath);
    }
    imagedestroy($watermark);
    imagedestroy($img);
    unlink($wtm_path);
    if($target_id && $state){
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'];
        if(!empty($target_data)){
            if(isset($target_data['sizes'])){
                $target_sizes = $target_data['sizes'];
                if(!empty($target_sizes)){
                    foreach ($target_sizes as $target_size){
                        $target_size_path = $upload_path . DIRECTORY_SEPARATOR . $target_size['file'];
                        if(!apwr_add_watermark($target_size_path, $wtrmrk_file, $wtm_position, $wtm_prc, 100, $wtm_margin, 0)){
                            $state = false;
                            return $state;
                        }
                    }
                }
            }
        }
        $post_meta = unserialize(get_post_meta($target_id,'apwr_watermark',true));
        if(!$post_meta){
            $post_meta = array($wtm_position);
            if(!update_post_meta($target_id, 'apwr_watermark', sanitize_text_field(serialize($post_meta))))
                $state = false;
        }
        else {
            $post_meta[] = $wtm_position;
            if(!update_post_meta($target_id, 'apwr_watermark', sanitize_text_field(serialize($post_meta))))
                $state = false;
        }
    }
    return $state;
}

/*
 *  Ajax handler function for do actions
 */

add_action('wp_ajax_apwr_change_all_img', 'apwr_change_all_img');
function apwr_change_all_img() {
    $nonce = sanitize_text_field($_POST['nonce']);
    if ( ! wp_verify_nonce( $nonce, 'aparg-wtm-resize' ) )
        die();
    if(!current_user_can('manage_options')){
        echo 'error';
        die();
    }
    $limit = intval($_POST['apwrBulkOptions']['limit']);
    $last_done_id = intval($_POST['apwrBulkOptions']['lastDoneId']);
    $action = sanitize_text_field($_POST['apwrBulkOptions']['action']);
    $height = intval($_POST['apwrBulkOptions']['maxHeight']) != 0 ? intval($_POST['apwrBulkOptions']['maxHeight']) : null;
    $width = intval($_POST['apwrBulkOptions']['maxWidth']) != 0 ? intval($_POST['apwrBulkOptions']['maxWidth']) : null;
    $quality = intval($_POST['apwrBulkOptions']['imgQuality']) != 0 ? intval($_POST['apwrBulkOptions']['imgQuality']) : 90;
    $logo_position = sanitize_text_field($_POST['apwrBulkOptions']['watermarkPosition']);
    $logo_path = sanitize_text_field($_POST['apwrBulkOptions']['watermarkPath']);
    $wtm_precent = intval($_POST['apwrBulkOptions']['watermarkPercentage']);
    $wtm_margin = sanitize_text_field($_POST['apwrBulkOptions']['watermarkMargin']) != '' ? intval($_POST['apwrBulkOptions']['watermarkMargin']) : 1;
    $interlace = intval($_POST['apwrBulkOptions']['interlace']);
    global $wpdb;
    $meta_table = $wpdb->prefix . 'postmeta';
    $post_table = $wpdb->prefix . 'posts';
    $current = 0;
    $not_done = 0;
    $not_done_path = array();
    $result = array(
        'action' => $action,
        'limit' => $limit,
        'current' => 0,
        'total' => 0,
        'lastDoneId' => $last_done_id,
        'notDone' => 0,
        'notDonePath' => $not_done_path,
        'height' => $height,
        'width' => $width,
        'nonce' => $nonce
    );
    update_option('apwr_time', time());
    update_option('apwr_status', 'active');
    if($action){
        if ($action == 'resize&') {
            if($width == null){
                $query_total = "SELECT DISTINCT COUNT($post_table.ID) FROM $meta_table,$post_table WHERE "
                        . "(SUBSTRING(SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10))-1)>$height) "
                        . "AND meta_key = '_wp_attachment_metadata' AND $meta_table.post_id=$post_table.ID AND (post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%')  AND $post_table.ID>$last_done_id";
                $query = "SELECT DISTINCT $post_table.ID FROM $meta_table,$post_table WHERE "
                        . "(SUBSTRING(SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10))-1)>$height) "
                        . "AND meta_key = '_wp_attachment_metadata' AND $meta_table.post_id=$post_table.ID AND (post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id order by $post_table.ID LIMIT $limit";
            }
            if($height == null){
                $query_total = "SELECT DISTINCT COUNT($post_table.ID) FROM $meta_table,$post_table WHERE "
                        . "(SUBSTRING(SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9))-1)>$width)"
                        . "AND meta_key = '_wp_attachment_metadata' AND $meta_table.post_id=$post_table.ID AND (post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%')  AND $post_table.ID>$last_done_id";
                $query = "SELECT DISTINCT $post_table.ID FROM $meta_table,$post_table WHERE "
                        . "(SUBSTRING(SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9))-1)>$width)"
                        . "AND meta_key = '_wp_attachment_metadata' AND $meta_table.post_id=$post_table.ID AND (post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id order by $post_table.ID LIMIT $limit";
            }
            if($width && $height){
                $query_total = "SELECT DISTINCT COUNT($post_table.ID) FROM $meta_table,$post_table WHERE "
                        . "((SUBSTRING(SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9))-1)>$width)"
                        . " OR (SUBSTRING(SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10))-1)>$height)) "
                        . "AND meta_key = '_wp_attachment_metadata' AND $meta_table.post_id=$post_table.ID AND (post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%')  AND $post_table.ID>$last_done_id";
                $query = "SELECT DISTINCT $post_table.ID FROM $meta_table,$post_table WHERE "
                        . "((SUBSTRING(SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9))-1)>$width)"
                        . " OR (SUBSTRING(SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10))-1)>$height)) "
                        . "AND meta_key = '_wp_attachment_metadata' AND $meta_table.post_id=$post_table.ID AND (post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id order by $post_table.ID LIMIT $limit";
            }
        }
        elseif ($action == 'resize&watermark') {
            if($width == null){
                $query_total = "SELECT DISTINCT COUNT($post_table.ID) FROM $meta_table,$post_table WHERE "
                        . "(((SUBSTRING(SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10))-1)>$height) AND meta_key = '_wp_attachment_metadata') OR "
                        . "((( meta_key='_wp_attachment_metadata')AND post_id NOT IN (SELECT post_id FROM $meta_table WHERE meta_value LIKE '%$logo_position%' )))) AND $meta_table.post_id=$post_table.ID AND "
                        . "(post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id";
                $query = "SELECT DISTINCT $post_table.ID FROM $meta_table,$post_table WHERE "
                        . "(((SUBSTRING(SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10))-1)>$height) AND meta_key = '_wp_attachment_metadata') OR "
                        . "((( meta_key='_wp_attachment_metadata')AND post_id NOT IN (SELECT post_id FROM $meta_table WHERE meta_value LIKE '%$logo_position%' )))) AND $meta_table.post_id=$post_table.ID AND "
                        . "(post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id order by $post_table.ID LIMIT $limit";
            }
            if($height == null){
                $query_total = "SELECT DISTINCT COUNT($post_table.ID) FROM $meta_table,$post_table WHERE "
                        . "(((SUBSTRING(SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9))-1)>$width) "
                        . " AND meta_key = '_wp_attachment_metadata') OR "
                        . "((( meta_key='_wp_attachment_metadata')AND post_id NOT IN (SELECT post_id FROM $meta_table WHERE meta_value LIKE '%$logo_position%' )))) AND $meta_table.post_id=$post_table.ID AND "
                        . "(post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id";
                $query = "SELECT DISTINCT $post_table.ID FROM $meta_table,$post_table WHERE "
                        . "(((SUBSTRING(SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9))-1)>$width) "
                        . " AND meta_key = '_wp_attachment_metadata') OR "
                        . "((( meta_key='_wp_attachment_metadata')AND post_id NOT IN (SELECT post_id FROM $meta_table WHERE meta_value LIKE '%$logo_position%' )))) AND $meta_table.post_id=$post_table.ID AND "
                        . "(post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id order by $post_table.ID LIMIT $limit";
            }
            if($width && $height){
                $query_total = "SELECT DISTINCT COUNT($post_table.ID) FROM $meta_table,$post_table WHERE "
                        . "((((SUBSTRING(SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9))-1)>$width) OR "
                        . "(SUBSTRING(SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10))-1)>$height)) AND meta_key = '_wp_attachment_metadata') OR "
                        . "((( meta_key='_wp_attachment_metadata')AND post_id NOT IN (SELECT post_id FROM $meta_table WHERE meta_value LIKE '%$logo_position%' )))) AND $meta_table.post_id=$post_table.ID AND "
                        . "(post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id";
                $query = "SELECT DISTINCT $post_table.ID FROM $meta_table,$post_table WHERE "
                        . "((((SUBSTRING(SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('width' IN meta_value) + 9))-1)>$width) OR "
                        . "(SUBSTRING(SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10),1,POSITION(';' IN SUBSTRING(meta_value,POSITION('height' IN meta_value) + 10))-1)>$height)) AND meta_key = '_wp_attachment_metadata') OR "
                        . "((( meta_key='_wp_attachment_metadata')AND post_id NOT IN (SELECT post_id FROM $meta_table WHERE meta_value LIKE '%$logo_position%' )))) AND $meta_table.post_id=$post_table.ID AND "
                        . "(post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id order by $post_table.ID LIMIT $limit";
            }
        }
        elseif ($action == '&watermark') {
                $query_total = "SELECT  COUNT($post_table.ID) FROM $meta_table,$post_table WHERE "
                        . "(( meta_key='_wp_attachment_metadata')AND post_id NOT IN "
                        . "(SELECT post_id FROM $meta_table WHERE meta_value LIKE '%$logo_position%' )) AND "
                        . "$meta_table.post_id=$post_table.ID AND (post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id";

                $query = "SELECT DISTINCT $post_table.ID FROM $meta_table,$post_table WHERE "
                        . "(( meta_key='_wp_attachment_metadata')AND post_id NOT IN "
                        . "(SELECT post_id FROM $meta_table WHERE meta_value LIKE '%$logo_position%' )) AND "
                        . "$meta_table.post_id=$post_table.ID AND (post_mime_type LIKE '%image/png%' OR post_mime_type LIKE '%image/jpeg%') AND $post_table.ID>$last_done_id order by $post_table.ID LIMIT $limit";
        }
        $attachments = $wpdb->get_results($query);
        $array_total = $wpdb->get_results($query_total, ARRAY_N);
        $count_total = (int) $array_total[0][0];
        $result['total'] = $count_total;
        $state = true;
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $attachment_path = get_attached_file($attachment->ID);
                $data = get_post_meta($attachment->ID, '_wp_attachment_metadata', false);
                if ($action == 'resize&') {
                    if ((intval($data[0]['height']) > $height && $height != null) || (intval($data[0]['width']) > $width && $width != null)) {
                        if (!apwr_resize_attachment($attachment->ID, $width, $height, $quality, 1, $interlace))
                            $state = false;
                    }
                }
                if ($action == '&watermark') {
                    if(!apwr_add_watermark($attachment_path, $logo_path, $logo_position, $wtm_precent, $quality, $wtm_margin, $interlace, $attachment->ID, $data[0]))
                        $state = false;
                }
                if ($action == 'resize&watermark') {
                    if ((intval($data[0]['height']) > $height && $height != null) || (intval($data[0]['width']) > $width && $width != null)) {
                        if (!apwr_resize_attachment($attachment->ID, $width, $height, $quality, 1, $interlace))
                            $state = false;
                    }
                    $post_meta = unserialize(get_post_meta($attachment->ID,'apwr_watermark',true));
                    if(!$post_meta || !in_array($logo_position, $post_meta)){
                        $data = get_post_meta($attachment->ID, '_wp_attachment_metadata', false);
                        if(!apwr_add_watermark($attachment_path, $logo_path, $logo_position, $wtm_precent, $quality, $wtm_margin, $interlace, $attachment->ID, $data[0]))
                            $state = false;
                    }
                }
                $current++;
                $result['current'] = $current;
                $result['lastDoneId'] = $attachment->ID;
                if(!$state) {
                    $not_done_path[$not_done] = $attachment_path;
                    $not_done++;
                    $result['notDone'] = $not_done;
                    $result['notDonePath'] = $not_done_path;
                    $state = true;
                }
                if(intval($data[0]['height']) > 2000 || intval($data[0]['width']) > 2000)
                    break;
            }
        }
    }
    echo json_encode($result);
    die();
}

/*
 * ajax handler function for check status
 */
add_action('wp_ajax_apwr_check_status', 'apwr_check_status');
function apwr_check_status(){
    $nonce = sanitize_text_field($_POST['nonce']);
    if ( ! wp_verify_nonce( $nonce, 'aparg-wtm-resize' ) )
        die();
    $status = get_option('apwr_status');
    $time = get_option('apwr_time');
    $result = array(
        'canStart' => false
        );
    if(($status != 'active') || ((time() - $time) > 60)){
        $result['canStart'] = true;
    }
    echo json_encode($result);
    die();
}

/*
 * ajax handler function for clear status
 */
add_action('wp_ajax_apwr_clear_status', 'apwr_clear_status');
function apwr_clear_status() {
    $nonce = sanitize_text_field($_POST['nonce']);
    if ( ! wp_verify_nonce( $nonce, 'aparg-wtm-resize' ) )
            die();
    update_option('apwr_status', '');
    die();
}

/**
 * when site is multisite, after delete blogs delete logo 
 */
add_action( 'delete_blog', 'apwr_delete_logo');
function apwr_delete_logo($blog_id){
    $logo_path = plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR  . 'img';
    array_map('unlink', glob($logo_path . DIRECTORY_SEPARATOR . $blog_id .".*"));
}