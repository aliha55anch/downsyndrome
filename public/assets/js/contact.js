// contact.js (Contact page)

document.getElementById("contact-form").onsubmit = function (event) {
  event.preventDefault();

  var name = document.getElementById("name").value.trim();
  var email = document.getElementById("email").value.trim();
  var message = document.getElementById("message").value.trim();
  var box = document.getElementById("form-message");

  // Check that everything is filled in
  if (name === "" || email === "" || message === "") {
    box.innerHTML = '<div class="alert alert-error">Please fill in all the boxes.</div>';
    return;
  }
  if (email.indexOf("@") === -1) {
    box.innerHTML = '<div class="alert alert-error">Please write a correct email address.</div>';
    return;
  }

  // Save the message
  var messages = getMessages();
  messages.push({
    id: nextId(messages),
    name: name,
    email: email,
    message: message,
    date: todayString()
  });
  saveList("messages", messages);

  box.innerHTML = '<div class="alert alert-success">Thank you! Your message was sent.</div>';
  document.getElementById("contact-form").reset();
};
