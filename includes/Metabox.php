<?php

namespace LinkGenius;

class Metabox
{
    public function cmb2_render_callback_for_clicks( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
        ?><span id="clicks_label"><?php echo $escaped_value ?></span>
        <a href='#' id='reset_clicks'><?php _e("Reset Clicks", "linkgenius")?></a>
        <?php
    }

    public function cmb2_render_callback_for_link_locations( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
        ?>
        <a href='#' class="button-primary" id='search_link_locations'><?php _e("Search Link in Content", "linkgenius")?></a>
        <div id="link_locations"></div>
        <?php
    }

    public function cmb2_render_callback_free_custom_field($field, $escaped_value, $object_id, $object_type, $field_type_object ) {
    $options = $field_type_object->field->options();


    echo '<select ' . $field_type_object->concat_attrs( array( 'name' => $field_type_object->_name(), 'id' => $field_type_object->_id() ) ) . '>';

    foreach ( $options as $option_key => $option_value ) {
        $disabled = preg_match("/_pro$/", $option_key) ? ' disabled="disabled"' : '';
        $escaped_value = $field_type_object->field->escaped_value();
        echo '<option value="' . $option_key . '"' . selected( $field_type_object->field->escaped_value(), $option_key, false ) . $disabled . '>' . $option_value . '</option>';
    }

    echo '</select>';

    $field_type_object->_desc( true, true );
}


    

    public function sanitize_checkbox($value, $field_args, $field) {
        // Return 0 instead of false if null value given. Otherwise no value will be saved and default value will be applied
        return is_null($value) ? '0' : '1';
    }

    /**
     * Adds the check options to a field, if for_settings is true a checkbox is added, if false a select with options is added
     *
     * @param [type] $field
     * @param [type] $for_settings
     * @return void
     */
    protected function add_check_options($field, $for_settings) {
        if ($for_settings) {
            $additions = array(
                'type' => 'checkbox',
                'sanitization_cb'  => [$this, 'sanitize_checkbox'],
            );
            if (isset(Settings::$DEFAULTS['appearance'][$field['id']]))
                $additions['default'] = Settings::$DEFAULTS['appearance'][$field['id']];
            return $field + $additions;
        } else {
            $default = Settings::instance()->get_settings()[$field['id']];
            return $field + array(
                'type' => 'select', 'options' =>
                array(
                    'default'   => sprintf(__('Default (%s)', 'linkgenius'), $default ? __('Enabled', 'linkgenius') : __('Disabled', 'linkgenius')),
                    '1'         => __('Enabled', 'linkgenius'),
                    '0'         => __('Disabled', 'linkgenius')
                )
            );
        }
    }

    public function get_default($for_settings, $main_name, $option_name) {
        $settings = Settings::instance()->get_settings();
        return (!$for_settings && isset($settings[$option_name])) 
                    ? $settings[$option_name]
                    : (Settings::$DEFAULTS[$main_name][$option_name]??'');
    }

