// parent_child.js (Add or edit a child)

// Only logged in parents can see this page
var user = requireRole("parent");

if (user !== null) {
  setupForm();
}

function setupForm() {
  // If the address has ?id=3 we are editing. If not, we are adding.
  var editId = getParam("id");
  var children = getChildren();

  if (editId !== null) {
    var child = null;
    for (var i = 0; i < children.length; i++) {
      if (children[i].id === Number(editId) && children[i].parentId === user.id) {
        child = children[i];
      }
    }
    if (child !== null) {
      document.getElementById("form-title").textContent = "Edit child";
      document.getElementById("name").value = child.name;
      document.getElementById("dob").value = child.dob;
    }
  }

  document.getElementById("child-form").onsubmit = function (event) {
    event.preventDefault();

    var name = document.getElementById("name").value.trim();
    var dob = document.getElementById("dob").value;
    var box = document.getElementById("form-message");

    if (name === "" || dob === "") {
      box.innerHTML = '<div class="alert alert-error">Please fill in the name and the date of birth.</div>';
      return;
    }
    if (dob > todayString()) {
      box.innerHTML = '<div class="alert alert-error">The date of birth cannot be in the future.</div>';
      return;
    }

    var all = getChildren();
    if (editId !== null) {
      // Change the existing child
      for (var j = 0; j < all.length; j++) {
        if (all[j].id === Number(editId) && all[j].parentId === user.id) {
          all[j].name = name;
          all[j].dob = dob;
        }
      }
    } else {
      // Add a new child
      all.push({ id: nextId(all), parentId: user.id, name: name, dob: dob });
    }
    saveList("children", all);

    window.location.href = "parent_dashboard.html";
  };
}
