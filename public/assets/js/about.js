// about.js (About page)

// Each item is a question and an answer.
var myths = [
  {
    question: "Myth: People with Down syndrome cannot learn.",
    answer: "Fact: They can learn new things all their life. They may need more time, more practice and clear teaching."
  },
  {
    question: "Myth: All children with Down syndrome are the same.",
    answer: "Fact: Every child is different. Each one has their own personality, talents and pace."
  },
  {
    question: "Myth: Children with Down syndrome are always sick.",
    answer: "Fact: Some health problems are more common, but with regular checkups most children stay healthy and active."
  },
  {
    question: "Myth: They cannot go to a regular school.",
    answer: "Fact: Many children go to schools that include all children, with extra support from teachers."
  },
  {
    question: "Myth: Early activities at home make no difference.",
    answer: "Fact: Playing, talking and practicing every day helps a child's speech, movement and confidence."
  }
];

// Build the open and close boxes
var html = "";
for (var i = 0; i < myths.length; i++) {
  html += '<div class="accordion-item">' +
    '<button class="accordion-title">' + myths[i].question + '</button>' +
    '<div class="accordion-body"><p>' + myths[i].answer + '</p></div>' +
    '</div>';
}
document.getElementById("myths").innerHTML = html;

// Open or close a box when its title is clicked
var titles = document.querySelectorAll(".accordion-title");
for (var j = 0; j < titles.length; j++) {
  titles[j].onclick = function () {
    this.parentElement.classList.toggle("open");
  };
}
