<?php
require_once __DIR__ . '/session_init.php';
include 'db_connect.php';
include 'helpers.php';
@include_once 'ai_config.php';

header('Content-Type: application/json');

// Always send valid JSON back to the browser, even if something crashes or times out.
@set_time_limit(30);
ob_start();
register_shutdown_function(function () {
  if (defined('CHATBOT_DEBUG')) { return; }   // used only by the admin diagnostic page
  $out = ob_get_contents();
  if ($out === false || json_decode(trim((string)$out), true) === null) {
    @ob_end_clean();
    if (!headers_sent()) { header('Content-Type: application/json'); http_response_code(200); }
    echo json_encode(["reply" => "Sorry, the assistant had a problem just now. Please try again in a moment."]);
  }
});

if (!csrf_verify_request()) {
  echo json_encode(["reply" => "Security check failed. Please refresh the page and try again."]);
  exit;
}

ensureSchema($conn);

if (isset($_POST['message_b64'])) {
  $userMessage = trim((string)base64_decode((string)$_POST['message_b64'], true));
} else {
  $userMessage = isset($_POST['message']) ? trim($_POST['message']) : "";
}
if (isset($_POST['history_b64'])) {
  $historyJson = (string)base64_decode((string)$_POST['history_b64'], true);
} else {
  $historyJson = isset($_POST['history']) ? $_POST['history'] : "[]";
}
$history = json_decode($historyJson, true);
if (!is_array($history)) { $history = []; }

if ($userMessage === "") {
  echo json_encode(["reply" => "Please type a question or pick one of the suggestion chips above!"]);
  exit;
}

$currentUser = isset($_SESSION['profileName']) ? $_SESSION['profileName'] : "";
$me = null;
if ($currentUser !== "") {
  $stmtMe = $conn->prepare("SELECT * FROM users WHERE name = ? LIMIT 1");
  if ($stmtMe) {
    $stmtMe->bind_param("s", $currentUser);
    $stmtMe->execute();
    $me = $stmtMe->get_result()->fetch_assoc();
    $stmtMe->close();
  }
}

// Gather live platform context from MySQL database
$allUsers = [];
$resU = $conn->query("SELECT id, name, branch, year_of_study, teach_skill, learn_skill, verified FROM users ORDER BY id DESC LIMIT 40");
if ($resU) { $allUsers = $resU->fetch_all(MYSQLI_ASSOC); }

$upcomingSessions = [];
$resS = $conn->query("SELECT s.*, (SELECT COUNT(*) FROM peer_session_participants p WHERE p.session_id = s.id) AS joined_count FROM peer_sessions s WHERE s.status = 'upcoming' ORDER BY s.featured DESC, s.session_date ASC LIMIT 10");
if ($resS) { $upcomingSessions = $resS->fetch_all(MYSQLI_ASSOC); }

$activeAnnouncements = [];
$resA = $conn->query("SELECT id, title, category, body, deadline, pinned, DATEDIFF(deadline, CURDATE()) AS days_left FROM announcements WHERE (deadline IS NULL OR deadline >= CURDATE()) ORDER BY pinned DESC, created_at DESC LIMIT 8");
if ($resA) { $activeAnnouncements = $resA->fetch_all(MYSQLI_ASSOC); }

$openHelp = [];
$resH = $conn->query("SELECT id, author_name, type, title, details FROM help_posts WHERE status = 'open' ORDER BY created_at DESC LIMIT 6");
if ($resH) { $openHelp = $resH->fetch_all(MYSQLI_ASSOC); }

function httpPostJson($url, $jsonBody, $extraHeaders = [], $timeoutSec = 12) {
  // Shared time budget for all outside AI calls, so a slow network can never hang the page.
  $remaining = ($GLOBALS['AI_DEADLINE'] ?? (microtime(true) + 25)) - microtime(true);
  if ($remaining < 1.5) { return ["ok" => false, "body" => ""]; }
  $timeoutSec = max(1, (int)floor(min($timeoutSec, $remaining)));
  $headers = array_merge([
    "Content-Type: application/json",
    "Accept: application/json"
  ], $extraHeaders);

  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSec);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(4, $timeoutSec));
    $body = curl_exec($ch);
    $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    curl_close($ch);
    if ($body !== false && $code >= 200 && $code < 300) {
      return ["ok" => true, "body" => $body];
    }
  } else {
    $opts = [
      'http' => [
        'method'  => 'POST',
        'header'  => implode("\r\n", $headers) . "\r\n",
        'content' => $jsonBody,
        'timeout' => $timeoutSec,
        'ignore_errors' => true
      ],
      'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
      ]
    ];
    $ctx = stream_context_create($opts);
    $body = @file_get_contents($url, false, $ctx);
    if ($body !== false && strlen(trim($body)) > 0) {
      return ["ok" => true, "body" => $body];
    }
  }
  return ["ok" => false, "body" => ""];
}

