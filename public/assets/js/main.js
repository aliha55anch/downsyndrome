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
function getChildren() { return loadList("children", []); }
function getLogs() { return loadList("logs", []); }
function getMessages() { return loadList("messages", []); }

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
function getCurrentUser() { return null; }

function appUrl(path) {
  var origin = window.location.origin;
  if (window.location.protocol === "file:" || origin === "null") {
    origin = "http://127.0.0.1:8000";
  }
  return origin + path;
}

// Use at the top of private pages. Sends visitors to the login page.
function requireRole(role) {
  window.location.href = appUrl("/login");
  return null;
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
    '<a class="btn btn-blue btn-small" href="/activities/' + a.id + '">See activity</a>' +
    '</div>';
}

// ---------- 5. Navigation bar and footer ----------
// Every page has <div id="navbar"></div> and <div id="footer"></div>.
// These two functions fill them, so we write the menu only once.

function buildPatientNotificationBell(user) {
  var items = (user.recent_notifications || []).map(function (item) {
    var date = item.created_at ? new Date(item.created_at).toLocaleString() : '';
    var action = (!item.read_at || item.url) ? '<form method="POST" action="/notifications/' + encodeURIComponent(item.id) + '/read"><input type="hidden" name="_token" value="' + escapeText(user.csrf_token || '') + '"><input type="hidden" name="_method" value="PATCH"><button class="text-button" type="submit">' + (item.url ? 'Open' : 'Mark read') + '</button></form>' : '';
    return '<article class="notification-item ' + (!item.read_at ? 'is-unread' : '') + '"><div><strong>' + escapeText(item.title || 'Update') + '</strong><p>' + escapeText(item.message || '') + '</p><time>' + escapeText(date) + '</time></div>' + action + '</article>';
  }).join('');
  if (!items) items = '<p class="muted">You’re all caught up.</p>';
  var unread = Number(user.unread_notifications_count || 0);
  var badge = unread ? '<b class="notification-count">' + (unread > 99 ? '99+' : unread) + '</b>' : '';
  return '<details class="notification-bell"><summary aria-label="Notifications, ' + unread + ' unread"><svg aria-hidden="true" viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>' + badge + '</summary><div class="notification-popover" role="region" aria-label="Recent notifications"><div class="panel-heading"><strong>Notifications</strong><form method="POST" action="/notifications/read-all"><input type="hidden" name="_token" value="' + escapeText(user.csrf_token || '') + '"><input type="hidden" name="_method" value="PATCH"><button class="text-button" type="submit">Mark all read</button></form></div>' + (items || '<p class="muted">You’re all caught up.</p>') + '<a class="notification-view-all" href="/notifications">View all notifications</a></div></details>';
}

function buildNavbar(user) {

  // Which page are we on? Used to highlight the menu link.
  var page = window.location.pathname.split("/").pop();
  if (page === "") { page = "/"; }
  var sameMenu = {
    "activity_detail.html": "activities",
    "doctor_detail.html": "doctors.html",
    "parent_child.html": "parent_dashboard.html",
    "register.html": "login.html"
  };
  if (sameMenu[page]) { page = sameMenu[page]; }

  var links = [
    ["/", "Home"],
    ["/about.html", "About"],
    ["/activities", "Activities"],
    ["/doctors", "Professional Consultancy"],
    ["/contact", "Contact"]
  ];

  var menu = "";
  for (var i = 0; i < links.length; i++) {
    var activeClass = "";
    if (links[i][0] === window.location.pathname) { activeClass = ' class="active"'; }
    menu += '<a href="' + links[i][0] + '"' + activeClass + '>' + links[i][1] + '</a>';
  }
  var accountMenu = '';
  if (user === null) {
    accountMenu = '<a href="' + appUrl('/login') + '">Login</a><a href="' + appUrl('/register') + '">Register</a>';
  } else {
    var avatar = user.avatar_url ? '<img src="' + user.avatar_url + '" alt="">' : '<span>U</span>';
    var account = '<details class="profile-menu"><summary class="profile-trigger" aria-label="Open account menu">' + avatar + '</summary><div class="profile-dropdown">';
    account += '<a href="' + user.dashboard_url + '">' + (user.role === 'admin' ? 'Admin dashboard' : (user.role === 'pending_doctor' || user.role === 'approved_doctor' ? 'Doctor Dashboard' : 'My Dashboard')) + '</a>';
    account += '<form method="POST" action="/logout"><input type="hidden" name="_token" value="' + escapeText(user.csrf_token || '') + '"><button type="submit">Logout</button></form></div></details>';
    accountMenu = (user.role === 'parent' ? buildPatientNotificationBell(user) : '') + account;
  }

  document.getElementById("navbar").innerHTML =
    '<header class="navbar"><div class="container nav-inner">' +
    '<a class="logo" href="/">Little <span>Steps</span></a>' +
    '<button class="menu-btn" id="menu-btn" aria-label="Open menu">☰</button>' +
    '<nav class="nav-links" id="nav-links">' + menu + '</nav>' +
    '<div class="nav-account">' + accountMenu + '</div>' +
    '</div></header>';

  var guestActions = document.getElementById('guest-actions');
  if (guestActions) guestActions.hidden = user !== null;

  document.querySelectorAll('.profile-menu').forEach(function (menu) {
    menu.addEventListener('toggle', function () { if (menu.open) document.querySelectorAll('.profile-menu').forEach(function (other) { if (other !== menu) other.removeAttribute('open'); }); });
  });
  document.addEventListener('click', function (event) { document.querySelectorAll('.profile-menu[open]').forEach(function (menu) { if (!menu.contains(event.target)) menu.removeAttribute('open'); }); });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') document.querySelectorAll('.profile-menu[open]').forEach(function (menu) { menu.removeAttribute('open'); }); });

  // Open and close the menu on phones.
  document.getElementById("menu-btn").onclick = function () {
    document.getElementById("nav-links").classList.toggle("open");
  };

}

