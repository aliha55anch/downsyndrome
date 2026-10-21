// =====================================================
// main.js  (used by every page, loaded after data.js)
// It holds the small helper functions that many pages need.
//
// IMPORTANT: Data is saved in the browser (localStorage) so
// the project works without a server. When you build the
// Laravel and MySQL backend, these save and load functions
// are the parts you replace.
// =====================================================


// ---------- 1. Saving and loading data ----------

// Get a list from the browser. If nothing is saved yet, use the default list.
function loadList(key, defaultList) {
  var text = localStorage.getItem(key);
  if (text === null) {
    return defaultList;
  }
  return JSON.parse(text);
}

// Save a list in the browser.
function saveList(key, list) {
  localStorage.setItem(key, JSON.stringify(list));
}

function getActivities() { return loadList("activities", defaultActivities); }
function getDoctors() { return loadList("doctors", defaultDoctors); }
function getChildren() { return loadList("children", []); }
function getLogs() { return loadList("logs", []); }
function getMessages() { return loadList("messages", []); }

// The first user is a ready made admin for testing.
function getUsers() {
  return loadList("users", [
    { id: 1, name: "Admin", email: "admin@example.com", password: "admin123", role: "admin" }
  ]);
}

// Give a new item the next free number.
function nextId(list) {
  var biggest = 0;
  for (var i = 0; i < list.length; i++) {
    if (list[i].id > biggest) {
      biggest = list[i].id;
    }
  }
  return biggest + 1;
}


// ---------- 2. Login helpers ----------

// Who is logged in right now? Returns null if nobody.
function getCurrentUser() {
  var text = localStorage.getItem("currentUser");
  if (text === null) {
    return null;
  }
  return JSON.parse(text);
}

function setCurrentUser(user) {
  localStorage.setItem("currentUser", JSON.stringify(user));
}

function logout() {
  localStorage.removeItem("currentUser");
  window.location.href = "index.html";
}

// Use at the top of private pages. Sends visitors to the login page.
function requireRole(role) {
  var user = getCurrentUser();
  if (user === null || user.role !== role) {
    window.location.href = "login.html";
    return null;
  }
  return user;
}


// ---------- 3. Small helpers ----------

// Read a value from the web address, for example ?id=3
function getParam(name) {
  var params = new URLSearchParams(window.location.search);
  return params.get(name);
}

