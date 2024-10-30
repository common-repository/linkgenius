<?php
namespace LinkGenius;

use DOMDocument;
use ReflectionClass;

class Importer {
    private $import_tmp_file = null;

    private $mandatory_fields = [
        "title"     => ["title","name", "link_title"],
        "general_slug" => ["slug", "short_url"],
        "general_target_url" => ["target_url","url","destination_url"]
    ];
    private $optional_fields = [
        "general_redirect_type" => ["redirect_type"],
        "general_order" => ["order"],
        "general_uncloak" => ["uncloak", "uncloaked", "uncloak_link"],
        "appearance_css_classes" => ['css_classes'],
        "appearance_new_tab" => ["new_tab", "new_window"],
        "appearance_parameter_forwarding" => ["parameter_forwarding", "param_forwarding", "pass_query_str"],
        "appearance_sponsored_attribute" => ["sponsored"],
        "appearance_nofollow_attribute" => ["nofollow","no_follow"],
        "appearance_rel_tags" => ["rel_tags"],
        "disclosure_type" => ["disclosure_type"],
        'categories'    => ['categories', 'category', "link_categories"],
        'tags'          => ['tags',"link_tags"],
    ];
    private $pro_fields = [
        "autolink_order" => ["autolink_order"],
        "expiration_clicks" => ["expiration_clicks"],
        "redirect_after_expiry" => ["redirect_after_expiry"],
        "tracking_enabled" => ["tracking_enabled", "google_tracking"],
        "tracking_name" => ["tracking_name"],
        "tracking_parameters" => ["tracking_parameters"],
        "autolink_keywords" => ["autolink_keywords","keywords"],
        "autolink_urls" => ["autolink_urls"],
        "expiration_date" => ["expiration_date"],
        "geolocation_redirects" => ["geolocation_redirects"],
        "user_agent_redirects" => ["user_agent_redirects"]
    ];
    public function __construct()
    {
        add_action( 'cmb2_admin_init', [$this, 'render_import'] );
        $this->import_tmp_file = wp_upload_dir()['basedir'] . '/linkgenius/import';
    }

