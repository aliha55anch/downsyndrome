// doctor_detail.js (One doctor page)

// 1. Find the doctor using the number in the address (?id=2)
var id = Number(getParam("id"));
var doctors = getDoctors();
var doctor = null;
for (var i = 0; i < doctors.length; i++) {
  if (doctors[i].id === id) {
    doctor = doctors[i];
  }
}

// 2. Show the doctor
if (doctor === null) {
  document.getElementById("detail").innerHTML =
    '<div class="card"><h2>Doctor not found</h2><p>Please go back and choose a doctor.</p></div>';
} else {
  document.getElementById("detail").innerHTML =
    '<div class="detail-box">' +
    '<div class="card-icon"><svg viewBox="0 0 24 24"><path d="M4.5 3v5a4.5 4.5 0 0 0 9 0V3"/><circle cx="18" cy="10" r="3"/><path d="M9 12.5v3.5a4 4 0 0 0 8 0v-3"/></svg></div>' +
    '<span class="tag">' + escapeText(doctor.specialty) + '</span>' +
    '<h1>' + escapeText(doctor.name) + '</h1>' +
    '<p>' + escapeText(doctor.bio) + '</p>' +
    '<div class="facts">' +
      '<div class="fact"><small>City</small><strong>' + escapeText(doctor.city) + '</strong></div>' +
      '<div class="fact"><small>Meeting</small><strong>' + escapeText(doctor.mode) + '</strong></div>' +
      '<div class="fact"><small>Fee</small><strong>Rs ' + doctor.fee + '</strong></div>' +
      '<div class="fact"><small>Experience</small><strong>' + plural(doctor.experience, "year") + '</strong></div>' +
    '</div>' +
    '<p><strong>Address:</strong> ' + escapeText(doctor.address) + '</p>' +
    '<p><strong>Phone:</strong> ' + escapeText(doctor.phone) + '</p>' +
    '<div class="btn-row">' +
      '<a class="btn btn-yellow" href="tel:' + escapeText(doctor.phone.replace(/\s/g, "")) + '">Call now</a>' +
      '<a class="btn btn-outline" href="contact.html">Send us a message</a>' +
    '</div>' +
    '</div>';
}
