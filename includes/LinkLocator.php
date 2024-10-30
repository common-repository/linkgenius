<?php
namespace LinkGenius;

class LinkLocator {
    private function __construct() {
        add_action('wp_ajax_linkgenius_search_locations', array($this, 'search_link_locations'));
    }
    public static function instance() {
        static $instance = null;
        if($instance == null) {
            $instance = new static();
        }
        return $instance;
    }

    public function search_link_locations() {
        global $wpdb;
        
        // Sanitize and validate the input
        $id = intval($_POST['linkgenius_id']);
        $url = get_permalink($id);

        // Prepare the content patterns to search for
        $content_likes = array(
            array (
                'LIKE' => "%<linkgenius-link%linkgenius_id=\"$id\"%",
                'REGEX' => "/<linkgenius-link[^>]*linkgenius_id=\"$id\"/",
                'NAME' => __('LinkGenius Link', 'linkgenius')
            ),
            array (
                'LIKE' => "%[linkgenius-link%id=\"$id\"]%",
                'REGEX' => "/class=\"wp-block-linkgenius-linkblock\">\[linkgenius-link[^]]*id=\"$id\"/",
                'NAME' => __('LinkGenius Link Block', 'linkgenius')
            ),
            array (
                'LIKE' => "",
                'REGEX' => "/(?<!class=\"wp-block-linkgenius-linkblock\">)\[linkgenius-link[^]]+id=\"$id\"/",
                'NAME' => __('Shortcode', 'linkgenius')
            ),
        );
        if($url != null) {
            $home_url = trim(home_url(), '/');
            $rel_url = str_replace($home_url, '', $url);
            $reg_url = "(". preg_quote($home_url, '/').")?".preg_quote($rel_url, "/");
            $content_likes[] = array (
                'LIKE' => "%$rel_url%",
                'REGEX' => "/href=\"$reg_url(\?|\")/",
                'NAME' => __('Normal Link', 'linkgenius')
            );
            $content_likes[] = array (
                'LIKE' => "",
                'REGEX' => "/(?<!href)=\"$reg_url(\?|\")/",
                'NAME' => __('URL', 'linkgenius')
            );
        }
        $content_likes = apply_filters('linkgenius_search_link_like', $content_likes, $id);

        
        // Construct the WHERE clause
        $map_like = function($plike) {
            $like = $plike['LIKE'];
            global $wpdb;
            if(empty($like))
                return null;
            $matches = null;
            if(preg_match('/^REGEXP (.*)$/ui', $like, $matches)) {
                return $wpdb->prepare("post_content REGEXP %s", $matches[1]);
            }
            else {
                return $wpdb->prepare("post_content LIKE %s", $like);
            }
        };
        $where = implode(' OR ', array_filter(array_map($map_like, $content_likes)));
    
        // Execute the query
        $results = $wpdb->get_results("SELECT ID, post_title, post_type, post_content FROM $wpdb->posts WHERE ($where) AND post_status = 'publish' ORDER BY post_title ASC");
        if(empty($results)) {
            $response = __("No results found", "linkgenius");
        }
        else {
            $content_likes_regex = array_combine(array_map(fn($cl) => $cl['REGEX'], $content_likes), array_map(fn($cl) => $cl['NAME'], $content_likes));
            $response = "<table><tr><th>".__("ID", "linkgenius")."</th><th>".__("Title", "linkgenius")."</th><th>".__("Post Type", "linkgenius")."</th><th>".__("Link Type", "linkgenius")."</th><th></th></tr>";
            $rows = [$this->result_to_row($results[0], $content_likes_regex)];
            $rows = apply_filters('linkgenius_search_link_rows', $rows, $results, $content_likes_regex);
            $response .= implode("", array_filter($rows))."</table>";
        }

        // Return the results as JSON
        echo json_encode($response);
        wp_die();
    }

    public function result_to_row($result, $content_likes_regex) {
        $types = [];
        foreach($content_likes_regex as $regex => $type) {
            if(preg_match($regex, $result->post_content)) {
                $types[$type] = 1;
            }
        }
        if(empty($types)) {
            return "";
        }
        return "<tr><td>".$result->ID."</td>
                    <td>".$result->post_title."</td>
                    <td>".$result->post_type."</td>
                    <td class=\"types\">".implode(", ", array_keys($types))."</td>
                    <td><a href='".get_edit_post_link($result->ID)."'><span class='dashicons dashicons-edit'></span></a></td>
                </tr>";
    }
}