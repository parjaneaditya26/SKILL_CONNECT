<?php
session_start();

// Check if a student/user is logged in (supports common session keys used in Skill Connect)
$isLoggedIn = !empty($_SESSION['user_id']) 
           || !empty($_SESSION['student_id']) 
           || !empty($_SESSION['user']) 
           || !empty($_SESSION['email']);

// Optional database connection fallback for live counts
$membersCount = 9;
$connectionsCount = 3;
$sessionsCount = 3;
$noticesCount = 1;

if (file_exists(__DIR__ . '/db.php')) {
    include_once __DIR__ . '/db.php';
} elseif (file_exists(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php';
}

if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    if ($res = @$conn->query("SELECT COUNT(*) AS c FROM users")) {
        $row = $res->fetch_assoc();
        $membersCount = (int)($row['c'] ?? $membersCount);
    } elseif ($res = @$conn->query("SELECT COUNT(*) AS c FROM students")) {
        $row = $res->fetch_assoc();
        $membersCount = (int)($row['c'] ?? $membersCount);
    }

    if ($res = @$conn->query("SELECT COUNT(*) AS c FROM connections")) {
        $row = $res->fetch_assoc();
        $connectionsCount = (int)($row['c'] ?? $connectionsCount);
    }

    if ($res = @$conn->query("SELECT COUNT(*) AS c FROM peer_sessions")) {
        $row = $res->fetch_assoc();
        $sessionsCount = (int)($row['c'] ?? $sessionsCount);
    } elseif ($res = @$conn->query("SELECT COUNT(*) AS c FROM sessions")) {
        $row = $res->fetch_assoc();
        $sessionsCount = (int)($row['c'] ?? $sessionsCount);
    }

    if ($res = @$conn->query("SELECT COUNT(*) AS c FROM announcements")) {
        $row = $res->fetch_assoc();
        $noticesCount = (int)($row['c'] ?? $noticesCount);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Connect — MIT CSN Peer Learning Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #fbf9f5;
            --bg-sidebar: #f3ede2;
            --bg-card: #ffffff;
            --text-primary: #1f1a17;
            --text-secondary: #6e655f;
            --accent: #c85a32;
            --accent-hover: #b24b26;
            --accent-soft: #f6e3dc;
            --border-color: #e6dfd5;
            --nav-active-bg: #e6dccb;
            --nav-active-text: #8c3f23;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Navigation */
        .sidebar {
            width: 240px;
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            padding: 24px 14px;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 10px 24px;
            text-decoration: none;
        }

        .brand-logo {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background-color: var(--accent);
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.5px;
        }

        .brand-text {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 19px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .brand-text span {
            color: var(--accent);
        }

        .nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 14px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.18s ease, color 0.18s ease;
        }

        .nav-link-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link:hover {
            background-color: rgba(200, 90, 50, 0.08);
        }

        .nav-link.active {
            background-color: var(--nav-active-bg);
            color: var(--nav-active-text);
            font-weight: 600;
        }

        .nav-badge {
            background-color: var(--accent-soft);
            color: var(--accent);
            font-size: 12px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 240px;
            flex: 1;
            padding: 44px 56px 80px;
            max-width: 1220px;
        }

        .campus-pill {
            display: inline-block;
            background-color: var(--accent-soft);
            color: var(--accent);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 18px;
        }

        .hero-title {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 46px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }

        .hero-subtitle {
            font-size: 16px;
            line-height: 1.6;
            color: var(--text-secondary);
            max-width: 650px;
            margin-bottom: 26px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 42px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.18s ease;
        }

        .btn-primary {
            background-color: var(--accent);
            color: #ffffff;
            border: 1px solid var(--accent);
        }

        .btn-primary:hover {
            background-color: var(--accent-hover);
        }

        .btn-secondary {
            background-color: #ffffff;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: #f5f2ec;
        }

        .btn-outline-accent {
            background-color: #ffffff;
            color: var(--accent);
            border: 1px solid #df9b82;
        }

        .btn-outline-accent:hover {
            background-color: var(--accent-soft);
        }

        /* Stats Row */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 210px));
            gap: 18px;
            margin-bottom: 48px;
        }

        .stat-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 22px 24px;
            box-shadow: 0 2px 6px rgba(31, 26, 23, 0.02);
        }

        .stat-number {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 13.5px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* How Skill Connect Works */
        .section-heading {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 22px;
            color: var(--text-primary);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .step-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 24px 22px;
            box-shadow: 0 2px 6px rgba(31, 26, 23, 0.02);
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--accent-soft);
            color: var(--accent);
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .step-title {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .step-desc {
            font-size: 13.5px;
            line-height: 1.6;
            color: var(--text-secondary);
        }

        /* Ask AI Floating Button */
        .ask-ai-fab {
            position: fixed;
            right: 24px;
            bottom: 20px;
            background-color: var(--accent);
            color: #ffffff;
            padding: 12px 22px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 20px rgba(200, 90, 50, 0.3);
            transition: transform 0.15s ease, background-color 0.15s ease;
        }

        .ask-ai-fab:hover {
            background-color: var(--accent-hover);
            transform: translateY(-2px);
        }

        @media (max-width: 1024px) {
            .steps-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

    <!-- Left Sidebar -->
    <aside class="sidebar">
        <a href="index.php" class="brand">
            <div class="brand-logo">SC</div>
            <div class="brand-text">Skill<span>Connect</span></div>
        </a>

        <ul class="nav-list">
            <li>
                <a href="index.php" class="nav-link active">
                    <span class="nav-link-left">🏠 Home</span>
                </a>
            </li>

            <?php if (!$isLoggedIn): ?>
                <!-- Guest / New User View (Before Login) -->
                <li>
                    <a href="create_profile.php" class="nav-link">
                        <span class="nav-link-left">✨ Create Profile</span>
                    </a>
                </li>
                <li>
                    <a href="login.php" class="nav-link">
                        <span class="nav-link-left">🔑 Student Login</span>
                    </a>
                </li>
            <?php else: ?>
                <!-- Logged-In Student View (Peer Sessions & Announcements unlocked after login) -->
                <li>
                    <a href="peer_sessions.php" class="nav-link">
                        <span class="nav-link-left">🎓 Peer Sessions</span>
                        <span class="nav-badge"><?php echo htmlspecialchars((string)$sessionsCount); ?></span>
                    </a>
                </li>
                <li>
                    <a href="announcements.php" class="nav-link">
                        <span class="nav-link-left">📢 Announcements</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="nav-link">
                        <span class="nav-link-left">🚪 Logout</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </aside>

    <!-- Main Homepage Content -->
    <main class="main-content">
        <span class="campus-pill">MIT CSN Campus Platform</span>

        <h1 class="hero-title">Skill Connect</h1>
        <p class="hero-subtitle">
            Learn a skill. Teach a skill. Join live peer teaching sessions and stay updated with MIT CSN campus notices.
        </p>

        <div class="hero-actions">
            <?php if (!$isLoggedIn): ?>
                <!-- Before Login: Direct new users to Create Profile or Login first -->
                <a href="create_profile.php" class="btn btn-primary">🚀 Get Started</a>
                <a href="login.php" class="btn btn-secondary">🔑 Student Login</a>
                <a href="admin_login.php" class="btn btn-outline-accent">🛡️ Admin Login</a>
            <?php else: ?>
                <!-- After Login: Show Peer Sessions & Announcements actions -->
                <a href="peer_sessions.php" class="btn btn-primary">🎓 Explore Peer Sessions</a>
                <a href="announcements.php" class="btn btn-secondary">📢 View Announcements</a>
            <?php endif; ?>
        </div>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo htmlspecialchars((string)$membersCount); ?></div>
                <div class="stat-label">Campus Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo htmlspecialchars((string)$connectionsCount); ?></div>
                <div class="stat-label">Connections Made</div>
            </div>
            <?php if ($isLoggedIn): ?>
                <div class="stat-card">
                    <div class="stat-number"><?php echo htmlspecialchars((string)$sessionsCount); ?></div>
                    <div class="stat-label">Upcoming Peer Sessions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo htmlspecialchars((string)$noticesCount); ?></div>
                    <div class="stat-label">College Notices</div>
                </div>
            <?php endif; ?>
        </section>

        <h2 class="section-heading">How Skill Connect Works</h2>
        <section class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3 class="step-title">Create Your Skill Profile</h3>
                <p class="step-desc">
                    List the skills you can teach and the skills you want to learn across engineering, coding, design, and more.
                </p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3 class="step-title">Find 2-Way Matches &amp; Mentors</h3>
                <p class="step-desc">
                    Use our smart matcher or ask the AI Assistant to discover classmates whose skills complement yours.
                </p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3 class="step-title">Join Peer Teaching Sessions</h3>
                <p class="step-desc">
                    Attend live student-led workshops online or on campus, or host your own session to build your portfolio.
                </p>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <h3 class="step-title">Track &amp; Bookmark Notices</h3>
                <p class="step-desc">
                    Never miss a placement drive, hackathon, or scholarship—bookmark notices to your Saved Announcements page.
                </p>
            </div>
        </section>
    </main>

    <a href="#" class="ask-ai-fab">💬 Ask AI</a>

</body>
</html>