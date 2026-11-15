<!-- Chat Widget (iframe‑based) with typing animation -->
<div class="chat-widget-fixed">
    <div id="chat-toggle" class="chat-toggle-btn">
        <i class="fas fa-comment-dots"></i>
    </div>
    <div id="chat-window" class="chat-window" style="display: none;">
        <div class="chat-header">
            <div class="chat-header-info">
                <i class="fas fa-robot"></i>
                <div><h4>SC Hotel AI</h4><span>Online</span></div>
            </div>
            <button id="chat-close" class="chat-close-btn"><i class="fas fa-times"></i></button>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div class="bot-message">
                <div class="bot-avatar"><i class="fas fa-robot"></i></div>
                <div class="message-bubble bot-bubble">Hello! I'm SC Assistant. Ask me about room prices, availability, check-in/out times, or booking help.</div>
            </div>
        </div>
        <div class="chat-input-area">
            <form id="chatForm" target="chatIframe" method="GET" action="<?= SITE_URL ?>assistant.php" style="display: flex; gap: 8px; width: 100%;">
                <input type="text" id="chat-input" name="msg" placeholder="Type your question..." autocomplete="off" style="flex:1; padding: 10px 16px; border-radius: 30px; border: 1px solid #dee2e6; outline: none;">
                <button type="submit" id="send-btn" style="background: #28a745; color: white; border: none; border-radius: 50%; width: 40px; height: 40px;"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
        <div class="chat-footer"><small>Powered by OpenAI</small></div>
    </div>
</div>

<iframe name="chatIframe" id="chatIframe" style="display: none;"></iframe>

