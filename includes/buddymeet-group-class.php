<?php
/**
 * BuddyMeet Groups
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_Group_Extension' ) ) :

/**
 * The BuddyMeet group class
 *
 * @package BuddyMeet
 * @since 1.0.0
 */
class BuddyMeet_Group extends BP_Group_Extension {
    function __construct() {
        global $bp;

        $enabled = false;
        if ( isset( $bp->groups->current_group->id ) ) {
            $enabled = buddymeet_is_enabled($bp->groups->current_group->id);
        }

        $args = array(
            'name' => buddymeet_get_name(),
            'slug' => buddymeet_get_slug(),
            'nav_item_position' => 40,
            'enable_nav_item' =>  $enabled
        );
        parent::init( $args );
    }

    function create_screen( $group_id = null) {
        global $bp;

        if ( ! $group_id ) {
            $group_id = $bp->groups->current_group->id;
        }

        if ( !bp_is_group_creation_step( $this->slug ) )
            return false;

        wp_nonce_field( 'groups_create_save_' . $this->slug );

        $this->render_settings($group_id, true);
    }

    function create_screen_save($group_id = null) {
        global $bp;

        if ( ! $group_id ) {
            $group_id = $bp->groups->current_group->id;
        }

        check_admin_referer( 'groups_create_save_' . $this->slug );

        $this->persist_settings($group_id);
    }

    function edit_screen( $group_id = null ) {
        global $bp;

        if ( !groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id ) && ! current_user_can( 'bp_moderate' ) ) {
            return false;
        }

        if ( !bp_is_group_admin_screen( $this->slug ) )
            return false;

        if (!$group_id){
            $group_id = $bp->groups->current_group->id;
        }

        wp_nonce_field( 'groups_edit_save_' . $this->slug );

        $this->render_settings($group_id, false);
        ?>

