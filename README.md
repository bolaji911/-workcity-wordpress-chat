# **Simple Custom Chat**

A lightweight, custom-built chat system designed for WordPress, offering real-time messaging, user authentication, and seamless integration with WooCommerce.

### **🚀 Setup and Installation**

1. **Download and Place:** Download the plugin files and place the simple-custom-chat folder into your WordPress installation's wp-content/plugins/ directory.
2. **Activate:** Navigate to your WordPress dashboard, go to the "Plugins" page, and click "Activate" next to "Simple Custom Chat."
3. **Create a Chat Session:** A new menu item called "Chat Sessions" will appear in your admin sidebar. Go to Chat Sessions \> Add New to create a new chat session.
4. **Configure Access:** On the chat session edit screen, use the **"Chat Access Roles"** meta box to select which user roles can view and participate in this chat.
5. **Display the Chatbox:** Use the \[simple_chat_box\] shortcode on any page or post.
   - To display a specific chat, use the session_id attribute: \[simple_chat_box session_id="123"\].
   - To link a chat directly to a WooCommerce product, use the product_id attribute: \[simple_chat_box product_id="456"\].
6. **WooCommerce Integration (Optional):** When editing a product in WooCommerce, a new **"Link to Chat Session"** meta box will appear on the product page. Enter a chat session ID here to automatically display the chat box on the product page.

### **💻 Technologies Used**

This plugin was built from scratch to be a self-contained solution, utilizing core WordPress functionalities and standard web technologies.

- **WordPress Core:** Custom Post Types (for chat sessions), the WordPress database API ($wpdb), and Transients (for temporary data).
- **PHP:** The primary server-side language for handling plugin logic, database operations, and user authentication.
- **WordPress REST API:** Provides the endpoints for the front-end to retrieve and send messages without refreshing the page.
- **JavaScript & jQuery:** Used for all front-end functionality, including AJAX calls, real-time polling, and dynamic DOM manipulation.
- **CSS:** Custom-written styles to ensure a clean, modern, and responsive chatbox design.
- **WooCommerce:** Integrated for linking specific chat sessions to products.

### **🚧 Challenges and Solutions**

#### **1\. Data Persistence and Structure**

- **Challenge:** How do we store chat messages in a way that is organized and efficient, separate from standard post data?
- **Solution:** We created a **custom database table** (wp_simple_chat_messages) to store chat messages. Using $wpdb, we can perform direct, optimized queries to add and retrieve messages, preventing clutter in the core wp_posts table.

#### **2\. Real-time Message Updates**

- **Challenge:** How can users see new messages as they are sent without having to refresh the page?
- **Solution:** We used **AJAX polling**. The JavaScript front-end repeatedly sends a GET request to a custom REST API endpoint (/messages) every few seconds to check for new messages. This creates a near-real-time experience.

#### **3\. Role-Based Access Control**

- **Challenge:** How do we restrict access so that only specific user roles can view or participate in a particular chat?
- **Solution:** We implemented **role-based access control** using a custom meta box on the simple_chat_session custom post type. The selected roles are stored in post meta. Both the shortcode and the REST API endpoints have permission callbacks that check the current user's role against the stored list, denying access if they don't match.

#### **4\. Temporary State Management (Typing Indicators)**

- **Challenge:** How can we show that a user is "typing..." without creating a permanent record in the database?
- **Solution:** We used the **WordPress Transients API**. When a user starts typing, we set a transient key with their user ID and a short expiration time (e.g., 10 seconds). The front-end polls a different API endpoint to check for active transients on that session. When the user stops typing, we delete the transient. This is a lightweight and efficient way to handle temporary data.