<style>
/* ========== CHAT WIDGET CSS ========== */
.chat-widget-fixed {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.chat-toggle-btn {
    background: linear-gradient(135deg, #1e7e34, #28a745);
    color: white;
    width: 55px;
    height: 55px;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}
.chat-toggle-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 12px 28px rgba(30,126,52,0.3);
}
.chat-window {
    width: 380px;
    height: 520px;
    background: white;
    border-radius: 28px;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: fixed;
    bottom: 90px;
    right: 0;
    z-index: 9998;
}
.chat-header {
    background: linear-gradient(135deg, #1e7e34, #28a745);
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
}
.chat-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.chat-header-info i { font-size: 1.8rem; }
.chat-header-info h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
}
.chat-header-info span { font-size: 0.7rem; opacity: 0.9; }
.chat-close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.chat-close-btn:hover { background: rgba(255,255,255,0.2); }
.chat-messages {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background: #f5f7fa;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.bot-message, .user-message {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    animation: fadeInUp 0.3s ease;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.user-message { flex-direction: row-reverse; }
.bot-avatar, .user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.bot-avatar { background: #e9ecef; color: #28a745; }
.user-avatar { background: #28a745; color: white; }
.message-bubble {
    max-width: 70%;
    padding: 10px 14px;
    border-radius: 20px;
    font-size: 0.9rem;
    line-height: 1.4;
    word-wrap: break-word;
}
.bot-bubble {
    background: white;
    color: #1e2a3a;
    border-top-left-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.user-bubble {
    background: #28a745;
    color: white;
    border-top-right-radius: 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.chat-input-area {
    display: flex;
    padding: 12px 16px;
    background: white;
    border-top: 1px solid #e9ecef;
    gap: 10px;
    align-items: center;
}
#chat-input {
    flex: 1;
    padding: 10px 16px;
    border: 1px solid #dee2e6;
    border-radius: 30px;
    outline: none;
    font-size: 0.9rem;
    transition: all 0.2s;
}
#chat-input:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 3px rgba(40,167,69,0.2);
}
#send-btn {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}
#send-btn:hover {
    background: #1e7e34;
    transform: scale(1.05);
}
.chat-footer {
    background: #f8f9fa;
    text-align: center;
    padding: 6px;
    font-size: 0.7rem;
    color: #6c757d;
    border-top: 1px solid #e9ecef;
}
/* ========== TYPING INDICATOR ========== */
.typing-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    background: white;
    border-radius: 20px;
    border-top-left-radius: 4px;
    width: fit-content;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.typing-indicator span {
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    display: inline-block;
    animation: typingBounce 1.4s infinite ease-in-out both;
}
.typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
.typing-indicator span:nth-child(3) { animation-delay: 0s; }
@keyframes typingBounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}
@media (max-width: 480px) {
    .chat-window {
        width: calc(100vw - 40px);
        right: 20px;
        bottom: 80px;
        height: 480px;
    }
    .chat-toggle-btn {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    var chatWindow = $('#chat-window');
    var chatMessages = $('#chat-messages');
    var chatInput = $('#chat-input');
    var chatForm = $('#chatForm');
    var iframe = $('#chatIframe');

    chatWindow.hide();
    $('#chat-toggle').click(function(){
        chatWindow.toggle();
        if (chatWindow.is(':visible')) chatInput.focus();
    });
    $('#chat-close').click(function(){ chatWindow.hide(); });

    // Typing indicator functions
    var typingDiv = null;
    function showTypingIndicator() {
        // remove any existing typing indicator
        removeTypingIndicator();
        typingDiv = $('<div class="bot-message">' +
            '<div class="bot-avatar"><i class="fas fa-robot"></i></div>' +
            '<div class="typing-indicator"><span></span><span></span><span></span></div>' +
            '</div>');
        chatMessages.append(typingDiv);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }
    function removeTypingIndicator() {
        if (typingDiv) {
            typingDiv.remove();
            typingDiv = null;
        }
    }

    function appendMessage(text, isUser) {
        var messageHtml = '';
        // Convert URLs to clickable links for bot messages
        if (!isUser) {
            var urlRegex = /(https?:\/\/[^\s]+)/g;
            text = text.replace(urlRegex, function(url) {
                return '<a href="' + url + '" target="_blank" style="color: #28a745; text-decoration: underline;">' + url + '</a>';
            });
        }
        if (isUser) {
            messageHtml = '<div class="user-message">' +
                '<div class="message-bubble user-bubble">' + escapeHtml(text) + '</div>' +
                '<div class="user-avatar"><i class="fas fa-user"></i></div>' +
                '</div>';
        } else {
            messageHtml = '<div class="bot-message">' +
                '<div class="bot-avatar"><i class="fas fa-robot"></i></div>' +
                '<div class="message-bubble bot-bubble">' + text + '</div>' +
                '</div>';
        }
        chatMessages.append(messageHtml);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    // When the iframe loads, read its content and hide typing indicator
    iframe.on('load', function() {
        removeTypingIndicator();
        try {
            var iframeDoc = this[0].contentWindow.document;
            var responseText = iframeDoc.body.innerText || iframeDoc.body.textContent;
            if (responseText && responseText.trim()) {
                var data = JSON.parse(responseText);
                if (data.reply) {
                    appendMessage(data.reply, false);
                } else {
                    appendMessage('Sorry, I did not understand.', false);
                }
            } else {
                appendMessage('No response from server.', false);
            }
        } catch(e) {
            console.error("Iframe error:", e);
            appendMessage('Sorry, I\'m having trouble. Please try again.', false);
        }
        // Clear the iframe body for the next request
        iframe.contents().find('body').empty();
    });

    // Handle form submission
    chatForm.on('submit', function(e) {
        var msg = chatInput.val().trim();
        if (msg === "") {
            e.preventDefault();
            return false;
        }
        // Show user message instantly
        appendMessage(msg, true);
        chatInput.val('');
        // Show typing indicator while waiting for response
        showTypingIndicator();
        // Allow the form to submit to the iframe – no page reload
    });
    
    function escapeHtml(str) {
        return str.replace(/[&<>]/g, function(m){
            if(m === '&') return '&amp;';
            if(m === '<') return '&lt;';
            if(m === '>') return '&gt;';
            return m;
        });
    }
});
</script>