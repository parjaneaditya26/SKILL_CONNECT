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