<?php

defined( 'ABSPATH' ) or exit;


final class Mobile_Contact_Bar_Page
{

    /**
     * Option page's hook.
     * @var string
     */
    public static $page = null;



    /**
     * Prevents activating on old versions of PHP or WordPress.
     * Creates the default option (settings, contacts, styles) during the plugin activation.
     *
     * @since 0.1.0
     *
     * @param bool $network_wide Whether to enable the plugin for all sites in the network or just for the current site.
     *
     * @global $wp_version
     * @global $wpdb
     */
    public static function on_activation( $network_wide = false )
    {
        global $wp_version;

        $readme_data = get_file_data( plugin_dir_path( MOBILE_CONTACT_BAR__PATH ) . 'readme.txt',
            array(
                'Requires PHP'      => 'Requires PHP',
                'Requires at least' => 'Requires at least',
            )
        );

        if( version_compare( PHP_VERSION, $readme_data['Requires PHP'], '<' ))
        {
            deactivate_plugins( basename( MOBILE_CONTACT_BAR__PATH ));
            wp_die(
                sprintf( __( 'Mobile Contact Bar requires at least PHP version %s. You are running version %s. Please upgrade and try again.', 'mobile-contact-bar' ), $readme_data['Requires PHP'], PHP_VERSION ),
                'Plugin Activation Error',
                array( 'back_link' => true, )
            );
        }
        elseif( version_compare( $wp_version, $readme_data['Requires at least'], '<' ))
        {
            deactivate_plugins( basename( MOBILE_CONTACT_BAR__PATH ));
            wp_die(
                sprintf( __( 'Mobile Contact Bar requires at least WordPress version %s. You are running version %s. Please upgrade and try again.', 'mobile-contact-bar' ), $readme_data['Requires at least'], $wp_version ),
                'Plugin Activation Error',
                array( 'back_link' => true, )
            );
        }
        else
        {
            $default_option = self::default_option();

            if( $network_wide )
            {
                global $wpdb;

                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                foreach( $blog_ids as $blog_id )
                {
                    switch_to_blog( $blog_id );

                    self::update_plugin_options( $default_option );

                    restore_current_blog();
                }
            }
            else
            {
                self::update_plugin_options( $default_option );
            }
        }
    }



    /**
     * Hooks WordPress's admin actions and filters.
     *
     * @since 0.1.0
     */
    public static function plugins_loaded()
    {
        $basename = plugin_basename( MOBILE_CONTACT_BAR__PATH );

        load_plugin_textdomain( 'mobile-contact-bar', false, dirname( $basename ) . '/languages' );

        add_action( 'init'                  , array( __CLASS__, 'init' ));
        add_action( 'wpmu_new_blog'         , array( __CLASS__, 'wpmu_new_blog' ));
        add_action( 'admin_menu'            , array( __CLASS__, 'admin_menu' ));
        add_action( 'add_meta_boxes'        , array( __CLASS__, 'add_meta_boxes' ));
        add_action( 'admin_enqueue_scripts' , array( __CLASS__, 'admin_enqueue_scripts' ));

        add_filter( 'plugin_action_links_' . $basename, array( __CLASS__, 'plugin_action_links' ));
    }



    /**
     * Updates plugin version
     * Restores option
     *
     * @since 2.0.1
     */
    public static function init()
    {
        $version = get_option( MOBILE_CONTACT_BAR__NAME . '_version' );

        if( version_compare( $version, MOBILE_CONTACT_BAR__VERSION, '<' ))
        {
            $default_option = self::default_option();
            self::update_plugin_options( $default_option );
        }
    }



    /**
     * Creates default option on blog creation.
     *
     * @since 1.0.0
     *
     * @param int $blog_id Blog ID of the newly created blog.
     */
    public static function wpmu_new_blog( $blog_id )
    {
        add_blog_option( $blog_id, MOBILE_CONTACT_BAR__NAME . '_version', MOBILE_CONTACT_BAR__VERSION );
        add_blog_option( $blog_id, MOBILE_CONTACT_BAR__NAME, self::default_option() );
    }



    /**
     * Adds 'Settings' link to the plugins overview page.
     *
     * @since 0.1.0
     *
     * @param  array $links Associative array of links.
     * @return array        Updated links.
     */
    public static function plugin_action_links( $links )
    {
        return array_merge(
            array( 'settings' => '<a href="' . admin_url( 'options-general.php?page=' . MOBILE_CONTACT_BAR__SLUG ) . '">' . esc_html__( 'Settings' ) . '</a>' ),
            $links
        );
    }



    /**
     * Adds option page to the admin menu.
     * Hooks the option page related screen tabs.
     *
     * @since 0.1.0
     */
    public static function admin_menu()
    {
        self::$page = add_options_page(
            __( 'Mobile Contact Bar', 'mobile-contact-bar' ),
            __( 'Mobile Contact Bar', 'mobile-contact-bar' ),
            'manage_options',
            MOBILE_CONTACT_BAR__SLUG,
            array( __CLASS__, 'callback_render_page' )
        );

        add_action( 'load-' . self::$page, array( __CLASS__, 'load_screen_options' ));
        add_action( 'load-' . self::$page, array( __CLASS__, 'load_help' ));
    }



    /**
     * Renders the option page skeleton.
     *
     * @since 0.1.0
     */
    public static function callback_render_page()
    {
        ?>
        <div class="wrap">
            <h2><?php esc_html_e( 'Mobile Contact Bar', 'mobile-contact-bar' ); ?></h2>

            <form id="mcb-form" action="options.php" method="post">
                <?php
                settings_fields( MOBILE_CONTACT_BAR__NAME . '_group' );
                wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
                wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
                ?>

                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-<?php echo ( 1 == get_current_screen()->get_columns() ) ? '1' : '2'; ?>">

                        <div id="postbox-container-2" class="postbox-container">
                            <?php do_meta_boxes( self::$page, 'advanced', null ); ?>
                        </div><!-- #postbox-container-2 -->

                        <div id="postbox-container-1" class="postbox-container">
                            <?php do_meta_boxes( self::$page, 'side', null ); ?>
                        </div><!-- #postbox-container-1 -->

                        <div id="post-body-content">
                            <?php submit_button(); ?>
                        </div>

                    </div><!-- #post-body -->
                    <br class="clear">

                </div><!-- #poststuff -->
                <br class="clear">

            </form><!-- #mcb-form -->
            <div class="clear"></div>

        </div>
        <div class="clear"></div>
        <?php
    }



