// activity_detail.js (One activity page)

// 1. Find the activity using the number in the address (?id=3)
var id = Number(getParam("id"));
var activities = getActivities();
var activity = null;
for (var i = 0; i < activities.length; i++) {
  if (activities[i].id === id) {
    activity = activities[i];
  }
}

// 2. Show the activity
if (activity === null) {
  document.getElementById("detail").innerHTML =
    '<div class="card"><h2>Activity not found</h2><p>Please go back and choose an activity.</p></div>';
} else {
  showActivity(activity);
  showDoneBox(activity);
}

function showActivity(a) {
  var area = areaInfo(a.area);

  var materials = "";
  for (var m = 0; m < a.materials.length; m++) {
    materials += "<li>" + escapeText(a.materials[m]) + "</li>";
  }
  var steps = "";
  for (var s = 0; s < a.steps.length; s++) {
    steps += "<li>" + escapeText(a.steps[s]) + "</li>";
  }

  document.getElementById("detail").innerHTML =
    '<div class="detail-box">' +
    '<div class="card-icon">' + area.icon + '</div>' +
    '<span class="tag ' + area.cls + '">' + escapeText(a.area) + '</span>' +
    '<h1>' + escapeText(a.title) + '</h1>' +
    '<p><strong>Goal:</strong> ' + escapeText(a.goal) + '</p>' +
    '<div class="facts">' +
      '<div class="fact"><small>Age</small><strong>' + escapeText(a.ageGroup) + '</strong></div>' +
      '<div class="fact"><small>Time</small><strong>' + a.minutes + ' minutes</strong></div>' +
      '<div class="fact"><small>How often</small><strong>' + escapeText(a.frequency) + '</strong></div>' +
    '</div>' +
    '<h3>What you need</h3><ul>' + materials + '</ul>' +
    '<h3>Steps</h3><ol>' + steps + '</ol>' +
    '<h3>What progress looks like</h3><p>' + escapeText(a.progress) + '</p>' +
    '</div>';
}

// 3. The "Mark as done" box
function showDoneBox(a) {
  var box = document.getElementById("done-box");
  var user = getCurrentUser();

  // Not logged in
  if (user === null) {
    box.innerHTML = '<div class="card" style="margin-top:20px"><h3>Track your progress</h3>' +
      '<p>Log in as a parent to mark this activity as done.</p>' +
      '<a class="btn btn-blue btn-small" href="login.html">Login</a> ' +
      '<a class="btn btn-outline btn-small" href="register.html">Create account</a></div>';
    return;
  }

  // Admin does not need this box
  if (user.role !== "parent") {
    return;
  }

  // Parent: find this parent's children
  var allChildren = getChildren();
  var myChildren = [];
  for (var c = 0; c < allChildren.length; c++) {
    if (allChildren[c].parentId === user.id) {
      myChildren.push(allChildren[c]);
    }
  }

  if (myChildren.length === 0) {
    box.innerHTML = '<div class="card" style="margin-top:20px"><h3>Track your progress</h3>' +
      '<p>Add your child first, then you can mark activities as done.</p>' +
      '<a class="btn btn-blue btn-small" href="parent_child.html">Add my child</a></div>';
    return;
  }

  var options = "";
  for (var k = 0; k < myChildren.length; k++) {
    options += '<option value="' + myChildren[k].id + '">' + escapeText(myChildren[k].name) + '</option>';
  }

  box.innerHTML = '<div class="form-box" style="margin-top:20px">' +
    '<h3>Did you do this activity today?</h3>' +
    '<label for="child-select">Child</label><select id="child-select">' + options + '</select>' +
    '<label for="note">Note (optional)</label>' +
    '<textarea id="note" placeholder="How did it go?"></textarea>' +
    '<button class="btn btn-yellow" id="done-btn">✔ Mark as done</button>' +
    '<div id="done-message"></div></div>';

  document.getElementById("done-btn").onclick = function () {
    var logs = getLogs();
    logs.push({
      id: nextId(logs),
      childId: Number(document.getElementById("child-select").value),
      activityId: a.id,
      date: todayString(),
      note: document.getElementById("note").value
    });
    saveList("logs", logs);
    document.getElementById("done-message").innerHTML =
      '<div class="alert alert-success">Well done! This activity was saved. <a href="parent_dashboard.html">See my dashboard</a></div>';
    document.getElementById("note").value = "";
  };
}
