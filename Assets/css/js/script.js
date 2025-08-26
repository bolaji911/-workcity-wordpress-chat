/**
 * Simple Custom Chat - Chatbox JavaScript
 *
 * This script handles all front-end functionality for the chatbox,
 * including loading messages, sending new messages, and managing
 * typing indicators.
 */
jQuery(document).ready(function ($) {
  // Cache selectors for performance.
  const chatBox = $("#simple-chat-box");
  const messagesContainer = $("#simple-chat-messages");
  const messageInput = $("#simple-chat-message-input");
  const sendButton = $("#simple-chat-send-button");
  const typingIndicator = $("#simple-chat-typing-indicator");

  // Get the session ID from the data attribute.
  const sessionId = chatBox.data("session-id");

  // Polling intervals in milliseconds.
  const fetchMessagesInterval = 2000; // Poll every 2 seconds for new messages.
  const typingStatusInterval = 3000; // Poll every 3 seconds for typing status.

  // A flag to prevent multiple message fetches at once.
  let isFetchingMessages = false;
  let lastMessageTimestamp = "";

  // A flag to track if the user is currently typing.
  let userIsTyping = false;
  let typingTimeout = null;

  // A variable to store the last fetched typing users to prevent unnecessary DOM updates.
  let lastTypingUsers = [];

  // URL to the REST API endpoint.
  const apiUrl = simpleChat.root + "simple-custom-chat/v1";

  // --------------------------------------------------------------------------
  // New WooCommerce Integration Functionality
  // --------------------------------------------------------------------------

  /**
   * Fetches and displays linked product information for the chat session.
   */
  function fetchAndDisplayProductInfo() {
    $.ajax({
      url: `${apiUrl}/session/${sessionId}/product`,
      method: "GET",
      dataType: "json",
      success: function (product) {
        if (product && Object.keys(product).length > 0) {
          const productHtml = `
                        <div class="simple-chat-product-info">
                            <a href="${product.url}" target="_blank">
                                <img src="${product.image}" alt="${product.name}" class="product-image">
                                <div class="product-details">
                                    <h4 class="product-name">${product.name}</h4>
                                    <p class="product-price">$${product.price}</p>
                                </div>
                            </a>
                        </div>
                    `;
          // Prepend the product info to the messages container.
          messagesContainer.before(productHtml);
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Failed to fetch product info:", textStatus, errorThrown);
      },
    });
  }

  // --------------------------------------------------------------------------
  // Core Chat Functions
  // --------------------------------------------------------------------------

  /**
   * Fetches messages from the REST API.
   */
  function fetchMessages() {
    if (isFetchingMessages) return;

    isFetchingMessages = true;

    $.ajax({
      url: `${apiUrl}/messages/${sessionId}`,
      method: "GET",
      dataType: "json",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", simpleChat.nonce);
      },
      success: function (messages) {
        if (messages && messages.length > 0) {
          // Reverse messages to display oldest at the top.
          const reversedMessages = messages.reverse();

          // Track the most recent message to prevent adding duplicates.
          if (messages[0].timestamp !== lastMessageTimestamp) {
            renderMessages(reversedMessages);
            lastMessageTimestamp = messages[0].timestamp;
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Failed to fetch messages:", textStatus, errorThrown);
      },
      complete: function () {
        isFetchingMessages = false;
      },
    });
  }

  /**
   * Renders messages to the chatbox.
   * @param {Array} messages - An array of message objects.
   */
  function renderMessages(messages) {
    messagesContainer.empty();
    messages.forEach((message) => {
      const isMe = parseInt(message.user_id) === parseInt(simpleChat.user_id);
      const messageClass = isMe ? "my-message" : "their-message";
      const userName = isMe ? simpleChat.user_name : message.user_name;
      const avatarHtml = `<img src="${message.avatar_url}" alt="${userName}" class="chat-avatar">`;

      const messageHtml = `
                <div class="simple-chat-message ${messageClass}">
                    <div class="message-header">
                        ${avatarHtml}
                        <span class="user-name">${userName}</span>
                        <span class="message-timestamp">${new Date(
                          message.timestamp
                        ).toLocaleTimeString()}</span>
                    </div>
                    <div class="message-body">
                        ${message.message}
                    </div>
                </div>
            `;
      messagesContainer.append(messageHtml);
    });

    // Scroll to the bottom of the messages container.
    messagesContainer.scrollTop(messagesContainer.prop("scrollHeight"));
  }

  /**
   * Sends a message to the REST API.
   */
  function sendMessage() {
    const messageText = messageInput.val().trim();
    if (messageText === "") {
      return;
    }

    $.ajax({
      url: `${apiUrl}/messages/${sessionId}`,
      method: "POST",
      data: JSON.stringify({ message: messageText }),
      contentType: "application/json",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", simpleChat.nonce);
      },
      success: function (response) {
        // Fetch messages again to get the new message and update the view.
        fetchMessages();
        // Clear the input field.
        messageInput.val("");
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error("Failed to send message:", textStatus, errorThrown);
      },
    });
  }

  /**
   * Updates the user's typing status.
   * @param {boolean} isTyping - True if the user is typing, false otherwise.
   */
  function updateTypingStatus(isTyping) {
    $.ajax({
      url: `${apiUrl}/typing/${sessionId}`,
      method: "POST",
      data: { is_typing: isTyping ? 1 : 0 },
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", simpleChat.nonce);
      },
    });
  }

  /**
   * Fetches and displays the typing status of other users.
   */
  function getTypingStatus() {
    $.ajax({
      url: `${apiUrl}/typing/${sessionId}`,
      method: "GET",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", simpleChat.nonce);
      },
      success: function (response) {
        const typingUsers = response.typing_users;

        // Check if the typing status has actually changed before updating the DOM.
        if (JSON.stringify(typingUsers) !== JSON.stringify(lastTypingUsers)) {
          lastTypingUsers = typingUsers;
          if (typingUsers.length > 0) {
            const userList = typingUsers.join(", ");
            typingIndicator.html(`<span>${userList} is typing...</span>`);
            typingIndicator.removeClass("hidden");
          } else {
            typingIndicator.addClass("hidden");
          }
        }
      },
    });
  }

  // --------------------------------------------------------------------------
  // Event Listeners and Initialization
  // --------------------------------------------------------------------------

  // Send button click.
  sendButton.on("click", function () {
    sendMessage();
  });

  // Enter key press in the input field.
  messageInput.on("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      sendMessage();
    }
  });

  // Typing indicator logic.
  messageInput.on("keyup", function () {
    if (messageInput.val().trim() !== "" && !userIsTyping) {
      userIsTyping = true;
      updateTypingStatus(true);
    }

    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
      userIsTyping = false;
      updateTypingStatus(false);
    }, 5000); // Stop typing after 5 seconds of no activity.
  });

  // Initial load and recurring polls.
  if (sessionId) {
    // Fetch and display product info on initial load.
    fetchAndDisplayProductInfo();
    // Fetch initial messages.
    fetchMessages();

    // Start polling for new messages.
    setInterval(fetchMessages, fetchMessagesInterval);
    // Start polling for typing status.
    setInterval(getTypingStatus, typingStatusInterval);
  }
});