    public function get_general_fields($for_settings = false)
    {
        $settings = Settings::instance()->get_settings();
        $fields = [];
        $redirect_options = array(
            '301' => __('301 Permanent Redirect', 'linkgenius'),
            '302' => __('302 Temporary Redirect', 'linkgenius'),
            '307' => __('307 Temporary Redirect', 'linkgenius'),
        );
        $permalink = get_permalink(intval($_GET['post'] ?? 0));
        if (!$for_settings) {
            $redirect_options = array('default' => sprintf(__('Default (%s)', 'linkgenius'), $redirect_options[$settings['general_redirect_type']]??"")) + $redirect_options;
            $fields[] = array(
                'name' => __('Slug*', 'linkgenius'),
                'id'   => 'general_slug',
                'type' => 'text_small',
                'attributes' => array(
                    'required' => 'required'
                ),
                'desc' => __('Used for the URL you can link to.', 'linkgenius'),
                'after_field' => 
                    $permalink ?
                        sprintf(__('<p>LinkGenius Link URL: <a id="linkgenius_url" href="%1$s" target="_blank">%1$s</a><br><a href="#" id="copy_linkgenius_url">Copy Url</a></p>', 'linkgenius'),
                            $permalink)
                        : '',
                'sanitization_cb' => 'sanitize_title'
            );
            $fields[] = array(
                'name' => __('Target URL*', 'linkgenius'),
                'id'   => 'general_target_url',
                'type' => 'text',
                'attributes' => array (
                    'required' => 'required'
                ),
                'desc' => __('The target (affiliate) link.', 'linkgenius')
            );
            $fields[] = array(
                'name' => __('Order', 'linkgenius'),
                'id'   => 'general_order',
                'type' => 'text_small',
                'attributes' => array (
                    'required' => 'required',
                    'type' => 'number',
                    'data-default' => '0'
                ),
                "default" => "0",
                'desc' => __('The order for the link, used when displaying all links of a tag or category', 'linkgenius')
            );
        }
        else {
            $fields[] = array(
                'id'                => 'general_prefix',
                'name'              => __('Link Prefix', 'linkgenius'),
                'type'              => 'text',
                'default'           => Settings::$DEFAULTS['general']['general_prefix'] ?? "go",
                'sanitization_cb'   => 'sanitize_title',
                'desc'              => sprintf(__('The prefix for your link, for example <i>go, recommends, out, link, affiliate</i>. The link will look like <b>%1$sprefix/slug</b>.', 'linkgenius'), site_url('/'))
            );
            $fields[] = array(
                'id'                => 'general_role',
                'name'              => __('Mimimum Role Linkmanagement', 'linkgenius'),
                'type'              => 'select',
                'options'           => array( // keys are the introduced capabilities for that role
                    'manage_options'     => __('Administrator', 'linkgenius'),
                    'delete_pages'       => __('Editor', 'linkgenius'),
                    'publish_posts'      => __('Author', 'linkgenius'),
                    'edit_posts'         => __('Contributor', 'linkgenius'),
                    'read'               => __('Subscriber', 'linkgenius')
                ),
                'default'           => Settings::$DEFAULTS['general']['general_role'],
                'desc'              => __('The minimum role a user needs in order to create, edit or delete LinkGenius Links. The settings page will remain visible for administrators only.', 'linkgenius')
            );
            $fields[] = array(
                'id'   => 'general_defaults_title',
                'name' => __('Defaults', 'linkgenius'),
                'type' => 'title',
                'desc' => __('Intro default general setings', 'linkgenius')
            );
        }
        // redirect options            
        $fields[] = array(
            'name'    => __('Redirect Type', 'linkgenius'),
            'id'      => 'general_redirect_type',
            'type'    => 'select',
            'options' => $redirect_options
        );
        $check_options = $for_settings
            ? array('type' => 'checkbox', 'default' => Settings::$DEFAULTS['general']['general_uncloak'])
            : array(
                'type' => 'select', 'options' =>
                array(
                    'default'   => sprintf(__('Default (%s)', 'linkgenius'), $settings['general_uncloak'] ? __('Enabled', 'linkgenius') : __('Disabled', 'linkgenius')),
                    '1'         => __('Enabled', 'linkgenius'),
                    '0'         => __('Disabled', 'linkgenius')
                )
            );
        $fields[] = array(
            'name'  => __('No Branding', 'linkgenius'),
            'id'    => 'general_uncloak',
            'desc'  => __('When enabled affiliate url of LinkGenius Links will be outputted in content instead of the slug.', 'linkgenius')
        ) + $check_options;
        return $fields;
    }