    /**
     * Triggers the 'add_meta_boxes' hooks.
     * Adds screen options.
     *
     * @since 2.0.0
     */
    public static function load_screen_options()
    {
        //do_action( 'add_meta_boxes_' . self::$page, null );
        do_action( 'add_meta_boxes', self::$page, null );
        add_screen_option( 'layout_columns', array( 'max' => 2, 'default' => 2 ));
    }



    /**
     * Adds contextual help menu.
     *
     * @since 2.0.0
     */
    public static function load_help()
    {
        $screen = get_current_screen();

        $tabs = array(
            array(
                'title'    => __( 'Phone calls', 'mobile-contact-bar' ),
                'id'       => 'mcb-tel',
                'callback' => array( __CLASS__, 'render_help_tab_tel' ),
            ),
            array(
                'title'    => __( 'Mobile texts', 'mobile-contact-bar' ),
                'id'       => 'mcb-sms',
                'callback' => array( __CLASS__, 'render_help_tab_sms' ),
            ),
            array(
                'title'    => __( 'Emails', 'mobile-contact-bar' ),
                'id'       => 'mcb-mailto',
                'callback' => array( __CLASS__, 'render_help_tab_mailto' ),
            ),
            array(
                'title'    => __( 'Links', 'mobile-contact-bar' ),
                'id'       => 'mcb-http',
                'callback' => array( __CLASS__, 'render_help_tab_http' ),
            ),
            array(
                'title'    => __( 'Skype', 'mobile-contact-bar' ),
                'id'       => 'mcb-skype',
                'callback' => array( __CLASS__, 'render_help_tab_skype' ),
            ),
        );

        foreach( $tabs as $tab )
        {
            $screen->add_help_tab( $tab );
        }

        $screen->set_help_sidebar( self::output_help_sidebar() );
    }



    /**
     * Adds meta boxes to the option page.
     * Adjusts meta box classes.
     *
     * @since 1.2.0
     *
     * @global $wp_settings_sections
     */
    public static function add_meta_boxes()
    {
        $screen = get_current_screen();
        if ( $screen->base !== self::$page )
        {
            return;
        }

        global $wp_settings_sections;

        add_meta_box(
            'mcb-section-model',
            __( 'Real-time Model', 'mobile-contact-bar' ),
            array( __CLASS__, 'callback_render_model' ),
            self::$page,
            'side',
            'default'
        );

        foreach( $wp_settings_sections[MOBILE_CONTACT_BAR__NAME] as $section )
        {
            add_meta_box(
                $section['id'],
                $section['title'],
                array( 'Mobile_Contact_Bar_Option', 'callback_render_section' ),
                self::$page,
                'advanced',
                'default'
            );

            // add 'mcb-settings' class to meta boxes except Contact List
            if( 'mcb-section-contacts' != $section['id'] )
            {
                add_filter( 'postbox_classes_' . self::$page . '_' . $section['id'], array( __CLASS__, 'metabox_classes_mcb_settings' ));
            }
        }

        $user = wp_get_current_user();
        $closed_meta_boxes = get_user_option( 'closedpostboxes_' . self::$page, $user->ID );

        // close meta boxes for the first time
        if( ! $closed_meta_boxes )
        {
            $meta_boxes = array_keys( $wp_settings_sections[MOBILE_CONTACT_BAR__NAME] );
            update_user_option( $user->ID, 'closedpostboxes_' . self::$page, $meta_boxes, true );
        }
    }



    /**
     * Adds classes to meta boxes.
     *
     * @since 2.0.0
     *
     * @param  array $classes Array of classes.
     * @return array          Updated array of classes.
     */
    public static function metabox_classes_close( $classes )
    {
        $classes[] = 'closed';
        return $classes;
    }



    /**
     * Adds classes to meta boxes.
     *
     * @since 2.0.0
     *
     * @param  array $classes Array of classes.
     * @return array          Updated array of classes.
     */
    public static function metabox_classes_mcb_settings( $classes )
    {
        $classes[] = 'mcb-settings';
        return $classes;
    }



    /**
     * Renders Real-time Model and Plugin Info meta box
     *
     * @since 2.0.0
     */
    public static function callback_render_model()
    {
        $plugin_data = get_file_data( MOBILE_CONTACT_BAR__PATH,
            array(
                'Description' => 'Description',
                'Plugin URI'  => 'Plugin URI',
                'Author URI'  => 'Author URI',
            )
        );

        ?>
        <div id="mcb-model">
            <?php include_once plugin_dir_path( MOBILE_CONTACT_BAR__PATH ) . 'assets/images/settings/real-time-model/model.svg'; ?>
            <footer><em><?php _e( 'The model is an approximation. A lot depends on your active theme"s styles.', 'mobile-contact-bar' ); ?></em></footer>
        </div>

        <div id="mcb-about">
            <h2><?php _e( 'Mobile Contact Bar', 'mobile-contact-bar' ); ?> <?php echo MOBILE_CONTACT_BAR__VERSION; ?></h2>
            <p><?php _e( $plugin_data['Description'], 'mobile-contact-bar' ); ?></p>
            <ul>
                <li><a href="<?php echo esc_url( $plugin_data['Plugin URI'] . '#developers' ); ?>" target="_blank" rel="noopener"><?php _e( 'Changelog', 'mobile-contact-bar' ); ?></a></li>
                <li><a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/mobile-contact-bar' ); ?>" target="_blank" rel="noopener"><?php _e( 'Forum', 'mobile-contact-bar' ); ?></a></li>
                <li><a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/mobile-contact-bar' ); ?>" target="_blank" rel="noopener"><?php _e( 'Requests', 'mobile-contact-bar' ); ?></a></li>
            </ul>
            <footer>
                <?php printf( __( 'Thank you for networking with <a href="%s">MCB</a>.', 'mobile-contact-bar' ), esc_url( $plugin_data['Plugin URI'] )); ?>
            </footer>
        </div>
        <?php
    }



