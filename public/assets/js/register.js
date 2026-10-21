// register.js (Register page)

document.getElementById("register-form").onsubmit = function (event) {
  event.preventDefault();

  var name = document.getElementById("name").value.trim();
  var email = document.getElementById("email").value.trim().toLowerCase();
  var password = document.getElementById("password").value;
  var password2 = document.getElementById("password2").value;
  var box = document.getElementById("form-message");

  // Check the answers one by one
  if (name === "" || email === "" || password === "") {
    box.innerHTML = '<div class="alert alert-error">Please fill in all the boxes.</div>';
    return;
  }
  if (email.indexOf("@") === -1) {
    box.innerHTML = '<div class="alert alert-error">Please write a correct email address.</div>';
    return;
  }
  if (password.length < 6) {
    box.innerHTML = '<div class="alert alert-error">The password must have at least 6 letters or numbers.</div>';
    return;
  }
  if (password !== password2) {
    box.innerHTML = '<div class="alert alert-error">The two passwords are not the same.</div>';
    return;
  }

  // Is this email already used?
  var users = getUsers();
  for (var i = 0; i < users.length; i++) {
    if (users[i].email === email) {
      box.innerHTML = '<div class="alert alert-error">This email already has an account. Please login.</div>';
      return;
    }
  }

  // Save the new parent and log them in
  var newUser = { id: nextId(users), name: name, email: email, password: password, role: "parent" };
  users.push(newUser);
  saveList("users", users);
  setCurrentUser({ id: newUser.id, name: newUser.name, email: newUser.email, role: newUser.role });

  window.location.href = "parent_dashboard.html";
};
