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