window.location.replace('/admin/activities');

// Only the admin can see this page
var user = requireRole("admin");

// editingId is the activity we are changing. It is null when we add a new one.
var editingId = null;

if (user !== null) {
  var areaNames = [];
  for (var i = 0; i < AREAS.length; i++) {
    areaNames.push(AREAS[i].name);
  }
  var ageNames = [];
  for (var j = 0; j < AGE_GROUPS.length; j++) {
    ageNames.push(AGE_GROUPS[j].name);
  }
  fillSelect("area", areaNames, "Choose area");
  fillSelect("ageGroup", ageNames, "Choose age group");

  showTable();

  document.getElementById("activity-form").onsubmit = saveActivity;
  document.getElementById("cancel-btn").onclick = clearForm;
}

// Turn text with one item on each line into a list
function linesToList(text) {
  var lines = text.split("\n");
  var list = [];
  for (var k = 0; k < lines.length; k++) {
    var line = lines[k].trim();
    if (line !== "") {
      list.push(line);
    }
  }
  return list;
}

// ----- Show the table -----
function showTable() {
  var activities = getActivities();
  var rows = "";
  for (var i = 0; i < activities.length; i++) {
    var a = activities[i];
    rows += "<tr>" +
      "<td>" + escapeText(a.title) + "</td>" +
      "<td>" + escapeText(a.area) + "</td>" +
      "<td>" + escapeText(a.ageGroup) + "</td>" +
      "<td>" + a.minutes + " min</td>" +
      '<td><button class="btn btn-outline btn-small edit-btn" data-id="' + a.id + '">Edit</button>' +
      '<button class="btn btn-red btn-small delete-btn" data-id="' + a.id + '">Delete</button></td>' +
      "</tr>";
  }

  document.getElementById("activity-table").innerHTML =
    '<div class="table-wrap"><table>' +
    "<tr><th>Title</th><th>Area</th><th>Age</th><th>Time</th><th>Actions</th></tr>" +
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
      deleteActivity(Number(this.getAttribute("data-id")));
    };
  }
}

// ----- Put an activity into the form for editing -----
function startEdit(id) {
  var activities = getActivities();
  for (var i = 0; i < activities.length; i++) {
    if (activities[i].id === id) {
      var a = activities[i];
      editingId = id;
      document.getElementById("form-title").textContent = "Edit activity";
      document.getElementById("title").value = a.title;
      document.getElementById("area").value = a.area;
      document.getElementById("ageGroup").value = a.ageGroup;
      document.getElementById("minutes").value = a.minutes;
      document.getElementById("frequency").value = a.frequency;
      document.getElementById("goal").value = a.goal;
      document.getElementById("materials").value = a.materials.join("\n");
      document.getElementById("steps").value = a.steps.join("\n");
      document.getElementById("progress").value = a.progress;
      window.scrollTo(0, document.body.scrollHeight);
    }
  }
}

// ----- Save a new activity or the changes -----
function saveActivity(event) {
  event.preventDefault();

  var activity = {
    title: document.getElementById("title").value.trim(),
    area: document.getElementById("area").value,
    ageGroup: document.getElementById("ageGroup").value,
    minutes: Number(document.getElementById("minutes").value),
    frequency: document.getElementById("frequency").value.trim(),
    goal: document.getElementById("goal").value.trim(),
    materials: linesToList(document.getElementById("materials").value),
    steps: linesToList(document.getElementById("steps").value),
    progress: document.getElementById("progress").value.trim()
  };

  var box = document.getElementById("form-message");
  if (activity.title === "" || activity.area === "" || activity.ageGroup === "" || activity.steps.length === 0) {
    box.innerHTML = '<div class="alert alert-error">Please fill in the title, area, age group and at least one step.</div>';
    return;
  }

  var activities = getActivities();
  if (editingId === null) {
    activity.id = nextId(activities);
    activities.push(activity);
  } else {
    for (var i = 0; i < activities.length; i++) {
      if (activities[i].id === editingId) {
        activity.id = editingId;
        activities[i] = activity;
      }
    }
  }
  saveList("activities", activities);

  clearForm();
  showTable();
  box.innerHTML = '<div class="alert alert-success">Activity saved.</div>';
}

// ----- Delete an activity -----
function deleteActivity(id) {
  if (!confirm("Delete this activity?")) {
    return;
  }
  var activities = getActivities();
  var keep = [];
  for (var i = 0; i < activities.length; i++) {
    if (activities[i].id !== id) {
      keep.push(activities[i]);
    }
  }
  saveList("activities", keep);
  showTable();
}

// ----- Empty the form -----
function clearForm() {
  editingId = null;
  document.getElementById("activity-form").reset();
  document.getElementById("form-title").textContent = "Add an activity";
  document.getElementById("form-message").innerHTML = "";
}
