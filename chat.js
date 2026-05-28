// Horizon Chat - WhatsApp-style Direct & Group Chat Application Controller
document.addEventListener('DOMContentLoaded', () => {
    // Elements Selection
    const usersListPane = document.getElementById('users-list-pane');
    const roomsListPane = document.getElementById('rooms-list-pane');
    const searchUsersInput = document.getElementById('search-users');

    // Chat viewports
    const noChatScreen = document.getElementById('no-chat-screen');
    const activeChatContainer = document.getElementById('active-chat-container');
    const activeChatAvatar = document.getElementById('active-chat-avatar');
    const activeChatUsername = document.getElementById('active-chat-username');
    const messagesFeed = document.getElementById('messages-feed');
    const chatForm = document.getElementById('chat-form');
    const chatMessageInput = document.getElementById('chat-message-input');
    const activeContactIdInput = document.getElementById('active-contact-id');
    const activeRoomIdInput = document.getElementById('active-room-id');

    // Sidebar Tabs
    const tabChatsTrigger = document.getElementById('tab-chats-trigger');
    const tabGroupsTrigger = document.getElementById('tab-groups-trigger');
    const chatsTabContent = document.getElementById('chats-tab-content');
    const groupsTabContent = document.getElementById('groups-tab-content');

    // Create Group Modal Elements
    const btnAddGroup = document.getElementById('btn-add-group');
    const groupModal = document.getElementById('group-modal');
    const btnCloseGroupModal = document.getElementById('btn-close-group-modal');
    const createGroupForm = document.getElementById('create-group-form');
    const newGroupNameInput = document.getElementById('new-group-name');

    // Retrieve active logged in user from body attributes
    const currentUserId = parseInt(document.body.getAttribute('data-user-id'));
    const currentUsername = document.body.getAttribute('data-username');
    
    // Application State Variables
    let activeReceiverId = null; // direct user ID
    let activeRoomId = null;     // group room ID
    let pollInterval = null;     // main messages update timer
    let sidebarPollInterval = null; // sidebar badges background update timer

    // Caches to prevent rendering glitches
    let cachedMessagesJSON = '';
    let cachedUsersJSON = '';
    let cachedRoomsJSON = '';

    // Unread Messages Seen Tracking in LocalStorage
    let seenMessages = {};
    try {
        seenMessages = JSON.parse(localStorage.getItem('vybe_seen_messages') || '{}');
    } catch (e) {
        // Storage is disabled or blocked in Incognito mode
    }

    // Helper: Save seen state to LocalStorage
    function markAsSeen(key, timestamp) {
        if (!timestamp) return;
        seenMessages[key] = timestamp;
        try {
            localStorage.setItem('vybe_seen_messages', JSON.stringify(seenMessages));
        } catch (e) {
            // Storage is disabled or blocked in Incognito mode
        }
    }

    // Tab Switching Logic
    function switchTab(target) {
        if (target === 'chats') {
            tabChatsTrigger.classList.add('active');
            tabGroupsTrigger.classList.remove('active');
            chatsTabContent.classList.remove('d-none');
            groupsTabContent.classList.add('d-none');
            searchUsersInput.parentElement.style.display = 'flex'; // show search for direct chats
        } else {
            tabChatsTrigger.classList.remove('active');
            tabGroupsTrigger.classList.add('active');
            chatsTabContent.classList.add('d-none');
            groupsTabContent.classList.remove('d-none');
            searchUsersInput.parentElement.style.display = 'none'; // hide search for group rooms
        }
    }

    tabChatsTrigger.addEventListener('click', () => switchTab('chats'));
    tabGroupsTrigger.addEventListener('click', () => switchTab('groups'));

    // Modal Display Logic
    btnAddGroup.addEventListener('click', () => {
        groupModal.classList.remove('d-none');
        newGroupNameInput.focus();
    });

    btnCloseGroupModal.addEventListener('click', () => {
        groupModal.classList.add('d-none');
        createGroupForm.reset();
    });

    // Close modal on clicking outside the card
    groupModal.addEventListener('click', (e) => {
        if (e.target === groupModal) {
            groupModal.classList.add('d-none');
            createGroupForm.reset();
        }
    });

    // Handle Group Room Creation Form Submission
    createGroupForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const roomName = newGroupNameInput.value.trim();
        if (roomName === '') return;

        $.ajax({
            url: 'api.php?action=create_room',
            method: 'POST',
            data: { room_name: roomName },
            dataType: 'json',
            success: function(result) {
                if (result.status === 'success') {
                    // Hide Modal and Reset Form
                    groupModal.classList.add('d-none');
                    createGroupForm.reset();

                    // Refresh rooms list immediately
                    cachedRoomsJSON = '';
                    loadRooms(() => {
                        // Dynamically open the newly created room!
                        const newRoomItem = document.querySelector(`.contact-item[data-room-id="${result.room_id}"]`);
                        if (newRoomItem) {
                            newRoomItem.click();
                        }
                    });
                } else {
                    alert(result.message);
                }
            },
            error: function() {
                alert("Error connecting to server. Please try again.");
            }
        });
    });

    // Load Active Messages (Direct or Group)
    function loadMessages() {
        if (!activeReceiverId && !activeRoomId) return;

        let queryParam = activeRoomId ? 'room_id=' + activeRoomId : 'receiver_id=' + activeReceiverId;

        $.ajax({
            url: 'api.php?action=get_messages&' + queryParam,
            dataType: 'json',
            success: function(result) {
                if (result.status === 'success') {
                    // Check if messages have changed to avoid unnecessary rerender
                    const currentMessagesJSON = JSON.stringify(result.data);
                    if (currentMessagesJSON === cachedMessagesJSON) {
                        return; // skip DOM repaint
                    }
                    cachedMessagesJSON = currentMessagesJSON;

                    let html = '';
                    result.data.forEach(msg => {
                        const isSent = parseInt(msg.user_id) === currentUserId;
                        const msgClass = isSent ? 'sent' : 'received';
                        
                        let dateObj = new Date(msg.created_at);
                        let formattedTime = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                        let dropdownHtml = '';
                        let isDeleted = parseInt(msg.is_deleted) === 1;
                        let messageContent = msg.message;
                        let bubbleClass = '';

                        // Show sender's name inside group chat for messages sent by others
                        let senderNameHtml = '';
                        if (activeRoomId && !isSent && msg.username) {
                            senderNameHtml = `<span class="msg-sender-username">${msg.username}</span>`;
                        }
                        
                        if (isDeleted) {
                            messageContent = '<i class="bx bx-block" style="font-size: 13.5px; opacity: 0.6; margin-right: 4px; vertical-align: middle;"></i> <i style="opacity: 0.6;">This message was deleted</i>';
                            bubbleClass = 'deleted-bubble';
                        } else if (isSent) {
                            dropdownHtml = `
                                <div class="msg-dropdown-wrap">
                                    <i class="bx bx-chevron-down msg-dropdown-trigger"></i>
                                    <div class="msg-dropdown-menu">
                                        <button class="btn-delete-msg" data-msg-id="${msg.id}">
                                            <i class="bx bx-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            `;
                        }

                        html += `
                            <div class="msg-wrapper ${msgClass}" data-msg-id="${msg.id}">
                                <div class="msg-bubble-container">
                                    <div class="msg-bubble-row">
                                        <div class="msg-bubble ${bubbleClass}">
                                            ${senderNameHtml}
                                            <div>${messageContent}</div>
                                        </div>
                                        ${dropdownHtml}
                                    </div>
                                    <span class="msg-time">${formattedTime}</span>
                                </div>
                            </div>
                        `;
                    });

                    const isAtBottom = messagesFeed.scrollHeight - messagesFeed.clientHeight <= messagesFeed.scrollTop + 80;
                    messagesFeed.innerHTML = html || `<div style="color:var(--text-muted);text-align:center;padding:20px;">No messages yet. Send a message to start the conversation!</div>`;
                    
                    if (isAtBottom || messagesFeed.getAttribute('data-first-load') === 'true') {
                        messagesFeed.scrollTop = messagesFeed.scrollHeight;
                        messagesFeed.removeAttribute('data-first-load');
                    }

                    // Update seen timestamp to the latest message time for the active conversation
                    if (result.data.length > 0) {
                        const lastMsg = result.data[result.data.length - 1];
                        const conversationKey = activeRoomId ? 'room_' + activeRoomId : 'user_' + activeReceiverId;
                        markAsSeen(conversationKey, lastMsg.created_at);
                    }
                }
            }
        });
    }

    // Load registered users (Direct Contacts) with optional search filter
    function loadUsers(search = '') {
        $.ajax({
            url: 'api.php?action=get_users&query=' + encodeURIComponent(search),
            dataType: 'json',
            success: function(result) {
                if (result.status === 'success') {
                    // Cache check
                    const currentUsersJSON = JSON.stringify(result.data) + '_active_' + activeReceiverId;
                    if (currentUsersJSON === cachedUsersJSON) {
                        return; // Skip DOM repaint
                    }
                    cachedUsersJSON = currentUsersJSON;

                    const scrollPos = usersListPane.scrollTop;
                    let html = '';

                    result.data.forEach(user => {
                        let initials = user.username.substring(0, 2).toUpperCase();
                        const isActive = activeReceiverId === parseInt(user.id) ? 'active' : '';
                        
                        let lastMsg = user.last_message ? user.last_message : 'No messages yet';
                        if (lastMsg.length > 22) {
                            lastMsg = lastMsg.substring(0, 19) + '...';
                        }

                        let lastTimeFormatted = '';
                        if (user.last_time) {
                            let dateObj = new Date(user.last_time);
                            lastTimeFormatted = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        }

                        // Notification Badge computation
                        let badgeHtml = '';
                        if (user.unread_count && parseInt(user.unread_count) > 0 && activeReceiverId !== parseInt(user.id)) {
                            badgeHtml = `<span class="unread-badge">${user.unread_count}</span>`;
                        }

                        html += `
                            <div class="contact-item ${isActive}" data-id="${user.id}" data-username="${user.username}">
                                <div class="contact-avatar-initials">${initials}</div> 
                                <div class="contact-details">
                                    <div class="contact-main-info">
                                        <span class="contact-name">${user.username}</span>
                                        <div class="contact-last-msg">${lastMsg}</div>
                                    </div>
                                    <div class="contact-side-info">
                                        <span class="contact-last-time">${lastTimeFormatted}</span>
                                        ${badgeHtml}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    usersListPane.innerHTML = html || '<div style="color:var(--text-muted);text-align:center;padding:20px;">No contacts found.</div>';
                    usersListPane.scrollTop = scrollPos;

                    // Bind click listeners for direct chats
                    document.querySelectorAll('#users-list-pane .contact-item').forEach(item => {
                        item.addEventListener('click', () => {
                            const receiverId = parseInt(item.getAttribute('data-id'));
                            const username = item.getAttribute('data-username');
                            
                            // Remove highlights elsewhere
                            document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('active'));
                            item.classList.add('active');
                            
                            // Setup State
                            activeReceiverId = receiverId;
                            activeRoomId = null;

                            activeContactIdInput.value = receiverId;
                            activeRoomIdInput.value = '';

                            activeChatUsername.innerText = username;
                            activeChatAvatar.innerText = username.substring(0, 2).toUpperCase();
                            activeChatAvatar.classList.remove('group-avatar'); // restore blue gradient
                            
                            noChatScreen.classList.add('d-none');
                            activeChatContainer.classList.remove('d-none');
                            
                            // Clear seen state badge locally
                            const lastTimeStr = item.querySelector('.contact-last-time').innerText;
                            if (lastTimeStr) {
                                seenMessages['user_' + receiverId] = new Date().toISOString().slice(0, 19).replace('T', ' ');
                                try {
                                    localStorage.setItem('vybe_seen_messages', JSON.stringify(seenMessages));
                                } catch (e) {
                                    // ignore in Incognito
                                }
                            }
                            
                            // Load fresh messages
                            cachedMessagesJSON = '';
                            messagesFeed.setAttribute('data-first-load', 'true');
                            loadMessages();
                            
                            // Reset Sidebar Cache briefly to clear the notification badge instantly
                            cachedUsersJSON = '';
                            loadUsers(searchUsersInput.value.trim());

                            // Restart Poller
                            if (pollInterval) clearInterval(pollInterval);
                            pollInterval = setInterval(loadMessages, 3000);
                        });
                    });
                } 
            }
        });
    }

    // Load Chat Rooms (Groups)
    function loadRooms(callback = null) {
        const seenTimesStr = JSON.stringify(seenMessages);
        $.ajax({
            url: 'api.php?action=get_rooms&seen_times=' + encodeURIComponent(seenTimesStr),
            dataType: 'json',
            success: function(result) {
                if (result.status === 'success') {
                    const currentRoomsJSON = JSON.stringify(result.data) + '_active_' + activeRoomId;
                    if (currentRoomsJSON === cachedRoomsJSON) {
                        if (callback) callback();
                        return; // Skip repaint
                    }
                    cachedRoomsJSON = currentRoomsJSON;

                    const scrollPos = roomsListPane.scrollTop;
                    let html = '';

                    result.data.forEach(room => {
                        let initials = room.name.substring(0, 2).toUpperCase();
                        const isActive = activeRoomId === parseInt(room.id) ? 'active' : '';
                        
                        let lastMsg = 'No messages yet';
                        if (room.last_message) {
                            lastMsg = (room.sender_name ? room.sender_name + ': ' : '') + room.last_message;
                        }
                        if (lastMsg.length > 22) {
                            lastMsg = lastMsg.substring(0, 19) + '...';
                        }

                        let lastTimeFormatted = '';
                        if (room.last_time) {
                            let dateObj = new Date(room.last_time);
                            lastTimeFormatted = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        }

                        // Notification Badge computation
                        let badgeHtml = '';
                        if (room.unread_count && parseInt(room.unread_count) > 0 && activeRoomId !== parseInt(room.id)) {
                            badgeHtml = `<span class="unread-badge">${room.unread_count}</span>`;
                        }

                        html += `
                            <div class="contact-item ${isActive}" data-room-id="${room.id}" data-room-name="${room.name}">
                                <div class="contact-avatar-initials group-avatar">${initials}</div> 
                                <div class="contact-details">
                                    <div class="contact-main-info">
                                        <span class="contact-name">${room.name}</span>
                                        <div class="contact-last-msg">${lastMsg}</div>
                                    </div>
                                    <div class="contact-side-info">
                                        <span class="contact-last-time">${lastTimeFormatted}</span>
                                        ${badgeHtml}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    roomsListPane.innerHTML = html || '<div style="color:var(--text-muted);text-align:center;padding:20px;">No groups available. Create one to get started!</div>';
                    roomsListPane.scrollTop = scrollPos;

                    // Bind click listeners for group rooms
                    document.querySelectorAll('#rooms-list-pane .contact-item').forEach(item => {
                        item.addEventListener('click', () => {
                            const roomId = parseInt(item.getAttribute('data-room-id'));
                            const roomName = item.getAttribute('data-room-name');
                            
                            // Remove highlights elsewhere
                            document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('active'));
                            item.classList.add('active');
                            
                            // Setup State
                            activeRoomId = roomId;
                            activeReceiverId = null;

                            activeContactIdInput.value = '';
                            activeRoomIdInput.value = roomId;

                            activeChatUsername.innerText = roomName;
                            activeChatAvatar.innerText = roomName.substring(0, 2).toUpperCase();
                            activeChatAvatar.classList.add('group-avatar'); // trigger special group colors
                            
                            noChatScreen.classList.add('d-none');
                            activeChatContainer.classList.remove('d-none');
                            
                            // Clear seen state badge locally
                            const lastTimeStr = item.querySelector('.contact-last-time').innerText;
                            if (lastTimeStr) {
                                seenMessages['room_' + roomId] = new Date().toISOString().slice(0, 19).replace('T', ' ');
                                try {
                                    localStorage.setItem('vybe_seen_messages', JSON.stringify(seenMessages));
                                } catch (e) {
                                    // ignore in Incognito
                                }
                            }
                            
                            // Load fresh messages
                            cachedMessagesJSON = '';
                            messagesFeed.setAttribute('data-first-load', 'true');
                            loadMessages();
                            
                            // Reset Rooms Cache briefly to clear the notification badge instantly
                            cachedRoomsJSON = '';
                            loadRooms();

                            // Restart Poller
                            if (pollInterval) clearInterval(pollInterval);
                            pollInterval = setInterval(loadMessages, 3000);
                        });
                    });

                    if (callback) callback();
                }
            }
        });
    }

    // Dynamic typing search contacts
    searchUsersInput.addEventListener('input', (e) => {
        const searchText = e.target.value.trim();
        loadUsers(searchText);
    });

    // Handle sending message (Direct or Group)
    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const messageText = chatMessageInput.value.trim();
        if (messageText === '') return;

        let postData = { message: messageText };
        if (activeRoomId) {
            postData.room_id = activeRoomId;
        } else if (activeReceiverId) {
            postData.receiver_id = activeReceiverId;
        } else {
            return;
        }

        $.ajax({
            url: 'api.php?action=send_message',
            method: 'POST',
            data: postData,
            dataType: 'json',
            success: function(result) {
                if (result.status === 'success') {
                    chatMessageInput.value = '';
                    
                    // Immediately refresh chat feed and scroll to bottom
                    loadMessages();
                    
                    // Clear sidebar caches briefly to update the last message snippet dynamically
                    if (activeRoomId) {
                        cachedRoomsJSON = '';
                        loadRooms();
                    } else {
                        cachedUsersJSON = '';
                        loadUsers(searchUsersInput.value.trim());
                    }
                } else {
                    alert("Error sending message: " + result.message);
                }
            },
            error: function(xhr, status, error) {
                alert("Failed to send message. Please ensure you are logged in and your connection is active.");
            }
        });
    });

    // Toggle message dropdown menu
    $(document).on('click', '.msg-dropdown-trigger', function(e) {
        e.stopPropagation();
        const menu = $(this).siblings('.msg-dropdown-menu');
        $('.msg-dropdown-menu').not(menu).removeClass('show'); // Close all other menus
        menu.toggleClass('show');
    });

    // Close dropdown menu when clicking anywhere else
    $(document).on('click', function() {
        $('.msg-dropdown-menu').removeClass('show');
    });

    // Soft delete message (Direct or Group message)
    $(document).on('click', '.btn-delete-msg', function() {
        const msgId = $(this).attr('data-msg-id');
        if (confirm("Are you sure you want to delete this message?")) {
            $.ajax({
                url: 'api.php?action=delete_message',
                method: 'POST',
                data: { message_id: msgId },
                dataType: 'json',
                success: function(result) {
                    if (result.status === 'success') {
                        // Dynamically refresh messages list
                        loadMessages();
                        
                        // Clear caches to update last message preview in sidebar
                        if (activeRoomId) {
                            cachedRoomsJSON = '';
                            loadRooms();
                        } else {
                            cachedUsersJSON = '';
                            loadUsers(searchUsersInput.value.trim());
                        }
                    } else {
                        alert("Error: " + result.message);
                    }
                }
            });
        }
    });

    // Dynamic background poller for Sidebar Lists to update unread badge notifications in real-time
    function backgroundSidebarPoller() {
        loadUsers(searchUsersInput.value.trim());
        loadRooms();
    }

    // Bootstrapping: Load sidebar data immediately on application boot
    loadUsers();
    loadRooms();

    // Start background poll for sidebar badges every 6 seconds
    sidebarPollInterval = setInterval(backgroundSidebarPoller, 6000);
});
