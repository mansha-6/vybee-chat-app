<?php
// Include centralized database connection
require_once 'db.php';

// Set response headers to JSON - the response from the file will be JSON.
header('Content-Type: application/json');

// If user is not logged in, return error
if (!isset($_SESSION['user_id'])) { //checks if the user is exists and if doesnt exists then the below msg is displayed.
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit; //used to stop the script.
}

//used to store logged user ID and action from the URL parameters.Session: $_SESSION['user_id']=5; NOW: $user_id=5;
$user_id = $_SESSION['user_id'];
//Read action from URL : api.php?action=get_users
//Then: $_GET['action'] is:get_users
//if api.php is missing then $action="" (empty strings to prevent errors)
$action = $_GET['action'] ?? '';

// Action: Get all other registered users (Contacts) with search query
if ($action === 'get_users') {
    $search = trim($_GET['query'] ?? ''); // api.php?action=get_users&query=raj then : $search=raj and if query is missing then $search="" (empty string to prevent errors)
    
    if ($search !== '') { // if the user type something else then return false as empty string LIKE = pattern matching LIKE '%raj%'Matches:raj,rajesh,raj123,myraj
    //this statement executes if the search exists
        $stmt = $pdo->prepare("SELECT u.id, u.username, 
        CASE WHEN m.is_deleted = 1 THEN 'This message was deleted' ELSE m.message END AS last_message, 
        m.created_at AS last_time,
        (SELECT COUNT(*) FROM messages WHERE user_id = u.id AND receiver_id = ? AND seen = 0 AND is_deleted = 0) AS unread_count
        FROM users u 
        LEFT JOIN messages m
        ON m.id = 
        ( SELECT id FROM messages WHERE (user_id = ? AND receiver_id = u.id) OR (user_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1 ) 
        WHERE u.id != ? AND u.username LIKE ? ORDER BY u.username ASC"); // ? = placeholder and WHERE id != ? means Get all users whose ID is NOT the currently logged-in user's ID.
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, "%$search%"]); //% is used for the partially - any no of characters
    } 
    else { //if no search exists and distinct is used to remove the duplicates
        $stmt = $pdo->prepare("SELECT u.id, u.username, 
        CASE WHEN m.is_deleted = 1 THEN 'This message was deleted' ELSE m.message END AS last_message, 
        m.created_at AS last_time,
        (SELECT COUNT(*) FROM messages WHERE user_id = u.id AND receiver_id = ? AND seen = 0 AND is_deleted = 0) AS unread_count
        FROM users u
        INNER JOIN messages m 
        ON m.id = 
        (SELECT id FROM messages WHERE (user_id = ? AND receiver_id = u.id) OR (user_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1)
        WHERE u.id != ? 
        ORDER BY last_time DESC
        "); // WHERE u.id != ? Exclude myself. Show only users having chat history.

        $stmt->execute([$user_id, $user_id, $user_id, $user_id]); //Four placeholders.
    }
    
    $users = $stmt->fetchAll(); //get all the rows
    echo json_encode([
        'status' => 'success', 
        'data' => $users
        ]);
}

// Action: Get all chat rooms (Groups)
if ($action === 'get_rooms') {
    // Select all rooms, left join with the latest message in that room and the sender's username
    $stmt = $pdo->prepare("SELECT r.id, r.name, 
        CASE WHEN m.is_deleted = 1 THEN 'This message was deleted' ELSE m.message END AS last_message, 
        m.created_at AS last_time, u.username AS sender_name 
        FROM rooms r 
        LEFT JOIN messages m ON m.id = (
            SELECT id FROM messages WHERE room_id = r.id ORDER BY created_at DESC LIMIT 1
        ) 
        LEFT JOIN users u ON m.user_id = u.id 
        ORDER BY r.name ASC");
    $stmt->execute();
    $rooms = $stmt->fetchAll();
    
    // Parse seen times from client to compute actual unread counts in rooms
    $seen_times = [];
    if (isset($_GET['seen_times'])) {
        $seen_times = json_decode($_GET['seen_times'], true);
        if (!is_array($seen_times)) {
            $seen_times = [];
        }
    }
    
    // Enrich room objects with unread counts
    foreach ($rooms as &$room) {
        $room_key = 'room_' . $room['id'];
        $last_seen = $seen_times[$room_key] ?? '';
        
        if (!empty($last_seen) && !empty($room['last_time'])) {
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE room_id = ? AND created_at > ? AND user_id != ? AND is_deleted = 0");
            $count_stmt->execute([$room['id'], $last_seen, $user_id]);
            $room['unread_count'] = intval($count_stmt->fetchColumn());
        } elseif (empty($last_seen) && !empty($room['last_time'])) {
            // If they have never opened the room, count all messages sent by others
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE room_id = ? AND user_id != ? AND is_deleted = 0");
            $count_stmt->execute([$room['id'], $user_id]);
            $room['unread_count'] = intval($count_stmt->fetchColumn());
        } else {
            $room['unread_count'] = 0;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $rooms
    ]);
}

// Action: Create a new chat room (Group)
if ($action === 'create_room') {
    $room_name = trim($_POST['room_name'] ?? '');
    
    // Server-side validation
    if (empty($room_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Room name is required.']);
        exit;
    }
    
    if (strlen($room_name) < 3 || strlen($room_name) > 30) {
        echo json_encode(['status' => 'error', 'message' => 'Room name must be between 3 and 30 characters.']);
        exit;
    }
    
    // Check if room name already exists
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE name = ?");
    $stmt->execute([$room_name]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'A group room with this name already exists.']);
        exit;
    }
    
    // Insert new room
    $stmt = $pdo->prepare("INSERT INTO rooms (name, created_by) VALUES (?, ?)");
    if ($stmt->execute([$room_name, $user_id])) {
        $new_room_id = $pdo->lastInsertId();
        echo json_encode([
            'status' => 'success',
            'message' => 'Room created successfully!',
            'room_id' => $new_room_id
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create room.']);
    }
}

// Action: Get messages from selected contact or group room
if ($action === 'get_messages') {
    $receiver_id = intval($_GET['receiver_id'] ?? 0);
    $room_id = intval($_GET['room_id'] ?? 0);
    
    if ($room_id > 0) {
        // Fetch group messages (including sender's username)
        $stmt = $pdo->prepare("SELECT m.*, u.username FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.room_id = ? 
            ORDER BY m.created_at ASC");
        $stmt->execute([$room_id]);
        $messages = $stmt->fetchAll();
        
        echo json_encode([
            'status' => 'success',
            'data' => $messages
        ]);
    } elseif ($receiver_id > 0) {
        // Mark all received messages in this conversation as seen
        $update_stmt = $pdo->prepare("UPDATE messages SET seen = 1 WHERE user_id = ? AND receiver_id = ? AND seen = 0");
        $update_stmt->execute([$receiver_id, $user_id]);

        // Fetch 1-on-1 direct messages (including sender's username)
        $stmt = $pdo->prepare("SELECT m.*, u.username FROM messages m 
            JOIN users u ON m.user_id = u.id 
            WHERE (m.user_id = ? AND m.receiver_id = ?) 
               OR (m.user_id = ? AND m.receiver_id = ?) 
            ORDER BY m.created_at ASC");
        $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
        $messages = $stmt->fetchAll();
        
        echo json_encode([
            'status' => 'success',
            'data' => $messages
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    }
}

// Action: Send message to selected contact or group room
if ($action === 'send_message') {
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $room_id = intval($_POST['room_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if ($message === '') {
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty.']);
        exit;
    }
    
    if ($room_id > 0) {
        // Send message to group room (receiver_id is null)
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, room_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$user_id, $room_id, $message])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Group message sent successfully!'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send group message.']);
        }
    } elseif ($receiver_id > 0) {
        // Send direct 1-on-1 message (room_id is null)
        $stmt = $pdo->prepare("INSERT INTO messages (user_id, receiver_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$user_id, $receiver_id, $message])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Message sent successfully!'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to send message.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid inputs.']);
    }
}

// Action: Delete message (WhatsApp style - Soft Deletion)
if ($action === 'delete_message') {
    $message_id = intval($_POST['message_id'] ?? 0);
    if ($message_id > 0) {
        // Soft delete: Update is_deleted to 1 instead of erasing the message from the database
        $stmt = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$message_id, $user_id])) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Message deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized or message not found']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid message ID']);
    }
}
?>