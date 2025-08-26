<?php
/**
 * Plugin Name:       Simple Custom Chat
 * Plugin URI:        https://example.com/simple-custom-chat/
 * Description:       A simple, lightweight chat system for WordPress, built from scratch.
 * Version:           1.4.0
 * Author:            SAMUEL VICTOR
 * Author URI:        https://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       simple-custom-chat
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The main class for the plugin.
 */
class Simple_Custom_Chat {

    /**
     * Constructor.
     */
    public function __construct() {
        // Activation and deactivation hooks.
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Register custom post type and taxonomy.
        add_action( 'init', array( $this, 'register_chat_session_cpt' ) );

        // Add a shortcode for the chatbox.
        add_shortcode( 'simple_chat_box', array( $this, 'display_chat_box' ) );

        // Enqueue scripts and styles.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Register REST API endpoints.
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );

        // Add meta box to the CPT.
        add_action( 'add_meta_boxes', array( $this, 'add_chat_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_chat_meta_box' ) );

        // Add meta box to WooCommerce products if WooCommerce is active.
        if ( $this->check_woocommerce_active() ) {
            add_action( 'add_meta_boxes', array( $this, 'add_product_meta_box' ) );
            add_action( 'save_post', array( $this, 'save_product_meta_box' ) );
        }
    }

    /**
     * Checks if WooCommerce is active.
     *
     * @return bool
     */
    private function check_woocommerce_active() {
        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            return true;
        }
        return false;
    }

    /**
     * Plugin activation hook.
     * Creates the custom database table.
     */
    public function activate() {
        $this->create_chat_table();
        // Flush rewrite rules on activation to ensure CPT URLs work.
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook.
     * Optionally drops the custom database table.
     */
    public function deactivate() {
        // Uncomment the line below to drop the table on deactivation.
        // $this->drop_chat_table();
        // Flush rewrite rules on deactivation.
        flush_rewrite_rules();
    }

    /**
     * Registers the Custom Post Type for chat sessions.
     */
    public function register_chat_session_cpt() {
        $labels = array(
            'name'                  => _x( 'Chat Sessions', 'Post Type General Name', 'simple-custom-chat' ),
            'singular_name'         => _x( 'Chat Session', 'Post Type Singular Name', 'simple-custom-chat' ),
            'menu_name'             => __( 'Chat Sessions', 'simple-custom-chat' ),
            'name_admin_bar'        => __( 'Chat Session', 'simple-custom-chat' ),
            'archives'              => __( 'Chat Session Archives', 'simple-custom-chat' ),
            'attributes'            => __( 'Chat Session Attributes', 'simple-custom-chat' ),
            'parent_item_colon'     => __( 'Parent Chat Session:', 'simple-custom-chat' ),
            'all_items'             => __( 'All Sessions', 'simple-custom-chat' ),
            'add_new_item'          => __( 'Add New Session', 'simple-custom-chat' ),
            'add_new'               => __( 'Add New', 'simple-custom-chat' ),
            'new_item'              => __( 'New Session', 'simple-custom-chat' ),
            'edit_item'             => __( 'Edit Session', 'simple-custom-chat' ),
            'update_item'           => __( 'Update Session', 'simple-custom-chat' ),
            'view_item'             => __( 'View Session', 'simple-custom-chat' ),
            'view_items'            => __( 'View Sessions', 'simple-custom-chat' ),
            'search_items'          => __( 'Search Session', 'simple-custom-chat' ),
            'not_found'             => __( 'Not found', 'simple-custom-chat' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'simple-custom-chat' ),
            'featured_image'        => __( 'Featured Image', 'simple-custom-chat' ),
            'set_featured_image'    => __( 'Set featured image', 'simple-custom-chat' ),
            'remove_featured_image' => __( 'Remove featured image', 'simple-custom-chat' ),
            'use_featured_image'    => __( 'Use as featured image', 'simple-custom-chat' ),
            'insert_into_item'      => __( 'Insert into item', 'simple-custom-chat' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'simple-custom-chat' ),
            'items_list'            => __( 'Sessions list', 'simple-custom-chat' ),
            'items_list_navigation' => __( 'Sessions list navigation', 'simple-custom-chat' ),
            'filter_items_list'     => __( 'Filter sessions list', 'simple-custom-chat' ),
        );
        $args = array(
            'label'                 => __( 'Chat Session', 'simple-custom-chat' ),
            'description'           => __( 'A container for all chat messages for a specific session.', 'simple-custom-chat' ),
            'labels'                => $labels,
            'supports'              => array( 'title' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'chat-sessions',
        );
        register_post_type( 'simple_chat_session', $args );
    }

    /**
     * Adds the meta box to the chat session CPT edit screen.
     */
    public function add_chat_meta_box() {
        add_meta_box(
            'simple_chat_access',
            __( 'Chat Access Roles', 'simple-custom-chat' ),
            array( $this, 'render_chat_meta_box' ),
            'simple_chat_session',
            'side',
            'high'
        );
    }

    /**
     * Renders the HTML for the chat access meta box.
     * @param WP_Post $post The post object.
     */
    public function render_chat_meta_box( $post ) {
        // Add a nonce field so we can check for it later.
        wp_nonce_field( 'simple_chat_meta_box_nonce', 'simple_chat_meta_box_nonce' );

        // Get existing allowed roles.
        $allowed_roles = get_post_meta( $post->ID, '_simple_chat_allowed_roles', true );
        if ( ! is_array( $allowed_roles ) ) {
            $allowed_roles = array();
        }

        // Get all default WordPress roles.
        global $wp_roles;
        $all_roles = $wp_roles->roles;

        // Render checkboxes for each role.
        echo '<p>Select the user roles that are allowed to access this chat session:</p>';
        foreach ( $all_roles as $role_key => $role_info ) {
            $checked = in_array( $role_key, $allowed_roles ) ? 'checked' : '';
            echo '<label>';
            echo '<input type="checkbox" name="simple_chat_allowed_roles[]" value="' . esc_attr( $role_key ) . '" ' . $checked . '>';
            echo ' ' . esc_html( $role_info['name'] );
            echo '</label><br>';
        }
    }

    /**
     * Saves the meta box data when the post is saved.
     * @param int $post_id The ID of the post being saved.
     */
    public function save_chat_meta_box( $post_id ) {
        // Check if our nonce is set and verified.
        if ( ! isset( $_POST['simple_chat_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['simple_chat_meta_box_nonce'], 'simple_chat_meta_box_nonce' ) ) {
            return;
        }

        // Check if the user has permission to save.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( 'simple_chat_session' !== $_POST['post_type'] || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Sanitize and save the roles.
        $allowed_roles = isset( $_POST['simple_chat_allowed_roles'] ) ? array_map( 'sanitize_text_field', $_POST['simple_chat_allowed_roles'] ) : array();
        update_post_meta( $post_id, '_simple_chat_allowed_roles', $allowed_roles );
    }

    /**
     * Adds the meta box to the product edit screen.
     */
    public function add_product_meta_box() {
        add_meta_box(
            'simple_chat_product_link',
            __( 'Link to Chat Session', 'simple-custom-chat' ),
            array( $this, 'render_product_meta_box' ),
            'product',
            'side',
            'high'
        );
    }

    /**
     * Renders the HTML for the product meta box.
     * @param WP_Post $post The product post object.
     */
    public function render_product_meta_box( $post ) {
        wp_nonce_field( 'simple_chat_product_meta_box_nonce', 'simple_chat_product_meta_box_nonce' );

        $linked_session_id = get_post_meta( $post->ID, '_simple_chat_linked_session_id', true );
        ?>
        <p>
            <label for="simple_chat_linked_session_id">
                <?php _e( 'Enter Chat Session ID:', 'simple-custom-chat' ); ?>
            </label>
            <input type="number" id="simple_chat_linked_session_id" name="simple_chat_linked_session_id" value="<?php echo esc_attr( $linked_session_id ); ?>" style="width: 100%;" />
            <br>
            <small><?php _e( 'Link this product to an existing chat session by entering its ID.', 'simple-custom-chat' ); ?></small>
        </p>
        <?php
    }

    /**
     * Saves the meta box data when a product is saved.
     * @param int $post_id The ID of the product being saved.
     */
    public function save_product_meta_box( $post_id ) {
        if ( ! isset( $_POST['simple_chat_product_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['simple_chat_product_meta_box_nonce'], 'simple_chat_product_meta_box_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( 'product' !== $_POST['post_type'] || ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $linked_session_id = isset( $_POST['simple_chat_linked_session_id'] ) ? intval( $_POST['simple_chat_linked_session_id'] ) : 0;
        update_post_meta( $post_id, '_simple_chat_linked_session_id', $linked_session_id );

        // Update the chat session with a reference to the product.
        if ( $linked_session_id > 0 ) {
            update_post_meta( $linked_session_id, '_simple_chat_linked_product_id', $post_id );
        }
    }

    /**
     * Creates the custom chat messages table.
     */
    private function create_chat_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'simple_chat_messages';

        // Check if the table already exists.
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                session_id bigint(20) NOT NULL,
                user_id bigint(20) NOT NULL,
                message text NOT NULL,
                timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id),
                KEY session_id (session_id)
            ) $charset_collate;";

            // Include the upgrade.php file to use dbDelta().
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }

    /**
     * Drops the custom chat messages table.
     */
    private function drop_chat_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_chat_messages';
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query( $sql );
    }

    /**
     * Enqueues the necessary CSS and JavaScript files for the chatbox.
     */
    public function enqueue_assets() {
        // Enqueue the chat-specific styles.
        wp_enqueue_style( 'simple-custom-chat-style', plugin_dir_url( __FILE__ ) . 'assets/css/chat-style.css', array(), '1.4.0' );

        // Enqueue the chat-specific script.
        wp_enqueue_script( 'simple-custom-chat-script', plugin_dir_url( __FILE__ ) . 'assets/js/chat-script.js', array( 'jquery' ), '1.4.0', true );

        // Pass PHP variables to JavaScript.
        $user_id = get_current_user_id();
        $user_name = 'Guest';
        if ( $user_id ) {
            $user_data = get_userdata( $user_id );
            $user_name = $user_data->display_name;
        }

        wp_localize_script( 'simple-custom-chat-script', 'simpleChat', array(
            'root'      => esc_url_raw( rest_url() ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'user_id'   => $user_id,
            'user_name' => $user_name
        ) );
    }

    /**
     * Displays the chatbox using a shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string The HTML markup for the chatbox.
     */
    public function display_chat_box( $atts ) {
        $atts = shortcode_atts(
            array(
                'session_id' => '',
                'product_id' => '', // New product_id attribute
            ),
            $atts,
            'simple_chat_box'
        );

        $session_id = intval( $atts['session_id'] );
        $product_id = intval( $atts['product_id'] );

        // If a product ID is provided, find the linked session or create a new one.
        if ( $product_id > 0 ) {
            $existing_session_id = get_post_meta( $product_id, '_simple_chat_linked_session_id', true );
            if ( $existing_session_id > 0 && get_post_status( $existing_session_id ) ) {
                $session_id = $existing_session_id;
            } else {
                // No session found, create a new one and link it to the product.
                $product_post = get_post( $product_id );
                $new_session = array(
                    'post_type'   => 'simple_chat_session',
                    'post_title'  => 'Chat for Product: ' . $product_post->post_title,
                    'post_status' => 'publish',
                );
                $session_id = wp_insert_post( $new_session );
                update_post_meta( $session_id, '_simple_chat_linked_product_id', $product_id );
                update_post_meta( $product_id, '_simple_chat_linked_session_id', $session_id );
            }
        } elseif ( ! $session_id && is_user_logged_in() ) {
            // Find or create a chat session for the current user if no specific session is provided.
            $args = array(
                'post_type'  => 'simple_chat_session',
                'author'     => get_current_user_id(),
                'fields'     => 'ids',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key'     => '_chat_status',
                        'value'   => 'active',
                        'compare' => '='
                    )
                )
            );
            $existing_sessions = get_posts( $args );

            if ( ! empty( $existing_sessions ) ) {
                $session_id = $existing_sessions[0];
            } else {
                // No active session found, create a new one.
                $new_session = array(
                    'post_type'   => 'simple_chat_session',
                    'post_title'  => 'Chat Session for User ' . get_current_user_id(),
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id(),
                );
                $session_id = wp_insert_post( $new_session );
                add_post_meta( $session_id, '_chat_status', 'active' );
            }
        }

        // Check if the user is allowed to view this session.
        if ( ! $this->can_access_session( $session_id ) ) {
            return '<p>You do not have permission to view this chat session.</p>';
        }

        ob_start();
        ?>
        <div id="simple-chat-box" data-session-id="<?php echo esc_attr( $session_id ); ?>">
            <div id="simple-chat-messages">
                <!-- Messages will be loaded here by JavaScript -->
            </div>
            <div id="simple-chat-typing-indicator" class="hidden">
                <span><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></span>
            </div>
            <div id="simple-chat-input-area">
                <input type="text" id="simple-chat-message-input" placeholder="Type your message...">
                <button id="simple-chat-send-button">Send</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Registers the REST API routes for the chat system.
     */
    public function register_routes() {
        // Updated permission callback to check user role.
        register_rest_route( 'simple-custom-chat/v1', '/messages/(?P<session_id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_messages' ),
            'permission_callback' => array( $this, 'can_access_session_api' ),
        ) );

        register_rest_route( 'simple-custom-chat/v1', '/messages/(?P<session_id>\d+)', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'send_message' ),
            'permission_callback' => array( $this, 'can_access_session_api' ),
        ) );

        register_rest_route( 'simple-custom-chat/v1', '/typing/(?P<session_id>\d+)', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'update_typing_status' ),
            'permission_callback' => array( $this, 'can_access_session_api' ),
        ) );

        register_rest_route( 'simple-custom-chat/v1', '/typing/(?P<session_id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_typing_status' ),
            'permission_callback' => array( $this, 'can_access_session_api' ),
        ) );

        // New route to get product info for a chat session.
        register_rest_route( 'simple-custom-chat/v1', '/session/(?P<session_id>\d+)/product', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_session_product_info' ),
            'permission_callback' => '__return_true', // No permission check needed, info is public
        ) );
    }

    /**
     * Checks if the current user has permission to access the chat session.
     * This is used by the shortcode.
     * @param int $session_id The ID of the chat session.
     * @return bool
     */
    private function can_access_session( $session_id ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        // Check for role-based access first.
        $allowed_roles = get_post_meta( $session_id, '_simple_chat_allowed_roles', true );
        if ( ! $allowed_roles || empty( $allowed_roles ) ) {
            // If no roles are explicitly set, assume all logged-in users can access.
            return true;
        }

        $current_user = wp_get_current_user();
        foreach ( $allowed_roles as $role ) {
            if ( in_array( $role, (array) $current_user->roles ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the current user has permission to access the chat session via the API.
     * @param WP_REST_Request $request The REST request object.
     * @return bool|WP_Error
     */
    public function can_access_session_api( WP_REST_Request $request ) {
        $session_id = intval( $request->get_param( 'session_id' ) );
        if ( ! $this->can_access_session( $session_id ) ) {
            return new WP_Error( 'rest_forbidden', 'You do not have permission to access this chat session.', array( 'status' => 403 ) );
        }
        return true;
    }

    /**
     * Callback to retrieve chat messages for a specific session.
     *
     * @return WP_REST_Response
     */
    public function get_messages( WP_REST_Request $request ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_chat_messages';
        $session_id = intval( $request->get_param( 'session_id' ) );

        if ( ! $session_id ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid session ID.' ), 400 );
        }
        
        $messages = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE session_id = %d ORDER BY timestamp DESC LIMIT 50", $session_id ) );

        $formatted_messages = array();
        foreach ( $messages as $message ) {
            $user_data = get_userdata( $message->user_id );
            $user_name = $user_data ? $user_data->display_name : 'Guest';
            $avatar_url = $user_data ? get_avatar_url( $user_data->user_email ) : '';
            $formatted_messages[] = array(
                'id'        => $message->id,
                'user_id'   => $message->user_id,
                'user_name' => $user_name,
                'avatar_url' => $avatar_url,
                'message'   => $message->message,
                'timestamp' => $message->timestamp
            );
        }

        return new WP_REST_Response( $formatted_messages, 200 );
    }

    /**
     * Callback to send a new message to a specific session.
     *
     * @return WP_REST_Response
     */
    public function send_message( WP_REST_Request $request ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'simple_chat_messages';

        $params = $request->get_json_params();
        $message_text = sanitize_text_field( $params['message'] );
        $user_id = get_current_user_id();
        $session_id = intval( $request->get_param( 'session_id' ) );

        if ( empty( $message_text ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Message cannot be empty.' ), 400 );
        }

        if ( ! $session_id ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid session ID.' ), 400 );
        }

        $result = $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'message' => $message_text
            )
        );

        if ( $result ) {
            return new WP_REST_Response( array( 'success' => true, 'message' => 'Message sent.' ), 201 );
        } else {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Failed to send message.' ), 500 );
        }
    }

    /**
     * Callback to update a user's typing status for a specific session.
     *
     * @return WP_REST_Response
     */
    public function update_typing_status( WP_REST_Request $request ) {
        $user_id = get_current_user_id();
        $is_typing = (bool) $request->get_param( 'is_typing' );
        $session_id = intval( $request->get_param( 'session_id' ) );

        if ( ! $session_id ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid session ID.' ), 400 );
        }

        $transient_key = 'simple_chat_typing_users_' . $session_id;
        $typing_users = get_transient( $transient_key );
        if ( false === $typing_users ) {
            $typing_users = array();
        }

        if ( $is_typing ) {
            $typing_users[ $user_id ] = time();
        } else {
            unset( $typing_users[ $user_id ] );
        }

        // Remove old entries to prevent clutter.
        $fresh_typing_users = array();
        foreach ( $typing_users as $id => $timestamp ) {
            if ( time() - $timestamp < 5 ) { // Consider typing for 5 seconds.
                $fresh_typing_users[ $id ] = $timestamp;
            }
        }

        set_transient( $transient_key, $fresh_typing_users, 10 ); // Transient expires in 10 seconds.
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * Callback to get the typing status of other users for a specific session.
     *
     * @return WP_REST_Response
     */
    public function get_typing_status( WP_REST_Request $request ) {
        $session_id = intval( $request->get_param( 'session_id' ) );
        if ( ! $session_id ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid session ID.' ), 400 );
        }

        $transient_key = 'simple_chat_typing_users_' . $session_id;
        $typing_users = get_transient( $transient_key );
        if ( false === $typing_users ) {
            $typing_users = array();
        }

        $current_user_id = get_current_user_id();
        $typing_status = array();
        
        foreach ( $typing_users as $user_id => $timestamp ) {
            // Exclude the current user from the typing list.
            if ( $user_id != $current_user_id ) {
                $user_data = get_userdata( $user_id );
                if ( $user_data ) {
                    $typing_status[] = $user_data->display_name;
                }
            }
        }

        return new WP_REST_Response( array( 'typing_users' => $typing_status ), 200 );
    }

    /**
     * Callback to get product information for a linked chat session.
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function get_session_product_info( WP_REST_Request $request ) {
        $session_id = intval( $request->get_param( 'session_id' ) );

        if ( ! $session_id ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid session ID.' ), 400 );
        }

        $product_id = get_post_meta( $session_id, '_simple_chat_linked_product_id', true );

        if ( $product_id > 0 && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $product_info = array(
                    'id'    => $product->get_id(),
                    'name'  => $product->get_name(),
                    'price' => $product->get_price(),
                    'image' => wp_get_attachment_image_src( $product->get_image_id(), 'thumbnail' )[0],
                    'url'   => $product->get_permalink(),
                );
                return new WP_REST_Response( $product_info, 200 );
            }
        }

        return new WP_REST_Response( array(), 404 );
    }
}

// Instantiate the class to run the plugin.
new Simple_Custom_Chat();