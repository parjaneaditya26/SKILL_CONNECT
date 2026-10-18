<?php
session_start();
include 'db_connect.php';
include 'helpers.php';
@include_once 'ai_config.php';

header('Content-Type: application/json');

$userMessage = isset($_POST['message']) ? trim($_POST['message']) : "";
$historyJson = isset($_POST['history']) ? $_POST['history'] : "[]";
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

// Gather live platform context from database
$allUsers = [];
$resU = $conn->query("SELECT id, name, branch, year_of_study, teach_skill, learn_skill, verified FROM users ORDER BY id DESC LIMIT 40");
if ($resU) { $allUsers = $resU->fetch_all(MYSQLI_ASSOC); }

$upcomingSessions = [];
$resS = $conn->query("SELECT s.*, (SELECT COUNT(*) FROM peer_session_participants p WHERE p.session_id = s.id) AS joined_count FROM peer_sessions s WHERE s.status = 'upcoming' ORDER BY s.featured DESC, s.session_date ASC LIMIT 10");
if ($resS) { $upcomingSessions = $resS->fetch_all(MYSQLI_ASSOC); }

$activeAnnouncements = [];
$resA = $conn->query("SELECT id, title, category, deadline, pinned, DATEDIFF(deadline, CURDATE()) AS days_left FROM announcements WHERE (deadline IS NULL OR deadline >= CURDATE()) ORDER BY pinned DESC, created_at DESC LIMIT 8");
if ($resA) { $activeAnnouncements = $resA->fetch_all(MYSQLI_ASSOC); }

$openHelp = [];
$resH = $conn->query("SELECT id, author_name, type, title, details FROM help_posts WHERE status = 'open' ORDER BY created_at DESC LIMIT 6");
if ($resH) { $openHelp = $resH->fetch_all(MYSQLI_ASSOC); }

function generateSmartLocalReply($conn, $msg, $currentUser, $me, $allUsers, $upcomingSessions, $activeAnnouncements, $openHelp) {
  $q = strtolower($msg);

  // 1. Peer teaching sessions intent
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

  // 2. Saved announcements or announcements / placements / internships / hackathons / exams
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
      return "No active announcements are posted right now. Check [Announcements](announcements.php) or use the [Admin Panel](admin_announcements.php) to post a notice!";
    }
  }

  // 3. Skill Match / Swap recommendations
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

  // 4. Help board / teammates
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

  // 5. Learning roadmaps (DSA, Python, Web Dev, ML, Java, UI/UX, Git)
  if (strpos($q, 'roadmap') !== false || strpos($q, 'how to learn') !== false || strpos($q, 'study plan') !== false || strpos($q, 'dsa') !== false) {
    if (strpos($q, 'python') !== false) {
      return "**🐍 4-Step Python Roadmap for MIT CSN Students:**\n• **Step 1 (Weeks 1–2):** Master syntax, lists, dicts, functions, and OOP basics.\n• **Step 2 (Weeks 3–4):** Build 2 mini-projects (CLI expense tracker, web scraper with BeautifulSoup).\n• **Step 3 (Weeks 5–6):** Pick a track — **Web** (Flask/FastAPI) or **Data/AI** (NumPy, Pandas, Scikit-learn).\n• **Step 4:** Join a [Peer Session](peer_sessions.php) or connect with a Python mentor on [Browse Skills](browse.php)!";
    }
    if (strpos($q, 'web') !== false || strpos($q, 'react') !== false || strpos($q, 'frontend') !== false || strpos($q, 'fullstack') !== false) {
      return "**🌐 4-Step Full-Stack Web Dev Roadmap:**\n• **Step 1:** HTML5, modern CSS (Flexbox, Grid, Tailwind), and responsive layouts.\n• **Step 2:** JavaScript ES6+ (DOM, Fetch API, async/await, localStorage).\n• **Step 3:** Frontend framework (React) + Backend (PHP/MySQL or Node.js/Express).\n• **Step 4:** Deploy a portfolio project and find teammates on [Help Board](help_board.php)!";
    }
    return "**🗺️ 4-Step DSA & Placement Prep Roadmap:**\n• **Step 1 (Foundations):** Time complexity (Big-O), Arrays, Strings, Hashing, and Two Pointers.\n• **Step 2 (Core Structures):** Linked Lists, Stacks, Queues, Binary Trees, and BSTs.\n• **Step 3 (Advanced):** Graphs (BFS/DFS), Heaps, Recursion/Backtracking, and Dynamic Programming.\n• **Step 4 (Practice):** Solve 2 problems daily and join a DSA study group on [Peer Sessions](peer_sessions.php)!";
  }

  // 6. Search for a specific skill or mentor (e.g. "who teaches...", "find mentor", "python", "java", "c++", "ui", "guitar", etc.)
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

  // 7. Admin access question
  if (strpos($q, 'admin') !== false) {
    return "You can open the **Admin Control Center** anytime from the **🛡️ Admin Login** button on the [Home Page](index.php?view_site=1) using the admin password. From there, you can manage users, peer sessions, announcements, help posts, and CSV exports!";
  }

  // Default helpful overview with live stats
  $uCount = count($allUsers);
  $sCount = count($upcomingSessions);
  $aCount = count($activeAnnouncements);
  $greeting = $currentUser ? "Hi **$currentUser**! " : "Hi there! ";
  return $greeting . "I'm your **Skill Connect Smart AI**. Right now on campus we have **$uCount+ members**, **$sCount upcoming peer sessions**, and **$aCount active announcements**.\n\nYou can ask me:\n• *\"Find a mentor for Python / Web Dev / DSA\"*\n• *\"Show upcoming peer teaching sessions\"*\n• *\"Who is my best skill swap match?\"*\n• *\"What are the latest announcements?\"*\n• *\"Give me a learning roadmap for DSA or Web Dev\"*";
}