function callGeminiChatbotApi($systemContext, $history, $userMessage) {
  $gemKey = defined('GEMINI_API_KEY') ? trim(GEMINI_API_KEY) : '';

  if ($gemKey !== '') {
    $convoText = "";
    foreach (array_slice($history, -6) as $turn) {
      if (isset($turn['sender'], $turn['text'])) {
        $convoText .= ($turn['sender'] === 'user' ? "Student: " : "Assistant: ") . $turn['text'] . "\n";
      }
    }
    $fullPrompt = $systemContext . "\n\nRecent Conversation:\n" . $convoText . "\nStudent question: " . $userMessage;

    $gemPayload = json_encode([
      "contents" => [
        [
          "role" => "user",
          "parts" => [["text" => $fullPrompt]]
        ]
      ],
      "generationConfig" => [
        "thinkingConfig" => ["thinkingLevel" => "low"]
      ]
    ]);

    // Support both Vertex AI Express Mode (for AQ.* keys) and Google AI Studio (generativelanguage.googleapis.com)
    // Models: gemini-3.8-flash is the current one, gemini-3.7-flash is the fallback.
    // (The old 2.5 / 2.0 / 1.5 names are retired for new keys, which caused the "connection" error.)
    $gl = "https://generativelanguage.googleapis.com/v1beta/models/";
    $ap = "https://aiplatform.googleapis.com/v1/publishers/google/models/";
    $q  = ":generateContent?key=" . urlencode($gemKey);
    if (strpos($gemKey, 'AQ.') === 0) {
      $endpoints = [$ap . "gemini-3.8-flash" . $q, $gl . "gemini-3.8-flash" . $q, $gl . "gemini-3.7-flash" . $q];
    } else {
      $endpoints = [$gl . "gemini-3.8-flash" . $q, $gl . "gemini-3.7-flash" . $q, $ap . "gemini-3.8-flash" . $q];
    }

    foreach (array_slice($endpoints, 0, 3) as $epUrl) {
      $resp = httpPostJson($epUrl, $gemPayload, ["x-goog-api-key: " . $gemKey], 15);
      if ($resp['ok']) {
        $data = json_decode($resp['body'], true);
        $txt = "";
        foreach (($data['candidates'][0]['content']['parts'] ?? []) as $part) {
          if (!empty($part['thought'])) { continue; }
          if (isset($part['text'])) { $txt .= $part['text']; }
        }
        if (trim($txt) !== '') {
          return ["ok" => true, "text" => trim($txt)];
        }
      }
    }
  }

  // Automatic backup Cloud AI endpoint so the chatbot still responds even if quota/network fluctuates
  $messages = [["role" => "system", "content" => $systemContext]];
  foreach (array_slice($history, -6) as $turn) {
    if (isset($turn['sender'], $turn['text']) && trim($turn['text']) !== '') {
      $messages[] = [
        "role" => ($turn['sender'] === 'user' ? "user" : "assistant"),
        "content" => (string)$turn['text']
      ];
    }
  }
  $messages[] = ["role" => "user", "content" => $userMessage];

  $backupPayload = json_encode([
    "model" => "openai",
    "messages" => $messages,
    "temperature" => 0.6
  ]);
  $resp = httpPostJson("https://text.pollinations.ai/openai", $backupPayload, [], 10);
  if ($resp['ok']) {
    $decoded = json_decode($resp['body'], true);
    $replyText = $decoded['choices'][0]['message']['content'] ?? '';
    if (trim($replyText) !== '') {
      return ["ok" => true, "text" => trim($replyText)];
    }
  }

  return ["ok" => false, "text" => ""];
}

