const getStartedBtn = document.getElementById("getStartedBtn");
const currentUser = (document.body && document.body.dataset.user) ? document.body.dataset.user : "";

if (getStartedBtn) {
  getStartedBtn.addEventListener("click", function () {
    window.location.href = currentUser ? "browse.php" : "profile.php";
  });
}

const requestsLink = document.querySelector('a[href="requests.php"]');

if (currentUser && requestsLink) {
  fetch("notifications.php?name=" + encodeURIComponent(currentUser))
    .then(function (response) { return response.json(); })
    .then(function (data) {
      if (data && data.count > 0) {
        const badge = document.createElement("span");
        badge.className = "notif-badge";
        badge.textContent = data.count;
        requestsLink.appendChild(badge);
      }
    })
    .catch(function () {});
}

document.querySelectorAll(".toggle-password").forEach(function (btn) {
  btn.addEventListener("click", function () {
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    if (input.type === "password") {
      input.type = "text";
      btn.textContent = "🙈";
    } else {
      input.type = "password";
      btn.textContent = "👁";
    }
  });
});

/* ---------- Persistent & Smarter AI Chatbot ---------- */
const chatToggle = document.getElementById("chatbot-toggle");
const chatWindow = document.getElementById("chatbot-window");
const chatInput = document.getElementById("chatbot-input");
const chatSend = document.getElementById("chatbot-send");
const chatMessages = document.getElementById("chatbot-messages");
const chatClear = document.getElementById("chatbot-clear");
const chatClose = document.getElementById("chatbot-close");

const CHAT_STORAGE_KEY = "sc_chat_history_v2_" + (currentUser || "guest");
const CHAT_OPEN_KEY = "sc_chat_open_v2_" + (currentUser || "guest");
const DEFAULT_BOT_GREETING = "Hi! I'm your **Skill Connect Smart AI**. I remember our chat as you move across pages! Ask me to find mentors, check upcoming peer sessions, summarize announcements, or build a learning roadmap.";

function safeGetStorage(key) {
  try { return localStorage.getItem(key); } catch (e) { return null; }
}

function safeSetStorage(key, val) {
  try { localStorage.setItem(key, val); } catch (e) {}
}