    public function get_link_appearance_fields($for_settings = false)
    {
        $fields = array(
            array(
                'id'   => 'appearance_title',
                'type' => 'title',
                'desc' => __('Intro text appearance', 'linkgenius')
            ),
            array(
                'name' => ($for_settings ? __('Global CSS Classes', 'linkgenius') : __('CSS Classes', 'linkgenius')),
                'id'   => 'appearance_css_classes',
                'type' => 'text',
                'desc' => __('Comma separated list of CSS classes', 'linkgenius'),
                'attributes' => array (
                    'placeholder' => $this->get_default($for_settings, 'appearance', 'appearance_css_classes')
                )
            ),
            $this->add_check_options(array(
                'name' => __('Open in New Tab', 'linkgenius'),
                'id'   => 'appearance_new_tab',
                'desc' => __('Open the URL in a new tab when clicked. Done by adding target="_blank" tag.', 'linkgenius')
            ), $for_settings),
            $this->add_check_options(array(
                'name' => __('Parameter Forwarding', 'linkgenius'),
                'id'   => 'appearance_parameter_forwarding'
            ), $for_settings),
            $this->add_check_options(array(
                'name' => __('Sponsored Attribute', 'linkgenius'),
                'id'   => 'appearance_sponsored_attribute'
            ), $for_settings),
            $this->add_check_options(array(
                'name' => __('Nofollow Attribute', 'linkgenius'),
                'id'   => 'appearance_nofollow_attribute'
            ), $for_settings)
        );
        $rel_tags = array(
            'name' => ($for_settings ? __('Global Additional Rel Tags', 'linkgenius') : __('Additional Rel Tags', 'linkgenius')),
            'id'   => 'appearance_rel_tags',
            'type' => 'text',
            'desc' => __('Comma separated list of additional rel tags', 'linkgenius'),
            'attributes' => array (
                'placeholder' => $this->get_default($for_settings, 'appearance', 'appearance_rel_tags') 
            )
        );
        if($for_settings) {
            // insert at third position
            array_splice($fields, 2, 0, array(
                $rel_tags,
                array(
                    'id'   => 'appearance_default_title',
                    'type' => 'title',
                    'name' => __('Default Link appearance', 'linkgenius'),
                    'desc' => __('Default settings, can be overriden per individual link.', 'linkgenius')
                )
            ));
        }
        else {
            $fields[] = $rel_tags;
        }
        return $fields;
    }

    public function get_disclosure_fields($for_settings = false)
    {
        $defaults = Settings::$DEFAULTS['disclosure'];
        $type_options = array(
            'none'              => __('None', 'linkgenius'),
            'tooltip'           => __('Tooltip', 'linkgenius'),
            'linktext'          => __('Text After Link', 'linkgenius'),
            'content_statement' => __('Content Statement', 'linkgenius'));
        $fields = array(
            array(
                'id'   => 'disclosure_title',
                'type' => 'title',
                'desc' => __('Intro text disclosure', 'linkgenius'),
            )
        );
        if($for_settings) {
            $fields[] = array(
                'id'   => 'disclosure_defaults_title',
                'type' => 'title',
                'name' => __('Default disclosure settings', 'linkgenius'),
                'desc' => __('Default settings, can be overriden per individual link.', 'linkgenius')
            );
        }

        $fields[] = array(
            'name'    => __('Disclosure Type', 'linkgenius'),
            'id'      => 'disclosure_type',
            'type'    => 'select',
            'options' => ($for_settings ? $type_options : array(
                'default' => sprintf(__('Default (%s)', 'linkgenius'), 
                    $type_options[Settings::instance()->get_settings()['disclosure_type']]??"")
                ) + $type_options
            ),
            'default' => $for_settings ? $defaults['disclosure_type'] : 'default'
        );

        if ($for_settings) {
            $fields = array_merge($fields, array(
                array(
                    'name'  => __('Disclosure Tooltip', 'linkgenius'),
                    'id'    => 'disclosure_tooltip',
                    'type'  => 'text',
                    'desc'  => __('default_tooltip_desc', 'linkgenius'),
                    'default' => $defaults['disclosure_tooltip']
                ),
                array(
                    'name'  => __('Text After Link', 'linkgenius'),
                    'id'    => 'disclosure_text_after',
                    'type'  => 'text',
                    'desc'  => __('default_after_link_text_desc', 'linkgenius'),
                    'default' => $defaults['disclosure_text_after']
                ),
                array(
                    'id'   => 'disclosure_content_title',
                    'type' => 'title',
                    'name' => __('Content disclosure settings', 'linkgenius'),
                ),
                array(
                    'name'    => __('Content Disclosure Location', 'linkgenius'),
                    'id'      => 'disclosure_location',
                    'type'    => 'select',
                    'options' => array(
                        'bottom'            => __('End of Post', 'linkgenius'),
                        'top'               => __('Beginning of Post', 'linkgenius'),
                        'custom'            => __('Custom (Via Shortcode or Action)', 'linkgenius')
                    ),
                    'default'  => $defaults['disclosure_location']
                ),
                array(
                    'name'  => __('Content Disclosure Text', 'linkgenius'),
                    'id'    => 'disclosure_statement',
                    'type'  => 'textarea',
                    'default'  => $defaults['disclosure_statement']
                )
            ));
        } else {
            $fields = array_merge($fields, array(
                array(
                    'name'       => __('Disclosure Text', 'linkgenius'),
                    'id'         => 'disclosure_tooltip',
                    'type'       => 'text',
                    'attributes' => array(
                        'placeholder'             => sprintf(__('Default: %s', 'linkgenius'), $defaults['disclosure_tooltip']),
                        'data-conditional-id'     => 'disclosure_type',
                        'data-conditional-value'  => 'tooltip'
                    ),
                ),
                array(
                    'name'  => __('Text After Link', 'linkgenius'),
                    'id'    => 'disclosure_text_after',
                    'type'  => 'text',
                    'desc'  => __('after_link_text_desc', 'linkgenius'),
                    'attributes' => array(
                        'placeholder'             => sprintf(__('Default: %s', 'linkgenius'), $defaults['disclosure_text_after']),
                        'data-conditional-id'     => 'disclosure_type',
                        'data-conditional-value'  => 'linktext'
                    ),
                ),
            ));
        }
        return $fields;
    }

