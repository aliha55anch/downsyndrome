window.location.replace('/activities');

var activities = getActivities();

// 1. Fill the two filter lists
var ageNames = [];
for (var i = 0; i < AGE_GROUPS.length; i++) {
  ageNames.push(AGE_GROUPS[i].name);
}
var areaNames = [];
for (var j = 0; j < AREAS.length; j++) {
  areaNames.push(AREAS[j].name);
}
fillSelect("age-filter", ageNames, "All ages");
fillSelect("area-filter", areaNames, "All areas");

// 2. If the address has ?age=...&area=..., choose those values
var ageFromAddress = getParam("age");
var areaFromAddress = getParam("area");
if (ageFromAddress !== null) {
  document.getElementById("age-filter").value = ageFromAddress;
}
if (areaFromAddress !== null) {
  document.getElementById("area-filter").value = areaFromAddress;
}

// 3. Show the activities that match the filters
function showActivities() {
  var age = document.getElementById("age-filter").value;
  var area = document.getElementById("area-filter").value;

  var html = "";
  var count = 0;
  for (var k = 0; k < activities.length; k++) {
    var a = activities[k];
    var ageOk = (age === "" || a.ageGroup === age);
    var areaOk = (area === "" || a.area === area);
    if (ageOk && areaOk) {
      html += activityCard(a);
      count = count + 1;
    }
  }

  if (count === 0) {
    html = '<div class="card"><h3>No activities found</h3><p>Try a different age or area.</p></div>';
  }
  document.getElementById("activity-list").innerHTML = html;
  document.getElementById("result-count").textContent = count + " activities found";
}

// 4. Update the list when a filter changes
document.getElementById("age-filter").onchange = showActivities;
document.getElementById("area-filter").onchange = showActivities;

showActivities();
