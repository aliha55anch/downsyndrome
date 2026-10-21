// login.js (Login page)

document.getElementById("login-form").onsubmit = function (event) {
  event.preventDefault();

  var email = document.getElementById("email").value.trim().toLowerCase();
  var password = document.getElementById("password").value;
  var box = document.getElementById("form-message");

  // Look for a user with this email and password
  var users = getUsers();
  var found = null;
  for (var i = 0; i < users.length; i++) {
    if (users[i].email === email && users[i].password === password) {
      found = users[i];
    }
  }

  if (found === null) {
    box.innerHTML = '<div class="alert alert-error">Wrong email or password. Please try again.</div>';
    return;
  }

  // Remember who is logged in (we do not save the password)
  setCurrentUser({ id: found.id, name: found.name, email: found.email, role: found.role });

  // Send each person to their own page
  if (found.role === "admin") {
    window.location.href = "admin_doctors.html";
  } else {
    window.location.href = "parent_dashboard.html";
  }
};
