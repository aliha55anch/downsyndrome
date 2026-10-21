// doctors.js (Doctor list page)

var doctors = getDoctors();

// 1. Make a list of cities (each city only once)
var cities = [];
for (var i = 0; i < doctors.length; i++) {
  if (cities.indexOf(doctors[i].city) === -1) {
    cities.push(doctors[i].city);
  }
}
cities.sort();

// 2. Fill the filter lists
fillSelect("specialty-filter", SPECIALTIES, "All types");
fillSelect("city-filter", cities, "All cities");

// 3. Show the doctors that match the filters
function showDoctors() {
  var specialty = document.getElementById("specialty-filter").value;
  var city = document.getElementById("city-filter").value;
  var mode = document.getElementById("mode-filter").value;

  var html = "";
  var count = 0;
  for (var j = 0; j < doctors.length; j++) {
    var d = doctors[j];
    var specialtyOk = (specialty === "" || d.specialty === specialty);
    var cityOk = (city === "" || d.city === city);
    // "Online and in person" matches both Online and In person
    var modeOk = (mode === "" || d.mode.indexOf(mode) !== -1);

    if (specialtyOk && cityOk && modeOk) {
      html += doctorCard(d);
      count = count + 1;
    }
  }

  if (count === 0) {
    html = '<div class="card"><h3>No doctors found</h3><p>Try a different type or city.</p></div>';
  }
  document.getElementById("doctor-list").innerHTML = html;
  document.getElementById("result-count").textContent = count + " found";
}

// 4. Update the list when a filter changes
document.getElementById("specialty-filter").onchange = showDoctors;
document.getElementById("city-filter").onchange = showDoctors;
document.getElementById("mode-filter").onchange = showDoctors;

showDoctors();
