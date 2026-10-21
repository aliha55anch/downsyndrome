// parent_dashboard.js (Parent dashboard)

// Only logged in parents can see this page
var user = requireRole("parent");

if (user !== null) {
  showDashboard();
}

// Get only the children that belong to this parent
function getMyChildren() {
  var all = getChildren();
  var mine = [];
  for (var i = 0; i < all.length; i++) {
    if (all[i].parentId === user.id) {
      mine.push(all[i]);
    }
  }
  return mine;
}

function showDashboard() {
  document.getElementById("welcome").textContent = "Hello, " + user.name + " 👋";

  var children = getMyChildren();

  // ----- Child cards -----
  var html = "";
  if (children.length === 0) {
    html = '<div class="card"><h3>No child added yet</h3><p>Add your child to start tracking activities.</p></div>';
  }
  for (var i = 0; i < children.length; i++) {
    var child = children[i];
    var ageGroup = ageGroupFor(child.dob);
    html += '<div class="card">' +
      '<div class="card-icon">🧒</div>' +
      '<h3>' + escapeText(child.name) + '</h3>' +
      '<p>Age: ' + calcAge(child.dob) + '</p>' +
      '<p><span class="tag tag-age">' + ageGroup + '</span></p>' +
      '<a class="btn btn-blue btn-small" href="activities.html?age=' + encodeURIComponent(ageGroup) + '">Find activities</a> ' +
      '<a class="btn btn-outline btn-small" href="parent_child.html?id=' + child.id + '">Edit</a> ' +
      '<button class="btn btn-red btn-small delete-child" data-id="' + child.id + '">Delete</button>' +
      '</div>';
  }
  document.getElementById("children-list").innerHTML = html;

  // Delete buttons
  var buttons = document.querySelectorAll(".delete-child");
  for (var b = 0; b < buttons.length; b++) {
    buttons[b].onclick = function () {
      deleteChild(Number(this.getAttribute("data-id")));
    };
  }

  showLogs(children);
}

// ----- Table of activities done -----
function showLogs(children) {
  var logs = getLogs();
  var activities = getActivities();
  var rows = "";
  var count = 0;

  // Go from the newest log to the oldest
  for (var i = logs.length - 1; i >= 0; i--) {
    var log = logs[i];

    // Find the child name and the activity title for this log
    var childName = null;
    for (var c = 0; c < children.length; c++) {
      if (children[c].id === log.childId) {
        childName = children[c].name;
      }
    }
    if (childName === null) {
      continue; // this log belongs to another parent
    }
    var title = "(removed activity)";
    for (var a = 0; a < activities.length; a++) {
      if (activities[a].id === log.activityId) {
        title = activities[a].title;
      }
    }

    rows += "<tr><td>" + log.date + "</td><td>" + escapeText(childName) + "</td><td>" +
      escapeText(title) + "</td><td>" + escapeText(log.note) + "</td></tr>";
    count = count + 1;
  }

  var area = document.getElementById("logs-area");
  if (count === 0) {
    area.innerHTML = '<div class="card"><p>No activities done yet. <a href="activities.html">Choose an activity</a> and tap "Mark as done".</p></div>';
  } else {
    area.innerHTML = '<div class="table-wrap"><table>' +
      '<tr><th>Date</th><th>Child</th><th>Activity</th><th>Note</th></tr>' + rows + '</table></div>';
  }
  document.getElementById("summary").textContent = count + " activities done so far. Keep going!";
}

// ----- Delete a child and their logs -----
function deleteChild(childId) {
  if (!confirm("Delete this child and all their saved activities?")) {
    return;
  }
  var children = getChildren();
  var keepChildren = [];
  for (var i = 0; i < children.length; i++) {
    if (children[i].id !== childId) {
      keepChildren.push(children[i]);
    }
  }
  saveList("children", keepChildren);

  var logs = getLogs();
  var keepLogs = [];
  for (var j = 0; j < logs.length; j++) {
    if (logs[j].childId !== childId) {
      keepLogs.push(logs[j]);
    }
  }
  saveList("logs", keepLogs);

  showDashboard();
}
