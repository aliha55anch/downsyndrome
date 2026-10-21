// admin_doctors.js (Admin: manage doctors)

// Only the admin can see this page
var user = requireRole("admin");

// editingId is the doctor we are changing. It is null when we add a new one.
var editingId = null;

if (user !== null) {
  fillSelect("specialty", SPECIALTIES, "Choose type");
  showTable();

  document.getElementById("doctor-form").onsubmit = saveDoctor;
  document.getElementById("cancel-btn").onclick = clearForm;
}

// ----- Show the table -----
function showTable() {
  var doctors = getDoctors();
  var rows = "";
  for (var i = 0; i < doctors.length; i++) {
    var d = doctors[i];
    rows += "<tr>" +
      "<td>" + escapeText(d.name) + "</td>" +
      "<td>" + escapeText(d.specialty) + "</td>" +
      "<td>" + escapeText(d.city) + "</td>" +
      "<td>Rs " + d.fee + "</td>" +
      '<td><button class="btn btn-outline btn-small edit-btn" data-id="' + d.id + '">Edit</button>' +
      '<button class="btn btn-red btn-small delete-btn" data-id="' + d.id + '">Delete</button></td>' +
      "</tr>";
  }

  document.getElementById("doctor-table").innerHTML =
    '<div class="table-wrap"><table>' +
    "<tr><th>Name</th><th>Type</th><th>City</th><th>Fee</th><th>Actions</th></tr>" +
    rows + "</table></div>";

  var editButtons = document.querySelectorAll(".edit-btn");
  for (var e = 0; e < editButtons.length; e++) {
    editButtons[e].onclick = function () {
      startEdit(Number(this.getAttribute("data-id")));
    };
  }
  var deleteButtons = document.querySelectorAll(".delete-btn");
  for (var x = 0; x < deleteButtons.length; x++) {
    deleteButtons[x].onclick = function () {
      deleteDoctor(Number(this.getAttribute("data-id")));
    };
  }
}

// ----- Put a doctor into the form for editing -----
function startEdit(id) {
  var doctors = getDoctors();
  for (var i = 0; i < doctors.length; i++) {
    if (doctors[i].id === id) {
      var d = doctors[i];
      editingId = id;
      document.getElementById("form-title").textContent = "Edit doctor";
      document.getElementById("name").value = d.name;
      document.getElementById("specialty").value = d.specialty;
      document.getElementById("city").value = d.city;
      document.getElementById("mode").value = d.mode;
      document.getElementById("phone").value = d.phone;
      document.getElementById("fee").value = d.fee;
      document.getElementById("experience").value = d.experience;
      document.getElementById("address").value = d.address;
      document.getElementById("bio").value = d.bio;
      window.scrollTo(0, document.body.scrollHeight);
    }
  }
}

// ----- Save a new doctor or the changes -----
function saveDoctor(event) {
  event.preventDefault();

  var doctor = {
    name: document.getElementById("name").value.trim(),
    specialty: document.getElementById("specialty").value,
    city: document.getElementById("city").value.trim(),
    mode: document.getElementById("mode").value,
    phone: document.getElementById("phone").value.trim(),
    fee: Number(document.getElementById("fee").value),
    experience: Number(document.getElementById("experience").value),
    address: document.getElementById("address").value.trim(),
    bio: document.getElementById("bio").value.trim()
  };

  var box = document.getElementById("form-message");
  if (doctor.name === "" || doctor.specialty === "" || doctor.city === "") {
    box.innerHTML = '<div class="alert alert-error">Please fill in the name, type and city.</div>';
    return;
  }

  var doctors = getDoctors();
  if (editingId === null) {
    doctor.id = nextId(doctors);
    doctors.push(doctor);
  } else {
    for (var i = 0; i < doctors.length; i++) {
      if (doctors[i].id === editingId) {
        doctor.id = editingId;
        doctors[i] = doctor;
      }
    }
  }
  saveList("doctors", doctors);

  clearForm();
  showTable();
  box.innerHTML = '<div class="alert alert-success">Doctor saved.</div>';
}

// ----- Delete a doctor -----
function deleteDoctor(id) {
  if (!confirm("Delete this doctor?")) {
    return;
  }
  var doctors = getDoctors();
  var keep = [];
  for (var i = 0; i < doctors.length; i++) {
    if (doctors[i].id !== id) {
      keep.push(doctors[i]);
    }
  }
  saveList("doctors", keep);
  showTable();
}

// ----- Empty the form -----
function clearForm() {
  editingId = null;
  document.getElementById("doctor-form").reset();
  document.getElementById("form-title").textContent = "Add a doctor";
  document.getElementById("form-message").innerHTML = "";
}