// Try Gemini API if a valid key is configured
$apiKey = defined('GEMINI_API_KEY') ? trim(GEMINI_API_KEY) : '';
if ($apiKey !== '' && $apiKey !== 'YOUR_API_KEY_HERE' && function_exists('curl_init')) {
  $mentorSummary = [];
  foreach (array_slice($allUsers, 0, 15) as $u) {
    $mentorSummary[] = $u['name'] . " (" . $u['branch'] . ", " . $u['year_of_study'] . ") Teaches: " . $u['teach_skill'] . " | Learns: " . $u['learn_skill'];
  }
  $sessionSummary = [];
  foreach (array_slice($upcomingSessions, 0, 6) as $s) {
    $sessionSummary[] = $s['title'] . " (" . $s['skill_topic'] . ") by " . $s['host_name'] . " on " . $s['session_date'] . " " . $s['session_time'] . " [" . $s['mode'] . "]";
  }
  $announceSummary = [];
  foreach (array_slice($activeAnnouncements, 0, 6) as $a) {
    $announceSummary[] = "[" . $a['category'] . "] " . $a['title'] . ($a['deadline'] ? " (Due: " . $a['deadline'] . ")" : "");
  }

  $systemContext = "You are the Smart AI Assistant for Skill Connect at MIT CSN (Maharashtra Institute of Technology Chhatrapati Sambhajinagar).\n"
    . "Current user: " . ($currentUser ?: "Guest Visitor") . ($me ? " (Teaches: {$me['teach_skill']}, Wants to learn: {$me['learn_skill']}, Branch: {$me['branch']})" : "") . ".\n"
    . "Platform pages you can link to using [Label](page.php): [Browse Skills](browse.php), [Peer Sessions](peer_sessions.php), [Announcements](announcements.php), [Saved Notices](saved_announcements.php), [Help Board](help_board.php), [My Profile](profile.php), [Admin Dashboard](admin.php).\n"
    . "Live Mentors on platform: " . implode(" ; ", $mentorSummary) . "\n"
    . "Upcoming Peer Teaching Sessions: " . implode(" ; ", $sessionSummary) . "\n"
    . "Active Announcements: " . implode(" ; ", $announceSummary) . "\n"
    . "Keep answers concise (2-5 bullet points or sentences), helpful, and grounded in this live campus data.";

  $convoText = "";
  foreach (array_slice($history, -6) as $turn) {
    if (isset($turn['sender'], $turn['text'])) {
      $convoText .= ($turn['sender'] === 'user' ? "Student: " : "Assistant: ") . $turn['text'] . "\n";
    }
  }

  $fullPrompt = $systemContext . "\n\nRecent Conversation:\n" . $convoText . "\nStudent question: " . $userMessage;
  $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . urlencode($apiKey);

  $data = [
    "contents" => [
      ["parts" => [["text" => $fullPrompt]]]
    ]
  ];

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 8);

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($response !== false && $httpCode == 200) {
    $result = json_decode($response, true);
    $apiReply = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (trim($apiReply) !== '') {
      echo json_encode(["reply" => trim($apiReply)]);
      exit;
    }
  }
}

// Smart Local Campus AI Engine (always works, zero latency, live MySQL data)
$smartReply = generateSmartLocalReply($conn, $userMessage, $currentUser, $me, $allUsers, $upcomingSessions, $activeAnnouncements, $openHelp);
echo json_encode(["reply" => $smartReply]);
?>