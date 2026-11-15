
<?php
require_once 'includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ------------------------------------------------------------
// 1. RESET CHAT
// ------------------------------------------------------------
if (isset($_GET['reset']) && $_GET['reset'] == 1) {
    $_SESSION['booking_state'] = [
        'step' => 'idle',
        'room_type' => null,
        'check_in' => null,
        'check_out' => null
    ];

    $_SESSION['chat_history'] = [];
    unset($_SESSION['last_chat_language']);

    echo 'ok';
    exit;
}

// ------------------------------------------------------------
// 2. AUTHENTICATION
// ------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'reply' => 'Please log in to book a room.'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$message = isset($_GET['msg'])
    ? trim($_GET['msg'])
    : (isset($_POST['msg']) ? trim($_POST['msg']) : '');

if (empty($message)) {
    echo json_encode([
        'reply' => 'Please type a message.'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ------------------------------------------------------------
// 3. LANGUAGE DETECTION
// ------------------------------------------------------------
function detectLanguage($text) {

    $textLower = strtolower(trim($text));

    $tagalogWords = [
        'ako', 'ikaw', 'ka', 'ko', 'mo', 'nyo', 'ninyo',
        'kayo', 'kami', 'tayo', 'natin', 'namin',
        'sila', 'nila', 'ang', 'mga', 'ng', 'sa',
        'na', 'pa', 'po', 'opo', 'ba', 'daw', 'raw',
        'din', 'rin', 'lang', 'lamang', 'kasi',
        'dahil', 'pero', 'kaya', 'at', 'ay', 'kung',
        'ano', 'sino', 'saan', 'kailan', 'bakit',
        'paano', 'magkano', 'gusto', 'gustong',
        'sige', 'cge', 'kamusta', 'kumusta', 'musta',
        'salamat', 'pwede', 'puwede', 'meron',
        'mayroon', 'wala', 'hindi', 'oo', 'muna',
        'kailangan', 'naman', 'talaga', 'itong',
        'iyan', 'iyon', 'ito', 'yan', 'yun',
        'dito', 'diyan', 'doon', 'bukas', 'ngayon',
        'kahapon', 'araw', 'petsa', 'presyo',
        'kuwarto', 'kwarto', 'reserba',
        'magbook', 'magpabook', 'pabook',
        'pa-book', 'i-book'
    ];

    $englishWords = [
        'the', 'a', 'an', 'is', 'are', 'was', 'were',
        'am', 'i', 'you', 'he', 'she', 'we', 'they',
        'it', 'my', 'your', 'our', 'their', 'me', 'us',
        'this', 'that', 'these', 'those', 'what',
        'where', 'when', 'why', 'how', 'who', 'which',
        'can', 'could', 'would', 'should', 'will',
        'do', 'does', 'did', 'have', 'has', 'had',
        'please', 'thank', 'thanks', 'hello', 'hey',
        'hi', 'yes', 'book', 'booking', 'reserve',
        'reservation', 'available', 'much', 'no',
        'want', 'need', 'like', 'looking', 'for',
        'tell', 'show', 'give', 'there', 'here',
        'with', 'from', 'to', 'and', 'but', 'or',
        'because', 'if'
    ];

    $tagalogScore = 0;
    $englishScore = 0;

    foreach ($tagalogWords as $word) {
        if (preg_match(
            '/(?<![a-z])' . preg_quote($word, '/') . '(?![a-z])/i',
            $textLower
        )) {
            $tagalogScore++;
        }
    }

    foreach ($englishWords as $word) {
        if (preg_match(
            '/(?<![a-z])' . preg_quote($word, '/') . '(?![a-z])/i',
            $textLower
        )) {
            $englishScore++;
        }
    }

    // Detect natural Taglish
    if (
        ($tagalogScore >= 1 && $englishScore >= 2) ||
        ($tagalogScore >= 2 && $englishScore >= 1)
    ) {
        return 'tl-en';
    }

    // Pure Tagalog
    if ($tagalogScore >= 1) {
        return 'tl';
    }

    // Pure English
    if ($englishScore >= 1) {
        return 'en';
    }

    // Retain previous language for short replies
    if (!empty($_SESSION['last_chat_language'])) {
        return $_SESSION['last_chat_language'];
    }

    return 'en';
}

$lang = detectLanguage($message);

$_SESSION['last_chat_language'] = $lang;

// ------------------------------------------------------------
// 4. LANGUAGE-BASED RESPONSES
// ------------------------------------------------------------
function reply($english, $tagalog, $taglish = null) {

    global $lang;

    if ($lang === 'tl') {
        return $tagalog;
    }

    if ($lang === 'tl-en' && $taglish !== null) {
        return $taglish;
    }

    if ($lang === 'tl-en' && $taglish === null) {
        return $tagalog;
    }

    return $english;
}

// ------------------------------------------------------------
// 5. GREETING DETECTION
// ------------------------------------------------------------
$greetings = [
    '/^hi\b/i',
    '/^hello\b/i',
    '/^hey\b/i',
    '/^good morning/i',
    '/^good afternoon/i',
    '/^kamusta/i',
    '/^kumusta/i',
    '/^musta/i'
];

foreach ($greetings as $pattern) {

    if (preg_match($pattern, $message)) {

        $_SESSION['booking_state'] = [
            'step' => 'idle',
            'room_type' => null,
            'check_in' => null,
            'check_out' => null
        ];

        break;
    }
}

// ------------------------------------------------------------
// 6. OFF-TOPIC FILTER
// ------------------------------------------------------------
$offTopicKeywords = [
    'stock', 'bitcoin', 'crypto', 'weather',
    'forecast', 'sport', 'football', 'basketball',
    'movie', 'celebrity', 'cooking', 'recipe',
    'math', 'physics', 'history', 'politics',
    'president', 'election', 'code', 'programming',
    'python', 'javascript', 'windows', 'mac',
    'iphone', 'android', 'game', 'fortnite',
    'minecraft', 'travel to paris',
    'what is the meaning of life',
    'who is', 'tell me a joke'
];

foreach ($offTopicKeywords as $kw) {

    if (stripos($message, $kw) !== false) {

        $response = reply(
            'I only answer questions about SC Hotel.',
            'Tungkol lang sa SC Hotel ang masasagot ko.',
            'Tungkol lang sa SC Hotel ang masasagot ko.'
        );

        echo json_encode([
            'reply' => $response
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }
}

// ------------------------------------------------------------
// 7. DATABASE ROOM DATA
// ------------------------------------------------------------
$stmt = $pdo->query("
    SELECT type, price_per_night, capacity, description
    FROM rooms
");

$roomsData = $stmt->fetchAll();

$dbRoomTypes = array_column($roomsData, 'type');

$roomTypesList = implode(', ', $dbRoomTypes);

$roomList = '';

foreach ($roomsData as $r) {

    $roomList .= "- {$r['type']}: ₱{$r['price_per_night']}/night, max {$r['capacity']} persons. {$r['description']}\n";
}

// ------------------------------------------------------------
// 8. AVAILABILITY HELPER FUNCTIONS
// ------------------------------------------------------------
function getAvailableRoomByType($pdo, $roomType, $checkIn, $checkOut) {

    $stmt = $pdo->prepare("
        SELECT id
        FROM rooms
        WHERE type = ?
    ");

    $stmt->execute([$roomType]);

    $rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($rooms as $roomId) {

        $sql = "
            SELECT COUNT(*)
            FROM bookings
            WHERE room_id = ?
            AND status IN ('approved', 'pending')
            AND check_in < ?
            AND check_out > ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $roomId,
            $checkOut,
            $checkIn
        ]);

        if ($stmt->fetchColumn() == 0) {
            return $roomId;
        }
    }

    return null;
}

function getAvailableAlternativeRoomTypes(
    $pdo,
    $excludeType,
    $checkIn,
    $checkOut
) {

    $stmt = $pdo->query("
        SELECT DISTINCT type
        FROM rooms
    ");

    $allTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $availableTypes = [];

    foreach ($allTypes as $type) {

        if ($type === $excludeType) {
            continue;
        }

        if (
            getAvailableRoomByType(
                $pdo,
                $type,
                $checkIn,
                $checkOut
            ) !== null
        ) {
            $availableTypes[] = $type;
        }
    }

    return $availableTypes;
}

// ------------------------------------------------------------
// 9. DATE PARSING FUNCTIONS
// ------------------------------------------------------------
function parseDateFlexible($text) {

    $text = trim($text);

    $monthMap = [
        'enero' => 'January',
        'pebrero' => 'February',
        'marso' => 'March',
        'abril' => 'April',
        'mayo' => 'May',
        'hunyo' => 'June',
        'hulyo' => 'July',
        'agosto' => 'August',
        'septiyembre' => 'September',
        'oktubre' => 'October',
        'nobyembre' => 'November',
        'disyembre' => 'December'
    ];

    foreach ($monthMap as $tag => $eng) {
        $text = str_ireplace($tag, $eng, $text);
    }

    $text = preg_replace(
        '/\b(\d+)(st|nd|rd|th)\b/',
        '$1',
        $text
    );

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
        return $text;
    }

    $currentYear = date('Y');

    $withYear = $text . ' ' . $currentYear;

    $ts = strtotime($withYear);

    if ($ts !== false && $ts > 0) {

        $date = date('Y-m-d', $ts);

        if ($date < date('Y-m-d')) {

            $withYear = $text . ' ' . ($currentYear + 1);

            $ts = strtotime($withYear);

            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }

        } else {

            return $date;
        }
    }

    $ts = strtotime($text);

    if ($ts !== false && $ts > 0) {
        return date('Y-m-d', $ts);
    }

    return null;
}

function extractBothDates($message) {

    $patterns = [
        '/from\s+(.+?)\s+to\s+(.+)/i',
        '/(.+?)\s+to\s+(.+)/i',
        '/(.+?)\s*-\s*(.+)/i',
        '/mula\s+(.+?)\s+(hanggang|until|to)\s+(.+)/i',
        '/check\s+in\s+(.+?)\s+check\s+out\s+(.+)/i'
    ];

    foreach ($patterns as $pat) {

        if (preg_match($pat, $message, $m)) {

            $in = parseDateFlexible(trim($m[1]));

            $outText = (
                isset($m[3]) &&
                in_array(strtolower($m[2]), ['hanggang', 'until', 'to'])
            ) ? $m[3] : $m[2];

            $out = parseDateFlexible(trim($outText));

            if ($in && $out) {

                return [
                    'check_in' => $in,
                    'check_out' => $out
                ];
            }
        }
    }

    preg_match_all(
        '/\b(\d{4}-\d{2}-\d{2}|\b\d{1,2}(?:st|nd|rd|th)?\s+[A-Za-z]+\s+\d{4}|\b[A-Za-z]+\s+\d{1,2}(?:st|nd|rd|th)?,?\s+\d{4}|\b[A-Za-z]+\s+\d{1,2})\b/',
        $message,
        $m
    );

    $dates = [];

    foreach ($m[0] as $d) {

        $parsed = parseDateFlexible($d);

        if ($parsed) {
            $dates[] = $parsed;
        }
    }

    if (count($dates) >= 2) {

        return [
            'check_in' => $dates[0],
            'check_out' => $dates[1]
        ];
    }

    if (count($dates) == 1) {

        return [
            'check_in' => $dates[0],
            'check_out' => null
        ];
    }

    return [
        'check_in' => null,
        'check_out' => null
    ];
}

function extractRoomTypeFromDB($text, $dbTypes) {

    $text = strtolower($text);

    foreach ($dbTypes as $type) {

        if (stripos($text, strtolower($type)) !== false) {
            return $type;
        }
    }

    return null;
}

// ------------------------------------------------------------
// 10. AVAILABILITY INTENT
// ------------------------------------------------------------
$availIntent = preg_match(
    '/\b(available|free|vacant|meron bang available|may available ba|is.*available|does.*available)\b/i',
    $message
);

if ($availIntent) {

    $room_type = extractRoomTypeFromDB(
        $message,
        $dbRoomTypes
    );

    if (!$room_type) {

        $response = reply(
            "Which room type would you like to check? We have: $roomTypesList.",
            "Anong room type ang gusto mong i-check? Meron tayong: $roomTypesList.",
            "Anong room type ang gusto mong i-check? Meron tayong: $roomTypesList."
        );

        echo json_encode([
            'reply' => $response
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    $dates = extractBothDates($message);

    if (!$dates['check_in']) {

        if (preg_match(
            '/\b(\d{4}-\d{2}-\d{2}|\b\d{1,2}(?:st|nd|rd|th)?\s+[A-Za-z]+\s+\d{4}|\b[A-Za-z]+\s+\d{1,2}(?:st|nd|rd|th)?,?\s+\d{4}|\b[A-Za-z]+\s+\d{1,2})\b/',
            $message,
            $m
        )) {

            $single = parseDateFlexible($m[1]);

            if ($single) {

                $dates['check_in'] = $single;

                $dates['check_out'] = date(
                    'Y-m-d',
                    strtotime($single . ' +1 day')
                );
            }
        }
    }

    if (!$dates['check_in']) {

        $response = reply(
            "Please provide a date (e.g., July 1, 2026) so I can check availability.",
            "Pakiusap, ibigay ang petsa (hal. Hulyo 1, 2026) para masuri ko ang availability.",
            "Please provide ang date (e.g., July 1, 2026) para ma-check ko ang availability."
        );

        echo json_encode([
            'reply' => $response
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    $check_in = $dates['check_in'];

    $check_out = $dates['check_out'] ??
        date('Y-m-d', strtotime($check_in . ' +1 day'));

    if ($check_in < date('Y-m-d')) {

        $response = reply(
            "The date cannot be in the past. Please choose a future date.",
            "Hindi pwedeng nakalipas na ang petsa. Pumili ng petsa sa hinaharap.",
            "Hindi pwedeng nakalipas ang date. Pumili ng future date."
        );

        echo json_encode([
            'reply' => $response
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    $availableRoomId = getAvailableRoomByType(
        $pdo,
        $room_type,
        $check_in,
        $check_out
    );

    if ($availableRoomId !== null) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM rooms
            WHERE id = ?
        ");

        $stmt->execute([$availableRoomId]);

        $room = $stmt->fetch();

        $response = reply(
            "Yes, the {$room_type} room is available from {$check_in} to {$check_out}. The rate is ₱{$room['price_per_night']} per night for up to {$room['capacity']} people. Would you like to book?",
            "Oo, available ang {$room_type} room mula {$check_in} hanggang {$check_out}. Ang rate ay ₱{$room['price_per_night']} kada gabi para sa maximum na {$room['capacity']} tao. Gusto mo bang i-book?",
            "Oo, available ang {$room_type} room mula {$check_in} hanggang {$check_out}. ₱{$room['price_per_night']} per night para sa {$room['capacity']} people. Gusto mo mag-book?"
        );

    } else {

        $alternatives = getAvailableAlternativeRoomTypes(
            $pdo,
            $room_type,
            $check_in,
            $check_out
        );

        if (empty($alternatives)) {

            $response = reply(
                "Sorry, no rooms are available for those dates. Please try different dates.",
                "Paumanhin, walang available na room sa mga petsang iyon. Subukan ang ibang petsa.",
                "Sorry, walang available na room sa mga dates na iyon. Subukan ang ibang dates."
            );

        } else {

            $altList = implode(', ', $alternatives);

            $response = reply(
                "Sorry, the {$room_type} is NOT available from {$check_in} to {$check_out}. However, these room types are available: {$altList}. Would you like to try one?",
                "Paumanhin, HINDI available ang {$room_type} sa {$check_in} hanggang {$check_out}. Subalit available ang: {$altList}. Gusto mo bang subukan ang isa?",
                "Sorry, HINDI available ang {$room_type} sa {$check_in} hanggang {$check_out}. Pero available ang: {$altList}. Gusto mo subukan?"
            );
        }
    }

    echo json_encode([
        'reply' => $response
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

// ------------------------------------------------------------
// 11. BOOKING INTENT
// ------------------------------------------------------------
if (!isset($_SESSION['booking_state'])) {

    $_SESSION['booking_state'] = [
        'step' => 'idle',
        'room_type' => null,
        'check_in' => null,
        'check_out' => null
    ];
}

$state = &$_SESSION['booking_state'];

$room = extractRoomTypeFromDB(
    $message,
    $dbRoomTypes
);

if ($room) {
    $state['room_type'] = $room;
}

$dates = extractBothDates($message);

if ($dates['check_in']) {
    $state['check_in'] = $dates['check_in'];
}

if ($dates['check_out']) {
    $state['check_out'] = $dates['check_out'];
}

$bookingIntent = preg_match(
    '/\b(book|booking|reserve|reservation|make a booking|i want to book|book it|book ako|pa\s*-?\s*book|pabook|magbook|mag-book|magpabook|magpa-book|gusto kong magbook|gusto ko magbook|i-book mo|i-book ako|pa-reserve|mag-reserve|reserba|pa-reserba|proceed)\b/i',
    $message
);

if ($bookingIntent || $state['step'] !== 'idle') {

    // ALL BOOKING DETAILS PROVIDED
    if (
        $state['room_type'] &&
        $state['check_in'] &&
        $state['check_out']
    ) {

        $check_in = $state['check_in'];
        $check_out = $state['check_out'];
        $room_type = $state['room_type'];

        if ($check_in < date('Y-m-d')) {

            $state['check_in'] = null;

            $response = reply(
                "Check-in cannot be in the past. Please choose a future date.",
                "Hindi pwedeng nakalipas ang check-in. Pumili ng petsa sa hinaharap.",
                "Hindi pwedeng nakalipas ang check-in. Pumili ng future date."
            );

            echo json_encode([
                'reply' => $response
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            exit;
        }

        if ($check_out <= $check_in) {

            $state['check_out'] = null;

            $response = reply(
                "Check-out must be after check-in.",
                "Ang check-out ay dapat pagkatapos ng check-in.",
                "Ang check-out ay dapat after ng check-in."
            );

            echo json_encode([
                'reply' => $response
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            exit;
        }

        $availableRoomId = getAvailableRoomByType(
            $pdo,
            $room_type,
            $check_in,
            $check_out
        );

        if ($availableRoomId !== null) {

            $link = SITE_URL .
                "booking.php?room_id={$availableRoomId}&check_in=$check_in&check_out=$check_out";

            $response = reply(
                "Great! The {$room_type} is available from {$check_in} to {$check_out}. Click here to book: $link",
                "Mahusay! Available ang {$room_type} mula {$check_in} hanggang {$check_out}. I-click ito para i-book: $link",
                "Great! Available ang {$room_type} mula {$check_in} hanggang {$check_out}. I-click ito para mag-book: $link"
            );

            $state = [
                'step' => 'idle',
                'room_type' => null,
                'check_in' => null,
                'check_out' => null
            ];

            echo json_encode([
                'reply' => $response
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            exit;

        } else {

            $alternatives = getAvailableAlternativeRoomTypes(
                $pdo,
                $room_type,
                $check_in,
                $check_out
            );

            if (empty($alternatives)) {

                $response = reply(
                    "Sorry, the {$room_type} is not available for those dates, and no other rooms are available. Please try different dates.",
                    "Paumanhin, ang {$room_type} ay hindi available sa mga petsang iyon, at walang ibang available na room. Subukan ang ibang petsa.",
                    "Sorry, ang {$room_type} ay hindi available sa mga dates na iyon, at walang ibang available. Subukan ang ibang dates."
                );

            } else {

                $altList = implode(', ', $alternatives);

                $response = reply(
                    "Sorry, the {$room_type} is already booked for those dates. Available rooms: {$altList}. Would you like to try one of those?",
                    "Paumanhin, ang {$room_type} ay may booking na sa mga petsang iyon. Available na rooms: {$altList}. Gusto mo bang subukan ang isa sa mga ito?",
                    "Sorry, ang {$room_type} ay booked na sa mga dates na iyon. Available na rooms: {$altList}. Gusto mo subukan ang isa?"
                );
            }

            $state['room_type'] = null;
            $state['step'] = 'awaiting_room_type';

            echo json_encode([
                'reply' => $response
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            exit;
        }
    }

    // ASK ROOM TYPE
    if (!$state['room_type']) {

        $state['step'] = 'awaiting_room_type';

        $response = reply(
            "Which room type do you want? We have: $roomTypesList.",
            "Aling room type ang gusto mo? Meron kaming: $roomTypesList.",
            "Aling room type ang gusto mo? Meron kaming: $roomTypesList."
        );

        echo json_encode([
            'reply' => $response
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    // ASK CHECK-IN
    if (!$state['check_in']) {

        $state['step'] = 'awaiting_checkin';

        $response = reply(
            "You want a {$state['room_type']}. When is your check-in date? (e.g., July 1, 2026)",
            "Gusto mo ng {$state['room_type']}. Kailan ang check-in date? (hal. Hulyo 1, 2026)",
            "Gusto mo ng {$state['room_type']}. Kailan ang check-in date? (e.g., July 1, 2026)"
        );

        echo json_encode([
            'reply' => $response
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    // ASK CHECK-OUT
    if (!$state['check_out']) {

        $state['step'] = 'awaiting_checkout';

        $response = reply(
            "When is your check-out date? (e.g., July 2, 2026)",
            "Kailan ang check-out date? (hal. Hulyo 2, 2026)",
            "Kailan ang check-out date? (e.g., July 2, 2026)"
        );

        echo json_encode([
            'reply' => $response
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }
}

// ------------------------------------------------------------
// 12. CONVERSATION HISTORY
// ------------------------------------------------------------
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

$_SESSION['chat_history'][] = [
    'role' => 'user',
    'content' => $message
];

if (count($_SESSION['chat_history']) > 10) {

    $_SESSION['chat_history'] = array_slice(
        $_SESSION['chat_history'],
        -10
    );
}

// ------------------------------------------------------------
// 13. LANGUAGE INSTRUCTIONS FOR OPENAI
// ------------------------------------------------------------
$languageInstruction = '';

if ($lang === 'tl') {

    $languageInstruction = "
    LANGUAGE FOR THIS RESPONSE: Filipino/Tagalog.

    Reply in natural, conversational Tagalog.
    Do not switch to English just because hotel terms
    such as room, check-in, booking, or availability
    are commonly used in English.
    ";

} elseif ($lang === 'tl-en') {

    $languageInstruction = "
    LANGUAGE FOR THIS RESPONSE: Taglish.

    Reply in natural Filipino-English Taglish,
    mixing both languages the way a fluent Filipino
    speaker naturally would.

    Do not force a full English or full Tagalog translation.
    ";

} else {

    $languageInstruction = "
    LANGUAGE FOR THIS RESPONSE: English.

    Reply in natural, conversational English.
    Do not switch to Tagalog unless the user switches language.
    ";
}

// ------------------------------------------------------------
// 14. OPENAI SYSTEM PROMPT
// ------------------------------------------------------------
$systemPrompt = "
You are SC Assistant, the friendly and professional
virtual receptionist of SC Hotel.

$languageInstruction

CONVERSATION STYLE:

- Sound natural, warm, helpful, and human,
  like a good hotel receptionist having a real conversation.

- Match the language and tone of the user's latest message,
  not just the language of earlier messages.

- Use conversation history to understand short replies
  and follow-up questions.

- Do not repeat your introduction or greeting
  in every response.

- Do not sound robotic, overly formal,
  or use awkward literal translations.

- Keep answers clear and concise.

- Ask only one useful question at a time.

- If the user says they want to book,
  guide them naturally through choosing a room
  and providing dates.

- If the user only says hello or hi,
  greet them naturally and ask how you can help.

HOTEL INFORMATION RULES:

- Use the supplied SC Hotel database information
  as the source of truth for room types, prices,
  capacities, and descriptions.

- Never invent room prices, room types, capacities,
  amenities, facilities, address details,
  contact details, or hotel policies.

- If the database does not contain the information requested,
  politely say that you do not have that information
  and suggest contacting the hotel.

- If the user asks about one room,
  focus on that room.

- If the user asks about all rooms,
  list the relevant rooms.

- If the user asks for the cheapest or most expensive room,
  compare the actual supplied prices.

- If the user asks for a multi-night price,
  calculate it using the actual nightly rate
  and number of nights.

- If the user asks about room capacity,
  use the actual database capacity.

- If the user asks for a recommendation,
  use only the supplied price, capacity, and description.

- Never claim that a room is available or unavailable
  based on a guess. Availability is checked by PHP/MySQL.

- Do not claim that a booking is confirmed.
  The booking page and hotel system handle the actual booking.

ROOM DATA FROM DATABASE:

$roomList
";

// ------------------------------------------------------------
// 15. BUILD CHAT MESSAGES
// ------------------------------------------------------------
$chatMessages = [
    [
        'role' => 'system',
        'content' => $systemPrompt
    ]
];

foreach ($_SESSION['chat_history'] as $entry) {
    $chatMessages[] = $entry;
}

// ------------------------------------------------------------
// 16. OPENAI API REQUEST
// ------------------------------------------------------------
$ch = curl_init();

curl_setopt(
    $ch,
    CURLOPT_URL,
    'https://api.openai.com/v1/chat/completions'
);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

curl_setopt($ch, CURLOPT_TIMEOUT, 45);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . OPENAI_API_KEY,
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
]);

$data = [
    'model' => OPENAI_MODEL,
    'messages' => $chatMessages,
    'temperature' => 0.3,
    'max_tokens' => 350
];

curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode($data)
);

$response = curl_exec($ch);

$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

$curlError = curl_error($ch);

curl_close($ch);

// ------------------------------------------------------------
// 17. PROCESS OPENAI RESPONSE
// ------------------------------------------------------------
$debugInfo = "HTTP Code: $httpCode | Error: $curlError | Response: " .
    substr((string)$response, 0, 500);

if ($httpCode === 200) {

    $result = json_decode($response, true);

    $aiReply = $result['choices'][0]['message']['content']
        ?? null;

    if (!empty($aiReply)) {

        $_SESSION['chat_history'][] = [
            'role' => 'assistant',
            'content' => $aiReply
        ];

        echo json_encode([
            'reply' => trim($aiReply)
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }
}

// ------------------------------------------------------------
// 18. RULE-BASED FALLBACK
// ------------------------------------------------------------
$msgLower = strtolower($message);

$fallback = null;

// ROOM PRICES
if (preg_match(
    '/\b(price|cost|rate|magkano|presyo|halaga)\b/i',
    $msgLower
)) {

    $priceList = '';

    foreach ($roomsData as $r) {

        $priceList .= "• {$r['type']}: ₱{$r['price_per_night']}/night\n";
    }

    $fallback = reply(
        "Here are our room rates:\n$priceList",
        "Narito ang aming room rates:\n$priceList",
        "Here are our room rates:\n$priceList"
    );
}

// ROOM TYPES
elseif (preg_match(
    '/\b(room|rooms|type|types|kwarto)\b/i',
    $msgLower
)) {

    $fallback = reply(
        "We have these room types: $roomTypesList. Would you like prices or availability?",
        "Meron kaming mga room types: $roomTypesList. Gusto mo ba ng presyo o availability?",
        "Meron kaming room types: $roomTypesList. Gusto mo ng price o availability?"
    );
}

// HOTEL AMENITIES
elseif (preg_match(
    '/\b(pool|gym|wifi|parking|restaurant|amenities|facility|facilities|amenidad|pasilidad)\b/i',
    $msgLower
)) {

    $fallback = reply(
        "I don't have verified details about the hotel's amenities right now. Please contact the hotel directly for accurate information.",
        "Wala pa akong kumpirmadong detalye tungkol sa mga amenities ng hotel. Mas mabuting direktang kontakin ang hotel para sa tamang impormasyon.",
        "Wala pa akong verified details tungkol sa hotel amenities. Please contact the hotel directly para sa accurate information."
    );
}

// HOTEL LOCATION
elseif (preg_match(
    '/\b(location|address|where|saan|lokasyon)\b/i',
    $msgLower
)) {

    $fallback = reply(
        "I don't have the hotel's verified address details right now. Please contact the hotel directly for its exact location.",
        "Wala pa akong kumpirmadong detalye ng address ng hotel. Pakikontak na lang ang hotel para malaman ang eksaktong lokasyon.",
        "Wala pa akong verified address ng hotel. Please contact the hotel directly para sa exact location."
    );
}

// GENERAL FALLBACK
if ($fallback === null) {

    $fallback = reply(
        "What do you need help with? I can assist with room rates, availability, booking, and hotel amenities.",
        "Ano pong kailangan ninyo? Maaari akong tumulong sa room rates, availability, booking, at hotel amenities.",
        "Ano pong kailangan ninyo? Maaari akong tumulong sa room rates, availability, booking, and hotel amenities."
    );
}

// RETURN FALLBACK RESPONSE
echo json_encode([
    'reply' => $fallback,
    'debug' => $debugInfo
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
