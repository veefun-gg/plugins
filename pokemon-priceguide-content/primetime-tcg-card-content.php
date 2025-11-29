<?php
/**
 * Plugin Name: Primetime TCG Card Content
 * Plugin URI: https://primetimepokemon.com
 * Description: Adds custom rich text content capabilities to Pokémon TCG card pages using a shadow post type system.
 * Version: 1.0.0
 * Author: PrimeTime Pokemon
 * Author URI: https://primetimepokemon.com
 * Text Domain: primetime-tcg-card-content
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Primetime_TCG_Card_Content {
    /**
     * Plugin instance.
     *
     * @var Primetime_TCG_Card_Content
     */
    private static $instance = null;

    /**
     * Get plugin instance.
     *
     * @return Primetime_TCG_Card_Content
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        // Define constants
        $this->define_constants();
        
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('init', array($this, 'init'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Filter content to add custom card content
        add_filter('the_content', array($this, 'filter_card_content'), 20);
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Add AJAX handlers
        add_action('wp_ajax_search_cards', array($this, 'ajax_search_cards'));
        add_action('wp_ajax_filter_cards', array($this, 'ajax_filter_cards'));
        add_action('wp_ajax_create_card_content', array($this, 'ajax_create_card_content'));
    }

    /**
     * Define constants.
     */
    private function define_constants() {
        define('PTCC_VERSION', '1.0.0');
        define('PTCC_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('PTCC_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('PTCC_PLUGIN_BASENAME', plugin_basename(__FILE__));
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Create mapping table
        $this->create_mapping_table();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create mapping table for card content.
     */
    private function create_mapping_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'ptp_card_content_map';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            card_id varchar(50) NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY card_id (card_id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Initialize plugin.
     */
    public function init() {
        // Register card content post type
        $this->register_card_content_post_type();
        
        // Load text domain
        load_plugin_textdomain('primetime-tcg-card-content', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Register card content post type.
     */
    private function register_card_content_post_type() {
        $labels = array(
            'name'               => _x('Card Content', 'post type general name', 'primetime-tcg-card-content'),
            'singular_name'      => _x('Card Content', 'post type singular name', 'primetime-tcg-card-content'),
            'menu_name'          => _x('Card Content', 'admin menu', 'primetime-tcg-card-content'),
            'name_admin_bar'     => _x('Card Content', 'add new on admin bar', 'primetime-tcg-card-content'),
            'add_new'            => _x('Add New', 'card content', 'primetime-tcg-card-content'),
            'add_new_item'       => __('Add New Card Content', 'primetime-tcg-card-content'),
            'new_item'           => __('New Card Content', 'primetime-tcg-card-content'),
            'edit_item'          => __('Edit Card Content', 'primetime-tcg-card-content'),
            'view_item'          => __('View Card Content', 'primetime-tcg-card-content'),
            'all_items'          => __('All Card Content', 'primetime-tcg-card-content'),
            'search_items'       => __('Search Card Content', 'primetime-tcg-card-content'),
            'parent_item_colon'  => __('Parent Card Content:', 'primetime-tcg-card-content'),
            'not_found'          => __('No card content found.', 'primetime-tcg-card-content'),
            'not_found_in_trash' => __('No card content found in Trash.', 'primetime-tcg-card-content')
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-tickets-alt',
            'supports'            => array(
                'title',
                'editor',
                'thumbnail',
                'revisions',
            ),
            'show_in_rest'        => true, // Enable Gutenberg editor
        );

        register_post_type('card_content', $args);
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=card_content',
            __('Manage Card Content', 'primetime-tcg-card-content'),
            __('Manage Content', 'primetime-tcg-card-content'),
            'manage_options',
            'card-content-manager',
            array($this, 'render_card_content_manager')
        );
    }

    /**
     * Render card content manager page.
     */
    public function render_card_content_manager() {
        // Get sets for filter dropdown
        global $wpdb;
        $sets = $wpdb->get_col("SELECT DISTINCT `set` FROM {$wpdb->prefix}ptp_cache_card ORDER BY `set`");
        
        // Get card types for filter dropdown
        $types = array('Pokémon', 'Trainer', 'Energy');
        
        // Get content status counts
        $with_content_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ptp_card_content_map");
        $total_cards_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ptp_cache_card");
        $without_content_count = $total_cards_count - $with_content_count;
        
        ?>
        <div class="wrap card-content-manager">
            <div class="card-content-manager-header">
                <h1><?php _e('Card Content Manager', 'primetime-tcg-card-content'); ?></h1>
                <div class="card-search">
                    <input type="text" id="card-search-input" placeholder="<?php _e('Search by card ID...', 'primetime-tcg-card-content'); ?>">
                </div>
            </div>
            
            <div class="card-search-results"></div>
            
            <div class="card-content-stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($total_cards_count); ?></div>
                    <div class="stat-label"><?php _e('Total Cards', 'primetime-tcg-card-content'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($with_content_count); ?></div>
                    <div class="stat-label"><?php _e('With Content', 'primetime-tcg-card-content'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($without_content_count); ?></div>
                    <div class="stat-label"><?php _e('Without Content', 'primetime-tcg-card-content'); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format(($with_content_count / $total_cards_count) * 100, 1); ?>%</div>
                    <div class="stat-label"><?php _e('Completion', 'primetime-tcg-card-content'); ?></div>
                </div>
            </div>
            
            <div class="card-content-filters">
                <form id="card-filters-form">
                    <div class="filter-group">
                        <label for="filter-card-id"><?php _e('Card ID', 'primetime-tcg-card-content'); ?></label>
                        <input type="text" id="filter-card-id" name="card_id" placeholder="<?php _e('e.g., base1-4', 'primetime-tcg-card-content'); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="filter-set"><?php _e('Set', 'primetime-tcg-card-content'); ?></label>
                        <select id="filter-set" name="set">
                            <option value="all"><?php _e('All Sets', 'primetime-tcg-card-content'); ?></option>
                            <?php foreach ($sets as $set) : ?>
                                <option value="<?php echo esc_attr($set); ?>"><?php echo esc_html($set); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter-type"><?php _e('Type', 'primetime-tcg-card-content'); ?></label>
                        <select id="filter-type" name="type">
                            <option value="all"><?php _e('All Types', 'primetime-tcg-card-content'); ?></option>
                            <?php foreach ($types as $type) : ?>
                                <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter-status"><?php _e('Status', 'primetime-tcg-card-content'); ?></label>
                        <select id="filter-status" name="status">
                            <option value="all"><?php _e('All Cards', 'primetime-tcg-card-content'); ?></option>
                            <option value="with-content"><?php _e('With Content', 'primetime-tcg-card-content'); ?></option>
                            <option value="without-content"><?php _e('Without Content', 'primetime-tcg-card-content'); ?></option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="button button-primary"><?php _e('Apply Filters', 'primetime-tcg-card-content'); ?></button>
                        <button type="button" id="filter-reset" class="button"><?php _e('Reset', 'primetime-tcg-card-content'); ?></button>
                    </div>
                </form>
            </div>
            
            <table class="wp-list-table widefat fixed striped card-content-table">
                <thead>
                    <tr>
                        <th class="column-card_image"><?php _e('Image', 'primetime-tcg-card-content'); ?></th>
                        <th class="column-card_name"><?php _e('Name', 'primetime-tcg-card-content'); ?></th>
                        <th class="column-card_set"><?php _e('Set', 'primetime-tcg-card-content'); ?></th>
                        <th class="column-card_number"><?php _e('Number', 'primetime-tcg-card-content'); ?></th>
                        <th class="column-card_id"><?php _e('Card ID', 'primetime-tcg-card-content'); ?></th>
                        <th class="column-last_updated"><?php _e('Last Updated', 'primetime-tcg-card-content'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'primetime-tcg-card-content'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7"><?php _e('Loading...', 'primetime-tcg-card-content'); ?></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="card-content-pagination">
                <div class="pagination-status"></div>
                <div class="pagination-links"></div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for searching cards.
     */
    public function ajax_search_cards() {
        // Check nonce
        check_ajax_referer('ptcc_ajax_nonce', 'nonce');
        
        // Get search term
        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        
        if (empty($search_term)) {
            wp_send_json_error(array('message' => __('Search term is required', 'primetime-tcg-card-content')));
        }
        
        global $wpdb;
        
        // Search for cards
        $cards = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, m.post_id, p.post_modified
            FROM {$wpdb->prefix}ptp_cache_card c
            LEFT JOIN {$wpdb->prefix}ptp_card_content_map m ON c.id = m.card_id
            LEFT JOIN {$wpdb->posts} p ON m.post_id = p.ID AND p.post_status = 'publish'
            WHERE c.name LIKE %s OR c.set LIKE %s OR c.id LIKE %s
            ORDER BY c.name
            LIMIT 20",
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%'
        ));
        
        // Format results
        $results = array();
        foreach ($cards as $card) {
            $results[] = array(
                'id' => $card->id,
                'name' => $card->name,
                'set' => $card->set,
                'number' => $card->number,
                'hasContent' => !empty($card->post_id),
                'lastUpdated' => !empty($card->post_modified) ? date('Y-m-d', strtotime($card->post_modified)) : null,
                'postId' => $card->post_id,
                'permalink' => $card->permalink
            );
        }
        
        wp_send_json_success(array('results' => $results));
    }

    /**
     * AJAX handler for filtering cards.
     */
    public function ajax_filter_cards() {
        // Check nonce
        check_ajax_referer('ptcc_ajax_nonce', 'nonce');
        
        // Get filter values
        $set = isset($_POST['set']) ? sanitize_text_field($_POST['set']) : 'all';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'all';
        $card_id = isset($_POST['card_id']) ? sanitize_text_field($_POST['card_id']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 20;
        
        global $wpdb;
        
        // Build query
        $query = "SELECT c.*, m.post_id, p.post_modified
                 FROM {$wpdb->prefix}ptp_cache_card c
                 LEFT JOIN {$wpdb->prefix}ptp_card_content_map m ON c.id = m.card_id
                 LEFT JOIN {$wpdb->posts} p ON m.post_id = p.ID AND p.post_status = 'publish'
                 WHERE 1=1";
        
        $query_args = array();
        
        // Add filters
        if ($set !== 'all') {
            $query .= " AND c.set = %s";
            $query_args[] = $set;
        }
        
        if ($type !== 'all') {
            $query .= " AND c.type = %s";
            $query_args[] = $type;
        }
        
        if ($status !== 'all') {
            if ($status === 'with-content') {
                $query .= " AND m.post_id IS NOT NULL";
            } else {
                $query .= " AND m.post_id IS NULL";
            }
        }
        
        if (!empty($card_id)) {
            $query .= " AND c.id LIKE %s";
            $query_args[] = '%' . $wpdb->esc_like($card_id) . '%';
        }
        
        // Count total
        $count_query = str_replace("c.*, m.post_id, p.post_modified", "COUNT(*)", $query);
        $total = $wpdb->get_var($wpdb->prepare($count_query, $query_args));
        
        // Add pagination
        $query .= " ORDER BY c.name";
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $per_page;
        $query_args[] = ($page - 1) * $per_page;
        
        // Get cards
        $cards = $wpdb->get_results($wpdb->prepare($query, $query_args));
        
        // Format results
        $results = array();
        foreach ($cards as $card) {
            $results[] = array(
                'id' => $card->id,
                'name' => $card->name,
                'set' => $card->set,
                'number' => $card->number,
                'hasContent' => !empty($card->post_id),
                'lastUpdated' => !empty($card->post_modified) ? date('Y-m-d', strtotime($card->post_modified)) : null,
                'postId' => $card->post_id,
                'permalink' => $card->permalink
            );
        }
        
        // Calculate pagination
        $total_pages = ceil($total / $per_page);
        
        wp_send_json_success(array(
            'results' => $results,
            'pagination' => array(
                'total' => $total,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page
            )
        ));
    }

    /**
     * AJAX handler for creating card content.
     */
    public function ajax_create_card_content() {
        // Check nonce
        check_ajax_referer('ptcc_ajax_nonce', 'nonce');
        
        // Get card ID
        $card_id = isset($_POST['card_id']) ? sanitize_text_field($_POST['card_id']) : '';
        
        if (empty($card_id)) {
            wp_send_json_error(array('message' => __('Card ID is required', 'primetime-tcg-card-content')));
        }
        
        // Create card content
        $post_id = $this->create_card_content($card_id);
        
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Failed to create card content', 'primetime-tcg-card-content')));
        }
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit')
        ));
    }

    /**
     * Create card content.
     *
     * @param string $card_id Card ID.
     * @return int|false Post ID on success, false on failure.
     */
    public function create_card_content($card_id) {
        global $wpdb;
        
        // Get card data
        $card = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ptp_cache_card WHERE id = %s",
            $card_id
        ));
        
        if (!$card) {
            return false;
        }
        
        // Check if content already exists
        $existing_post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->prefix}ptp_card_content_map WHERE card_id = %s",
            $card_id
        ));
        
        if ($existing_post_id) {
            return $existing_post_id;
        }
        
        // Get template content
        $template_content = $this->get_card_content_template($card);
        
        // Create a new post for this card
        $post_id = wp_insert_post(array(
            'post_title'    => $card->name . ' | ' . $card->set,
            'post_content'  => $template_content,
            'post_status'   => 'publish',
            'post_type'     => 'card_content',
        ));
        
        if (!$post_id) {
            return false;
        }
        
        // Add mapping
        $wpdb->insert(
            $wpdb->prefix . 'ptp_card_content_map',
            array(
                'card_id' => $card_id,
                'post_id' => $post_id,
            )
        );
        
        return $post_id;
    }

    /**
     * Get card content template with review section.
     *
     * @param object $card Card data.
     * @return string Template content.
     */
    private function get_card_content_template($card) {
        // Template with review section and placeholders replaced
        $template = '<!-- wp:heading {"level":3} -->
<h3>Overview</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>This ' . esc_html($card->name) . ' card is part of the ' . esc_html($card->set) . ' expansion.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Card Variants</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>This card has the following known variants:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
  <li>Regular</li>
  <li>Holofoil (if applicable)</li>
  <li>Reverse Holofoil (if applicable)</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3>Release Information</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Add information about this card\'s release history.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>Video Reviews</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>PrimeTime Pokemon Reviews</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>This card has been featured in the following PrimeTime Pokemon video reviews:</p>
<!-- /wp:paragraph -->

<!-- wp:table {"className":"is-style-stripes"} -->
<figure class="wp-block-table is-style-stripes"><table><thead><tr><th>Video Title</th><th>Upload Date</th><th>Views</th><th>Likes</th></tr></thead><tbody>
<tr><td>Add video title</td><td>Add upload date</td><td>Add view count</td><td>Add like count</td></tr>
</tbody></table></figure>
<!-- /wp:table -->

<!-- wp:heading {"level":4} -->
<h4>Review Details</h4>
<!-- /wp:heading -->

<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%">
<!-- wp:heading {"level":5} -->
<h5>Video Information</h5>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
  <li><strong>Video ID:</strong> Add YouTube ID</li>
  <li><strong>Length:</strong> Add video length</li>
  <li><strong>Comments:</strong> Add comment count</li>
  <li><strong>Content Type:</strong> Add content type</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%">
<!-- wp:heading {"level":5} -->
<h5>Product Details</h5>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
  <li><strong>Product Name:</strong> Add product name</li>
  <li><strong>Product Type:</strong> Add product type</li>
  <li><strong>Expansion Set:</strong> ' . esc_html($card->set) . '</li>
  <li><strong>Featured Pokémon:</strong> ' . esc_html($card->name) . '</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%">
<!-- wp:heading {"level":5} -->
<h5>Integration</h5>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
  <li><strong>Tags:</strong> Add tags</li>
  <li><strong>Website Integration:</strong> Add integration details</li>
  <li><strong>Thumbnail:</strong> Add thumbnail URL</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:heading {"level":4} -->
<h4>Video Description</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Add video description here.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4} -->
<h4>Key Timestamps</h4>
<!-- /wp:heading -->