    public function render_import() {
        $args = array(
            'id'            => 'linkgenius_importer',
            'title'         => __('Import', 'linkgenius'),
            'menu_title'    => __('Import', 'linkgenius'),
            'object_types'  => array('options-page'),
            'option_key'    => 'linkgenius_import',
            'parent_slug'   => LINKGENIUS_POST_TYPE_SLUG,
            'save_fields'   => false,
            'save_button'   => __('Start Import', 'linkgenius'),
        );
        $cmb = new_cmb2_box($args);


        // Handle Form submissions
        if(($_POST['action']??"") == 'linkgenius_import') {
            if(isset($_POST['discard_import'])) {
                // import confirmation submit
                if($_POST['discard_import'] == "import") {
                    $this->import_from_file($this->import_tmp_file);
                }
                chmod($this->import_tmp_file,0755); //Change the file permissions if allowed
                unlink($this->import_tmp_file); //remove the file
            }
            else {
                // file upload submit
                if (!file_exists(dirname($this->import_tmp_file))) {
                    mkdir(dirname($this->import_tmp_file), 0777, true);
                }
                else if(file_exists($this->import_tmp_file)) {
                    chmod($this->import_tmp_file,0755); //Change the file permissions if allowed
                    unlink($this->import_tmp_file); //remove the file
                }
                // Move the uploaded file to the destination path
                if (!move_uploaded_file($_FILES['linkgenius_import_file']['tmp_name']??"", $this->import_tmp_file)) {
                    throw new \Exception('Error moving uploaded file');
                }
            }
        }
        // no post
        else if(file_exists($this->import_tmp_file)) {
            // An uploaded file exists, show how the import will be and ask confirmation
            ['headers' => $headers, 'items' => $items] = $this->parse_import_file($this->import_tmp_file);
            ob_start();
            
            if(sizeof($items) <= 0) :
                _e("It looks like the uploaded file could not be parsed or did not contain any links. Please check the file and try again.", "linkgenius");
            else:
            ?><div style="overflow-x:scroll">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo implode("</th><th>", array_map(fn($v) => preg_replace("/^[a-z]+_/", "", $v) ,$headers)) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <td><?= implode('</td><td>', array_map(function($header) use ($item) {
                                return is_array($item[$header]??"") 
                                    ? implode(", \n", array_map(fn($key, $value) => "$key => $value", array_keys($item[$header]), $item[$header])) 
                                    : ($item[$header]??'');
                            }, $headers)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
            <?php
            endif;

            $extra = ob_get_clean();
            $cmb->add_field( array(
                'name'    => __('Import', 'linkgenius'),
                'desc'    => __('This will import the LinkGenius Links as listed below. Select import to import or discard to upload a different file and click the Confirm button.', 'linkgenius'),
                'id'      => 'linkgenius_import',
                'type'    => 'title',
            ) );
            $cmb->add_field( array(
                'name'             => 'Discard or Save?',
                'id'               => 'discard_import',
                'type'             => 'radio_inline',
                'show_option_none' => false,
                'label_cb' => '',
                'options'          => array(
                    'discard' => __( 'Discard', 'cmb2' ),
                    'import'   => __( 'Import', 'cmb2' ),
                ),
                'before' => fn($v) => $extra,
            ) );
            $cmb->set_prop('save_button', __('Confirm', 'linkgenius'));
        }
        else {
            // no posts or existing file so show the starting screen to upload file
            $cmb->add_field( array(
                'name'    => __('Import', 'linkgenius'),
                'desc'    => __('Import links from a CSV file or an WordPress export XML file.', 'linkgenius'),
                'id'      => 'linkgenius_import',
                'type'    => 'title',
            ) );
            $map_fields = fn($array) => implode(", ", array_map(fn($v) => implode(' or ', $v), $array));
            $cmb->add_field( array(
                'name'    => __('CSV or XML file', 'linkgenius'),
                'desc'    => sprintf(__('The file containing the link data.')),
                'id'      => 'linkgenius_import_file',
                'type'    => 'text',
                'attributes' => array(
                    'type' => 'file', // Let's use a standard file upload field
                    'accept' => ".csv,.xml"
                )
            ));
            $cmb->add_field(array(
                    'id'      => 'linkgenius_import_fields',
                    'name'  => __('Supported fields', 'linkgenius'),
                    'type'    => 'title'
            ));
            $cmb->add_field(array(
                'id'    => 'linkgenius_import_fields_hidden',
                'name'  => 'dummy',
                'type'  => 'text',
                'label_cb' => '',
                'attributes' => array(
                    'type' => 'hidden'
                ),
                'after' => fn($v) => sprintf(__('<p>Below you can find what fields are supported by the import. This importer should be used for importing links form thirt-party plugins. With these fields you can directly import export files from most major LinkGenius alternatives. If you are missing field names please create a topic a <a href="https://wordpress.org/support/plugin/linkgenius/">Wordpress.org</a></p>
                    <p><strong>Mandatory fields:</strong> %1$s<br><strong>Optional fields:</strong> %2$s<br><!--<strong>Pro fields:</strong> %3$s--></p><p>If you want to export and import from the LinkGenius plugin between sites you can use the WordPress default import/export functions under "Tools > Export" and "Tools > Import" in the admin menu.</p>', 'linkgenius'),
                        $map_fields($this->mandatory_fields),
                        $map_fields($this->optional_fields),
                        $map_fields($this->pro_fields)),
            ));
        }
    }


    private function parse_import_file($file) {
        $content = file_get_contents($this->import_tmp_file);
        $return_items = [];
        if(strpos($content, '<?xml') === 0) {
            // parse the xml file
            $dom = new DOMDocument();
            $dom->loadXML($content);
    
            // determine rootNode(s)
            $rootNodes = [];
            foreach($dom->childNodes as $child) 
            {
                if($child->nodeType !== XML_COMMENT_NODE) {// <?xml version="1.0" encoding="UTF-8"
                    $rootNodes[] = $child;
                }
            }
    
            $return_items = [];
            if(sizeof($rootNodes) == 1) {
                $rootNode = $rootNodes[0];
                if($rootNode->nodeName == 'rss') {
                    $wp_namespace = $rootNode->lookupNamespaceUri('wp');
                    // parse wordpress rss feed
                    $items = $dom->getElementsByTagName('item');
                    foreach($items as $item) {
                        $item_array = [];
                        $item_array['title'] = $item->getElementsByTagName('title')->item(0)->nodeValue;
                        $item_array['slug'] = $item->getElementsByTagNameNS($wp_namespace, 'post_name')->item(0)->nodeValue;
                        $item_array['categories'] = [];
                        $cats = $item->getElementsByTagName('category');
                        foreach($cats as $cat) {
                            $item_array['categories'][$cat->getAttribute('nicename')] = $cat->nodeValue;
                        }
                        $metas = $item->getElementsByTagNameNS($wp_namespace, 'postmeta');
                        foreach($metas as $m) {
                            $item_array[$m->getElementsByTagNameNS($wp_namespace, 'meta_key')->item(0)->nodeValue] = $m->getElementsByTagNameNS($wp_namespace, 'meta_value')->item(0)->nodeValue;
                        }
                        $return_items[] = $this->map_array($item_array);
                    }
                }
            }
            // Get all keys from the inner arrays into a single array without duplicates
            $headers = array_unique(array_reduce(
                $return_items,
                function ($carry, $item) {
                    return array_merge($carry, array_keys($item));
                },
                array()
            ));
        }
        else {
            $f = fopen($file, 'r');
            $headers = null;
            while ($line = fgetcsv($f)) {
                if(sizeof($line) <= 1 && ($line[0]??null) == null)
                    continue;
                if($headers == null) {
                    $headers = $line;
                    continue;
                }
                $return_items[] = $this->map_array(array_combine($headers, $line));
            }
        }
        
        // Get all keys from the inner arrays into a single array without duplicates
        $headers = array_unique(array_reduce(
            $return_items,
            function ($carry, $item) {
                return array_merge($carry, array_keys($item));
            },
            array()
        ));
        return [ 'headers' => $headers, 'items' => $return_items ];
    }

    private function map_array($original) {
        $new_arr = [];
        // check all fields
        foreach([$this->mandatory_fields, $this->optional_fields] as $fields) {
            // k is destination, v is accepted values
            foreach($fields as $k => $v) {
                $v[] = $k; // add the destination key to the accepted values
                foreach($original as $ko_unmapped => $vo) {
                    $ko = str_replace(["_ta_", "link_"], "", $ko_unmapped);
                    // $ko (oroiginal key) is in the accepted values (v), assign actual value $vo to the destination key $k
                    if(in_array($ko, $v)) {
                        switch($k) { // take out some special cases first
                            case "title":
                            case "apearance_css_classes":
                            case "appearance_rel_tags":
                                $vo = sanitize_text_field($vo);
                                break;
                            case "general_slug":
                                $vo = sanitize_title($vo);
                                break;
                            case "general_target_url":
                                $vo = esc_url_raw($vo);
                                break;
                            case "general_redirect_type":
                                switch($vo) {
                                    case "301":
                                    case "302":
                                    case "307:":
                                        $vo = $vo;
                                        break;
                                    default:
                                        $vo = "301";
                                        break;
                                }
                                break;
                            case "general_order":
                                $vo = intval($vo);
                                break;
                            case "general_uncloak":
                            case "appearance_css_classes":
                            case "appearance_new_tab":
                            case "appearance_parameter_forwarding":
                            case "appearance_sponsored_attribute":
                            case "appearance_nofollow_attribute":
                                switch($vo) {
                                    case "yes":
                                        $vo = 1;
                                        break;
                                    case "no":
                                        $vo = 0;
                                        break;
                                    case "global":
                                    default:
                                        $vo = "default";
                                        break;
                                }
                                break;
                            case "categories":
                            case "tags": {
                                // If it is a string parse it.
                                if(is_string($vo)) {
                                    if(preg_match('/[a-zA-Z0-9,]+/', $vo)) {
                                        $vo = explode(',', $vo);
                                        $vo = array_combine($vo, $vo);
                                    }
                                    else if(empty($vo)) {
                                        $vo = [];
                                    }
                                }
                                
                                // It's something unexpected, so just ignore it.
                                if(!is_array($vo)) {
                                    $vo = ['unparsed' => $vo];
                                }
                                else {
                                    // Sanitize the array
                                    $new = [];
                                    foreach($vo as $key => $value) {
                                        $new[sanitize_title($key)] = sanitize_text_field($value);
                                    }
                                    $vo = $new;
                                }
                                break;
                            }
                            default: // all yes/no/default
                                //$vo = "warning_unescaped($vo)";
                                continue 2;
                        }
                        $new_arr[$k] = $vo;
                        // unset($original[$ko_unmapped]);
                        break;
                    }
                }
            }
        }
        return apply_filters("linkgenius_import_map", $new_arr, $original);
    }

    private function import_from_file($file) {
        // Do the actual importing form the file
        ['headers' => $headers, 'items' => $items] = $this->parse_import_file($file);
        foreach($items as $item) {

            $categories = $item['categories'] ?? [];
            $tags = $item['tags'] ?? [];

            // Insert post
            $post_id = wp_insert_post([
                'post_title' => $item['title'],
                'post_name' => $item['general_slug'],
                'post_type' => LINKGENIUS_TYPE_LINK,
                'post_status' => 'publish',
                'meta_input' => array_diff_key($item, array_flip(['title', 'categories', 'tags']))
            ]);
            if(is_wp_error($post_id)) {
                throw new \Exception($post_id->get_error_message());
            }

            // Assign categories
            if (!empty($categories)) {
                $category_ids = array();
                foreach ($categories as $category_slug => $category_name) {
                    // Check if category exists, if not, create it
                    $category_id = term_exists($category_slug, LINKGENIUS_TYPE_CATEGORY);
                    if (!$category_id) {
                        $category_id = wp_insert_term($category_name, LINKGENIUS_TYPE_CATEGORY, array('slug' => $category_slug));
                    }
                    $category_ids[] = intval($category_id['term_id']);
                }
                wp_set_object_terms($post_id, $category_ids, LINKGENIUS_TYPE_CATEGORY);
            }

            // // Assign tags
            if (!empty($tags)) {
                $tag_ids = array();
                foreach ($tags as $tag_slug => $tag_name) {
                    // Check if tag exists, if not, create it
                    $tag_id = term_exists($tag_slug, LINKGENIUS_TYPE_TAG);
                    if (!$tag_id) {
                        $tag_id = wp_insert_term($tag_name, LINKGENIUS_TYPE_TAG, array('slug' => $tag_slug));
                    }
                    $tag_ids[] = intval($tag_id['term_id']);
                }
                wp_set_object_terms($post_id, $tag_ids, LINKGENIUS_TYPE_TAG);
            }
        }
    }

    public static function init()
    {
        static $instance = false;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }
}