// Build live campus context string for the Gemini AI
$mentorSummary = [];
foreach (array_slice($allUsers, 0, 15) as $u) {
  $mentorSummary[] = $u['name'] . " (ID:" . $u['id'] . ", " . $u['branch'] . ", " . $u['year_of_study'] . ") Teaches: " . $u['teach_skill'] . " | Learns: " . $u['learn_skill'];
}
$sessionSummary = [];
foreach (array_slice($upcomingSessions, 0, 6) as $s) {
  $sessionSummary[] = $s['title'] . " (" . $s['skill_topic'] . ") by " . $s['host_name'] . " on " . $s['session_date'] . " at " . $s['session_time'] . " [" . $s['mode'] . "]";
}
$announceSummary = [];
foreach (array_slice($activeAnnouncements, 0, 6) as $a) {
  $announceSummary[] = "[" . $a['category'] . "] " . $a['title'] . ($a['deadline'] ? " (Deadline: " . $a['deadline'] . ")" : "") . " - " . textCut($a['body'] ?? '', 120);
}

$systemContext = "You are the Smart AI Assistant for Skill Connect at MIT CSN (Maharashtra Institute of Technology Chhatrapati Sambhajinagar).\n"
  . "Current logged-in student: " . ($currentUser ?: "Guest Student") . ($me ? " (Teaches: {$me['teach_skill']}, Wants to learn: {$me['learn_skill']}, Branch: {$me['branch']}, Year: {$me['year_of_study']})" : "") . ".\n"
  . "Platform links you can include using markdown [Label](page.php): [Browse Skills](browse.php), [Peer Sessions](peer_sessions.php), [Announcements](announcements.php), [Saved Notices](saved_announcements.php), [Help Board](help_board.php), [My Profile](profile.php).\n"
  . "Live Campus Mentors in DB: " . (count($mentorSummary) ? implode(" ; ", $mentorSummary) : "No mentors registered yet") . "\n"
  . "Upcoming Peer Teaching Sessions in DB: " . (count($sessionSummary) ? implode(" ; ", $sessionSummary) : "No upcoming sessions scheduled right now") . "\n"
  . "Active College Announcements in DB: " . (count($announceSummary) ? implode(" ; ", $announceSummary) : "No active announcements right now") . "\n"
  . "Instructions: Answer the student's question accurately, concisely (2-6 bullet points or short paragraphs), and helpfully. If they ask about campus mentors, peer sessions, or announcements, use the live DB facts above and link to the relevant page. If they ask a coding, DSA, placement, or study question, give a clear, practical explanation.";

// Call the Gemini API. Gemini 3.x can need 5-10 seconds on a free host, so allow up to 25 seconds in total
// (the host allows 60 s per request). If it is still too slow, the built-in assistant answers instead.
$GLOBALS['AI_DEADLINE'] = microtime(true) + 25;
$aiResult = callGeminiChatbotApi($systemContext, $history, $userMessage);
if ($aiResult['ok']) {
  echo json_encode(["reply" => $aiResult['text']]);
  exit;
}

