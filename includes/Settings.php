<?php

namespace LinkGenius;

use function HFG\setting;

class Settings
{
    public static $DEFAULTS;

    private function __construct()
    {
        add_action('cmb2_admin_init', [$this, 'linkgenius_options_metabox']);
        add_action('admin_menu', [$this, 'modify_admin_menu'], 100);
        add_action('update_option_'.LINKGENIUS_OPTIONS_PREFIX.'_general', [$this, 'maybe_flush_rewrite_rules'], 10, 2); 
    }

    function modify_admin_menu()
    {
        global $submenu;

        // Remove the Settings_hidden submenu items
        if (isset($submenu[LINKGENIUS_POST_TYPE_SLUG])) {
            foreach ($submenu[LINKGENIUS_POST_TYPE_SLUG] as $key => $item) {
                if ('Settings_hidden' === $item[0]) {
                    unset($submenu[LINKGENIUS_POST_TYPE_SLUG][$key]);
                }
            }
        }

        // Highlight the Settings submenu item when on one of the settings pages
        if ( // Is this a settings page
            isset($_GET['page']) && str_starts_with($_GET['page'], LINKGENIUS_OPTIONS_PREFIX)
            && isset($_GET['post_type']) && $_GET['post_type'] === LINKGENIUS_TYPE_LINK
        ) {
            foreach ($submenu[LINKGENIUS_POST_TYPE_SLUG] as $key => $item) {
                if ('Settings' === $item[0]) {
                    $submenu[LINKGENIUS_POST_TYPE_SLUG][$key][4] = ($submenu[LINKGENIUS_POST_TYPE_SLUG][$key][4] ?? '') . ' current';
                    break;
                }
            }
            // enqueue the js for conditinals also
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        }
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('cmb2-conditionals', plugins_url('../vendor/jcchavezs/cmb2-conditionals/cmb2-conditionals.js', __FILE__), array('jquery'), '1.0.2', true);
        wp_enqueue_script('linkgenius-settings', plugins_url('../assets/js/linkgenius-settings.js', __FILE__), array('jquery'), '1.0.0', true);
        $fieldsPrototypeMetabox = new_cmb2_box(array(
            'id' => 'linkgenius_prototypes',
            'object_types' => array('options-page'),
        ));
        $prototypes = [];
        foreach(['singleline', 'multiline', 'image'] as $type) {
            $id = $fieldsPrototypeMetabox->add_field(
                array_merge(array(
                    'id' => 'custom_free_default',
                    'name' => __('Free Custom Field Default', 'linkgenius'),
                ), Fields::instance()->get_cmb2_type_custom_field($type) 
            ));
            $field = $fieldsPrototypeMetabox->get_field($id);
            ob_start();
            $field->render_field();
            $prototypes[$type] = ob_get_clean();
            $fieldsPrototypeMetabox->remove_field($id);
        }
        wp_localize_script(
            'linkgenius-settings',
            'linkgenius_prototypes',
            $prototypes
        );
    }

    /**
     * Hook in and register a metabox to handle a theme options page and adds a menu item.
     */
    function linkgenius_options_metabox()
    {
        $metabox = new Metabox();

        /**
         * Registers default options page menu item and form.
         */
        $args = array(
            'id'            => LINKGENIUS_OPTIONS_PREFIX . '_general',
            'title'         => __('Settings', 'linkgenius'),
            'menu_title'    => __('Settings', 'linkgenius'),
            'object_types'  => array('options-page'),
            'option_key'    => LINKGENIUS_OPTIONS_PREFIX . '_general',
            'tab_group'     => LINKGENIUS_OPTIONS_TAB_GROUP,
            'tab_title'     => __('General', 'linkgenius'),
            'parent_slug'   => LINKGENIUS_POST_TYPE_SLUG,
        );
        $general_options = new_cmb2_box($args);
        $fields = $metabox->get_general_fields(true);
        foreach ($fields as $field) {
            $general_options->add_field($field);
        }
        $general_options = apply_filters('linkgenius_links_settings_metabox', $general_options);

        /**
         * Registers disclosure options page, and set main item as parent.
         */
        $args = array(
            'id'           => LINKGENIUS_OPTIONS_PREFIX . '_appearance',
            'title'        => __('Appearance', 'linkgenius'),
            'menu_title'   => 'Settings_hidden', // Use menu title, & not title to hide main h2.
            'object_types' => array('options-page'),
            'option_key'   => LINKGENIUS_OPTIONS_PREFIX . '_appearance',
            'parent_slug'  => LINKGENIUS_POST_TYPE_SLUG,
            'tab_group'    => LINKGENIUS_OPTIONS_TAB_GROUP,
            'tab_title'    => __('Appearance', 'linkgenius'),
        );
        $appearance_options = new_cmb2_box($args);
        $fields = $metabox->get_link_appearance_fields(true);
        foreach ($fields as $field) {
            $appearance_options->add_field($field);
        }
        $appearance_options = apply_filters('linkgenius_links_settings_metabox', $appearance_options);


        /**
         * Registers disclosure options page, and set main item as parent.
         */
        $args = array(
            'id'           => LINKGENIUS_OPTIONS_PREFIX . '_disclosure',
            'title'        => __('Disclosure', 'linkgenius'),
            'menu_title'   => 'Settings_hidden', // Use menu title, & not title to hide main h2.
            'object_types' => array('options-page'),
            'option_key'   => LINKGENIUS_OPTIONS_PREFIX . '_disclosure',
            'parent_slug'  => LINKGENIUS_POST_TYPE_SLUG,
            'tab_group'    => LINKGENIUS_OPTIONS_TAB_GROUP,
            'tab_title'    => __('Disclosure', 'linkgenius'),
        );
        $disclosure_options = new_cmb2_box($args);
        $fields = $metabox->get_disclosure_fields(true);
        foreach ($fields as $field) {
            $disclosure_options->add_field($field);
        }
        $disclosure_options = apply_filters('linkgenius_links_settings_metabox', $disclosure_options);

        /**
         * Registers Custom Fields options page, and set main item as parent.
         */
        $args = array(
            'id'           => LINKGENIUS_OPTIONS_PREFIX . '_fields',
            'title'        => __('Custom Fields', 'linkgenius'),
            'menu_title'   => 'Settings_hidden', // Use menu title, & not title to hide main h2.
            'object_types' => array('options-page'),
            'option_key'   => LINKGENIUS_OPTIONS_PREFIX . '_fields',
            'parent_slug'  => LINKGENIUS_POST_TYPE_SLUG,
            'tab_group'    => LINKGENIUS_OPTIONS_TAB_GROUP,
            'tab_title'    => __('Custom Fields', 'linkgenius'),
        );

        $fields_options = new_cmb2_box($args);
        $fields = $metabox->get_custom_fields(true);
        foreach ($fields as $field) {
            $fields_options->add_field($field);
        }
        $fields_options = apply_filters('linkgenius_links_settings_metabox', $fields_options);
    }