        <input type="submit" name="save" value="Save" />
        <?php
    }

    function edit_screen_save( $group_id = null ) {
        global $bp;

        if ( sanitize_text_field( $_POST['save'] == null ) )
            return false;

        if ( !$group_id ) {
            $group_id = $bp->groups->current_group->id;
        }

        check_admin_referer( 'groups_edit_save_' . $this->slug );

        $this->persist_settings($group_id);

        bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );

        bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/' . $this->slug );
    }

    function display( $group_id = null ) {
        global $bp;

        if (!$group_id) {
            $group_id = $bp->groups->current_group->id;
        }

        if ( groups_is_user_member( $bp->loggedin_user->id, $group_id )
            || groups_is_user_mod( $bp->loggedin_user->id, $group_id )
            || groups_is_user_admin( $bp->loggedin_user->id, $group_id )
            || is_super_admin() ) {

            $enabled = buddymeet_is_enabled($bp->groups->current_group->id);
            if ( $enabled == 1 ) {
                $this->get_groups_template_part( 'buddymeet' );
            }
        } else {
            echo '<div id="message" class="error"><p>'.__('This content is only available to group members.', 'buddymeet').'</p></div>';
        }
    }

    function widget_display() {
        // Not used
    }

    function render_settings($group_id, $is_create){
        $defaults = buddymeet_default_settings();
        $display_settings = apply_filters( 'buddymeet_display_group_settings', array_keys($defaults) );

        ?>
        <div class="wrap">
            <h4><?php _e( buddymeet_get_name() . ' Settings', 'buddymeet' ) ?></h4>

            <fieldset>
                <p><?php _e( 'Allow members of this group to enter the same video conference room.', 'buddymeet' ); ?></p>
                <?php
                $enabled = $is_create ? $defaults['enabled'] : buddymeet_is_enabled($group_id);

                //if there is not any room set up create a uuid
                $room = buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_room', wp_generate_uuid4());
                $password = buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_password', '');
                $domain =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_domain', $defaults['domain']);
                $toolbar =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_toolbar',  $defaults['toolbar']);
                $settings =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_settings',  $defaults['settings']);
                $width =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_width',  $defaults['width']);
                $height =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_height',  $defaults['height']);
                $background_color =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_background_color',  $defaults['background_color']);
                $default_language =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_default_language',  $defaults['default_language']);
                $show_watermark =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_show_watermark',  $defaults['show_watermark']);
                $film_strip_only =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_film_strip_only',  $defaults['film_strip_only']);
                $start_audio_only =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_start_audio_only',  $defaults['start_audio_only']);
                $disable_video_quality_label =  buddymeet_groups_get_groupmeta( $group_id, 'buddymeet_disable_video_quality_label',  $defaults['disable_video_quality_label']);
                ?>

                <div class="field-group">
                    <div class="checkbox">
                        <label><input type="checkbox" name="buddymeet_enabled" value="1" <?php checked( (bool) $enabled )?>> <?php _e( 'Activate', 'buddymeet' ); ?></label>
                    </div>
                </div>

                <?php if(in_array('domain', $display_settings)): ?>
                <div class="field-group">
                    <label><?php _e( 'Domain', 'buddymeet' ); ?></label>
                    <input type="text" name="buddymeet_domain" id="buddymeet_domain" value="<?php echo $domain; ?>"/>
                    <p class="description"><?php esc_html_e( 'The domain the Jitsi Meet server runs. Defaults to their free hosted service.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('room', $display_settings)): ?>
                <div class="field-group">
                        <label><?php _e( 'Room', 'buddymeet' ); ?></label>
                        <input type="text" name="buddymeet_room" id="buddymeet_room" value="<?php echo $room; ?>"/>
                        <p class="description"><?php esc_html_e( 'Set the room group members will enter automatically when visiting the ' .buddymeet_get_name(). ' menu.', 'buddymeet' ); ?></p>
                </div>
                <?php else: ?>
                    <input type="hidden" name="buddymeet_room" value="<?php echo $room; ?>"/>
                <?php endif; ?>

                <?php if(in_array('password', $display_settings)): ?>
                <div class="field-group">
                    <label><?php _e( 'Password', 'buddymeet' ); ?></label>
                    <input type="password" name="buddymeet_password" value="<?php echo $password; ?>"/>
                    <p class="description"><?php esc_html_e( 'Set the password the group members will have to enter to join the room. The first to visit - and therefore create - the room will enter without any password. The rest participants will have to fill-in the password.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('toolbar', $display_settings)): ?>
                <div class="field-group">
                    <label><?php _e( 'Toolbar', 'buddymeet' ); ?></label>
                    <input type="text" name="buddymeet_toolbar" id="buddymeet_toolbar" value="<?php echo $toolbar; ?>"/>
                    <p class="description"><?php _e( 'The toolbar buttons to get displayed in comma separated format. For more information refer to <a  target="_blank" href="https://github.com/jitsi/jitsi-meet/blob/master/interface_config.js#L49">TOOLBAR_BUTTONS</a>.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('settings', $display_settings)): ?>
                <div class="field-group">
                    <label><?php _e( 'Settings', 'buddymeet' ); ?></label>
                    <input type="text" name="buddymeet_settings" id="buddymeet_settings" value="<?php echo $settings; ?>"/>
                    <p class="description"><?php _e( 'The settings to be available in comma separated format. For more information refer to <a  target="_blank" href="https://github.com/jitsi/jitsi-meet/blob/master/interface_config.js#L57">SETTINGS_SECTIONS</a>.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('width', $display_settings)): ?>
                <div class="field-group">
                    <label><?php _e( 'Width', 'buddymeet' ); ?></label>
                    <input type="text" name="buddymeet_width" id="buddymeet_width" value="<?php echo $width; ?>"/>
                    <p class="description"><?php esc_html_e( 'The width in pixels or percentage of the embedded window.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('height', $display_settings)): ?>
                <div class="field-group">
                    <label><?php _e( 'Height', 'buddymeet' ); ?></label>
                    <input type="text" name="buddymeet_height" id="buddymeet_height" value="<?php echo $height; ?>"/>
                    <p class="description"><?php esc_html_e( 'The height in pixels or percentage of the embedded window.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('background_color', $display_settings)): ?>
                <div class="field-group">
                    <label><?php _e( 'Background Color', 'buddymeet' ); ?></label>
                    <input type="text" name="buddymeet_background_color" id="buddymeet_background_color" value="<?php echo $background_color; ?>"/>
                    <p class="description"><?php esc_html_e( 'The background color of the window when camera is off.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('default_language', $display_settings)): ?>
                <div class="field-group">
                    <label><?php _e( 'Default Language', 'buddymeet' ); ?></label>
                    <input type="text" name="buddymeet_default_language" id="buddymeet_default_language" value="<?php echo $default_language; ?>"/>
                    <p class="description"><?php esc_html_e( 'The default language.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('show_watermark', $display_settings)): ?>
                <div class="field-group">
                    <div class="checkbox">
                        <label><input type="checkbox" name="buddymeet_show_watermark" value="1" <?php checked( (bool) $show_watermark)?>> <?php _e( 'Show Watermark', 'buddymeet' ); ?></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Show/Hide the Jitsi Meet watermark. Please leave it checked unless you use your own domain.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('film_strip_only', $display_settings)): ?>
                <div class="field-group">
                    <div class="checkbox">
                        <label><input type="checkbox" name="buddymeet_film_strip_only" value="1" <?php checked( (bool) $film_strip_only)?>> <?php _e( 'Film Strip Mode Only', 'buddymeet' ); ?></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Display the window in film strip only mode.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('start_audio_only', $display_settings)): ?>
                <div class="field-group">
                    <div class="checkbox">
                        <label><input type="checkbox" name="buddymeet_start_audio_only" value="1" <?php checked( (bool) $start_audio_only)?>> <?php _e( 'Start Audio Only', 'buddymeet' ); ?></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Every participant enters the room having enabled only their microphone. Camera is off.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

                <?php if(in_array('disable_video_quality_label', $display_settings)): ?>
                <div class="field-group">
                    <div class="checkbox">
                        <label><input type="checkbox" name="buddymeet_disable_video_quality_label" value="1" <?php checked( (bool) $disable_video_quality_label)?>> <?php _e( 'Disable Video Quality Indicator', 'buddymeet' ); ?></label>
                    </div>
                    <p class="description"><?php esc_html_e( 'Hide/Show the video quality indicator.', 'buddymeet' ); ?></p>
                </div>
                <?php endif; ?>

            </fieldset>
        </div>
        <?php
    }

    function persist_settings($group_id){
        $defaults = buddymeet_default_settings();

        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_enabled', "0");
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_room', '');
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_password', '');
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_domain', $defaults['domain'] );
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_toolbar', $defaults['toolbar'] );
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_settings', $defaults['settings'] );
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_width', $defaults['width'] );
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_height', $defaults['height'] );
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_background_color', $defaults['background_color'] );
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_default_language', $defaults['default_language'] );
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_show_watermark', "0" );
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_film_strip_only', "0" );
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_start_audio_only', "0" );
        buddymeet_groups_update_groupmeta($group_id, 'buddymeet_disable_video_quality_label', "0" );
    }

    function get_groups_template_part( $slug ) {
        add_filter( 'bp_locate_template_and_load', '__return_true'                        );
        add_filter( 'bp_get_template_stack', array($this, 'set_template_stack'), 10, 1 );

        bp_get_template_part( 'groups/single/' . $slug );

        remove_filter( 'bp_locate_template_and_load', '__return_true' );
        remove_filter( 'bp_get_template_stack', array($this, 'set_template_stack'), 10);
    }

    function set_template_stack( $stack = array() ) {
        if ( empty( $stack ) ) {
            $stack = array( buddymeet_get_plugin_dir() . 'templates' );
        } else {
            $stack[] = buddymeet_get_plugin_dir() . 'templates';
        }

        return $stack;
    }
}

/**
 * Waits for bp_init hook before loading the group extension
 *
 * Let's make sure the group id is defined before loading our stuff
 *
 * @since 1.0.0
 *
 * @uses bp_register_group_extension() to register the group extension
 */
function buddymeet_register_group_extension() {
    bp_register_group_extension( 'BuddyMeet_Group' );
}

add_action( 'bp_init', 'buddymeet_register_group_extension' );

endif;