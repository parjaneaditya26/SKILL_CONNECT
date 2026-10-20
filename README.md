<div align="center">

# 🎓 Skill Connect

### A campus peer-learning platform where MIT CSN students teach what they know and learn what they want.

[![Live Demo](https://img.shields.io/badge/🌐_Live_Demo-skillconnect2026.site.je-4f46e5?style=for-the-badge)](https://skillconnect2026.site.je/)

![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat-square&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat-square&logo=css3&logoColor=white)
![Gemini](https://img.shields.io/badge/Gemini_AI-8E75B2?style=flat-square&logo=googlegemini&logoColor=white)

**🔗 Live Website: [https://skillconnect2026.site.je/](https://skillconnect2026.site.je/)**

</div>

---

## 📖 About

**Skill Connect** is a server-rendered PHP + MySQL web application built for college campuses. Students create a profile, list the skills they can **teach** and the skills they want to **learn**, discover classmates with matching interests, send connection requests, chat after acceptance, and rate each other. A built-in admin panel handles moderation and management.

Built for **MIT CSN** as a college project (CEP), it is designed to encourage peer-to-peer learning and skill exchange on campus.

## 🚀 Live Demo

🌐 **Website:** [https://skillconnect2026.site.je/](https://skillconnect2026.site.je/)

> Create an account with a valid email (OTP verification is required), fill in your profile, and start browsing skills.

## ✨ Features

### 👤 For Students
- **Account & Profile**: sign up with email OTP verification, create and edit your student profile
- **Skills Management**: list skills you can teach and skills you want to learn
- **Browse & Search**: find classmates by skill, branch, or keyword
- **Smart Matching**: two-way match discovery for real skill-swap opportunities
- **Connection Requests**: send, accept, or reject requests
- **Messaging**: chat with connected users, with **AI-assisted smart replies** (Gemini)
- **Skill Connect AI (Ask AI)**: floating campus assistant that finds mentors, lists peer sessions and announcements, suggests skill-swap matches, and gives learning roadmaps
- **Ratings & Reviews**: rate peers after learning together
- **Peer Sessions**: create and join peer learning sessions
- **Help Board**: post doubts, find teammates for hackathons, and let others respond
- **Announcements**: view and save campus announcements
- **Notifications**: stay updated on requests and activity
- **Password Recovery**: reset your password through an email link

### 🛡️ For Admins
- Control center with analytics: registered students, connections, sessions, announcements, help board activity, and ratings
- Students-by-branch, academic-year breakdown, and top taught/requested skills
- Manage students, peer sessions (feature, complete, delete), announcements (pin, delete), and help posts (close, delete)
- Live platform activity feed
- Quick filter across all tables
- Create and manage announcements
- Moderate reported users
- Export users and requests as CSV

### 🔒 Security
- CSRF protection on forms
- Secure, centralized session handling (`session_init.php`)
- Session regeneration after login
- Timing-safe comparisons using `hash_equals()`
- OTP rate limiting and retry limits
- Email links built from a fixed `APP_URL` (prevents host-header manipulation)
- `.htaccess` security headers and protection for sensitive files

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP |
| Database | MySQL |
| Frontend | HTML, CSS, JavaScript |
| Email | PHPMailer (Gmail SMTP) |
| AI | Google Gemini API (smart replies) |
| Server | Apache (XAMPP / shared hosting) |

## 🗄️ Database

Core tables defined in `schema.sql`:

`users` · `requests` · `ratings` · `reports` · `messages`

Additional feature tables (announcements, help board, peer sessions, etc.) are created automatically by the helper functions in `helpers.php`.

## 📁 Project Structure

```text
SKILL_CONNECT/
├── index.php                  # Landing page
├── login.php / logout.php     # Authentication (user + admin)
├── send_signup_otp.php        # Signup OTP
├── verify_signup_otp.php
├── forgot_password.php        # Password reset
├── reset_password.php
│
├── profile.php                # Create/edit profile
├── save_profile.php
├── profile_view.php
├── delete_profile.php
├── browse.php                 # Search & discover students
├── dashboard.php              # User dashboard
│
├── connect.php                # Connection requests
├── requests.php
├── update_request.php
├── connections.php
├── disconnect.php
├── messages.php               # Chat
├── send_message.php
├── smart_reply.php            # AI smart replies
│
├── rate.php / save_rating.php # Ratings
├── report_user.php / save_report.php
│
├── peer_sessions.php          # Peer sessions
├── save_peer_session.php
├── join_peer_session.php
├── peer_session_action.php
├── help_board.php             # Help board
├── save_help_post.php
├── respond_help_post.php
├── help_post_action.php
├── announcements.php          # Announcements
├── saved_announcements.php
├── toggle_save_announcement.php
├── toggle_interest.php
├── notifications.php
│
├── admin.php                  # Admin panel
├── admin_*.php                # Admin actions
├── export_users.php
├── export_requests.php
│
├── helpers.php                # Utilities & DB setup helpers
├── db_connect.php             # MySQL connection
├── session_init.php           # Session & security bootstrap
├── mailer.php                 # Email sending
├── mailer_config.example.php  # Mail config template
├── ai_config.php              # AI configuration
├── schema.sql                 # Database schema
├── style.css / script.js      # Frontend
├── .env.example               # Environment template
├── .htaccess                  # Security & rewrite rules
├── CHANGES.md                 # Security hardening notes
└── PHPMailer/                 # Email library
```

## ⚙️ Installation & Setup

### Prerequisites
- PHP 7.4+ (8.x recommended)
- MySQL / MariaDB
- Apache (XAMPP, WAMP, or similar)
- A Gmail account with an [App Password](https://support.google.com/accounts/answer/185833) (for OTP emails)
- A [Gemini API key](https://aistudio.google.com/app/apikey) (optional, for smart replies)

### 1. Clone the repository
```bash
git clone https://github.com/<your-username>/SKILL_CONNECT.git
```
Place the folder inside your web root (e.g. `htdocs/` in XAMPP).

### 2. Create the database
1. Open phpMyAdmin and create a new database (e.g. `skill_connect`).
2. Import `schema.sql` into that database.

### 3. Configure environment variables
Copy `.env.example` to `.env` and fill in your values:

```env
GEMINI_API_KEY="your_gemini_key"
ADMIN_USER=admin
ADMIN_PASS="choose_a_long_password"

DB_HOST=localhost
DB_USER=your_db_user
DB_PASS="your_db_password"
DB_NAME=your_db_name

# Full site address, no trailing slash. Used for links in emails.
APP_URL=http://localhost/SKILL_CONNECT
```

> 💡 Wrap values in double quotes if they contain special characters such as `! $ ; =` or spaces.

### 4. Configure email (OTP & password reset)
Copy `mailer_config.example.php` to `mailer_config.php` and update:

```php
define('MAIL_USERNAME', 'your_gmail@gmail.com');
define('MAIL_APP_PASSWORD', 'your_16_char_app_password');
define('MAIL_FROM_NAME', 'Skill Connect');
```

### 5. Run the app
Start Apache and MySQL, then open:

```text
http://localhost/SKILL_CONNECT/
```

## 🔑 Admin Access

Sign in from the normal login page using the `ADMIN_USER` and `ADMIN_PASS` values from your `.env` file.

## 🔄 How It Works

```text
Sign up (OTP) → Build profile → Browse / match → Send request
      → Request accepted → Chat & learn → Rate each other
```

## ⚠️ Security Notes

- **Never commit** `.env` or `mailer_config.php`. They are already covered by `.gitignore`.
- Use a long, unique `ADMIN_PASS`.
- Always set `APP_URL` explicitly in production.
- Rotate any API key or password that was ever exposed publicly.

See [`CHANGES.md`](CHANGES.md) for the full list of security improvements.

## 🛣️ Future Improvements

- Prepared statements across all PHP files
- Centralized input validation
- Reusable routing for admin and user pages
- Automated tests
- Detailed production deployment guide
- Screenshots and demo video

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

1. Fork the repository
2. Create a branch: `git checkout -b feature/your-feature`
3. Commit your changes: `git commit -m "Add your feature"`
4. Push: `git push origin feature/your-feature`
5. Open a Pull Request

## 📄 License

No license has been declared yet. If you want others to reuse the code, consider adding one (for example [MIT](https://choosealicense.com/licenses/mit/)).

## 📬 About the Project

Skill Connect is a college project for campus networking and peer skill exchange.

<div align="center">

**🌐 [Visit Skill Connect Live](https://skillconnect2026.site.je/)**

⭐ If you like this project, give it a star on GitHub!

</div>
