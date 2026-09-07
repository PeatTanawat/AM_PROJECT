<?php
// chatmessage.php
?>
<style>
    /* Floating Chat Button */
    .floating-chat-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 65px;
        height: 65px;
        background-color: #3b5998;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        cursor: pointer;
        z-index: 1000;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .floating-chat-btn:hover {
        background-color: #2d4373;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        color: #fff;
    }

    /* Chat Box */
    .chat-box-container {
        position: fixed;
        bottom: 105px;
        right: 30px;
        width: 350px;
        max-height: calc(100vh - 150px);
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.15);
        display: none;
        flex-direction: column;
        z-index: 9999;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }

    .chat-box-container.active {
        display: flex;
        animation: chatSlideUp 0.3s ease-out forwards;
    }

    @keyframes chatSlideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .chat-box-header {
        background-color: #3b5998;
        color: #fff;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-box-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chat-box-back {
        background: none;
        border: none;
        color: #fff;
        font-size: 1.4rem;
        cursor: pointer;
        padding: 0;
        display: none;
        align-items: center;
        justify-content: center;
        line-height: 1;
        transition: opacity 0.2s;
    }

    .chat-box-back:hover {
        opacity: 0.8;
    }

    .chat-box-title {
        font-weight: 600;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .chat-box-close {
        background: none;
        border: none;
        color: #fff;
        font-size: 1.5rem;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        opacity: 0.8;
        transition: opacity 0.2s;
    }

    .chat-box-close:hover {
        opacity: 1;
    }

    .chat-box-body {
        padding: 20px;
        flex: 1;
        overflow-y: auto;
        background-color: #f8fafc;
        min-height: 300px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .chat-message-wrapper {
        display: flex;
        flex-direction: column;
        max-width: 85%;
    }

    .chat-message-wrapper.received {
        align-self: flex-start;
    }

    .chat-message-wrapper.sent {
        align-self: flex-end;
    }

    .chat-message {
        padding: 10px 16px;
        border-radius: 18px;
        font-size: 0.95rem;
        line-height: 1.5;
        background-color: #fff;
        color: #334155;
        border: 1px solid #e2e8f0;
        word-break: break-word;
    }

    .chat-message-wrapper.received .chat-message {
        border-bottom-left-radius: 4px;
    }

    .chat-message-wrapper.sent .chat-message {
        background-color: #5d5fef;
        color: #fff;
        border: none;
        border-bottom-right-radius: 4px;
    }

    .chat-time {
        font-size: 0.75rem;
        margin-top: 4px;
        color: #64748b;
    }

    .chat-message-wrapper.received .chat-time {
        align-self: flex-start;
        margin-left: 4px;
    }

    .chat-message-wrapper.sent .chat-time {
        align-self: flex-end;
        margin-right: 4px;
    }

    .chat-date-divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 16px 0 8px 0;
        color: #64748b;
        font-size: 0.75rem;
    }
    
    .chat-date-divider::before,
    .chat-date-divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid #cbd5e1;
    }
    
    .chat-date-divider::before {
        margin-right: 10px;
    }
    
    .chat-date-divider::after {
        margin-left: 10px;
    }

    .chat-box-footer {
        padding: 15px;
        background-color: #fff;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .chat-input {
        flex: 1;
        padding: 10px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 20px;
        outline: none;
        font-size: 0.95rem;
        font-family: inherit;
        transition: border-color 0.2s;
    }

    .chat-input:focus {
        border-color: #3b5998;
    }

    .chat-send-btn {
        background-color: #3b5998;
        color: #fff;
        border: none;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .chat-send-btn:hover {
        background-color: #2d4373;
    }

    /* Mobile Responsive - Instagram DM Style Full Screen */
    @media (max-width: 576px) {
        .chat-box-container {
            width: 100% !important;
            height: 100% !important;
            height: 100dvh !important;
            max-height: 100dvh !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            border-radius: 0 !important;
            border: none !important;
            position: fixed !important;
            z-index: 99999 !important;
        }

        .chat-box-header {
            padding: 14px 16px;
        }

        .chat-box-back {
            display: flex;
        }

        .chat-box-close {
            display: none;
        }

        .floating-chat-btn.active-chat {
            display: none;
        }
    }
</style>

<!-- Floating Chat Button -->
<a href="#" class="floating-chat-btn" title="ติดต่อเรา">
    <i class="bi bi-chat-text-fill"></i>
</a>

<!-- Chat Box Container -->
<div class="chat-box-container" id="chatBoxContainer">
    <div class="chat-box-header">
        <div class="chat-box-header-left">
            <button class="chat-box-back" id="backChatBtn" title="ย้อนกลับ">
                <i class="bi bi-chevron-left"></i>
            </button>
            <div class="chat-box-title">
                <i class="bi bi-headset"></i> ติดต่อผู้ดูแลระบบ
            </div>
        </div>
        <button class="chat-box-close" id="closeChatBtn" title="ปิดหน้าต่าง">&times;</button>
    </div>
    <div class="chat-box-body" id="chatBoxBody">
    </div>
    <div class="chat-box-footer">
        <input type="text" class="chat-input" id="chatInputMessage" placeholder="พิมพ์ข้อความที่นี่..." autocomplete="off">
        <button class="chat-send-btn" id="btnSendChat" title="ส่งข้อความ"><i class="bi bi-send-fill"></i></button>
    </div>
</div>

<script>
// Chat Box Toggle Logic
document.addEventListener('DOMContentLoaded', function() {
    const chatBtn = document.querySelector('.floating-chat-btn');
    const chatBox = document.getElementById('chatBoxContainer');
    const closeChatBtn = document.getElementById('closeChatBtn');
    const backChatBtn = document.getElementById('backChatBtn');
    
    const chatBody = document.getElementById('chatBoxBody');
    const chatInput = document.getElementById('chatInputMessage');
    const btnSendChat = document.getElementById('btnSendChat');

    function toggleChat(open) {
        if (!chatBox) return;
        if (open) {
            chatBox.classList.add('active');
            if (chatBtn) chatBtn.classList.add('active-chat');
            if (window.innerWidth <= 576) {
                document.body.style.overflow = 'hidden';
            }
            if (chatInput) chatInput.focus();
            loadMessages();
        } else {
            chatBox.classList.remove('active');
            if (chatBtn) chatBtn.classList.remove('active-chat');
            document.body.style.overflow = '';
        }
    }

    if (chatBtn && chatBox) {
        chatBtn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleChat(!chatBox.classList.contains('active'));
        });
    }

    if (closeChatBtn) {
        closeChatBtn.addEventListener('click', function() {
            toggleChat(false);
        });
    }

    if (backChatBtn) {
        backChatBtn.addEventListener('click', function() {
            toggleChat(false);
        });
    }

    function scrollToBottom() {
        if(chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }
    }

    function formatChatDate(dateObj) {
        const now = new Date();
        const isToday = dateObj.getDate() === now.getDate() && 
                        dateObj.getMonth() === now.getMonth() && 
                        dateObj.getFullYear() === now.getFullYear();
        const isSameYear = dateObj.getFullYear() === now.getFullYear();
        
        if (isToday) {
            return "Today"; // Today
        }
        
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        const day = dateObj.getDate();
        const month = monthNames[dateObj.getMonth()];
        
        if (isSameYear) {
            return day + ' ' + month;
        }
        
        const year = dateObj.getFullYear();
        return day + ' ' + month + ' ' + year;
    }

    function formatChatTimeOnly(dateObj) {
        const hours = dateObj.getHours().toString().padStart(2, '0');
        const mins = dateObj.getMinutes().toString().padStart(2, '0');
        return hours + ':' + mins;
    }

    let lastMessageCount = -1;

    function loadMessages() {
        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "chat",
                request_function: "get_messages"
            },
            dataType: "json",
            success: function(response) {
                if (response.result == 1) {
                    renderMessages(response.data);
                }
            }
        });
    }

    // Set polling every 5 seconds
    setInterval(loadMessages, 5000);

    function renderMessages(messages) {
        if (messages.length === lastMessageCount) {
            return; // No new messages, skip re-rendering to prevent flicker
        }
        lastMessageCount = messages.length;

        chatBody.innerHTML = '';
        if (messages.length > 0) {
            let lastDateString = "";

            messages.forEach(msg => {
                let msgDateObj = null;
                
                if (msg.created_at) {
                    msgDateObj = new Date(msg.created_at.replace(/-/g, "/"));
                    if (!isNaN(msgDateObj.getTime())) {
                        const dateString = formatChatDate(msgDateObj);
                        
                        if (dateString !== lastDateString) {
                            const dividerDiv = document.createElement('div');
                            dividerDiv.className = 'chat-date-divider';
                            dividerDiv.textContent = dateString;
                            chatBody.appendChild(dividerDiv);
                            lastDateString = dateString;
                        }
                    }
                }

                const wrapperDiv = document.createElement('div');
                wrapperDiv.className = 'chat-message-wrapper ' + (msg.is_mine ? 'sent' : 'received');

                const msgDiv = document.createElement('div');
                msgDiv.className = 'chat-message';
                msgDiv.textContent = msg.message;
                wrapperDiv.appendChild(msgDiv);
                
                if (msgDateObj && !isNaN(msgDateObj.getTime())) {
                    const timeDiv = document.createElement('div');
                    timeDiv.className = 'chat-time';
                    timeDiv.textContent = formatChatTimeOnly(msgDateObj);
                    wrapperDiv.appendChild(timeDiv);
                }

                chatBody.appendChild(wrapperDiv);
            });
        }
        scrollToBottom();
    }

    function sendChatMessage() {
        const message = chatInput.value.trim();
        if (message === '') return;

        // Add message to UI
        const now = new Date();
        const dateString = formatChatDate(now);
        
        // Find if we need to add a divider
        const dividers = chatBody.querySelectorAll('.chat-date-divider');
        const lastDivider = dividers.length > 0 ? dividers[dividers.length - 1].textContent : "";
        if (lastDivider !== dateString) {
            const dividerDiv = document.createElement('div');
            dividerDiv.className = 'chat-date-divider';
            dividerDiv.textContent = dateString;
            chatBody.appendChild(dividerDiv);
        }

        const wrapperDiv = document.createElement('div');
        wrapperDiv.className = 'chat-message-wrapper sent';

        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-message';
        msgDiv.textContent = message;
        wrapperDiv.appendChild(msgDiv);
        
        const timeDiv = document.createElement('div');
        timeDiv.className = 'chat-time';
        timeDiv.textContent = formatChatTimeOnly(now);
        wrapperDiv.appendChild(timeDiv);
        
        chatBody.appendChild(wrapperDiv);
        
        chatInput.value = '';
        scrollToBottom();

        // Send AJAX request
        $.ajax({
            type: "POST",
            url: "core.php",
            data: {
                request_state: "chat",
                request_function: "send_message",
                message: message
            },
            dataType: "json",
            success: function(response) {
                if (response.result != 1) {
                    console.error("Failed to send message: ", response.msg);
                }
            },
            error: function() {
                console.error("Error sending message via AJAX");
            }
        });
    }

    if (btnSendChat && chatInput) {
        btnSendChat.addEventListener('click', sendChatMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendChatMessage();
            }
        });
    }
});
</script>

