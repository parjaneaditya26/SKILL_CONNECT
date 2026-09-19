const getStartedBtn = document.getElementById("getStartedBtn");

if (getStartedBtn) {
  getStartedBtn.addEventListener("click", function () {
    window.location.href = "profile.html";
  });
}

// Only run this code if we're on profile.html
const profileForm = document.getElementById("profileForm");

// If we're on profile.html and there's saved data, show it
if (profileForm) {
  const savedProfile = localStorage.getItem("myProfile");

  if (savedProfile) {
    const profile = JSON.parse(savedProfile);

    document.getElementById("name").value = profile.name;
    document.getElementById("teachSkill").value = profile.teachSkill;
    document.getElementById("learnSkill").value = profile.learnSkill;
  }
}
// Remember the user's name locally (for notification badge)
if (profileForm) {
  profileForm.addEventListener("submit", function () {
    const nameField = document.getElementById("name");
    if (nameField && nameField.value) {
      localStorage.setItem("myName", nameField.value);
    }
  });
}

// Show a notification badge for pending requests, if we know the user's name
const savedName = localStorage.getItem("myName");
const requestsLink = document.querySelector('a[href="requests.php"]');

if (savedName && requestsLink) {
  fetch("notifications.php?name=" + encodeURIComponent(savedName))
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