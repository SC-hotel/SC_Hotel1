<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
redirectIfNotLoggedIn();

if (isBanned()) {
    session_destroy();
    header('Location: login.php?error=banned');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $message = "Security validation failed.";
        $messageType = "danger";
    } else {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        
        $profile_pic = $user['profile_pic'];
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $upload = uploadFile($_FILES['profile_pic'], UPLOAD_DIR . 'profiles/');
            if ($upload) {
                if ($profile_pic != 'default.png') {
                    $oldPath = UPLOAD_DIR . 'profiles/' . $profile_pic;
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $profile_pic = $upload;
            }
        }
        
        if (!empty($_POST['password'])) {
            $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET name = ?, email = ?, password = ?, profile_pic = ? WHERE id = ?";
            $params = [$name, $email, $new_password, $profile_pic, $user_id];
        } else {
            $sql = "UPDATE users SET name = ?, email = ?, profile_pic = ? WHERE id = ?";
            $params = [$name, $email, $profile_pic, $user_id];
        }
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $_SESSION['user_name'] = $name;
            $message = "Profile updated successfully!";
            $messageType = "success";
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $message = "Error updating profile.";
            $messageType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | SC Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>assets/img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ========== LUXURY DESIGN SYSTEM ========== */
        :root {
            --gold: #c9a227;
            --gold-light: #e6c85a;
            --charcoal: #1a1a1a;
            --cream: #faf8f5;
            --cream-dark: #f0ece4;
            --text-muted: #8a8a8a;
            --font-display: 'Cormorant Garamond', Georgia, serif;
            --font-body: 'Jost', 'Segoe UI', sans-serif;
            --shadow-sm: 0 2px 12px rgba(0,0,0,0.06);
            --shadow-md: 0 8px 30px rgba(0,0,0,0.08);
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.12);
            --transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        body {
            font-family: var(--font-body);
            background: var(--cream);
            min-height: 100vh;
            color: var(--charcoal);
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(201, 162, 39, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 85% 30%, rgba(201, 162, 39, 0.04) 0%, transparent 50%);
        }

        /* ========== PROFILE CARD ========== */
        .profile-card {
            background: #ffffff;
            border: 1px solid rgba(201, 162, 39, 0.15);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            margin-top: 50px;
        }
        
        .profile-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .profile-header {
            background: var(--charcoal);
            padding: 50px 35px 40px;
            text-align: center;
            color: #ffffff;
            border-bottom: 3px solid var(--gold);
        }

        .profile-header h2 {
            font-family: var(--font-display);
            font-size: 2.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .profile-header p {
            font-family: var(--font-body);
            font-size: 0.85rem;
            color: var(--gold-light);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin: 0;
        }

        /* Overlapping Avatar */
        .avatar-wrapper {
            text-align: center;
            margin-top: -75px;
            position: relative;
            z-index: 2;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            background: #ffffff;
            transition: var(--transition);
        }

        .profile-avatar:hover {
            transform: scale(1.03);
            border-color: var(--gold);
        }

        .profile-body {
            padding: 30px 40px 40px;
        }

        /* ========== FORM ELEMENTS ========== */
        .form-label {
            font-family: var(--font-body);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--charcoal);
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            border-radius: 0;
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            font-family: var(--font-body);
            font-size: 0.9rem;
            transition: var(--transition);
            background: var(--cream);
            color: var(--charcoal);
        }

        .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.15rem rgba(201, 162, 39, 0.15);
            background: #fff;
        }

        .form-control::placeholder {
            color: #aaa;
            font-weight: 300;
        }

        /* ========== BUTTONS ========== */
        .btn-update {
            background: var(--charcoal);
            color: #ffffff;
            border: 1px solid var(--charcoal);
            border-radius: 0;
            padding: 16px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.8rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            transition: var(--transition);
            width: 100%;
        }

        .btn-update:hover {
            background: var(--gold);
            border-color: var(--gold);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(201, 162, 39, 0.25);
        }

        .btn-outline-primary {
            border: 1px solid var(--charcoal);
            color: var(--charcoal);
            border-radius: 0;
            padding: 12px 24px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-outline-primary:hover {
            background: var(--charcoal);
            color: #ffffff;
        }

        .btn-outline-danger {
            border: 1px solid #dc3545;
            color: #dc3545;
            border-radius: 0;
            padding: 12px 24px;
            font-family: var(--font-body);
            font-weight: 500;
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: #ffffff;
        }

        /* ========== ALERTS ========== */
        .alert-danger, .alert-success {
            border-radius: 0;
            font-size: 0.85rem;
            border-left: 4px solid;
        }
        
        .alert-danger {
            background-color: #fdf3f3;
            border-color: #dc3545;
            color: #721c24;
        }

        .alert-success {
            background-color: #f0f9f4;
            border-color: var(--gold);
            color: #155724;
        }

        /* ========== CHAT WIDGET ========== */
        .chat-widget-fixed {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            font-family: var(--font-body);
        }
        .chat-toggle-btn {
            background: var(--charcoal);
            color: #fff;
            width: 58px;
            height: 58px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }
        .chat-toggle-btn:hover {
            transform: scale(1.08);
            background: var(--gold);
        }
        .chat-window {
            width: 380px;
            height: 580px;
            background: #fff;
            border-radius: 0;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: fixed;
            bottom: 94px;
            right: 0;
            z-index: 9998;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .chat-header {
            background: var(--charcoal);
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
        }
        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .chat-header-info i {
            font-size: 1.5rem;
            color: var(--gold);
        }
        .chat-header-info h4 {
            margin: 0;
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }
        .chat-header-info span {
            font-size: 0.65rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            opacity: 0.7;
            color: var(--gold-light);
        }
        .chat-close-btn {
            background: none;
            border: none;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .chat-close-btn:hover {
            background: rgba(255,255,255,0.1);
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: var(--cream);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .chat-messages::-webkit-scrollbar {
            width: 4px;
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.1);
            border-radius: 2px;
        }
        .bot-message, .user-message {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: fadeInUpChat 0.3s ease;
        }
        @keyframes fadeInUpChat {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .user-message {
            flex-direction: row-reverse;
        }
        .bot-avatar, .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.75rem;
        }
        .bot-avatar {
            background: var(--charcoal);
            color: var(--gold);
        }
        .user-avatar {
            background: var(--gold);
            color: #fff;
        }
        .message-bubble {
            max-width: 72%;
            padding: 12px 16px;
            border-radius: 0;
            font-size: 0.85rem;
            line-height: 1.5;
            word-wrap: break-word;
            font-weight: 300;
        }
        .bot-bubble {
            background: #fff;
            color: var(--charcoal);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border-left: 2px solid var(--gold);
        }
        .user-bubble {
            background: var(--charcoal);
            color: #fff;
        }
        .chat-input-area {
            display: flex;
            flex-direction: column;
            background: #fff;
            border-top: 1px solid rgba(0,0,0,0.06);
        }
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            background: var(--cream);
        }
        .suggestion-chip {
            background: #fff;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 0;
            padding: 6px 12px;
            font-size: 0.7rem;
            cursor: pointer;
            transition: var(--transition);
            color: var(--charcoal);
            font-weight: 400;
            letter-spacing: 0.03em;
        }
        .suggestion-chip:hover {
            background: var(--gold);
            color: #fff;
            border-color: var(--gold);
        }
        .input-wrapper {
            display: flex;
            padding: 12px 16px;
            gap: 10px;
            align-items: center;
        }
        #chat-input {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 0;
            outline: none;
            font-size: 0.85rem;
            transition: var(--transition);
            font-family: var(--font-body);
            background: var(--cream);
        }
        #chat-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 0.15rem rgba(201, 162, 39, 0.1);
            background: #fff;
        }
        #send-btn {
            background: var(--charcoal);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.85rem;
        }
        #send-btn:hover {
            background: var(--gold);
            transform: scale(1.05);
        }
        .chat-footer {
            background: var(--cream);
            text-align: center;
            padding: 8px;
            font-size: 0.65rem;
            color: var(--text-muted);
            border-top: 1px solid rgba(0,0,0,0.06);
            letter-spacing: 0.05em;
        }
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff;
            padding: 12px 18px;
            border-radius: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border-left: 2px solid var(--gold);
        }
        .typing-indicator span {
            width: 7px;
            height: 7px;
            background: var(--gold);
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

        @media (max-width: 768px) {
            .profile-body { padding: 25px; }
            .profile-avatar { width: 120px; height: 120px; margin-top: -60px; }
            .profile-header { padding: 40px 25px 35px; }
            .chat-window { width: calc(100vw - 32px); right: 16px; bottom: 84px; height: 540px; }
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="profile-card">
                <div class="profile-header">
                    <h2>My Profile</h2>
                    <p>Manage your account information</p>
                </div>
                <div class="avatar-wrapper">
                    <img src="assets/uploads/profiles/<?= htmlspecialchars($user['profile_pic']) ?>" class="profile-avatar" alt="Profile Picture" onerror="this.src='assets/uploads/profiles/default.png'">
                </div>
                <div class="profile-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 0.8rem; padding-top: 1.1rem;"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-4">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" name="profile_pic" class="form-control" accept="image/*">
                            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Leave empty to keep current picture</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Minimum 6 characters</small>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn-update">Update Profile</button>
                        </div>
                    </form>
                    
                    <hr class="my-4" style="opacity: 0.1;">
                    
                    <div class="text-center d-flex justify-content-center gap-3">
                        <a href="mybookings.php" class="btn-outline-primary"><i class="fas fa-bookmark me-2"></i> My Bookings</a>
                        <a href="logout.php" class="btn-outline-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Chat Widget -->
<div class="chat-widget-fixed">
    <div id="chat-toggle" class="chat-toggle-btn"><i class="fas fa-comment-dots"></i></div>
    <div id="chat-window" class="chat-window" style="display: none;">
        <div class="chat-header">
            <div class="chat-header-info"><i class="fas fa-concierge-bell"></i><div><h4>SC Concierge</h4><span>Online</span></div></div>
            <button id="chat-close" class="chat-close-btn"><i class="fas fa-times"></i></button>
        </div>
        <div id="chat-messages" class="chat-messages">
            <div class="bot-message"><div class="bot-avatar"><i class="fas fa-concierge-bell"></i></div><div class="message-bubble bot-bubble">Hello! I'm your SC Concierge. Ask me about room prices, availability, check-in/out times, or booking help.</div></div>
        </div>
        <div class="chat-input-area">
            <div class="suggestions" id="suggestions"></div>
            <div class="input-wrapper">
                <input type="text" id="chat-input" placeholder="Type your question..." autocomplete="off">
                <button id="send-btn"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
        <div class="chat-footer"><small>Powered by SC Hotel Concierge</small></div>
    </div>
</div>
<iframe id="chatIframe" style="display: none;"></iframe>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Reset session on page load (fresh conversation)
        fetch('<?= SITE_URL ?>assistant.php?reset=1', { method: 'GET', cache: 'no-store' });

        var chatToggle = document.getElementById('chat-toggle');
        var chatWindow = document.getElementById('chat-window');
        var chatClose = document.getElementById('chat-close');
        var chatInput = document.getElementById('chat-input');
        var sendBtn = document.getElementById('send-btn');
        var chatMessages = document.getElementById('chat-messages');
        var chatIframe = document.getElementById('chatIframe');
        var suggestionsDiv = document.getElementById('suggestions');

        var typingIndicator = null;

        function showTypingIndicator() {
            removeTypingIndicator();
            typingIndicator = document.createElement('div');
            typingIndicator.className = 'bot-message';
            typingIndicator.innerHTML = '<div class="bot-avatar"><i class="fas fa-concierge-bell"></i></div><div class="typing-indicator"><span></span><span></span><span></span></div>';
            chatMessages.appendChild(typingIndicator);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function removeTypingIndicator() {
            if (typingIndicator) {
                typingIndicator.remove();
                typingIndicator = null;
            }
        }

        function escapeHtml(str) {
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        function appendMessage(text, isUser) {
            var div = document.createElement('div');
            div.className = isUser ? 'user-message' : 'bot-message';

            if (isUser) {
                div.innerHTML = '<div class="message-bubble user-bubble">' + escapeHtml(text) + '</div><div class="user-avatar"><i class="fas fa-user"></i></div>';
            } else {
                var escaped = escapeHtml(text);
                var urlRegex = /(https?:\/\/[^\s]+)/g;
                var linkedText = escaped.replace(urlRegex, function(url) {
                    return '<a href="' + url + '" target="_blank" style="color: var(--gold); text-decoration: underline;">' + url + '</a>';
                });
                div.innerHTML = '<div class="bot-avatar"><i class="fas fa-concierge-bell"></i></div><div class="message-bubble bot-bubble">' + linkedText + '</div>';
            }
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function updateSuggestions(botMessage) {
            suggestionsDiv.innerHTML = '';
            var suggestions = [];
            var lowerMsg = botMessage.toLowerCase();

            if (lowerMsg.includes('which room type') || lowerMsg.includes('standard twin') || lowerMsg.includes('deluxe')) {
                suggestions = ['Standard Twin', 'Standard Double', 'Deluxe', 'Superior', 'Suite'];
            } 
            else if (lowerMsg.includes('check‑in date')) {
                suggestions = ['Tomorrow', 'Next Monday', 'August 1, 2026'];
            }
            else if (lowerMsg.includes('check‑out date')) {
                suggestions = ['Next day', 'August 2, 2026', 'August 5, 2026'];
            }
            else if (lowerMsg.includes('available') && (lowerMsg.includes('yes') || lowerMsg.includes('not'))) {
                suggestions = ['Book this room', 'Check another date', 'Show other rooms'];
            }
            else {
                suggestions = ['Show room prices', 'Book a room', 'Check availability', 'Hotel amenities', 'Contact info'];
            }

            suggestions = [...new Set(suggestions)].slice(0, 5);
            suggestions.forEach(sug => {
                var chip = document.createElement('div');
                chip.className = 'suggestion-chip';
                chip.textContent = sug;
                chip.addEventListener('click', function() {
                    chatInput.value = sug;
                    sendBtn.click();
                });
                suggestionsDiv.appendChild(chip);
            });
        }

        if (chatToggle) {
            chatToggle.addEventListener('click', function() {
                chatWindow.style.display = chatWindow.style.display === 'none' ? 'flex' : 'none';
                if (chatWindow.style.display === 'flex') chatInput.focus();
            });
        }
        if (chatClose) {
            chatClose.addEventListener('click', function() { chatWindow.style.display = 'none'; });
        }

        if (chatIframe) {
            chatIframe.addEventListener('load', function() {
                setTimeout(function() {
                    removeTypingIndicator();
                    try {
                        var iframeDoc = chatIframe.contentDocument || chatIframe.contentWindow.document;
                        var responseText = iframeDoc.body.innerText;
                        if (responseText && responseText.trim()) {
                            var data = JSON.parse(responseText);
                            if (data.reply) {
                                appendMessage(data.reply, false);
                                updateSuggestions(data.reply);
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
                    if (chatIframe.contentDocument) chatIframe.contentDocument.body.innerHTML = '';
                }, 400);
            });
        }

        if (sendBtn) {
            sendBtn.addEventListener('click', function() {
                var msg = chatInput.value.trim();
                if (msg === "") return;
                appendMessage(msg, true);
                chatInput.value = '';
                showTypingIndicator();
                suggestionsDiv.innerHTML = '';
                var url = '<?= SITE_URL ?>assistant.php?msg=' + encodeURIComponent(msg);
                chatIframe.src = url;
            });
        }
        if (chatInput) {
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') sendBtn.click();
            });
        }

        updateSuggestions('Hello');
    });
</script>
</body>
</html>