jQuery(document).ready(function($) {
    
    console.log('AIChatbot: Script loaded, jQuery version:', $.fn.jquery);
    console.log('AIChatbot: AJAX object:', aichatbot_ajax);
    
    // Track state to prevent infinite loops
    let lastMessageCount = 0;
    let isWaitingForResponse = false;
    let refreshInterval = null;
    let currentSessionId = null; // Track session ID for conversation continuity
    
    $('#aichatbot-send-btn').click(sendMessage);
    $('#aichatbot-test-btn').click(testAI);
    
    $('#aichatbot-message-input').keypress(function(e) {
        if (e.which == 13) {
            sendMessage();
        }
    });
    
    function addMessage(type, message, sender) {
        const messageHtml = '<div class="aichatbot-message ' + type + '-message"><strong>' + sender + ':</strong> ' + message + '</div>';
        $('#aichatbot-messages').append(messageHtml);
        $('#aichatbot-messages').scrollTop($('#aichatbot-messages')[0].scrollHeight);
    }

    function loadMessages() {
        if (isWaitingForResponse) {
            return; // Don't load messages while waiting for response
        }
        
        $.ajax({
            url: aichatbot_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aichatbot_get_messages',
                session_id: currentSessionId, // Send current session ID
                nonce: aichatbot_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const messages = response.data;
                    const currentMessageCount = messages.length;
                    
                    console.log('AIChatbot: Messages response:', messages);
                    console.log('AIChatbot: Current message count:', currentMessageCount, 'Last message count:', lastMessageCount);
                    
                    // Only update if we have new messages or we're waiting for a response
                    if (currentMessageCount > lastMessageCount || isWaitingForResponse) {
                        $('#aichatbot-messages').empty();
                        
                        messages.forEach(function(msg) {
                            const messageType = msg.message_type === 'user_input' ? 'user' : 'ai';
                            const sender = msg.message_type === 'user_input' ? msg.user_name : 'AI Assistant';
                            addMessage(messageType, msg.message, sender);
                        });
                        
                        lastMessageCount = currentMessageCount;
                        console.log('AIChatbot: Rendered', currentMessageCount, 'messages');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('AIChatbot: Load messages error:', error);
            }
        });
    }
    
    function sendMessage() {
        console.log('AIChatbot: sendMessage called');
        
        const messageInput = $('#aichatbot-message-input');
        console.log('AIChatbot: messageInput element:', messageInput.length > 0 ? 'found' : 'not found');
        
        if (messageInput.length === 0) {
            console.error('AIChatbot: Message input element not found');
            return;
        }
        
        const messageValue = messageInput.val();
        console.log('AIChatbot: messageValue:', messageValue);
        
        const message = messageValue && typeof messageValue === 'string' ? messageValue.trim() : '';
        console.log('AIChatbot: trimmed message:', message);
        
        const displayNameInput = $('#aichatbot-user-name');
        console.log('AIChatbot: displayNameInput element:', displayNameInput.length > 0 ? 'found' : 'not found');
        
        let displayName = '';
        if (displayNameInput.length > 0) {
            const displayNameValue = displayNameInput.val();
            displayName = displayNameValue && typeof displayNameValue === 'string' ? displayNameValue.trim() : '';
        }
        console.log('AIChatbot: displayName:', displayName);
        
        if (!message) {
            console.log('AIChatbot: No message to send');
            return;
        }
        
        // Clear input
        messageInput.val('');
        
        // Add user message to chat
        addMessage('user', message, displayName || 'You');
        
        // Show typing indicator
        addMessage('ai', 'Typing...', 'AI Assistant');
        
        // Set waiting state
        isWaitingForResponse = true;
        
        // Send message to server
        $.ajax({
            url: aichatbot_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aichatbot_send_message',
                message: message,
                display_name: displayName,
                session_id: currentSessionId, // Send current session ID
                nonce: aichatbot_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Remove typing indicator
                    $('.ai-message:last').remove();
                    
                    // Add AI response
                    addMessage('ai', response.data.ai_response, 'AI Assistant');
                    
                    // Update session ID for next message
                    if (response.data.session_id) {
                        currentSessionId = response.data.session_id;
                        console.log('AIChatbot: Session ID updated:', currentSessionId);
                    }
                    
                    // Load messages after a short delay
                    setTimeout(function() {
                        loadMessages();
                    }, 500);
                    
                    setTimeout(function() {
                        loadMessages();
                    }, 1000);
                    
                } else {
                    $('.ai-message:last').remove();
                    addMessage('ai', 'Sorry, there was an error processing your message.', 'AI Assistant');
                }
            },
            error: function() {
                $('.ai-message:last').remove();
                addMessage('ai', 'Sorry, there was an error processing your message.', 'AI Assistant');
            },
            complete: function() {
                isWaitingForResponse = false;
            }
        });
        
        // Fallback if no response after 3 seconds
        setTimeout(function() {
            if (isWaitingForResponse) {
                $('.ai-message:last').remove();
                addMessage('ai', 'I apologize, but I\'m having trouble responding right now. Please try again in a moment or contact support if the issue persists.', 'AI Assistant');
                isWaitingForResponse = false;
            }
        }, 3000);
    }
    
    function testAI() {
        console.log('AIChatbot: Testing AI...');
        
        $.ajax({
            url: aichatbot_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'aichatbot_test_ai',
                nonce: aichatbot_ajax.nonce
            },
            success: function(response) {
                console.log('AIChatbot: Test response:', response);
                if (response.success) {
                    alert('AI Test Successful!\nResponse: ' + response.data.response.substring(0, 100) + '...');
                } else {
                    alert('AI Test Failed: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.log('AIChatbot: Test error:', error, xhr.responseText);
                alert('AI Test Error: ' + error);
            }
        });
    }
    
    // Toggle chat widget
    $('#aichatbot-toggle').click(function() {
        $('#aichatbot-box').toggle();
        if ($('#aichatbot-box').is(':visible')) {
            // Initialize new session when chat is opened
            currentSessionId = null;
            lastMessageCount = 0;
            $('#aichatbot-messages').empty();
            console.log('AIChatbot: Chat opened, new session initialized');
            
            // Start message refresh
            if (!refreshInterval) {
                refreshInterval = setInterval(loadMessages, 10000);
            }
        } else {
            // Clear interval when chat is closed
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
        }
    });
    
    // Minimize chat widget
    $('#aichatbot-minimize').click(function() {
        $('#aichatbot-box').hide();
        // Clear interval when chat is minimized
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    });
});