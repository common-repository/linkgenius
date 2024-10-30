<?php
namespace LinkGenius;

class Shortcode {
    function __construct()
    {
        // for disclosure shortcode see Disclosure.php
        add_shortcode('linkgenius-link', array($this, 'linkgenius_link_shortcode'));
        add_shortcode('linkgenius-list', array($this, 'linkgenius_list_shortcode'));
    }

    function linkgenius_link_shortcode($atts, $content) {
        $atts = shortcode_atts(array(
          'id' => '',
          'extra_atts' => ''
        ), $atts);
        
        $link_id = intval($atts['id']);
        $extra_atts = shortcode_parse_atts(htmlspecialchars_decode($atts['extra_atts']));
        return LinkBuilder::instance()->get_link($link_id, $content, $extra_atts) ?? "Link not found";
    }
    
    function linkgenius_list_shortcode($atts, $content) {
        $atts = shortcode_atts(array(
          'category' => '',
          'tag' => '',
          'sort'  => 'order',
          'blocks' => false
        ), $atts);
        if(empty($content)) {
          $content = ", ";
        }
        /**
         * @var \WP_Post[] $links
         */
        $links = [];
        if(!empty($atts['category']) || !empty($atts['tag'])) {
            $taxonomy_type = !empty($atts['category']) ? 'category' : 'tag';
            $args = array(
              'post_type' => LINKGENIUS_TYPE_LINK,
              'tax_query' => array(
                array(
                  'taxonomy' => $taxonomy_type === 'category' ? LINKGENIUS_TYPE_CATEGORY : LINKGENIUS_TYPE_TAG,
                  'field' => 'slug',
                  'terms' => $atts[$taxonomy_type]
                )
              ),
              'posts_per_page' => -1
            );
            if($atts['sort'] === 'title') {
              $args['orderby'] = 'title';
              $args['order'] = 'ASC';
            }
            else {
              $args['meta_key'] = 'general_order';  // Meta key for sorting
              $args['orderby'] = 'meta_value_num';  // Sort by meta value as numeric
              $args['order'] = 'ASC';  // Order in ascending order
            }
            $links = get_posts($args);
        }
        else {
          return __("You must specify a category or tag", 'linkgenius');
        }

        $matches = []; 
        $output = '';
        $content = str_replace(['&#8217;', '&#8221;'], ['\'', '"'], wp_specialchars_decode($content));        
        if($atts['blocks'] !== 'true') { // Manual template
          if(preg_match("/(?<prelist>.*){links}(?<innerlist>.*?){\/links}(?<postlist>.*)/us",$content, $matches)) {
            $prelist = $matches['prelist'];
            $innerlist = $matches['innerlist'];
            $postlist = $matches['postlist'];
            $output = $prelist;
            $replacements = Fields::instance()->get_fields();
            $replacements['{link}'] = $replacements['{{lg_link}}'];
            foreach($links as $link) {
              $output .= str_replace(array_keys($replacements), array_map(fn($field) => $field['callback']($link->ID), $replacements), $innerlist);
            }
            $output .= $postlist;
          }
          else {
            $output = implode($content, array_map(fn($l) => LinkBuilder::instance()->get_link($l->ID, $l->post_title), $links));
          }
          return $output;
        }
        else { // Block Template
           $replacements = Fields::instance()->get_fields();
           return preg_replace_callback("/<linkgenius-links[^>]+>(.*?)<\/linkgenius-Links>/sui", function($matches) use ($links, $replacements) {
              $output = '';
              foreach($links as $link) {
                $output .= str_replace(array_keys($replacements), array_map(fn($field) => $field['callback']($link->ID), $replacements), $matches[1]);
              }
              return $output;
           }, $content);
        }
    }
}