// Offline Fallback: Smart Local Campus AI Engine
function generateSmartLocalReply($conn, $msg, $currentUser, $me, $allUsers, $upcomingSessions, $activeAnnouncements, $openHelp) {
  $q = strtolower($msg);

  if (strpos($q, 'session') !== false || strpos($q, 'workshop') !== false || strpos($q, 'peer teach') !== false || strpos($q, 'class') !== false || strpos($q, 'live') !== false) {
    if (count($upcomingSessions) > 0) {
      $lines = ["Here are the **upcoming Peer Teaching Sessions** on campus:"];
      foreach (array_slice($upcomingSessions, 0, 4) as $s) {
        $seatsLeft = max(0, intval($s['max_seats']) - intval($s['joined_count']));
        $lines[] = "• **" . $s['title'] . "** (" . $s['skill_topic'] . ") by " . $s['host_name'] . " — " . $s['session_date'] . " at " . $s['session_time'] . " [" . $s['mode'] . ", " . $seatsLeft . " seats left]";
      }
      $lines[] = "👉 Visit [Peer Sessions](peer_sessions.php) to RSVP or host your own session!";
      return implode("\n", $lines);
    } else {
      return "There are no scheduled peer teaching sessions right now. You can be the first to host a group study or workshop on [Peer Sessions](peer_sessions.php)!";
    }
  }

  if (strpos($q, 'announce') !== false || strpos($q, 'notice') !== false || strpos($q, 'placement') !== false || strpos($q, 'internship') !== false || strpos($q, 'hackathon') !== false || strpos($q, 'scholarship') !== false || strpos($q, 'deadline') !== false || strpos($q, 'saved') !== false) {
    if (strpos($q, 'save') !== false || strpos($q, 'bookmark') !== false) {
      return "You can bookmark any college notice by clicking **🔖 Save** on any card in [Announcements](announcements.php). View all your bookmarked notices anytime on [Saved Notices](saved_announcements.php)!";
    }
    if (count($activeAnnouncements) > 0) {
      $lines = ["Here are the **latest active college announcements**:"];
      foreach (array_slice($activeAnnouncements, 0, 4) as $a) {
        $dl = $a['deadline'] ? " (Deadline: " . $a['deadline'] . ")" : "";
        $pin = $a['pinned'] ? "📌 " : "";
        $lines[] = "• " . $pin . "**[" . $a['category'] . "] " . $a['title'] . "**" . $dl;
      }
      $lines[] = "👉 Open [Announcements](announcements.php) to read full details or check your [Saved Notices](saved_announcements.php).";
      return implode("\n", $lines);
    } else {
      return "No active announcements are posted right now. Check [Announcements](announcements.php) soon for new campus notices!";
    }
  }

  if (strpos($q, 'match') !== false || strpos($q, 'swap') !== false || strpos($q, 'suggest') !== false || strpos($q, 'recommend') !== false) {
    if ($me) {
      $matches = [];
      foreach ($allUsers as $u) {
        if ($u['name'] === $currentUser) continue;
        $theyTeach = matchingSkills($me['learn_skill'], $u['teach_skill']);
        $iTeach = matchingSkills($u['learn_skill'], $me['teach_skill']);
        if (!empty($theyTeach)) {
          $matches[] = [
            'user' => $u,
            'they_teach' => $theyTeach,
            'i_teach' => $iTeach,
            'two_way' => !empty($iTeach)
          ];
        }
      }
      if (count($matches) > 0) {
        $lines = ["Based on your profile (wants to learn **" . $me['learn_skill'] . "**), here are your top matches:"];
        foreach (array_slice($matches, 0, 4) as $m) {
          $u = $m['user'];
          $badge = $m['two_way'] ? "✨ **2-Way Match**" : "👍 **Mentor Match**";
          $lines[] = "• " . $badge . ": [" . $u['name'] . "](profile_view.php?id=" . $u['id'] . ") (" . $u['branch'] . ") teaches **" . implode(", ", $m['they_teach']) . "**" . ($m['two_way'] ? " & wants to learn **" . implode(", ", $m['i_teach']) . "**" : "");
        }
        $lines[] = "👉 Head to [Browse Skills](browse.php) to send them a connection request!";
        return implode("\n", $lines);
      } else {
        return "I couldn't find an exact match for **" . $me['learn_skill'] . "** yet. Try updating your skills on [My Profile](profile.php) or explore all classmates on [Browse Skills](browse.php)!";
      }
    } else {
      return "Log in on [Student Login](login.php) or create your profile on [Create Profile](profile.php) so I can analyze your skills and find your 2-way skill swap matches!";
    }
  }

  if (strpos($q, 'help board') !== false || strpos($q, 'teammate') !== false || strpos($q, 'partner') !== false || strpos($q, 'project team') !== false) {
    if (count($openHelp) > 0) {
      $lines = ["Here are open posts on the **Help Board**:"];
      foreach (array_slice($openHelp, 0, 4) as $hPost) {
        $lines[] = "• **" . $hPost['title'] . "** by " . $hPost['author_name'] . " (" . ($hPost['type'] === 'teammates' ? 'Looking for teammates' : 'Needs help') . ")";
      }
      $lines[] = "👉 Visit [Help Board](help_board.php) to reply or post your own requirement!";
      return implode("\n", $lines);
    }
    return "You can find hackathon teammates or ask classmates for project help on the [Help Board](help_board.php)!";
  }

  if (strpos($q, 'roadmap') !== false || strpos($q, 'how to learn') !== false || strpos($q, 'study plan') !== false || strpos($q, 'dsa') !== false) {
    if (strpos($q, 'python') !== false) {
      return "**🐍 4-Step Python Roadmap for MIT CSN Students:**\n• **Step 1 (Weeks 1–2):** Master syntax, lists, dicts, functions, and OOP basics.\n• **Step 2 (Weeks 3–4):** Build 2 mini-projects (CLI expense tracker, web scraper with BeautifulSoup).\n• **Step 3 (Weeks 5–6):** Pick a track — **Web** (Flask/FastAPI) or **Data/AI** (NumPy, Pandas, Scikit-learn).\n• **Step 4:** Join a [Peer Session](peer_sessions.php) or connect with a Python mentor on [Browse Skills](browse.php)!";
    }
    if (strpos($q, 'web') !== false || strpos($q, 'react') !== false || strpos($q, 'frontend') !== false || strpos($q, 'fullstack') !== false) {
      return "**🌐 4-Step Full-Stack Web Dev Roadmap:**\n• **Step 1:** HTML5, modern CSS (Flexbox, Grid, Tailwind), and responsive layouts.\n• **Step 2:** JavaScript ES6+ (DOM, Fetch API, async/await, localStorage).\n• **Step 3:** Frontend framework (React) + Backend (PHP/MySQL or Node.js/Express).\n• **Step 4:** Deploy a portfolio project and find teammates on [Help Board](help_board.php)!";
    }
    return "**🗺️ 4-Step DSA & Placement Prep Roadmap:**\n• **Step 1 (Foundations):** Time complexity (Big-O), Arrays, Strings, Hashing, and Two Pointers.\n• **Step 2 (Core Structures):** Linked Lists, Stacks, Queues, Binary Trees, and BSTs.\n• **Step 3 (Advanced):** Graphs (BFS/DFS), Heaps, Recursion/Backtracking, and Dynamic Programming.\n• **Step 4 (Practice):** Solve 2 problems daily and join a DSA study group on [Peer Sessions](peer_sessions.php)!";
  }

  $skillKeywords = ['python', 'java', 'javascript', 'react', 'web', 'html', 'css', 'php', 'sql', 'mysql', 'c++', 'c ', 'dsa', 'machine learning', 'ai', 'data', 'flutter', 'android', 'ui', 'ux', 'figma', 'design', 'git', 'linux', 'cloud', 'aws', 'cyber', 'iot', 'robotics', 'autocad', 'solidworks', 'matlab', 'guitar', 'music', 'photography', 'public speaking', 'english'];
  $detectedSkill = '';
  foreach ($skillKeywords as $kw) {
    if (strpos($q, $kw) !== false) {
      $detectedSkill = trim($kw);
      break;
    }
  }

  if ($detectedSkill !== '' || strpos($q, 'mentor') !== false || strpos($q, 'teach') !== false || strpos($q, 'who can') !== false || strpos($q, 'find') !== false) {
    $matchedMentors = [];
    foreach ($allUsers as $u) {
      if ($u['teach_skill'] === 'Nothing yet' || trim($u['teach_skill']) === '') continue;
      if ($detectedSkill === '' || stripos($u['teach_skill'], $detectedSkill) !== false || stripos($u['branch'], $detectedSkill) !== false) {
        $matchedMentors[] = $u;
      }
    }
    if (count($matchedMentors) > 0) {
      $header = $detectedSkill !== ''
        ? "I found **" . count($matchedMentors) . " mentor(s)** on campus for **" . ucfirst($detectedSkill) . "**:"
        : "Here are active **student mentors** on Skill Connect:";
      $lines = [$header];
      foreach (array_slice($matchedMentors, 0, 5) as $m) {
        $lines[] = "• **[" . $m['name'] . "](profile_view.php?id=" . $m['id'] . ")** (" . $m['branch'] . ", " . $m['year_of_study'] . ") — Teaches: *" . $m['teach_skill'] . "*";
      }
      $lines[] = "👉 Go to [Browse Skills](browse.php" . ($detectedSkill ? "?search=" . urlencode($detectedSkill) : "") . ") to connect with them!";
      return implode("\n", $lines);
    } elseif ($detectedSkill !== '') {
      return "No student has listed **" . ucfirst($detectedSkill) . "** under 'Skills You Can Teach' yet. You can post a request on the [Help Board](help_board.php) or browse other mentors on [Browse Skills](browse.php)!";
    }
  }

  $uCount = count($allUsers);
  $sCount = count($upcomingSessions);
  $aCount = count($activeAnnouncements);
  $greeting = $currentUser ? "Hi **$currentUser**! " : "Hi there! ";
  return $greeting . "I'm your **Skill Connect Smart AI**. Right now on campus we have **$uCount registered student(s)**, **$sCount upcoming peer session(s)**, and **$aCount active announcement(s)**.\n\nYou can ask me:\n• *\"Find a mentor for Python / Web Dev / DSA\"*\n• *\"Show upcoming peer teaching sessions\"*\n• *\"Who is my best skill swap match?\"*\n• *\"What are the latest announcements?\"*\n• *\"Give me a learning roadmap for DSA or Web Dev\"*";
}

$smartReply = generateSmartLocalReply($conn, $userMessage, $currentUser, $me, $allUsers, $upcomingSessions, $activeAnnouncements, $openHelp);
echo json_encode(["reply" => $smartReply]);
?>