function buildFooter() {
  var mount = document.getElementById("footer");
  if (!mount) return;
  var year = new Date().getFullYear();
  mount.innerHTML =
    '<footer class="site-footer"><div class="container">' +
      '<div class="site-footer-grid">' +
        '<section class="site-footer-about"><a class="site-footer-brand" href="/">Little <span>Steps</span></a>' +
          '<p>A welcoming place for families to find learning activities and professional support.</p>' +
          '<p class="site-footer-note">Information on this site is general guidance, not medical advice.</p></section>' +
        '<nav class="site-footer-links" aria-label="Footer links"><h2>Explore</h2>' +
          '<a href="/">Home</a><a href="/about.html">About us</a><a href="/activities">Activities</a>' +
          '<a href="/doctors">Find a doctor</a><a href="/contact">Contact us</a></nav>' +
        '<section class="site-footer-contact"><h2>Contact</h2>' +
          '<p><span>Address</span> Placeholder, Karachi, Pakistan</p>' +
          '<p><span>Email</span> <a href="mailto:hello@example.com">hello@example.com</a></p>' +
          '<p><span>Phone</span> <a href="tel:+923000000000">+92 300 000 0000</a></p>' +
          '<small>Sample contact details — replace before launch.</small></section>' +
        '<section class="site-footer-social"><h2>Follow</h2><p>Placeholder links — replace with official accounts.</p><div class="site-social-links">' +
          '<a href="https://example.com/replace-with-instagram" target="_blank" rel="noopener noreferrer" aria-label="Instagram (placeholder link)" title="Instagram placeholder">' +
            '<svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" class="site-social-fill"/></svg></a>' +
          '<a href="https://example.com/replace-with-whatsapp" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp (placeholder link)" title="WhatsApp placeholder">' +
            '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20.2 11.8a8.2 8.2 0 0 1-12.1 7.1L3.5 20l1.2-4.4a8.2 8.2 0 1 1 15.5-3.8Z"/><path d="M9 8.2c.3-.6.6-.6.9-.6l.6.1.8 1.8-.1.6-.7.8c.7 1.4 1.8 2.4 3.3 3l.7-.8.5-.3 1.9.9.1.5c-.1.7-.8 1.5-1.6 1.6-1.3.2-3.4-.7-5-2.2-1.5-1.4-2.6-3.5-2.4-4.8.1-.3.4-.5.8-.6Z"/></svg></a>' +
          '<a href="https://example.com/replace-with-linkedin" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn (placeholder link)" title="LinkedIn placeholder">' +
            '<svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M7.5 10v7M7.5 7.3v.1M11 17v-7h3v1.1a2.5 2.5 0 0 1 2.5-1.2c1.8 0 2.5 1.2 2.5 3.2V17M11 13c0-1.8 1-3.1 2.7-3.1"/></svg></a>' +
        '</div></section>' +
      '</div><div class="site-footer-bottom"><span>© ' + year + ' Little Steps</span><span>Built with care for families and their communities.</span></div>' +
    '</div></footer>';
}

// Run when the page loads. The public HTML pages use the same session endpoint
// as the Laravel Blade pages, so Login/Register and the account menu stay in sync.
fetch(appUrl('/api/session'), { credentials: 'same-origin', cache: 'no-store' })
  .then(function (response) { return response.json(); })
  .then(function (session) { buildNavbar(session.authenticated ? session : null); })
  .catch(function () { buildNavbar(null); });
buildFooter();