// Make text safe before putting it inside HTML.
function escapeText(text) {
  var div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

// Today's date as text, like 2026-10-21
function todayString() {
  var now = new Date();
  var month = String(now.getMonth() + 1).padStart(2, "0");
  var day = String(now.getDate()).padStart(2, "0");
  return now.getFullYear() + "-" + month + "-" + day;
}

// Add "s" when the number is not 1.
function plural(number, word) {
  if (number === 1) {
    return number + " " + word;
  }
  return number + " " + word + "s";
}

// Turn a birth date into words like "3 years 2 months".
function calcAge(birthDate) {
  var birth = new Date(birthDate);
  var today = new Date();
  var months = (today.getFullYear() - birth.getFullYear()) * 12 + (today.getMonth() - birth.getMonth());
  if (today.getDate() < birth.getDate()) {
    months = months - 1;
  }
  if (months < 0) {
    months = 0;
  }
  var years = Math.floor(months / 12);
  var rest = months % 12;
  if (years === 0) {
    return plural(rest, "month");
  }
  return plural(years, "year") + " " + plural(rest, "month");
}

// Find which age group a child belongs to, from the birth date.
function ageGroupFor(birthDate) {
  var birth = new Date(birthDate);
  var today = new Date();
  var months = (today.getFullYear() - birth.getFullYear()) * 12 + (today.getMonth() - birth.getMonth());
  if (months < 6) { return AGE_GROUPS[0].name; }
  if (months < 12) { return AGE_GROUPS[1].name; }
  if (months < 24) { return AGE_GROUPS[2].name; }
  if (months < 36) { return AGE_GROUPS[3].name; }
  if (months < 60) { return AGE_GROUPS[4].name; }
  return AGE_GROUPS[5].name;
}

// Find the icon and color of an area, for example "Speech".
function areaInfo(areaName) {
  for (var i = 0; i < AREAS.length; i++) {
    if (AREAS[i].name === areaName) {
      return AREAS[i];
    }
  }
  return AREAS[0];
}

// Fill a <select> with options. Pass a list of words.
function fillSelect(selectId, words, firstOptionText) {
  var select = document.getElementById(selectId);
  var html = '<option value="">' + firstOptionText + '</option>';
  for (var i = 0; i < words.length; i++) {
    html += '<option value="' + escapeText(words[i]) + '">' + escapeText(words[i]) + '</option>';
  }
  select.innerHTML = html;
}


// ---------- 4. Cards used on more than one page ----------

function activityCard(a) {
  var area = areaInfo(a.area);
  return '<div class="card">' +
    '<div class="card-icon">' + area.icon + '</div>' +
    '<span class="tag ' + area.cls + '">' + escapeText(a.area) + '</span>' +
    '<span class="tag tag-age">' + escapeText(a.ageGroup) + '</span>' +
    '<h3>' + escapeText(a.title) + '</h3>' +
    '<p>' + escapeText(a.goal) + '</p>' +
    '<p class="muted">⏱ ' + a.minutes + ' minutes</p>' +
    '<a class="btn btn-blue btn-small" href="activity_detail.html?id=' + a.id + '">See activity</a>' +
    '</div>';
}

function doctorCard(d) {
  return '<div class="card">' +
    '<div class="card-icon">🩺</div>' +
    '<span class="tag">' + escapeText(d.specialty) + '</span>' +
    '<h3>' + escapeText(d.name) + '</h3>' +
    '<p class="muted">📍 ' + escapeText(d.city) + ' &nbsp;|&nbsp; ' + escapeText(d.mode) + '</p>' +
    '<p>Fee: Rs ' + d.fee + '</p>' +
    '<a class="btn btn-blue btn-small" href="doctor_detail.html?id=' + d.id + '">View profile</a>' +
    '</div>';
}


// ---------- 5. Navigation bar and footer ----------
// Every page has <div id="navbar"></div> and <div id="footer"></div>.
// These two functions fill them, so we write the menu only once.

function buildNavbar() {
  var user = getCurrentUser();

  // Which page are we on? Used to highlight the menu link.
  var page = window.location.pathname.split("/").pop();
  if (page === "") { page = "index.html"; }
  var sameMenu = {
    "activity_detail.html": "activities.html",
    "doctor_detail.html": "doctors.html",
    "parent_child.html": "parent_dashboard.html",
    "register.html": "login.html"
  };
  if (sameMenu[page]) { page = sameMenu[page]; }

  var links = [
    ["index.html", "Home"],
    ["about.html", "About"],
    ["activities.html", "Activities"],
    ["doctors.html", "Find a Doctor"],
    ["contact.html", "Contact"]
  ];

  if (user !== null && user.role === "parent") {
    links.push(["parent_dashboard.html", "My Dashboard"]);
  }
  if (user !== null && user.role === "admin") {
    links.push(["admin_doctors.html", "Manage Doctors"]);
    links.push(["admin_activities.html", "Manage Activities"]);
  }
  if (user === null) {
    links.push(["login.html", "Login"]);
    links.push(["register.html", "Register"]);
  }

  var menu = "";
  for (var i = 0; i < links.length; i++) {
    var activeClass = "";
    if (links[i][0] === page) { activeClass = ' class="active"'; }
    menu += '<a href="' + links[i][0] + '"' + activeClass + '>' + links[i][1] + '</a>';
  }
  if (user !== null) {
    menu += '<a href="#" id="logout-link">Logout (' + escapeText(user.name) + ')</a>';
  }

  document.getElementById("navbar").innerHTML =
    '<header class="navbar"><div class="container nav-inner">' +
    '<a class="logo" href="index.html">🧩 Little <span>Steps</span></a>' +
    '<button class="menu-btn" id="menu-btn" aria-label="Open menu">☰</button>' +
    '<nav class="nav-links" id="nav-links">' + menu + '</nav>' +
    '</div></header>';

  // Open and close the menu on phones.
  document.getElementById("menu-btn").onclick = function () {
    document.getElementById("nav-links").classList.toggle("open");
  };

  // Logout link
  var logoutLink = document.getElementById("logout-link");
  if (logoutLink !== null) {
    logoutLink.onclick = function (event) {
      event.preventDefault();
      logout();
    };
  }
}

function buildFooter() {
  document.getElementById("footer").innerHTML =
    '<footer class="footer"><div class="container">' +
    '<p>🧩 Little Steps: support for families of children with Down syndrome</p>' +
    '<p>This website gives general guidance. It is not medical advice. ' +
    'Please talk to a qualified doctor or therapist about your child.</p>' +
    '<p>Made as a university project.</p>' +
    '</div></footer>';
}

// Run when the page loads.
buildNavbar();
buildFooter();
