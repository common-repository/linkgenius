<?php
namespace LinkGenius;

/**
 * Class responsible for building links and enqueing the correct styles based on the link settings.
 */
class LinkBuilder {
    private $has_tooltip = false;
    private function __construct()
    {
        add_action("wp_enqueue_scripts", array($this, "maybe_enqueue_styles"));    
    }

    function maybe_enqueue_styles() {
        if($this->has_tooltip) {
            wp_enqueue_style('linkgenius-tooltip', plugin_dir_url(__FILE__).'../assets/css/tooltip.css');
        }
    }

    public function get_link($link_id, $text, $additional_attributes = []) {

        [
          "attributes" => $attributes,
          "after_text" => $after_text,
          "after_output" => $after_output
        ] = $this->get_link_data($link_id);
        // Output the link
        $attributes = array_merge($additional_attributes??[], $attributes??[]);
        $output = array_reduce(array_keys($attributes??[]), fn($carry, $k) => $carry . " ".$k . "='". $attributes[$k]."'", "<a")
            .">".$text.$after_text."</a>".$after_output;

        

        return $output;
    }

    public function get_link_data($link_id) {
        // Retrieve all metadata for the link
        $link_metadata = get_post_meta($link_id);
        if($link_metadata === null || !isset($link_metadata['general_target_url'][0])) {
          return null;
        }

        $link_metadata = array_map(fn($v) => $v[0], $link_metadata);
        $link_metadata = apply_filters('linkgenius_link_metadata', $link_metadata, $link_id);

        // Retrieve global settings
        $settings = Settings::instance()->get_settings();
        
        
        $attributes = array(
          "href" => $this->get_url($link_id)
        );
        if($this->is_meta_enabled($link_id, 'appearance_new_tab')) {
            $attributes['target'] = "_blank";
        }
        $rel_tags = trim($settings['appearance_rel_tags']);
        if($this->is_meta_enabled($link_id, 'appearance_sponsored_attribute')) {
          $rel_tags .= " sponsored";
        }
        if($this->is_meta_enabled($link_id, 'appearance_nofollow_attribute')) {
          $rel_tags .= " nofollow";
        }
        $rel_tags .= ' '.trim($link_metadata['appearance_rel_tags']??"");
        $rel_tags = esc_attr(trim($rel_tags));
        if(!empty($rel_tags)) {
          $attributes['rel'] = $rel_tags;
        }
        $classes = esc_attr(trim(trim($settings['appearance_css_classes']).' '.trim($link_metadata['appearance_css_classes']??"")));
        if(!empty($classes)) {
          $attributes['class'] = $classes;
        }
        $attributes = array_merge($attributes, $link_metadata['atts']??[]);
        
        $link_metadata += $settings; // apply defaults

        $after_output = "";
        $after_text = "";
        if ($link_metadata['disclosure_type'] === 'tooltip') {
          $attributes['class'] = trim(($attributes['classes'] ?? '')." linkgenius-tooltip");
          $after_text .= ""
              ."<span class='linkgenius-tooltiptext'>"
              . ($link_metadata['disclosure_tooltip'] ?? $settings['disclosure_tooltip'])
              ."</span>";
          $this->has_tooltip = true;
        }
        else if($link_metadata['disclosure_type'] === 'linktext') {
          $after_output .= $link_metadata['disclosure_text_after'] ?? $settings['disclosure_text_after'];
        }
        else if($link_metadata['disclosure_type'] === 'content_statement') {
            Discloser::instance()->add_disclosure();
        }

        return array(
          'attributes' => $attributes,
          'after_text' => $after_text,
          'after_output' => $after_output
        );
    }

    public function is_meta_enabled($link_id, $key) {
        $settings = Settings::instance()->get_settings();
        $meta_value = get_post_meta($link_id, $key, true);
        return ($meta_value === 'default' ? $settings[$key] : ($meta_value === '1'));
    }

    public function get_url($link_id) {
        return esc_url($this->is_meta_enabled($link_id, 'general_uncloak') 
            ? get_post_meta($link_id, 'general_target_url', true) 
            : get_permalink($link_id));
    }

    public static function instance() {
        $instance = null;
        if($instance == null) {
            $instance = new static();
        }
        return $instance;
    }
}