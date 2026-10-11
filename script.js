const getStartedBtn = document.getElementById("getStartedBtn");
const currentUser = document.body.dataset.user;

if (getStartedBtn) {
  getStartedBtn.addEventListener("click", function () {
    window.location.href = currentUser ? "browse.php" : "profile.php";
  });
}

const requestsLink = document.querySelector('a[href="requests.php"]');

if (currentUser && requestsLink) {
  fetch("notifications.php?name=" + encodeURIComponent(currentUser))
    .then(function (response) {
      return response.json();
    })
    .then(function (data) {
      if (data.count > 0) {
        const badge = document.createElement("span");
        badge.className = "notif-badge";
        badge.textContent = data.count;
        requestsLink.appendChild(badge);
      }
    });
}

document.querySelectorAll(".toggle-password").forEach(function (btn) {
  btn.addEventListener("click", function () {
    const input = document.getElementById(btn.dataset.target);
    if (input.type === "password") {
      input.type = "text";
      btn.textContent = "🙈";
    } else {
      input.type = "password";
      btn.textContent = "👁";
    }
  });
});
const chatToggle = document.getElementById("chatbot-toggle");
const chatWindow = document.getElementById("chatbot-window");
const chatInput = document.getElementById("chatbot-input");
const chatSend = document.getElementById("chatbot-send");
const chatMessages = document.getElementById("chatbot-messages");

if (chatToggle) {
  chatToggle.addEventListener("click", function () {
    chatWindow.classList.toggle("open");
  });

  function addMessage(text, sender) {
    const msg = document.createElement("div");
    msg.className = "chat-msg " + sender;
    msg.textContent = text;
    chatMessages.appendChild(msg);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function sendChatMessage() {
    const text = chatInput.value.trim();
    if (text === "") return;

    addMessage(text, "user");
    chatInput.value = "";

    const loadingMsg = document.createElement("div");
    loadingMsg.className = "chat-msg bot";
    loadingMsg.textContent = "Typing...";
    chatMessages.appendChild(loadingMsg);
    chatMessages.scrollTop = chatMessages.scrollHeight;

    fetch("chatbot.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "message=" + encodeURIComponent(text)
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        loadingMsg.remove();
        addMessage(data.reply, "bot");
      })
      .catch(function () {
        loadingMsg.remove();
        addMessage("Sorry, something went wrong. Please try again.", "bot");
      });
  }

  chatSend.addEventListener("click", sendChatMessage);
  chatInput.addEventListener("keypress", function (e) {
    if (e.key === "Enter") sendChatMessage();
  });
}
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
          setTimeout(function () { window.location.href = data.redirect; }, 900);
        } else if (onSuccess) {
          setTimeout(onSuccess, 900);
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

(function highlightActiveNav() {
  const page = window.location.pathname.split("/").pop() || "index.php";
  document.querySelectorAll("nav a.nav-link").forEach(function (a) {
    const href = (a.getAttribute("href") || "").split("?")[0];
    if (href === page) { a.classList.add("active"); }
  });
})();
