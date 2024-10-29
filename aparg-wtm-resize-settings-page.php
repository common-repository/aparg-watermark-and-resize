<?php
defined('ABSPATH') or die('No script kiddies please!');

add_action("admin_menu", "apwr_add_menu_item");

/**
 * Adding admin sattings page
 */
function apwr_add_menu_item() {
    add_menu_page(__("Watermark and Resize", "aparg-watermark-and-resize"), __("Watermark and Resize", "aparg-watermark-and-resize"), "manage_options", "watermark-and-resize", "apwr_img_opt_settings_page", plugin_dir_url(__FILE__). DIRECTORY_SEPARATOR .'image'. DIRECTORY_SEPARATOR .'plugin-icon.png');
}

/**
 * function for view
 */
function apwr_img_opt_settings_page() {
    if (isset($_POST['save_settings'])) {
        $nonce = sanitize_text_field($_POST['apwr_nonce']);
        if ( ! wp_verify_nonce( $nonce, 'aparg-wtm-resize' ) )
            return;
        update_option('apwr_max_width', sanitize_text_field($_POST['max_width']) != '' ? intval($_POST['max_width']) : '');
        update_option('apwr_max_height', sanitize_text_field($_POST['max_height']) != '' ? intval($_POST['max_height']) : '');
        update_option('apwr_img_quality', sanitize_text_field($_POST['img_quality']) != '' ? intval($_POST['img_quality']) : '');
        update_option('apwr_resize_enable', isset($_POST['resize_enable']) ? intval($_POST['resize_enable']) : '');
        update_option('apwr_watermark_position', sanitize_text_field($_POST['watermark_position']));
        update_option('apwr_watermark_percentage', sanitize_text_field($_POST['watermark_percentage']) != '' ? intval($_POST['watermark_percentage']) : '');
        update_option('apwr_watermark_margin', sanitize_text_field($_POST['watermark_margin']) != '' ? intval($_POST['watermark_margin']) : '');
        update_option('apwr_watermark_enable', isset($_POST['watermark_enable']) ? intval($_POST['watermark_enable']) : '');
        update_option('apwr_interlace_enable', isset($_POST['interlace_enable']) ? intval($_POST['interlace_enable']) : '');
        if (sanitize_text_field($_POST['delete_img']) == "delete") {
            delete_option('apwr_logo');
        } elseif (sanitize_text_field ($_POST['delete_img']) == "update") {
            update_option('apwr_logo', apwr_handle_logo_upload());
        }
        echo '<div id="message" class="updated fade"><p><strong>' . __('Settings Saved.', 'aparg-watermark-and-resize') . '</strong></p></div>';
    }
    ?>
    <div class="wrap">
        <div>
            <h1>
                <?php _e('Aparg Watermark and Resize Settings', 'aparg-watermark-and-resize') ?>
                <div class="developed-by">
                    <span>
                        <?php _e('Developed by', 'aparg-watermark-and-resize') ?>
                        <a href="http://aparg.com/" target="_blank">Aparg</a>
                    </span>
                </div>
            </h1>
        </div>
        <h2><?php _e('Resize Settings', 'aparg-watermark-and-resize') ?></h2>    
        <form method="post" action="" enctype="multipart/form-data" id="ioSettingsForm">
            <table class="form-table" id="form-table-resize">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="max_width"><?php _e('Max Width', 'aparg-watermark-and-resize') ?></label>
                        </th>
                        <td>
                            <input id="max_width" class="small-text number" type="text" value="<?php echo esc_attr(get_option('apwr_max_width')); ?>" name="max_width"> <?php _e('px', 'aparg-watermark-and-resize') ?>
                            </br>
                            <span class="instructions"><?php _e('Set max width of picture,</br>Recommended value 1200', 'aparg-watermark-and-resize') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_height"><?php _e('Max Height', 'aparg-watermark-and-resize') ?></label>
                        </th>
                        <td>
                            <input id="max_height" class="small-text number" type="text" value="<?php echo esc_attr(get_option('apwr_max_height')); ?>" name="max_height"> <?php _e('px', 'aparg-watermark-and-resize') ?>
                            </br>
                            <span class="instructions"><?php _e('Set max height of picture </br>Recommended value 1200', 'aparg-watermark-and-resize') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="resize_enable"><?php _e('Resize on-fly', 'aparg-watermark-and-resize') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="resize_enable" value="1" <?php checked('1', get_option('apwr_resize_enable'), true); ?> />
                            </br>
                            <span class="instructions"><?php _e('Automatically apply to uploaded images', 'aparg-watermark-and-resize') ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <hr>
            <h2 id='wtm_header'><?php _e('Watermark Settings ', 'aparg-watermark-and-resize') ?></h2>
            <table class="form-table" id="form-table-wtm">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="watermark_position"><?php _e('Position', 'aparg-watermark-and-resize') ?></label>
                        </th>
                        <td>
                            <select id="watermark_position" value="<?php echo esc_attr_e(get_option('apwr_watermark_position'), $domain = 'aparg-watermark-and-resize'); ?>" name="watermark_position">
                                <option hidden="true"><?php echo esc_attr(get_option('apwr_watermark_position')); ?></option>
                                <option value="Top Left"><?php _e('Top Left', 'aparg-watermark-and-resize') ?></option>
                                <option value="Top Right"><?php _e('Top Right', 'aparg-watermark-and-resize') ?></option>
                                <option value="Bottom Left"><?php _e('Bottom Left', 'aparg-watermark-and-resize') ?></option>
                                <option value="Bottom Right"><?php _e('Bottom Right', 'aparg-watermark-and-resize') ?></option>
                                <option value="Center"><?php _e('Center', 'aparg-watermark-and-resize') ?></option>
                            </select> 
                            </br>
                            <span class="instructions"><?php _e('Set position of watermark ', 'aparg-watermark-and-resize') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="watermark_percentage"><?php _e('Size of watermark box', 'aparg-watermark-and-resize') ?></label>
                        </th>
                        <td>
                            <input id="watermark_percentage" class="small-text precent" type="text" value="<?php echo esc_attr(get_option('apwr_watermark_percentage')); ?>" name="watermark_percentage"> %
                            </br>
                            <span class="instructions"><?php _e('Set size of watermark from picture', 'aparg-watermark-and-resize') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="watermark_margin"><?php _e('Margin of watermark', 'aparg-watermark-and-resize') ?></label>
                        </th>
                        <td>
                            <input id="watermark_margin" class="small-text margin" type="text" value="<?php echo esc_attr(get_option('apwr_watermark_margin')); ?>" name="watermark_margin"> %
                            </br>
                            <span class="instructions"><?php _e('Set margin of watermark from borders </br>Defult value 1', 'aparg-watermark-and-resize') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="logo"><?php _e('Logo', 'aparg-watermark-and-resize') ?></label>
                        </th>
                        <td>
                            <div class="show-image">
                                <?php
                                if (get_option('apwr_logo')) {
                                    $logo = get_option('apwr_logo');
                                    ?>
                                    <img src="<?php echo esc_url($logo['url']); ?>" class="logoImg" id="logoImg" data-path="<?php echo esc_attr($logo['file']); ?>"/>
                                <?php } ?>
                                <a class="delete">
                                    <img src="<?php echo esc_url(plugins_url('aparg-watermark-and-resize') .'/image/closeButton.png'); ?>" class="deleteBtn"/>
                                </a>
                            </div>
                            <input type="file" id="add-img" name="logo" />
                            <input type="hidden" id="delete-img" name="delete_img" value="" />
                            </br>
                            <span class="instructions"><?php _e('Select watermark </br> Accepted type: PNG and JPEG', 'aparg-watermark-and-resize') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="watermark_enable"><?php _e('Watermark on-fly', 'aparg-watermark-and-resize') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="watermark_enable" value="1" <?php checked('1', get_option('apwr_watermark_enable'), true); ?> />
                            </br>
                            <span class="instructions"><?php _e('Automatically apply to uploaded images', 'aparg-watermark-and-resize') ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <hr>
            <h2><?php _e('Export Settings', 'aparg-watermark-and-resize') ?></h2>
            <table class="form-table" id="form-table-export">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="img_quality"><?php _e('JPEG Quality', 'aparg-watermark-and-resize') ?></label>
                        </th>
                        <td>
                            <input id="img_quality" class="small-text precent quality" type="text" value="<?php echo esc_attr(get_option('apwr_img_quality')); ?>" name="img_quality"> %
                            </br>
                            <span class="instructions"><?php _e('1 = low quality (smallest files)  </br>100 = best quality (largest files)</br>Defult value 90', 'aparg-watermark-and-resize') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="interlace_enable"><?php _e('Interlaced', 'aparg-watermark-and-resize') ?></label>
                        </th>
                        <td>
                            <input type="checkbox" name="interlace_enable" id="interlace_enable" value="1" <?php checked('1', get_option('apwr_interlace_enable'), true); ?> />
                            </br>
                            <span class="instructions"><?php _e('Allow partial load of images', 'aparg-watermark-and-resize') ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <input id="save_settings" class="button button-primary" type="submit" value="<?php _e('Save Settings', 'aparg-watermark-and-resize') ?>" name="save_settings">
            </p>
            <hr>
            <div>
                <button class='button media-button select-mode-toggle-button' id="bulkBtn"><?php _e('Proceed All', 'aparg-watermark-and-resize') ?></button>
                <div><label id="bulkNotification" class="notification"><?php _e('There is active process', 'aparg-watermark-and-resize') ?></label></div>
                <button class='button button-cancel' id="stopBtn"><?php _e('Stop Action', 'aparg-watermark-and-resize') ?></button>
                <div><label id="stopNotification" class="notification"><?php _e('Stopping current process', 'aparg-watermark-and-resize') ?></label></div>
                <div class="meter animate" id="meter">
                    <span style="width: 0.1%" id="load_bar"><span ></span></span><div id="load_pracent"></div>
                </div>
                <label id="serverNotification" class="notification"><?php _e('Sorry something went wrong on server side. Try to increase memory limit in WordPress config file.', 'aparg-watermark-and-resize') ?></label>
                <div id="showErrorList" class="upload-errors">
                    <a class="close-btn" href="#">X</a>
                    <div id="errorHeader"></div>
                    <div id="errorList"></div>
                </div>
                <div id="messageSuccess" class="updated fade"><p><strong> <?php _e('Success.', 'aparg_img_optimizer') ?></strong></p></div>
            </div>
            <?php wp_nonce_field('aparg-wtm-resize','apwr_nonce'); ?>
        </form>
        <div id="ioModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="close">×</span>
                    <p class="modal-title"><?php _e('Choose Action', 'aparg-watermark-and-resize') ?></p>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="modal-action">
                            <input type="checkbox" id="rAll" value="resize"><br>
                            <label><?php _e('Resize all images', 'aparg-watermark-and-resize') ?></label>
                        </div>
                        <div class="modal-action">
                            <input type="checkbox" id="wAll" value="watermark"><br>
                            <label><?php _e('Add watermark to all', 'aparg-watermark-and-resize') ?></label>
                        </div>
                    </div>
                    <div class="modal-notification"><?php _e('Note: Bulk action will be applied to all items in media library with current settings. We recommend to make backup of your website before start.', 'aparg-watermark-and-resize') ?></div>
                </div>
                <div class="modal-footer">
                    <button id="okBtn" class="button-primary"><?php _e('Ok', 'aparg-watermark-and-resize') ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * get upload image url 
 */
function apwr_handle_logo_upload() {
    if (!empty($_FILES["logo"]["tmp_name"])) {
        $apwr_logo = $_FILES["logo"];
        $apwr_ext = '';
        global $wpdb;
        $apwr_blogid =  $wpdb->blogid;
        if($apwr_logo['type'] == 'image/jpeg')
            $apwr_ext = '.jpg';
        if($apwr_logo['type'] == 'image/png')
            $apwr_ext = '.png';
        $apwr_logo["name"] = $apwr_blogid . $apwr_ext;
        add_filter('upload_dir', 'apwr_upload_dir');
        $movefile = wp_handle_upload($apwr_logo, array('test_form' => false));
        if ($movefile && !isset($movefile['error'])) {
            return $movefile;
        } else {
            return $movefile['error'];
        }
    }
    return '';
}

/**
 * Set upload image dir and empty this folder
 */
function apwr_upload_dir($movefile) {
    $movefile['subdir'] = '/img';
    $movefile['path'] = plugin_dir_path(__FILE__) . $movefile['subdir'];
    $movefile['url'] = plugins_url('aparg-watermark-and-resize') . $movefile['subdir'];
    global $wpdb;
    $apwr_blogid =  $wpdb->blogid;
    array_map('unlink', glob($movefile['path'] ."/". $apwr_blogid .".*"));
    return $movefile;
}
