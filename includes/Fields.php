<?php
namespace LinkGenius{
    class Fields {
        protected $fields = [];
        protected $custom_fields = [];
        protected $field_types = [];

        private function __construct() {
            $this->register_field_types();
            $this->register_fields();
        }
        public static function instance() {
            static $instance = null;
            if($instance == null) {
                $instance = new static();
            }
            return $instance;
        }

        protected function register_field_types() {
            $this->field_types = apply_filters('linkgenius_custom_field_types', array(
                'singleline' => __('Single Line', 'linkgenius'),
                'multiline'  => __('Multi Line', 'linkgenius'),
                'image'  => __('Image', 'linkgenius'),
                'color_pro'  => __('Color (Pro)', 'linkgenius'),
                'date_pro'  => __('Date (Pro)', 'linkgenius'),
                'file_pro'  => __('File (Pro)', 'linkgenius'),
                'number_pro'  => __('Number (Pro)', 'linkgenius'),
                'url_pro'        => __('URL (Pro)', 'linkgenius'),
                'wysiwyg_pro' => __('WYSIWYG (Pro)', 'linkgenius'),
            ));
        }

        protected function register_fields() {
            $this->fields = array(
                '{{lg_link}}' => array(
                    'name' => __('Link', 'linkgenius'),
                    'callback' => fn($id) => LinkBuilder::instance()->get_link($id, get_post($id)->post_title),
                ),
                '{{lg_id}}' => array(
                    'name' => __('ID', 'linkgenius'),
                    'callback' => fn($id) => $id,
                ),
                '{{lg_title}}' => array(
                    'name' => __('Title', 'linkgenius'),
                    'callback' => fn($id) => get_post($id)->post_title,
                ),
                '{{lg_url}}' => array(
                    'name' => __('URL', 'linkgenius'),
                    'callback' => fn($id) => get_permalink($id),
                ),
                '{{lg_slug}}' => array(
                    'name' => __('Slug', 'linkgenius'),
                    'callback' => fn($id) => get_post_meta($id, 'general_slug', true)??'',
                ),
                '{{lg_target_url}}' => array(
                    'name' => __('Target URL', 'linkgenius'),
                    'callback' => fn($id) => esc_url(get_post_meta($id, 'general_target_url', true)),
                ),
             );
             $this->custom_fields = apply_filters('linkgenius_custom_fields', array(
                array(
                    'slug' => 'free',
                    'name' => 'Free Custom Field',
                    'type' => Settings::instance()->get_settings()['custom_free_type'],
                    'default' => Settings::instance()->get_settings()['custom_free_default'],
                )
             ));
             foreach($this->custom_fields as $field) {
                $this->fields["{{lg_custom_{$field['slug']}}}"] = array(
                    'name' => __('Custom: ', 'linkgenius').$field['name'],
                    'callback' => function($id) use($field) {
                        $value = get_post_meta($id, "custom_{$field['slug']}");
                        $value = empty($value)? ($field['default']??"") : $value[0];
                        return $this->display_custom_field($field['type'], $value);
                    },
                );    
            }
        }

        protected function display_custom_field($type, $value) {
            switch($type) {
                case 'multiline':
                case 'singleline':
                    return wp_kses($value, 'post');
                    break;
                case 'image':
                    return "<img src=\"{$value}\" />";
                    break;
                default:
                    return wp_kses(apply_filters('linkgenius_display_custom_field', $value, $type), 'post');
                    break;
            }
        }

        public function get_cmb2_type_custom_field($type) {
            switch($type) {
                case 'multiline':
                    return array('type' => 'textarea_small');
                    break;
                case 'singleline':
                    return array('type' => 'text');
                    break;
                case 'image':
                    return array(
                        'type' => 'file',
                        'query_args' => array( 'type' => 'image' )
                    );
                    break;
                default:
                    return apply_filters('linkgenius_cmb2_type_custom_field', ['type' => $type]);
                    break;
            }
        }

        public function get_fields() {
            return $this->fields;
        }

        public function get_custom_fields() {
            return $this->custom_fields;
        }

        public function get_field_types() {
            return $this->field_types;
        }
    }
}