// index.js (Home page)

// 1. Fill the two drop down lists in the "What should we do next?" box
var ageNames = [];
for (var i = 0; i < AGE_GROUPS.length; i++) {
  ageNames.push(AGE_GROUPS[i].name);
}
var areaNames = [];
for (var j = 0; j < AREAS.length; j++) {
  areaNames.push(AREAS[j].name);
}
fillSelect("age-select", ageNames, "Choose age");
fillSelect("area-select", areaNames, "Any area");

// 2. When the form is sent, go to the activities page with the choices in the address
document.getElementById("next-form").onsubmit = function (event) {
  event.preventDefault();
  var age = document.getElementById("age-select").value;
  var area = document.getElementById("area-select").value;
  window.location.href = "activities.html?age=" + encodeURIComponent(age) + "&area=" + encodeURIComponent(area);
};

// 3. Show the six age group cards
var ageHtml = "";
for (var k = 0; k < AGE_GROUPS.length; k++) {
  var group = AGE_GROUPS[k];
  ageHtml += '<a class="age-card" href="activities.html?age=' + encodeURIComponent(group.name) + '">' +
    group.name + '<small>' + group.focus + '</small></a>';
}
document.getElementById("age-list").innerHTML = ageHtml;

// 4. Show the first three activities
var activities = getActivities();
var featuredHtml = "";
for (var m = 0; m < activities.length && m < 3; m++) {
  featuredHtml += activityCard(activities[m]);
}
document.getElementById("featured").innerHTML = featuredHtml;