    public static function instance()
    {
        static $instance = null;
        if($instance == null) {
            $instance = new static();
        }
        return $instance;
    }

    /**
     * @return array the array containing the settings
     *   - 'general': An array of general configuration options.
     *     - 'general_prefix': The prefix to use for general settings.
     *     - 'general_redirect_type': The redirect type to use.
     *     - 'general_uncloak': Whether or not to uncloak links.
     *   - 'appearance': An array of appearance configuration options.
     *     - 'appearance_css_classes': The CSS classes to apply.
     *     - 'appearance_new_tab': Whether or not to open links in a new tab.
     *     - 'appearance_parameter_forwarding': Whether or not to forward parameters.
     *     - 'appearance_sponsored_attribute': Whether or not to add a sponsored attribute.
     *     - 'appearance_nofollow_attribute': Whether or not to add a nofollow attribute.
     *     - 'appearance_rel_tags': The rel tags to apply.
     *   - 'disclosure': An array of disclosure configuration options.
     *     - 'disclosure_type': The type of disclosure to use.
     *     - 'disclosure_tooltip': The tooltip text to display.
     *     - 'disclosure_text_after': The text to append.
     *     - 'disclosure_location': The location of the disclosure text.
     *     - 'disclosure_statement': The disclosure statement to use.
     *  - 'fields': An array of field configuration options.
     *     - 'custom_free_type': The type of custom field to use.
     *     - 'custom_free_default': The default value for the custom field.
     *   - 'tracking': An array of tracking configuration options.
     *     - 'tracking_enabled': Whether or not tracking is enabled.
     *     - 'tracking_name': The name of the tracking cookie.
     *     - 'tracking_parameters': The parameters to use for tracking.
     *  - 'parameters': An array of parameter configuration options.
     *     - 'parameters_enabled': Whether or not parameters are enabled.
     *     - 'parameters_replace': The parameters to replace.
     * 
     */
    public function get_settings()
    {
        static $options = null;
        if ($options === null) {
            $options = [];
            $def = self::$DEFAULTS;
            foreach (self::$DEFAULTS as $option_name => $option_defaults) {
                $real_options = get_option(LINKGENIUS_OPTIONS_PREFIX . "_" . $option_name, $option_defaults);
                $options = array_merge($option_defaults, $options, $real_options);
                foreach ($option_defaults as $k => $v) {
                    if (is_bool($option_defaults[$k])) {
                        if (!isset($real_options[$k])) {
                            $options[$k] = false;
                        } else if ($options[$k] === 'on') {
                            $options[$k] = true;
                        }
                    }
                }
            }
        }
        return $options;
    }

    public function maybe_flush_rewrite_rules($old_val, $new_val) {
        if(($old_val['general_prefix'] ?? "") !== $new_val['general_prefix']) {
            add_option('linkgenius_should_flush', true);
        }
    }
}
Settings::$DEFAULTS = [
    'general' => [
        'general_prefix' => "out",
        'general_role' => 'manage_options',
        'general_redirect_type' => '301',
        'general_uncloak' => false,
    ],
    'appearance' => [
        'appearance_css_classes' => '',
        'appearance_new_tab' => true,
        'appearance_parameter_forwarding' => false,
        'appearance_sponsored_attribute' => true,
        'appearance_nofollow_attribute' => true,
        'appearance_rel_tags' => '',
    ],
    'disclosure' => [
        'disclosure_type'  => 'none',
        'disclosure_tooltip' => __('Affiliate Link', 'linkgenius'),
        'disclosure_text_after' => __(' (Affiliate Link)', 'linkgenius'),
        'disclosure_location' =>  'bottom',
        'disclosure_statement' => __('default_content_disclosure_text', 'linkgenius'),
    ],
    'fields' => [
        'custom_free_type' =>  'multiline',
        'custom_free_default' => '',
    ],
    'tracking' => [
        'tracking_enabled' => '0',
        'tracking_name' => 'linkgenius',
        'tracking_parameters' => "slug: {link_slug}\r\nreferrer: {referrer}"
    ],
    'parameters' => [
        'parameters_enabled' => false,
        "parameters_replace" => implode(PHP_EOL, [
            "{referrer}: referrer",
            "{client_id}: client_id",
            "{categories}: categories",
            "{tags}: tags",
            "{target_url}: target_url",
            "{link_slug}: link_slug",
            "{link_id}: link_id",
            "{COOKIE[(.*?)]}: cookie_value",
            "{GET[(.*?)]}: get_value",
            "{SESSION[(.*?)]}: session_value",
        ])
        ],
];