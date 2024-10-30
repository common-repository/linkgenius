<?php
namespace LinkGenius;

use WP_Query;

class Editor {
    function __construct()
    {
        add_filter( 'block_categories_all', [$this, 'add_linkgenius_link_block_category']);
        // enqueue scripts and styles for the editor
        add_action('enqueue_block_editor_assets', array($this, 'my_plugin_enqueue_assets'));
        // ajax calls
        add_action('wp_ajax_search_linkgenius_links', array($this, 'search_linkgenius_links'));
        add_action('wp_ajax_get_linkgenius_link', array($this, 'get_linkgenius_link'));
        add_action('wp_ajax_preview_linkgenius_taxonomy', array($this, 'preview_linkgenius_taxonomy'));
        // filter
        add_filter('the_content', array($this, 'replace_linkgenius_tag_with_link'));
        add_filter('wp_kses_allowed_html', array($this, 'custom_allowed_html_tags'));
    }

    function my_plugin_enqueue_assets() {
        $asset_dir = dirname(__FILE__, 2).'/assets/js/editor/editor.asset.php';
        $reqs = require ($asset_dir);
        wp_enqueue_script(
          'linkgenius-link-editor-script',
          plugins_url('../assets/js/editor/editor.js', __FILE__),
          $reqs['dependencies']
        );
        $fields =  Fields::instance()->get_fields();
        wp_localize_script(
            'linkgenius-link-editor-script',
            'linkgenius_editor_data',
            array(
                'allowed_placeholders' => array_map(
                    fn($key, $value) => array('value' => preg_replace('/{{lg_(.*?)}}/', '$1', $key), 'label' => $value['name']),
                    array_keys($fields), $fields),
            )
        );
        wp_enqueue_style(
            'linkgenius-editor-css',
            plugins_url('../assets/css/linkgenius-editor.css', __FILE__)
        );
    }

    function add_linkgenius_link_block_category ($categories) {
        return array_merge(
            array(
                array(
                    'slug' => 'linkgenius',
                    'title' => 'LinkGenius Links',
                ),
            ),
            $categories
        );
    }
    
    //
    // AJAX CALLS
    //
    public function get_linkgenius_link() {
        $post = null;
        if (! defined('DOING_AJAX') || ! DOING_AJAX) {
            wp_send_json_error(__('Invalid AJAX call'));
        }
        elseif( isset( $_GET[ 'linkgenius_id' ] ) ){
            $id = intval(wp_unslash($_GET['linkgenius_id']));
            $post = get_post($id);
            if($post === null || $post->post_type != LINKGENIUS_TYPE_LINK) {
                wp_send_json_error( __( 'Invalid Id' ) );
            }
        }
        elseif( isset( $_GET['linkgenius_url'])) {
            $post = get_post(url_to_postid(esc_url_raw($_GET['linkgenius_url'])));
            if($post === null || $post->post_type != LINKGENIUS_TYPE_LINK) {
                wp_send_json_error( __( 'Invalid URL' ) );
            }
        }
        else {
            wp_send_json_error( __( 'Missing required post data' ) );
        }


        if($post == null || $post->post_type != LINKGENIUS_TYPE_LINK) {
            wp_send_json_error( __( 'Invalid Post Parameters' ) );
        }
        wp_send_json_success(array(
            "id" => $post->ID,
            "title" => $post->post_title,
            "url" => get_permalink($post),
            "target_url" => esc_url(get_post_meta($post->ID, 'general_target_url', true))
        ));
    }

    public function search_linkgenius_links() {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ){
            wp_send_json_error(__('Invalid AJAX call'));
        }
        elseif ( ! isset( $_GET[ 'keyword' ] ) ) {
            wp_send_json_error( __( 'Missing required post data' ));
        }
        else {
            $keyword = sanitize_text_field( wp_unslash($_GET['keyword'] ));
            $args = array(
                'post_type' => LINKGENIUS_TYPE_LINK,
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'general_target_url',
                        'value' => $keyword,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'general_slug',
                        'value' => $keyword,
                        'compare' => 'LIKE',
                    )                    
                ),
                'meta_key' => 'general_target_url',
            );
            $query = new WP_Query( $args );
        
            $links = array_map(fn($post) => [
                "id" => $post->ID,
                "title" => $post->post_title,
                "url" => str_replace(home_url(), '', get_permalink($post)),
                'target_url' => esc_url(get_post_meta($post->ID, 'general_target_url', true))
            ], $query->posts);
            wp_send_json_success( $links );
        }
    }

    function preview_linkgenius_taxonomy() {
        if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            wp_send_json_error(__('Invalid AJAX call'));
        }
        elseif ( ! isset( $_GET[ 'taxonomy'] ) || !isset($_GET['item_slug']) || !isset($_GET['template']) || !isset($_GET['sort'])) {
            wp_send_json_error( __( 'Missing required post data' ));
        }
        elseif ( $_GET['taxonomy'] !== 'category' && $_GET['taxonomy'] !== 'tag' ) {
            wp_send_json_error( __( 'Invalid taxonomy' ) );
        }
        elseif ( $_GET['sort'] !== 'order' && $_GET['sort'] !== 'title') {
            wp_send_json_error( __( 'Invalid sort' ) );
        }
        else {
            $blocks_att = $_GET['template_type'] == "blocks" ? "blocks=true" : "";
            $template = wp_kses_post($_GET['template']);
            $shortcode = "[linkgenius-list ".$_GET['taxonomy']."=".sanitize_title($_GET['item_slug'])." sort=".$_GET['sort']." {$blocks_att}]{$template}[/linkgenius-list]";
            $preview = do_shortcode($shortcode);
            wp_send_json_success( $preview);
        }
    }

    function custom_allowed_html_tags($allowed_tags) {
        // Only modify the allowed tags for post content
        $allowed_tags['linkgenius-links'] = array(
            'class' => true,
        );
        return $allowed_tags;
    }


    //
    // PROCESSING
    //

    /**
     * Filter the content of the post and replace the <aal> tag with the shortcode
     *
     * @param [type] $content
     * @return void
     */
    function replace_linkgenius_tag_with_link($content) {
        $pattern = '/<linkgenius-link.*?linkgenius_id="(?<id>[0-9]+)"(?<atts>.*?)>(?<text>.*?)<\/linkgenius-link>/';
        return preg_replace_callback($pattern, function($matches) {
            $id = $matches['id'] ?? "";
            return do_shortcode("[linkgenius-link id={$id} extra_atts='".esc_attr($matches['atts'])."']{$matches['text']}[/linkgenius-link]");
        }, $content);
    }
}