    /**
     * Loads styles and scripts for plugin option page.
     *
     * @since 0.1.0
     *
     * @param string $hook The specific admin page.
     */
    public static function admin_enqueue_scripts( $hook )
    {
        if( self::$page == $hook )
        {
            // WordPress's postboxes logic
            wp_enqueue_script( 'postbox' );

            // WordPress's color picker styles and scripts
            wp_enqueue_style(  'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );

            wp_enqueue_style(
                'mcb-admin',
                plugins_url( 'assets/css/admin.min.css', MOBILE_CONTACT_BAR__PATH ),
                array( 'wp-color-picker', ),
                MOBILE_CONTACT_BAR__VERSION,
                'all'
            );

            wp_enqueue_script(
                'mcb-admin',
                plugins_url( 'assets/js/admin.min.js', MOBILE_CONTACT_BAR__PATH ),
                array( 'jquery', 'jquery-ui-slider', 'jquery-ui-sortable', 'postbox', 'wp-color-picker', ),
                MOBILE_CONTACT_BAR__VERSION,
                false
            );

            wp_localize_script(
                'mcb-admin',
                MOBILE_CONTACT_BAR__NAME,
                array(
                    'nonce' => wp_create_nonce( MOBILE_CONTACT_BAR__NAME ),
                )
            );
        }
    }



    /**
     * Renders 'tel' help tab
     *
     * @since 2.0.0
     */
    public static function render_help_tab_tel()
    {
        ?>
        <h4><?php _e( 'Initiating phone or mobile audio calls', 'mobile-contact-bar' ); ?></h4>
        <code>tel:+1-541-754-3010</code> <?php _e( 'or', 'mobile-contact-bar' ); ?> <code>tel:+15417543010</code>
        <p><?php _e( 'Use the international dialing format: the plus sign (<code>+</code>), country code, area code, and number. You can separate each segment of the number with a hyphen (<code>-</code>) for easier reading.', 'mobile-contact-bar' ); ?></p>
        <p class="mcb-tab-status-green"><?php _e( 'Standardised protocol', 'mobile-contact-bar' ); ?></p>
        <?php
    }



    /**
     * Renders 'sms' help tab
     *
     * @since 2.0.0
     */
    public static function render_help_tab_sms()
    {
        ?>
        <h4><?php _e( 'Sending text messages to mobile phones', 'mobile-contact-bar' ); ?></h4>
        <code>sms:+1-541-754-3010</code> <?php _e( 'or', 'mobile-contact-bar' ); ?> <code>sms:+15417543010</code>
        <p><?php _e( 'Use the international dialing format: the plus sign (<code>+</code>), country code, area code, and number. You can separate each segment of the number with a hyphen (<code>-</code>) for easier reading.', 'mobile-contact-bar' ); ?></p>
        <p><?php _e( 'Optional query parameter:', 'mobile-contact-bar' ); ?></p>
        <ul class="mcb-query-parameters">
            <li>
                <span class="mcb-query-parameter-key">body</span>
                <span><?php _e( 'Text to appear in the body of the message (it does not always work).', 'mobile-contact-bar' ); ?></span>
            </li>
        </ul>
        <p class="mcb-tab-status-yellow"><?php _e( 'Inconsistent protocol', 'mobile-contact-bar' ); ?></p>
        <?php
    }



    /**
     * Renders 'mailto' help tab
     *
     * @since 2.0.0
     */
    public static function render_help_tab_mailto()
    {
        ?>
        <h4><?php _e( 'Sending emails to email addresses', 'mobile-contact-bar' ); ?></h4>
        <code>mailto:someone@domain.com</code>
        <p><?php _e( 'Optional query parameters:', 'mobile-contact-bar' ); ?></p>
        <ul class="mcb-query-parameters">
            <li>
                <span class="mcb-query-parameter-key">subject</span>
                <span><?php _e( 'Text to appear in the subject line of the message.', 'mobile-contact-bar' ); ?></span>
            </li>
            <li>
                <span class="mcb-query-parameter-key">body</span>
                <span><?php _e( 'Text to appear in the body of the message.', 'mobile-contact-bar' ); ?></span>
            </li>
            <li>
                <span class="mcb-query-parameter-key">cc</span>
                <span><?php _e( 'Addresses to be included in the carbon copy section of the message. Separate addresses with commas.', 'mobile-contact-bar' ); ?></span>
            </li>
            <li>
                <span class="mcb-query-parameter-key">bcc</span>
                <span><?php _e( 'Addresses to be included in the blind carbon copy section of the message. Separate addresses with commas.', 'mobile-contact-bar' ); ?></span>
            </li>
        </ul>
        <p class="mcb-tab-status-green"><?php _e( 'Standardised protocol', 'mobile-contact-bar' ); ?></p>
        <?php
    }



    /**
     * Renders 'http' help tab
     *
     * @since 2.0.0
     */
    public static function render_help_tab_http()
    {
        ?>
        <h4><?php _e( 'Linking to web pages on your or others websites', 'mobile-contact-bar' ); ?></h4>
        <code>http://domain.com</code> <?php _e( 'or', 'mobile-contact-bar' ); ?> <code>http://domain.com/path/to/page</code>
        <p><?php _e( 'For secure websites using SSL to encrypt data and authenticate the website use the <code>https</code> protocol:', 'mobile-contact-bar' ); ?></p>
        <code>https://domain.com</code> <?php _e( 'or', 'mobile-contact-bar' ); ?> <code>https://domain.com/path/to/page</code>
        <p><?php _e( 'You can append query parameters to URLs using the', 'mobile-contact-bar' ); ?> <span class="mcb-tab-button button"><i class="fas fa-plus fa-sm" aria-hidden="true"></i></span> <?php _e( 'button', 'mobile-contact-bar' ); ?></p>
        <p class="mcb-tab-status-green"><?php _e( 'Standardised protocol', 'mobile-contact-bar' ); ?></p>
        <?php
    }



    /**
     * Renders 'skype' help tab
     *
     * @since 2.0.0
     */
    public static function render_help_tab_skype()
    {
        ?>
        <h4><?php _e( 'Sending instant messages to other Skype users, phones, or mobiles', 'mobile-contact-bar' ); ?></h4>
        <code>skype:username?chat</code> <?php _e( 'or', 'mobile-contact-bar' ); ?> <code>skype:+phone-number?chat</code>
        <h4><?php _e( 'Initiating audio calls to other Skype users, phones, or mobiles', 'mobile-contact-bar' ); ?></h4>
        <code>skype:username?call</code> <?php _e( 'or', 'mobile-contact-bar' ); ?> <code>skype:+phone-number?call</code>
        <p class="mcb-tab-status-yellow"><?php _e( 'Inconsistent protocol', 'mobile-contact-bar' ); ?></p>
        <?php
    }



    /**
     * Outputs help sidebar
     *
     * @since 2.0.0
     */
    public static function output_help_sidebar()
    {
        $out  = '';
        $out .= '<h4>' . __( 'More info', 'mobile-contact-bar' ) . '</h4>';
        $out .= '<p><a href="'. esc_url( 'https://en.wikipedia.org/wiki/Uniform_Resource_Identifier' ) . '" target="_blank" rel="noopener">' . __( 'Uniform Resource Identifier', 'mobile-contact-bar' ) . '</a></p>';

        return $out;
    }



    /**
     * Checks whether an icon exists or not.
     *
     * @since 2.0.0
     *
     * @param  string $classes Font Awesome icon classes.
     * @return bool            Whether the icon exists or not.
     */
    public static function in_icons( $classes )
    {
        $class_list = explode( ' ', $classes );
        $name       = substr( $class_list[1], 3 );
        $icons      = self::icons();

        foreach( $icons as $id => $section )
        {
            if( $class_list[0] == $id )
            {
                foreach( $section as $icon )
                {
                    if( $name == $icon )
                    {
                        return true;
                    }
                }
            }
        }
        return false;
    }



    /**
     * Defines the multidimensional array of icons divided into sections: 'fas', 'far', 'fab'.
     *
     * @since 2.0.0
     *
     * @return array Array of Fontawesome Icon names.
     */
    public static function icons()
    {
        return array(
            'fas' => array('0','1','2','3','4','5','6','7','8','9','a','address-book','address-card','align-center','align-justify','align-left','align-right','anchor','anchor-circle-check','anchor-circle-exclamation','anchor-circle-xmark','anchor-lock','angle-down','angle-left','angle-right','angle-up','angles-down','angles-left','angles-right','angles-up','ankh','apple-whole','archway','arrow-down','arrow-down-1-9','arrow-down-9-1','arrow-down-a-z','arrow-down-long','arrow-down-short-wide','arrow-down-up-across-line','arrow-down-up-lock','arrow-down-wide-short','arrow-down-z-a','arrow-left','arrow-left-long','arrow-pointer','arrow-right','arrow-right-arrow-left','arrow-right-from-bracket','arrow-right-long','arrow-right-to-bracket','arrow-right-to-city','arrow-rotate-left','arrow-rotate-right','arrow-trend-down','arrow-trend-up','arrow-turn-down','arrow-turn-up','arrow-up','arrow-up-1-9','arrow-up-9-1','arrow-up-a-z','arrow-up-from-bracket','arrow-up-from-ground-water','arrow-up-from-water-pump','arrow-up-long','arrow-up-right-dots','arrow-up-right-from-square','arrow-up-short-wide','arrow-up-wide-short','arrow-up-z-a','arrows-down-to-line','arrows-down-to-people','arrows-left-right','arrows-left-right-to-line','arrows-rotate','arrows-spin','arrows-split-up-and-left','arrows-to-circle','arrows-to-dot','arrows-to-eye','arrows-turn-right','arrows-turn-to-dots','arrows-up-down','arrows-up-down-left-right','arrows-up-to-line','asterisk','at','atom','audio-description','austral-sign','award','b','baby','baby-carriage','backward','backward-fast','backward-step','bacon','bacteria','bacterium','bag-shopping','bahai','baht-sign','ban','ban-smoking','bandage','bangladeshi-taka-sign','barcode','bars','bars-progress','bars-staggered','baseball','baseball-bat-ball','basket-shopping','basketball','bath','battery-empty','battery-full','battery-half','battery-quarter','battery-three-quarters','bed','bed-pulse','beer-mug-empty','bell','bell-concierge','bell-slash','bezier-curve','bicycle','binoculars','biohazard','bitcoin-sign','blender','blender-phone','blog','bold','bolt','bolt-lightning','bomb','bone','bong','book','book-atlas','book-bible','book-bookmark','book-journal-whills','book-medical','book-open','book-open-reader','book-quran','book-skull','book-tanakh','bookmark','border-all','border-none','border-top-left','bore-hole','bottle-droplet','bottle-water','bowl-food','bowl-rice','bowling-ball','box','box-archive','box-open','box-tissue','boxes-packing','boxes-stacked','braille','brain','brazilian-real-sign','bread-slice','bridge','bridge-circle-check','bridge-circle-exclamation','bridge-circle-xmark','bridge-lock','bridge-water','briefcase','briefcase-medical','broom','broom-ball','brush','bucket','bug','bug-slash','bugs','building','building-circle-arrow-right','building-circle-check','building-circle-exclamation','building-circle-xmark','building-columns','building-flag','building-lock','building-ngo','building-shield','building-un','building-user','building-wheat','bullhorn','bullseye','burger','burst','bus','bus-simple','business-time','c','cable-car','cake-candles','calculator','calendar','calendar-check','calendar-day','calendar-days','calendar-minus','calendar-plus','calendar-week','calendar-xmark','camera','camera-retro','camera-rotate','campground','candy-cane','cannabis','capsules','car','car-battery','car-burst','car-on','car-rear','car-side','car-tunnel','caravan','caret-down','caret-left','caret-right','caret-up','carrot','cart-arrow-down','cart-flatbed','cart-flatbed-suitcase','cart-plus','cart-shopping','cash-register','cat','cedi-sign','cent-sign','certificate','chair','chalkboard','chalkboard-user','champagne-glasses','charging-station','chart-area','chart-bar','chart-column','chart-gantt','chart-line','chart-pie','chart-simple','check','check-double','check-to-slot','cheese','chess','chess-bishop','chess-board','chess-king','chess-knight','chess-pawn','chess-queen','chess-rook','chevron-down','chevron-left','chevron-right','chevron-up','child','child-combatant','child-dress','child-reaching','children','church','circle','circle-arrow-down','circle-arrow-left','circle-arrow-right','circle-arrow-up','circle-check','circle-chevron-down','circle-chevron-left','circle-chevron-right','circle-chevron-up','circle-dollar-to-slot','circle-dot','circle-down','circle-exclamation','circle-h','circle-half-stroke','circle-info','circle-left','circle-minus','circle-nodes','circle-notch','circle-pause','circle-play','circle-plus','circle-question','circle-radiation','circle-right','circle-stop','circle-up','circle-user','circle-xmark','city','clapperboard','clipboard','clipboard-check','clipboard-list','clipboard-question','clipboard-user','clock','clock-rotate-left','clone','closed-captioning','cloud','cloud-arrow-down','cloud-arrow-up','cloud-bolt','cloud-meatball','cloud-moon','cloud-moon-rain','cloud-rain','cloud-showers-heavy','cloud-showers-water','cloud-sun','cloud-sun-rain','clover','code','code-branch','code-commit','code-compare','code-fork','code-merge','code-pull-request','coins','colon-sign','comment','comment-dollar','comment-dots','comment-medical','comment-slash','comment-sms','comments','comments-dollar','compact-disc','compass','compass-drafting','compress','computer','computer-mouse','cookie','cookie-bite','copy','copyright','couch','cow','credit-card','crop','crop-simple','cross','crosshairs','crow','crown','crutch','cruzeiro-sign','cube','cubes','cubes-stacked','d','database','delete-left','democrat','desktop','dharmachakra','diagram-next','diagram-predecessor','diagram-project','diagram-successor','diamond','diamond-turn-right','dice','dice-d20','dice-d6','dice-five','dice-four','dice-one','dice-six','dice-three','dice-two','disease','display','divide','dna','dog','dollar-sign','dolly','dong-sign','door-closed','door-open','dove','down-left-and-up-right-to-center','down-long','download','dragon','draw-polygon','droplet','droplet-slash','drum','drum-steelpan','drumstick-bite','dumbbell','dumpster','dumpster-fire','dungeon','e','ear-deaf','ear-listen','earth-africa','earth-americas','earth-asia','earth-europe','earth-oceania','egg','eject','elevator','ellipsis','ellipsis-vertical','envelope','envelope-circle-check','envelope-open','envelope-open-text','envelopes-bulk','equals','eraser','ethernet','euro-sign','exclamation','expand','explosion','eye','eye-dropper','eye-low-vision','eye-slash','f','face-angry','face-dizzy','face-flushed','face-frown','face-frown-open','face-grimace','face-grin','face-grin-beam','face-grin-beam-sweat','face-grin-hearts','face-grin-squint','face-grin-squint-tears','face-grin-stars','face-grin-tears','face-grin-tongue','face-grin-tongue-squint','face-grin-tongue-wink','face-grin-wide','face-grin-wink','face-kiss','face-kiss-beam','face-kiss-wink-heart','face-laugh','face-laugh-beam','face-laugh-squint','face-laugh-wink','face-meh','face-meh-blank','face-rolling-eyes','face-sad-cry','face-sad-tear','face-smile','face-smile-beam','face-smile-wink','face-surprise','face-tired','fan','faucet','faucet-drip','fax','feather','feather-pointed','ferry','file','file-arrow-down','file-arrow-up','file-audio','file-circle-check','file-circle-exclamation','file-circle-minus','file-circle-plus','file-circle-question','file-circle-xmark','file-code','file-contract','file-csv','file-excel','file-export','file-image','file-import','file-invoice','file-invoice-dollar','file-lines','file-medical','file-pdf','file-pen','file-powerpoint','file-prescription','file-shield','file-signature','file-video','file-waveform','file-word','file-zipper','fill','fill-drip','film','filter','filter-circle-dollar','filter-circle-xmark','fingerprint','fire','fire-burner','fire-extinguisher','fire-flame-curved','fire-flame-simple','fish','fish-fins','flag','flag-checkered','flag-usa','flask','flask-vial','floppy-disk','florin-sign','folder','folder-closed','folder-minus','folder-open','folder-plus','folder-tree','font','font-awesome','football','forward','forward-fast','forward-step','franc-sign','frog','futbol','g','gamepad','gas-pump','gauge','gauge-high','gauge-simple','gauge-simple-high','gavel','gear','gears','gem','genderless','ghost','gift','gifts','glass-water','glass-water-droplet','glasses','globe','golf-ball-tee','gopuram','graduation-cap','greater-than','greater-than-equal','grip','grip-lines','grip-lines-vertical','grip-vertical','group-arrows-rotate','guarani-sign','guitar','gun','h','hammer','hamsa','hand','hand-back-fist','hand-dots','hand-fist','hand-holding','hand-holding-dollar','hand-holding-droplet','hand-holding-hand','hand-holding-heart','hand-holding-medical','hand-lizard','hand-middle-finger','hand-peace','hand-point-down','hand-point-left','hand-point-right','hand-point-up','hand-pointer','hand-scissors','hand-sparkles','hand-spock','handcuffs','hands','hands-asl-interpreting','hands-bound','hands-bubbles','hands-clapping','hands-holding','hands-holding-child','hands-holding-circle','hands-praying','handshake','handshake-angle','handshake-simple','handshake-simple-slash','handshake-slash','hanukiah','hard-drive','hashtag','hat-cowboy','hat-cowboy-side','hat-wizard','head-side-cough','head-side-cough-slash','head-side-mask','head-side-virus','heading','headphones','headphones-simple','headset','heart','heart-circle-bolt','heart-circle-check','heart-circle-exclamation','heart-circle-minus','heart-circle-plus','heart-circle-xmark','heart-crack','heart-pulse','helicopter','helicopter-symbol','helmet-safety','helmet-un','highlighter','hill-avalanche','hill-rockslide','hippo','hockey-puck','holly-berry','horse','horse-head','hospital','hospital-user','hot-tub-person','hotdog','hotel','hourglass','hourglass-end','hourglass-half','hourglass-start','house','house-chimney','house-chimney-crack','house-chimney-medical','house-chimney-user','house-chimney-window','house-circle-check','house-circle-exclamation','house-circle-xmark','house-crack','house-fire','house-flag','house-flood-water','house-flood-water-circle-arrow-right','house-laptop','house-lock','house-medical','house-medical-circle-check','house-medical-circle-exclamation','house-medical-circle-xmark','house-medical-flag','house-signal','house-tsunami','house-user','hryvnia-sign','hurricane','i','i-cursor','ice-cream','icicles','icons','id-badge','id-card','id-card-clip','igloo','image','image-portrait','images','inbox','indent','indian-rupee-sign','industry','infinity','info','italic','j','jar','jar-wheat','jedi','jet-fighter','jet-fighter-up','joint','jug-detergent','k','kaaba','key','keyboard','khanda','kip-sign','kit-medical','kitchen-set','kiwi-bird','l','land-mine-on','landmark','landmark-dome','landmark-flag','language','laptop','laptop-code','laptop-file','laptop-medical','lari-sign','layer-group','leaf','left-long','left-right','lemon','less-than','less-than-equal','life-ring','lightbulb','lines-leaning','link','link-slash','lira-sign','list','list-check','list-ol','list-ul','litecoin-sign','location-arrow','location-crosshairs','location-dot','location-pin','location-pin-lock','lock','lock-open','locust','lungs','lungs-virus','m','magnet','magnifying-glass','magnifying-glass-arrow-right','magnifying-glass-chart','magnifying-glass-dollar','magnifying-glass-location','magnifying-glass-minus','magnifying-glass-plus','manat-sign','map','map-location','map-location-dot','map-pin','marker','mars','mars-and-venus','mars-and-venus-burst','mars-double','mars-stroke','mars-stroke-right','mars-stroke-up','martini-glass','martini-glass-citrus','martini-glass-empty','mask','mask-face','mask-ventilator','masks-theater','mattress-pillow','maximize','medal','memory','menorah','mercury','message','meteor','microchip','microphone','microphone-lines','microphone-lines-slash','microphone-slash','microscope','mill-sign','minimize','minus','mitten','mobile','mobile-button','mobile-retro','mobile-screen','mobile-screen-button','money-bill','money-bill-1','money-bill-1-wave','money-bill-transfer','money-bill-trend-up','money-bill-wave','money-bill-wheat','money-bills','money-check','money-check-dollar','monument','moon','mortar-pestle','mosque','mosquito','mosquito-net','motorcycle','mound','mountain','mountain-city','mountain-sun','mug-hot','mug-saucer','music','n','naira-sign','network-wired','neuter','newspaper','not-equal','notdef','note-sticky','notes-medical','o','object-group','object-ungroup','oil-can','oil-well','om','otter','outdent','p','pager','paint-roller','paintbrush','palette','pallet','panorama','paper-plane','paperclip','parachute-box','paragraph','passport','paste','pause','paw','peace','pen','pen-clip','pen-fancy','pen-nib','pen-ruler','pen-to-square','pencil','people-arrows','people-carry-box','people-group','people-line','people-pulling','people-robbery','people-roof','pepper-hot','percent','person','person-arrow-down-to-line','person-arrow-up-from-line','person-biking','person-booth','person-breastfeeding','person-burst','person-cane','person-chalkboard','person-circle-check','person-circle-exclamation','person-circle-minus','person-circle-plus','person-circle-question','person-circle-xmark','person-digging','person-dots-from-line','person-dress','person-dress-burst','person-drowning','person-falling','person-falling-burst','person-half-dress','person-harassing','person-hiking','person-military-pointing','person-military-rifle','person-military-to-person','person-praying','person-pregnant','person-rays','person-rifle','person-running','person-shelter','person-skating','person-skiing','person-skiing-nordic','person-snowboarding','person-swimming','person-through-window','person-walking','person-walking-arrow-loop-left','person-walking-arrow-right','person-walking-dashed-line-arrow-right','person-walking-luggage','person-walking-with-cane','peseta-sign','peso-sign','phone','phone-flip','phone-slash','phone-volume','photo-film','piggy-bank','pills','pizza-slice','place-of-worship','plane','plane-arrival','plane-circle-check','plane-circle-exclamation','plane-circle-xmark','plane-departure','plane-lock','plane-slash','plane-up','plant-wilt','plate-wheat','play','plug','plug-circle-bolt','plug-circle-check','plug-circle-exclamation','plug-circle-minus','plug-circle-plus','plug-circle-xmark','plus','plus-minus','podcast','poo','poo-storm','poop','power-off','prescription','prescription-bottle','prescription-bottle-medical','print','pump-medical','pump-soap','puzzle-piece','q','qrcode','question','quote-left','quote-right','r','radiation','radio','rainbow','ranking-star','receipt','record-vinyl','rectangle-ad','rectangle-list','rectangle-xmark','recycle','registered','repeat','reply','reply-all','republican','restroom','retweet','ribbon','right-from-bracket','right-left','right-long','right-to-bracket','ring','road','road-barrier','road-bridge','road-circle-check','road-circle-exclamation','road-circle-xmark','road-lock','road-spikes','robot','rocket','rotate','rotate-left','rotate-right','route','rss','ruble-sign','rug','ruler','ruler-combined','ruler-horizontal','ruler-vertical','rupee-sign','rupiah-sign','s','sack-dollar','sack-xmark','sailboat','satellite','satellite-dish','scale-balanced','scale-unbalanced','scale-unbalanced-flip','school','school-circle-check','school-circle-exclamation','school-circle-xmark','school-flag','school-lock','scissors','screwdriver','screwdriver-wrench','scroll','scroll-torah','sd-card','section','seedling','server','shapes','share','share-from-square','share-nodes','sheet-plastic','shekel-sign','shield','shield-cat','shield-dog','shield-halved','shield-heart','shield-virus','ship','shirt','shoe-prints','shop','shop-lock','shop-slash','shower','shrimp','shuffle','shuttle-space','sign-hanging','signal','signature','signs-post','sim-card','sink','sitemap','skull','skull-crossbones','slash','sleigh','sliders','smog','smoking','snowflake','snowman','snowplow','soap','socks','solar-panel','sort','sort-down','sort-up','spa','spaghetti-monster-flying','spell-check','spider','spinner','splotch','spoon','spray-can','spray-can-sparkles','square','square-arrow-up-right','square-caret-down','square-caret-left','square-caret-right','square-caret-up','square-check','square-envelope','square-full','square-h','square-minus','square-nfi','square-parking','square-pen','square-person-confined','square-phone','square-phone-flip','square-plus','square-poll-horizontal','square-poll-vertical','square-root-variable','square-rss','square-share-nodes','square-up-right','square-virus','square-xmark','staff-snake','stairs','stamp','stapler','star','star-and-crescent','star-half','star-half-stroke','star-of-david','star-of-life','sterling-sign','stethoscope','stop','stopwatch','stopwatch-20','store','store-slash','street-view','strikethrough','stroopwafel','subscript','suitcase','suitcase-medical','suitcase-rolling','sun','sun-plant-wilt','superscript','swatchbook','synagogue','syringe','t','table','table-cells','table-cells-large','table-columns','table-list','table-tennis-paddle-ball','tablet','tablet-button','tablet-screen-button','tablets','tachograph-digital','tag','tags','tape','tarp','tarp-droplet','taxi','teeth','teeth-open','temperature-arrow-down','temperature-arrow-up','temperature-empty','temperature-full','temperature-half','temperature-high','temperature-low','temperature-quarter','temperature-three-quarters','tenge-sign','tent','tent-arrow-down-to-line','tent-arrow-left-right','tent-arrow-turn-left','tent-arrows-down','tents','terminal','text-height','text-slash','text-width','thermometer','thumbs-down','thumbs-up','thumbtack','ticket','ticket-simple','timeline','toggle-off','toggle-on','toilet','toilet-paper','toilet-paper-slash','toilet-portable','toilets-portable','toolbox','tooth','torii-gate','tornado','tower-broadcast','tower-cell','tower-observation','tractor','trademark','traffic-light','trailer','train','train-subway','train-tram','transgender','trash','trash-arrow-up','trash-can','trash-can-arrow-up','tree','tree-city','triangle-exclamation','trophy','trowel','trowel-bricks','truck','truck-arrow-right','truck-droplet','truck-fast','truck-field','truck-field-un','truck-front','truck-medical','truck-monster','truck-moving','truck-pickup','truck-plane','truck-ramp-box','tty','turkish-lira-sign','turn-down','turn-up','tv','u','umbrella','umbrella-beach','underline','universal-access','unlock','unlock-keyhole','up-down','up-down-left-right','up-long','up-right-and-down-left-from-center','up-right-from-square','upload','user','user-astronaut','user-check','user-clock','user-doctor','user-gear','user-graduate','user-group','user-injured','user-large','user-large-slash','user-lock','user-minus','user-ninja','user-nurse','user-pen','user-plus','user-secret','user-shield','user-slash','user-tag','user-tie','user-xmark','users','users-between-lines','users-gear','users-line','users-rays','users-rectangle','users-slash','users-viewfinder','utensils','v','van-shuttle','vault','vector-square','venus','venus-double','venus-mars','vest','vest-patches','vial','vial-circle-check','vial-virus','vials','video','video-slash','vihara','virus','virus-covid','virus-covid-slash','virus-slash','viruses','voicemail','volcano','volleyball','volume-high','volume-low','volume-off','volume-xmark','vr-cardboard','w','walkie-talkie','wallet','wand-magic','wand-magic-sparkles','wand-sparkles','warehouse','water','water-ladder','wave-square','weight-hanging','weight-scale','wheat-awn','wheat-awn-circle-exclamation','wheelchair','wheelchair-move','whiskey-glass','wifi','wind','window-maximize','window-minimize','window-restore','wine-bottle','wine-glass','wine-glass-empty','won-sign','worm','wrench','x','x-ray','xmark','xmarks-lines','y','yen-sign','yin-yang','z'),
            'far' => array('address-book','address-card','bell-slash','bell','bookmark','building','calendar-check','calendar-days','calendar-minus','calendar-plus','calendar-xmark','calendar','chart-bar','chess-bishop','chess-king','chess-knight','chess-pawn','chess-queen','chess-rook','circle-check','circle-dot','circle-down','circle-left','circle-pause','circle-play','circle-question','circle-right','circle-stop','circle-up','circle-user','circle-xmark','circle','clipboard','clock','clone','closed-captioning','comment-dots','comment','comments','compass','copy','copyright','credit-card','envelope-open','envelope','eye-slash','eye','face-angry','face-dizzy','face-flushed','face-frown-open','face-frown','face-grimace','face-grin-beam-sweat','face-grin-beam','face-grin-hearts','face-grin-squint-tears','face-grin-squint','face-grin-stars','face-grin-tears','face-grin-tongue-squint','face-grin-tongue-wink','face-grin-tongue','face-grin-wide','face-grin-wink','face-grin','face-kiss-beam','face-kiss-wink-heart','face-kiss','face-laugh-beam','face-laugh-squint','face-laugh-wink','face-laugh','face-meh-blank','face-meh','face-rolling-eyes','face-sad-cry','face-sad-tear','face-smile-beam','face-smile-wink','face-smile','face-surprise','face-tired','file-audio','file-code','file-excel','file-image','file-lines','file-pdf','file-powerpoint','file-video','file-word','file-zipper','file','flag','floppy-disk','folder-closed','folder-open','folder','font-awesome','futbol','gem','hand-back-fist','hand-lizard','hand-peace','hand-point-down','hand-point-left','hand-point-right','hand-point-up','hand-pointer','hand-scissors','hand-spock','hand','handshake','hard-drive','heart','hospital','hourglass-half','hourglass','id-badge','id-card','image','images','keyboard','lemon','life-ring','lightbulb','map','message','money-bill-1','moon','newspaper','note-sticky','object-group','object-ungroup','paper-plane','paste','pen-to-square','rectangle-list','rectangle-xmark','registered','share-from-square','snowflake','square-caret-down','square-caret-left','square-caret-right','square-caret-up','square-check','square-full','square-minus','square-plus','square','star-half-stroke','star-half','star','sun','thumbs-down','thumbs-up','trash-can','user','window-maximize','window-minimize','window-restore'),
            'fab' => array('42-group','500px','accessible-icon','accusoft','adn','adversal','affiliatetheme','airbnb','algolia','alipay','amazon-pay','amazon','amilia','android','angellist','angrycreative','angular','app-store-ios','app-store','apper','apple-pay','apple','artstation','asymmetrik','atlassian','audible','autoprefixer','avianex','aviato','aws','bandcamp','battle-net','behance','bilibili','bimobject','bitbucket','bitcoin','bity','black-tie','blackberry','blogger-b','blogger','bluetooth-b','bluetooth','bootstrap','bots','brave-reverse','brave','btc','buffer','buromobelexperte','buy-n-large','buysellads','canadian-maple-leaf','cc-amazon-pay','cc-amex','cc-apple-pay','cc-diners-club','cc-discover','cc-jcb','cc-mastercard','cc-paypal','cc-stripe','cc-visa','centercode','centos','chrome','chromecast','cloudflare','cloudscale','cloudsmith','cloudversify','cmplid','codepen','codiepie','confluence','connectdevelop','contao','cotton-bureau','cpanel','creative-commons-by','creative-commons-nc-eu','creative-commons-nc-jp','creative-commons-nc','creative-commons-nd','creative-commons-pd-alt','creative-commons-pd','creative-commons-remix','creative-commons-sa','creative-commons-sampling-plus','creative-commons-sampling','creative-commons-share','creative-commons-zero','creative-commons','critical-role','css3-alt','css3','cuttlefish','d-and-d-beyond','d-and-d','dailymotion','dashcube','debian','deezer','delicious','deploydog','deskpro','dev','deviantart','dhl','diaspora','digg','digital-ocean','discord','discourse','dochub','docker','draft2digital','dribbble','dropbox','drupal','dyalog','earlybirds','ebay','edge-legacy','edge','elementor','ello','ember','empire','envira','erlang','ethereum','etsy','evernote','expeditedssl','facebook-f','facebook-messenger','facebook','fantasy-flight-games','fedex','fedora','figma','firefox-browser','firefox','first-order-alt','first-order','firstdraft','flickr','flipboard','fly','font-awesome','fonticons-fi','fonticons','fort-awesome-alt','fort-awesome','forumbee','foursquare','free-code-camp','freebsd','fulcrum','galactic-republic','galactic-senate','get-pocket','gg-circle','gg','git-alt','git','github-alt','github','gitkraken','gitlab','gitter','glide-g','glide','gofore','golang','goodreads-g','goodreads','google-drive','google-pay','google-play','google-plus-g','google-plus','google-scholar','google-wallet','google','gratipay','grav','gripfire','grunt','guilded','gulp','hacker-news','hackerrank','hashnode','hips','hire-a-helper','hive','hooli','hornbill','hotjar','houzz','html5','hubspot','ideal','imdb','instagram','instalod','intercom','internet-explorer','invision','ioxhost','itch-io','itunes-note','itunes','java','jedi-order','jenkins','jira','joget','joomla','js','jsfiddle','kaggle','keybase','keycdn','kickstarter-k','kickstarter','korvue','laravel','lastfm','leanpub','less','letterboxd','line','linkedin-in','linkedin','linode','linux','lyft','magento','mailchimp','mandalorian','markdown','mastodon','maxcdn','mdb','medapps','medium','medrt','meetup','megaport','mendeley','meta','microblog','microsoft','mintbit','mix','mixcloud','mixer','mizuni','modx','monero','napster','neos','nfc-directional','nfc-symbol','nimblr','node-js','node','npm','ns8','nutritionix','octopus-deploy','odnoklassniki','odysee','old-republic','opencart','openid','opensuse','opera','optin-monster','orcid','osi','padlet','page4','pagelines','palfed','patreon','paypal','perbyte','periscope','phabricator','phoenix-framework','phoenix-squadron','php','pied-piper-alt','pied-piper-hat','pied-piper-pp','pied-piper','pinterest-p','pinterest','pix','pixiv','playstation','product-hunt','pushed','python','qq','quinscape','quora','r-project','raspberry-pi','ravelry','react','reacteurope','readme','rebel','red-river','reddit-alien','reddit','redhat','renren','replyd','researchgate','resolving','rev','rocketchat','rockrms','rust','safari','salesforce','sass','schlix','screenpal','scribd','searchengin','sellcast','sellsy','servicestack','shirtsinbulk','shoelace','shopify','shopware','signal-messenger','simplybuilt','sistrix','sith','sitrox','sketch','skyatlas','skype','slack','slideshare','snapchat','soundcloud','sourcetree','space-awesome','speakap','speaker-deck','spotify','square-behance','square-dribbble','square-facebook','square-font-awesome-stroke','square-font-awesome','square-git','square-github','square-gitlab','square-google-plus','square-hacker-news','square-instagram','square-js','square-lastfm','square-letterboxd','square-odnoklassniki','square-pied-piper','square-pinterest','square-reddit','square-snapchat','square-steam','square-threads','square-tumblr','square-twitter','square-viadeo','square-vimeo','square-whatsapp','square-x-twitter','square-xing','square-youtube','squarespace','stack-exchange','stack-overflow','stackpath','staylinked','steam-symbol','steam','sticker-mule','strava','stripe-s','stripe','stubber','studiovinari','stumbleupon-circle','stumbleupon','superpowers','supple','suse','swift','symfony','teamspeak','telegram','tencent-weibo','the-red-yeti','themeco','themeisle','think-peaks','threads','tiktok','trade-federation','trello','tumblr','twitch','twitter','typo3','uber','ubuntu','uikit','umbraco','uncharted','uniregistry','unity','unsplash','untappd','ups','upwork','usb','usps','ussunnah','vaadin','viacoin','viadeo','viber','vimeo-v','vimeo','vine','vk','vnv','vuejs','watchman-monitoring','waze','webflow','weebly','weibo','weixin','whatsapp','whmcs','wikipedia-w','windows','wirsindhandwerk','wix','wizards-of-the-coast','wodu','wolf-pack-battalion','wordpress-simple','wordpress','wpbeginner','wpexplorer','wpforms','wpressr','x-twitter','xbox','xing','y-combinator','yahoo','yammer','yandex-international','yandex','yarn','yelp','yoast','youtube','zhihu')
        );
    }



    /**
     * Returns the default option.
     *
     * @since 1.0.0
     *
     * @return array Option initialized with version number, default settings, and contacts.
     */
    public static function default_option()
    {
        $option = array();

        $option['settings'] = Mobile_Contact_Bar_Settings::get_defaults();
        $option['contacts'] = Mobile_Contact_Bar_Contact_Sample::mcb_admin_add_contact();
        $option/* styles */ = Mobile_Contact_Bar_Option::pre_update_option( $option );

        return array(
            'settings' => $option['settings'],
            'contacts' => $option['contacts'],
            'styles'   => $option['styles'],
        );
    }



    /**
     * Updates version, repairs or creates plugin option.
     *
     * @since 2.0.0
     *
     * @param array $default_option Default option.
     */
    private static function update_plugin_options( $default_option )
    {
        $option = get_option( MOBILE_CONTACT_BAR__NAME );

        if( $option )
        {
            $damaged = false;

            // repair 'settings'
            foreach( $default_option['settings'] as $section_id => $section )
            {
                foreach( $section as $setting_id => $setting )
                {
                    if( ! isset( $option['settings'][$section_id][$setting_id] ))
                    {
                        $option['settings'][$section_id][$setting_id] = $setting;
                        $damaged = true;
                    }
                }
            }

            // repair 'styles'
            if( ! isset( $option['styles'] ) || ! $option['styles'] || $damaged )
            {
                $option = Mobile_Contact_Bar_Option::pre_update_option( $option );
            }
            update_option( MOBILE_CONTACT_BAR__NAME, $option );
        }
        else
        {
            add_option( MOBILE_CONTACT_BAR__NAME, $default_option );
        }

        update_option( MOBILE_CONTACT_BAR__NAME . '_version', MOBILE_CONTACT_BAR__VERSION );
    }
}
