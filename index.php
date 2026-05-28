<?php
// Include centralized database connection
require_once 'db.php';

// Auth check - redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) { //check if the user_id is exist or not
    header('Location: login.php');
    exit;
}

$current_username = $_SESSION['username'];
$current_user_id = $_SESSION['user_id'];

// Resolve application base folder path for relative asset loading
$base_href = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/'; //$_SERVER['SCRIPT_NAME'] This gives: current PHP file path in URL If page:http://localhost/chatapp/index.php then: $_SERVER['SCRIPT_NAME'] returns: /chatapp/index.php dirname() means: Get folder name only str.replace() Replace: \ with: / 
// rtrim = remove characters from RIGHT side
// ./ means concatenate 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vybee – Chat Different</title>
    <!-- Base Path for Relative Assets -->
    <base href="<?php echo htmlspecialchars($base_href); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Boxicons Icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=8.0">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Frontend JS controller -->
    <script src="chat.js?v=2.1" defer></script>
</head>
<body class="chat-body" 
 data-user-id="<?php echo $current_user_id; ?>" 
 data-username="<?php echo htmlspecialchars($current_username); ?>">

    <div class="chat-app-container">  <!--whole app wrapper -->
        <!-- Sidebar Panel -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="logo-animation-wrap">
                        <svg class="animated-logo-svg" viewBox="0 0 102 82" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="logo-grad" x1="0%" y1="100%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#00f5a0" />
                                    <stop offset="100%" stop-color="#00d9f9" />
                                </linearGradient>
                                <filter id="neon-glow" x="-20%" y="-20%" width="140%" height="140%">
                                    <feGaussianBlur stdDeviation="3" result="blur" />
                                    <feMerge>
                                        <feMergeNode in="blur" />
                                        <feMergeNode in="SourceGraphic" />
                                    </feMerge>
                                </filter>
                            </defs>
                            <g filter="url(#neon-glow)" fill="none" stroke="url(#logo-grad)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M 38,20 C 20,20 8,30 8,42.5 C 8,49.5 12.5,55.5 19,59 L 16,70 L 29,64 C 32,64.5 35,65 38,65 C 56,65 68,55 68,42.5 C 68,30 56,20 38,20 Z" />
                                <circle cx="26" cy="42.5" r="3.5" fill="url(#logo-grad)" stroke="none" />
                                <circle cx="38" cy="42.5" r="3.5" fill="url(#logo-grad)" stroke="none" />
                                <circle cx="50" cy="42.5" r="3.5" fill="url(#logo-grad)" stroke="none" />
                                <path d="M 64,36 C 64,33 61.5,30.5 58,29 C 61,25 66.5,22 73.5,22 C 86,22 94,29 94,38.5 C 94,43.5 90.5,47.5 85.5,50 L 88,58 L 78.5,53.5 C 76.5,54 75,54 73.5,54 C 69.5,54 66,52.5 63.5,50" opacity="0.85" />
                                <circle cx="73.5" cy="38" r="3.5" fill="url(#logo-grad)" stroke="none" opacity="0.85" />
                            </g>
                        </svg>
                    </div>
                    <span>Vybee</span>
                </div>
            </div>

            <!-- Active User Section -->
            <div class="user-profile-section">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_username, 0, 2)); ?> <!-- current user vanshi then VA-->
                    </div>
                    <div class="user-meta">
                        <span class="user-name"><?php echo htmlspecialchars($current_username); ?></span> <!-- <script> alert(1)</script> after htmlspecialchar() &lt;script&gt;alert(1)&lt;/script&gt; -->
                    </div> 
                </div>
                <a href="logout.php" class="btn-logout" title="Log Out">
                    <i class="bx bx-log-out"></i>
                </a>
            </div>

            <!-- Sidebar Search box -->
            <div class="sidebar-search-action">
                <div class="search-box">
                    <span class="search-icon">
                        <i class="bx bx-search"></i>
                    </span>
                    <input type="text" id="search-users" placeholder="Search contacts..." autocomplete="off">
                </div>
            </div>

            <!-- Sidebar Navigation Tabs -->
            <div class="sidebar-tabs">
                <button class="tab-btn active" data-tab="chats" id="tab-chats-trigger">
                    <i class="bx bx-message-square-detail"></i>
                    <span>Direct</span>
                </button>
                <button class="tab-btn" data-tab="groups" id="tab-groups-trigger">
                    <i class="bx bx-group"></i>
                    <span>Groups</span>
                </button>
            </div>

            <!-- Chats Scrollable Panel -->
            <div class="contacts-list-container active-tab-content" id="chats-tab-content">
                <div class="section-title-row">
                    <div class="section-title">DIRECT CHATS</div>
                </div>
                <div class="contacts-list" id="users-list-pane">
                    <div class="contacts-skeleton">
                        <div class="skeleton-item"></div>
                        <div class="skeleton-item"></div>
                        <div class="skeleton-item"></div>
                    </div>
                </div>
            </div>

            <!-- Groups Scrollable Panel -->
            <div class="contacts-list-container d-none" id="groups-tab-content">
                <div class="section-title-row">
                    <div class="section-title">GROUP ROOMS</div>
                    <button class="btn-create-group" id="btn-add-group" title="Create New Group">
                        <i class="bx bx-plus"></i>
                    </button>
                </div>
                <div class="contacts-list" id="rooms-list-pane">
                    <div class="contacts-skeleton">
                        <div class="skeleton-item"></div>
                        <div class="skeleton-item"></div>
                        <div class="skeleton-item"></div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Chat Panel (Static contacts directory view) -->
        <main class="chat-viewport">
            <!-- Dashboard Overlay Splash Screen -->
            <div class="no-chat-overlay" id="no-chat-screen">
                <div class="no-chat-content">
                    <div class="logo-animation-wrap main-logo">
                        <svg class="animated-logo-svg" viewBox="0 0 102 82" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="logo-grad-welcome" x1="0%" y1="100%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#00f5a0" />
                                    <stop offset="100%" stop-color="#00d9f9" />
                                </linearGradient>
                                <filter id="neon-glow-welcome" x="-20%" y="-20%" width="140%" height="140%">
                                    <feGaussianBlur stdDeviation="3" result="blur" />
                                    <feMerge>
                                        <feMergeNode in="blur" />
                                        <feMergeNode in="SourceGraphic" />
                                    </feMerge>
                                </filter>
                            </defs>
                            <g filter="url(#neon-glow-welcome)" fill="none" stroke="url(#logo-grad-welcome)" stroke-width="5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M 38,20 C 20,20 8,30 8,42.5 C 8,49.5 12.5,55.5 19,59 L 16,70 L 29,64 C 32,64.5 35,65 38,65 C 56,65 68,55 68,42.5 C 68,30 56,20 38,20 Z" />
                                <circle cx="26" cy="42.5" r="3.5" fill="url(#logo-grad-welcome)" stroke="none" />
                                <circle cx="38" cy="42.5" r="3.5" fill="url(#logo-grad-welcome)" stroke="none" />
                                <circle cx="50" cy="42.5" r="3.5" fill="url(#logo-grad-welcome)" stroke="none" />
                                <path d="M 64,36 C 64,33 61.5,30.5 58,29 C 61,25 66.5,22 73.5,22 C 86,22 94,29 94,38.5 C 94,43.5 90.5,47.5 85.5,50 L 88,58 L 78.5,53.5 C 76.5,54 75,54 73.5,54 C 69.5,54 66,52.5 63.5,50" opacity="0.85" />
                                <circle cx="73.5" cy="38" r="3.5" fill="url(#logo-grad-welcome)" stroke="none" opacity="0.85" />
                            </g>
                        </svg>
                    </div>
                    <h3>Welcome to Vybee</h3>
                    <p>Select a contact or a group chat room from the sidebar list to start a real-time conversation.</p>
                </div>
            </div>

            <!-- Active Chat Interface Panel -->
            <div class="active-chat-container d-none" id="active-chat-container">
                <!-- Chat Viewport Header -->
                <header class="chat-header">
                    <div class="chat-header-info">
                        <div class="contact-avatar" id="active-chat-avatar">--</div>
                        <div>
                            <h3 id="active-chat-username">Chat partner</h3>
                        </div>
                    </div>
                </header>

                <!-- Messages dynamic feed scroll area -->
                <div class="messages-feed" id="messages-feed">
                    <!-- Loaded dynamically via JS -->
                </div>

                <!-- Chat composer message input -->
                <footer class="chat-composer-footer">
                    <form id="chat-form" class="composer-wrapper" autocomplete="off">
                        <input type="hidden" id="active-contact-id" value="">
                        <!-- Hidden field to mark if we are in a room or a user -->
                        <input type="hidden" id="active-room-id" value="">
                        <input type="text" id="chat-message-input" placeholder="Type a message..." required>
                        <button type="submit" class="btn-send" title="Send Message">
                            <i class="bx bx-paper-plane"></i>
                        </button>
                    </form>
                </footer>
            </div>
        </main>
    </div>

    <!-- Frosted Glass Modal for Creating Rooms/Groups -->
    <div class="modal-overlay d-none" id="group-modal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Create Group Room</h3>
                <button class="btn-close-modal" id="btn-close-group-modal" title="Close Modal">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <form id="create-group-form" autocomplete="off">
                <div class="form-group">
                    <label for="new-group-name">Group Name</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <i class="bx bx-hash"></i>
                        </span>
                        <input type="text" id="new-group-name" name="room_name" placeholder="e.g. study-lounge" required minlength="3" maxlength="30">
                    </div>
                    <span class="form-help">Enter a short, descriptive name (3 to 30 characters).</span>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Create Room</button>
            </form>
        </div>
    </div>
</body>
</html>