    public function get_analytics_fields($for_settings = false)
    {
        $fields = array();
        $settings = Settings::instance()->get_settings();
        if(!$for_settings) {
            $fields[] = array(
                'id'   => 'tracking_title',
                'type' => 'title',
                'desc' => __('Intro text GA tracking', 'linkgenius')
            );
        }
        $fields = array_merge($fields, array(
            $this->add_check_options(array(
                'name' => __('Enabled', 'linkgenius'),
                'id'   => 'tracking_enabled'
            ), $for_settings),
            array(
                'name' => __('Event Name', 'linkgenius'),
                'id'   => 'tracking_name',
                'type' => 'text',
                'attributes' => array(
                    'placeholder' => $this->get_default($for_settings, 'tracking', 'tracking_name')
                )
            ),
            array(
                'name'    => __('Event Parameters', 'linkgenius'),
                'id'      => 'tracking_parameters',
                'type'    => 'textarea_small',
                'attributes' => array(
                    'placeholder' => sprintf(__('Default:&#10;%s', 'linkgenius'), $this->get_default($for_settings, 'tracking', 'tracking_parameters'))
                ),
                'desc' => sprintf(__('You can use the variables %s', 'linkgenius'),
                    implode(', ', array_map(fn($v) => strstr($v, ':', true), explode(PHP_EOL, $settings['parameters_replace'])))
                )
            )
        ));
        return $fields;
    }

    public function get_autolink_fields($for_settings = false) {
        $fields = array();
        if($for_settings)
            return $fields;
        $fields = array_merge($fields, array(
            array(
                'id'      => 'autolink_title',
                'type'    => 'title',
                'desc'    => __('Intro text autolink', 'linkgenius')
            ),
            array(
                'name' => __('Order', 'linkgenius'),
                'id'   => 'autolink_order',
                'type' => 'text_small',
                    'attributes' => array (
                        'required' => 'required',
                        'type' => 'number',
                        'data-default' => '10'
                    ),
                "default" => "10",
                'desc' => __('A lower order means earlier execution when dealing with conflicting keywords or urls', 'linkgenius')
            ),
            array(
                'name' => __('Keywords', 'linkgenius'),
                'id'   => 'autolink_keywords',
                'type' => 'textarea_small',
                'desc' => __('Enter keywords that will automatically create a link to this LinkGenius link if they occur in the page or post content. One keyword per line, case-insensitive.', 'linkgenius')
            ),
            array(
                'name' => __('URLs', 'linkgenius'),
                'id'   => 'autolink_urls',
                'type' => 'textarea_small',
                'desc' => __('Enter URLs that will automatically be replaced with this LinkGenius link. One url per line.', 'linkgenius')
            ),
        ));
        return $fields;
    }