function formatBotHtml(rawText) {
  const escaped = String(rawText || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");

  return escaped
    .replace(/\*\*([^*]+)\*\*/g, "<strong>$1</strong>")
    .replace(/\*([^*]+)\*/g, "<em>$1</em>")
    .replace(/\[([^\]]+)\]\(([a-zA-Z0-9_.\-?=&/#]+)\)/g, '<a href="$2" class="chat-inline-link">$1</a>')
    .replace(/\n/g, "<br>");
}

function loadChatHistory() {
  const raw = safeGetStorage(CHAT_STORAGE_KEY);
  if (!raw) return null;
  try {
    const parsed = JSON.parse(raw);
    if (Array.isArray(parsed) && parsed.length > 0) return parsed;
  } catch (e) {}
  return null;
}

function saveChatHistory(historyArr) {
  safeSetStorage(CHAT_STORAGE_KEY, JSON.stringify(historyArr.slice(-30)));
}

if (chatToggle && chatWindow && chatMessages) {
  let chatHistory = loadChatHistory();
  if (!chatHistory) {
    chatHistory = [{ sender: "bot", text: DEFAULT_BOT_GREETING }];
    saveChatHistory(chatHistory);
  }

  function renderAllMessages() {
    chatMessages.innerHTML = "";
    chatHistory.forEach(function (item) {
      const msg = document.createElement("div");
      msg.className = "chat-msg " + (item.sender === "user" ? "user" : "bot");
      if (item.sender === "bot") {
        msg.innerHTML = formatBotHtml(item.text);
      } else {
        msg.textContent = item.text;
      }
      chatMessages.appendChild(msg);
    });
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  renderAllMessages();

  // Restore open/closed state across page navigation
  if (safeGetStorage(CHAT_OPEN_KEY) === "1") {
    chatWindow.classList.add("open");
    chatToggle.classList.add("is-open");
    setTimeout(function () {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 50);
  }

  function setChatOpen(isOpen) {
    if (isOpen) {
      chatWindow.classList.add("open");
      chatToggle.classList.add("is-open");
      safeSetStorage(CHAT_OPEN_KEY, "1");
      chatMessages.scrollTop = chatMessages.scrollHeight;
      if (chatInput) chatInput.focus();
    } else {
      chatWindow.classList.remove("open");
      chatToggle.classList.remove("is-open");
      safeSetStorage(CHAT_OPEN_KEY, "0");
    }
  }

  chatToggle.addEventListener("click", function () {
    setChatOpen(!chatWindow.classList.contains("open"));
  });

  if (chatClose) {
    chatClose.addEventListener("click", function () {
      setChatOpen(false);
    });
  }

  if (chatClear) {
    chatClear.addEventListener("click", function () {
      chatHistory = [{ sender: "bot", text: DEFAULT_BOT_GREETING }];
      saveChatHistory(chatHistory);
      renderAllMessages();
      showToast("Chat history reset", false);
    });
  }

  function appendChatMessage(text, sender) {
    chatHistory.push({ sender: sender, text: text });
    saveChatHistory(chatHistory);
    const msg = document.createElement("div");
    msg.className = "chat-msg " + sender;
    if (sender === "bot") {
      msg.innerHTML = formatBotHtml(text);
    } else {
      msg.textContent = text;
    }
    chatMessages.appendChild(msg);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function sendChatMessage(overrideText) {
    const text = (typeof overrideText === "string" ? overrideText : (chatInput ? chatInput.value : "")).trim();
    if (text === "") return;

    const recentContext = chatHistory.slice(-6);
    appendChatMessage(text, "user");
    if (chatInput && typeof overrideText !== "string") {
      chatInput.value = "";
    }

    const loadingMsg = document.createElement("div");
    loadingMsg.className = "chat-msg bot typing-indicator";
    loadingMsg.textContent = "Thinking...";
    chatMessages.appendChild(loadingMsg);
    chatMessages.scrollTop = chatMessages.scrollHeight;

    const params = "message=" + encodeURIComponent(text) + "&history=" + encodeURIComponent(JSON.stringify(recentContext));

    fetch("chatbot.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        loadingMsg.remove();
        appendChatMessage(data.reply || "Sorry, I couldn't generate a response.", "bot");
      })
      .catch(function () {
        loadingMsg.remove();
        appendChatMessage("Sorry, something went wrong connecting to the server. Please try again.", "bot");
      });
  }

  if (chatSend) {
    chatSend.addEventListener("click", function () { sendChatMessage(); });
  }
  if (chatInput) {
    chatInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") sendChatMessage();
    });
  }

  document.querySelectorAll(".chat-chip").forEach(function (chip) {
    chip.addEventListener("click", function () {
      const prompt = chip.getAttribute("data-prompt");
      if (prompt) sendChatMessage(prompt);
    });
  });
}

/* ---------- Toast & AJAX Forms ---------- */
function showToast(message, isError) {
  let toast = document.getElementById("toast");
  if (!toast) {
    toast = document.createElement("div");
    toast.id = "toast";
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.className = isError ? "show error" : "show";
  setTimeout(function () {
    toast.className = "";
  }, 3000);
}

function ajaxAction(url, formData, onSuccess) {
  fetch(url, { method: "POST", body: formData })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      showToast(data.message, !data.success);
      if (data.success) {
        if (data.redirect) {
          setTimeout(function () { window.location.href = data.redirect; }, 750);
        } else if (onSuccess) {
          setTimeout(onSuccess, 750);
        }
      }
    })
    .catch(function () {
      showToast("Something went wrong. Please try again.", true);
    });
}

document.querySelectorAll(".ajax-form").forEach(function (form) {
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(form);
    const url = form.getAttribute("action");
    const onSuccess = form.dataset.noReload
      ? function () { startCooldown(form); }
      : function () { window.location.reload(); };
    ajaxAction(url, formData, onSuccess);
  });
});

function startCooldown(form) {
  const btn = form.querySelector("button[type=submit]");
  if (!btn) return;
  let seconds = 30;
  btn.disabled = true;
  btn.textContent = "Code sent ✓ (resend in " + seconds + "s)";
  const timer = setInterval(function () {
    seconds--;
    if (seconds <= 0) {
      clearInterval(timer);
      btn.disabled = false;
      btn.textContent = "Resend code";
    } else {
      btn.textContent = "Code sent ✓ (resend in " + seconds + "s)";
    }
  }, 1000);
}

/* ---------- Admin Dashboard Interactive Tabs & Search ---------- */
const adminTabs = document.querySelectorAll(".admin-tab-btn");
const adminPanels = document.querySelectorAll(".admin-section-panel");
if (adminTabs.length > 0) {
  adminTabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      const target = tab.getAttribute("data-target");
      adminTabs.forEach(function (t) { t.classList.remove("active"); });
      tab.classList.add("active");
      adminPanels.forEach(function (panel) {
        if (target === "all" || panel.id === target) {
          panel.style.display = "block";
        } else {
          panel.style.display = "none";
        }
      });
    });
  });
}

const adminSearchInput = document.getElementById("adminSearchInput");
if (adminSearchInput) {
  adminSearchInput.addEventListener("input", function () {
    const query = adminSearchInput.value.toLowerCase().trim();
    document.querySelectorAll(".admin-table tbody tr.filterable-row").forEach(function (row) {
      const text = row.textContent.toLowerCase();
      row.style.display = (query === "" || text.indexOf(query) !== -1) ? "" : "none";
    });
  });
}

(function highlightActiveNav() {
  const page = window.location.pathname.split("/").pop() || "index.php";
  document.querySelectorAll("nav a.nav-link").forEach(function (a) {
    const href = (a.getAttribute("href") || "").split("?")[0].split("#")[0];
    if (href === page && !(a.getAttribute("href") || "").includes("#")) {
      a.classList.add("active");
    }
  });
})();


/* Admin Password Modal Handling */
const openAdminModalBtn = document.getElementById("openAdminModalBtn");
const adminPasswordModal = document.getElementById("adminPasswordModal");
const closeAdminModalBtn = document.getElementById("closeAdminModalBtn");
const cancelAdminModalBtn = document.getElementById("cancelAdminModalBtn");
const homeAdminPassword = document.getElementById("homeAdminPassword");

if (openAdminModalBtn && adminPasswordModal) {
  openAdminModalBtn.addEventListener("click", function (e) {
    e.preventDefault();
    adminPasswordModal.style.display = "flex";
    if (homeAdminPassword) {
      setTimeout(function () { homeAdminPassword.focus(); }, 100);
    }
  });
}

function hideAdminModal() {
  if (adminPasswordModal) {
    adminPasswordModal.style.display = "none";
  }
}

if (closeAdminModalBtn) closeAdminModalBtn.addEventListener("click", hideAdminModal);
if (cancelAdminModalBtn) cancelAdminModalBtn.addEventListener("click", hideAdminModal);
if (adminPasswordModal) {
  adminPasswordModal.addEventListener("click", function (e) {
    if (e.target === adminPasswordModal) hideAdminModal();
  });
}