<!-- wp:table {"className":"is-style-stripes"} -->
<figure class="wp-block-table is-style-stripes"><table><thead><tr><th>Time</th><th>Content</th></tr></thead><tbody>
<tr><td>00:00</td><td>Add description</td></tr>
</tbody></table></figure>
<!-- /wp:table -->

<!-- wp:heading {"level":3} -->
<h3>Card Pulls</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>This card has been pulled in the following PrimeTime Pokemon opening videos:</p>
<!-- /wp:paragraph -->

<!-- wp:table {"className":"is-style-stripes"} -->
<figure class="wp-block-table is-style-stripes"><table><thead><tr><th>Video</th><th>Timestamp</th><th>Reaction</th></tr></thead><tbody>
<tr><td>Add video title</td><td>Add timestamp</td><td>Add reaction</td></tr>
</tbody></table></figure>
<!-- /wp:table -->

<!-- wp:heading {"level":4} -->
<h4>Best Pulls</h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>In the "to summarize" sections of videos, this card was highlighted in:</p>
<!-- /wp:paragraph -->

<!-- wp:table {"className":"is-style-stripes"} -->
<figure class="wp-block-table is-style-stripes"><table><thead><tr><th>Video</th><th>Best Pull Timestamp</th><th>Comments</th></tr></thead><tbody>
<tr><td>Add video title</td><td>Add timestamp</td><td>Add comments</td></tr>
</tbody></table></figure>
<!-- /wp:table -->';

        return $template;
    }

    /**
     * Filter card content to add custom content.
     *
     * @param string $content Post content.
     * @return string Filtered content.
     */
    public function filter_card_content($content) {
        global $wpdb;
        
        // Check if we're inside the main loop in a single Post with pgpokeid
        if (get_query_var('pgpokeid') != '' && in_the_loop()) {
            $card_id = get_query_var('pgpokeid');
            
            // Check if we have custom content for this card
            $content_post_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->prefix}ptp_card_content_map WHERE card_id = %s",
                $card_id
            ));
            
            if ($content_post_id) {
                // Get the custom content
                $custom_content = get_post_field('post_content', $content_post_id);
                
                // Apply filters to render blocks properly
                $custom_content = apply_filters('the_content', $custom_content);
                
                // Remove the wpautop filter to avoid extra paragraph tags
                remove_filter('the_content', 'wpautop');
                
                // Append custom content to the template content
                $content .= '<div class="card-custom-content"><h2>Additional Information</h2>' . $custom_content . '</div>';
                
                // Re-add the wpautop filter
                add_filter('the_content', 'wpautop');
            }
        }
        
        return $content;
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Hook suffix.
     */
    public function enqueue_admin_assets($hook) {
        // Only enqueue on our admin page
        if ('card_content_page_card-content-manager' !== $hook) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'primetime-tcg-card-content-admin',
            PTCC_PLUGIN_URL . 'css/admin.css',
            array(),
            PTCC_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'primetime-tcg-card-content-admin',
            PTCC_PLUGIN_URL . 'js/admin.js',
            array('jquery'),
            PTCC_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script(
            'primetime-tcg-card-content-admin',
            'ptcc_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ptcc_ajax_nonce'),
                'strings' => array(
                    'search_placeholder' => __('Search by card ID...', 'primetime-tcg-card-content'),
                    'no_results' => __('No cards found matching your search.', 'primetime-tcg-card-content'),
                    'loading' => __('Loading...', 'primetime-tcg-card-content'),
                    'edit_content' => __('Edit Content', 'primetime-tcg-card-content'),
                    'create_content' => __('Create Content', 'primetime-tcg-card-content'),
                    'view_card' => __('View Card', 'primetime-tcg-card-content'),
                    'creating_content' => __('Creating content...', 'primetime-tcg-card-content'),
                    'error_creating' => __('Error creating content', 'primetime-tcg-card-content'),
                    'pagination_status' => __('Showing %1$s to %2$s of %3$s cards', 'primetime-tcg-card-content'),
                )
            )
        );
    }
}

// Initialize the plugin
Primetime_TCG_Card_Content::get_instance();