    public function get_link_locator_fields($for_settings = false) {
        $fields = array(
            array(
                'id'      => 'link_locator_title',
                'type'    => 'title',
                'desc'    => __('Intro link locator', 'linkgenius')
            ),
            array(
                // 'name' => __('Link Locations', 'linkgenius'),
                'id'   =>  'link_locations',
                'type' => 'link_locations',
                'default' => ''
            ),
        );

        return $fields;
    }

    public function get_expiration_fields($for_settings = false) {
        $fields = [];
        if($for_settings) {
            return $fields;
        }
        $fields = array_merge($fields, array(
            array(
                'id'      => 'expiration_title',
                'type'    => 'title',
                'desc'    => __('Intro text expiration', 'linkgenius')
            ),
            array(
                'name' => __('Expiration Date', 'linkgenius'),
                'id'   => 'expiration_date',
                'type' => 'text_datetime_timestamp',
                'desc' => __('Date after which link expires. (optional)', 'linkgenius')
            ),
            array(
                'name' => __('Expiration Clicks', 'linkgenius'),
                'id'   => 'expiration_clicks',
                'type' => 'text_small',
                'attributes' => array(
                    'type' => 'number',
                    'pattern' => '\d*',
                ),
                'sanitization_cb' => 'absint',
                'escape_cb'       => 'absint',
                'desc' => __('Number of clicks after which the link expires. (optional)', 'linkgenius')
            ),
            array(
                'name' => __('Current Clicks', 'linkgenius'),
                'id'   =>  'clicks',
                'type' => 'clicks',
                'default' => 0
            ),
            array(
                'name' => __('Redirect After Expiry', 'linkgenius'),
                'id'   => 'redirect_after_expiry',
                'type' => 'text_url',
                'desc' => __('The url to redirect to after link expired (used only if date or clicks are set).', 'linkgenius'),
                'default' => '/',
                'attributes' => array(
                    'required' => 'required'
                )
            ),
        ));
        return $fields;
    }

    public function get_custom_fields($for_settings = false) {
        $field_utils = Fields::instance();
        $fields = [];
        $fields[] = array(
            'id'      => 'custom_fields_title',
            'type'    => 'title',
            'desc'    => __('Intro text custom fields', 'linkgenius')
        );
        if($for_settings) {
            $fields[] = array(
                'id'      => 'custom_free_type',
                'name'    => __('Free Custom Field Type', 'linkgenius'),
                'type'    => 'custom_free_type',
                'options' => $field_utils->get_field_types(),
                'default' => $this->get_default($for_settings, 'fields', 'custom_free_type')
            );
            $fields[] = array_merge(
                array (
                    'id' => 'custom_free_default',
                    'name' => __('Free Custom Field Default', 'linkgenius')
                ), 
                $field_utils->get_cmb2_type_custom_field(Settings::instance()->get_settings()['custom_free_type'])
            );
            return $fields;
        }
        else {
            $custom_fields = $field_utils->get_custom_fields();
            foreach($custom_fields as $field) {
                $fields[] = array_merge(
                    array(
                        'name' => $field['name'],
                        'id'   => "custom_{$field['slug']}",
                        'desc' => '',
                        'attributes' => array(
                            'placeholder' => $field['default']??''
                        ),
                        // 'after_field' => "<span class=\"cmb2-metabox-description\">".sprintf(__('Reference by {{lg_custom_%s}}', 'linkgenius_pro'), $field['slug'])."</span>"
                    ),
                    $field_utils->get_cmb2_type_custom_field($field['type'])
                );
            }
        }
        return $fields;
    }
}
add_action( 'cmb2_render_clicks', [new Metabox(), 'cmb2_render_callback_for_clicks'], 10, 5 );
add_action( 'cmb2_render_link_locations', [new Metabox(), 'cmb2_render_callback_for_link_locations'], 10, 5 );
add_action( 'cmb2_render_custom_free_type', [new Metabox(), 'cmb2_render_callback_free_custom_field'], 10, 5 );