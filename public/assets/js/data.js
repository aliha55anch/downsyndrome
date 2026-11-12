// =====================================================
// data.js
// Sample data used by the pages. Later, this data comes
// from the MySQL database. For now it is plain JavaScript.
// =====================================================

// The six age groups. Activities use these exact words.
var AGE_GROUPS = [
  { name: "0 to 6 months", focus: "Movement, feeding and senses" },
  { name: "6 to 12 months", focus: "Sitting, crawling and talking" },
  { name: "1 to 2 years", focus: "Walking, first words and play" },
  { name: "2 to 3 years", focus: "Words, instructions and self help" },
  { name: "3 to 5 years", focus: "Speech, hands and early learning" },
  { name: "5+ years", focus: "School skills and friendships" }
];

// The six development areas. "cls" is the color label in style.css.
var AREAS = [
  { name: "Speech", icon: "", cls: "tag-speech" },
  { name: "Gross Motor", icon: "", cls: "tag-gross" },
  { name: "Fine Motor", icon: "", cls: "tag-fine" },
  { name: "Learning", icon: "", cls: "tag-learning" },
  { name: "Self Help", icon: "", cls: "tag-self" },
  { name: "Social", icon: "", cls: "tag-social" }
];

// Types of doctors and therapists.
var SPECIALTIES = [
  "Speech Therapist",
  "Physiotherapist",
  "Occupational Therapist",
  "Developmental Pediatrician",
  "Special Educator",
  "Audiologist"
];

// ---------- Starting activities ----------
var defaultActivities = [
  {
    id: 1, title: "Tummy Time Play", area: "Gross Motor", ageGroup: "0 to 6 months",
    goal: "Build neck and shoulder strength", minutes: 10, frequency: "2 times every day",
    materials: ["Soft mat or blanket", "A bright toy"],
    steps: ["Place the baby on the mat on their tummy.", "Put the toy in front of the baby.", "Move the toy slowly to the left and right.", "Stop when the baby is tired or upset."],
    progress: "The baby lifts the head for a little longer each week."
  },
  {
    id: 2, title: "Sing and Look", area: "Speech", ageGroup: "0 to 6 months",
    goal: "Build early communication", minutes: 10, frequency: "Every day",
    materials: ["A quiet place", "Your voice"],
    steps: ["Hold the baby so your faces are close.", "Sing a short, simple song.", "Pause and wait for the baby to look or make a sound.", "Smile and repeat the song."],
    progress: "The baby looks at your face and makes more sounds."
  },
  {
    id: 3, title: "Reach and Grab", area: "Fine Motor", ageGroup: "6 to 12 months",
    goal: "Practice reaching and holding", minutes: 10, frequency: "Every day",
    materials: ["Light rattle", "Soft ball"],
    steps: ["Sit the baby with support.", "Hold a toy a little way from the baby's hand.", "Let the baby reach and grab it.", "Praise the baby, then try the other hand."],
    progress: "The baby reaches with both hands and holds the toy longer."
  },
  {
    id: 4, title: "Peek a Boo", area: "Social", ageGroup: "6 to 12 months",
    goal: "Learn turn taking and eye contact", minutes: 5, frequency: "Several times a day",
    materials: ["A small cloth"],
    steps: ["Cover your face with the cloth.", "Say \"Where is mama?\" or \"Where is papa?\".", "Pull the cloth away and say \"Peek a boo!\".", "Wait for a smile, then do it again."],
    progress: "The baby smiles and waits for the next turn."
  },
  {
    id: 5, title: "Walking With Support", area: "Gross Motor", ageGroup: "1 to 2 years",
    goal: "Build balance and leg strength", minutes: 15, frequency: "Every day",
    materials: ["A push toy or a stable chair", "Open, safe floor space"],
    steps: ["Clear the floor of anything sharp or slippery.", "Let the child hold the push toy.", "Walk beside the child, a few steps at a time.", "Cheer after each try and let the child rest."],
    progress: "The child takes more steps with less help."
  },
  {
    id: 6, title: "Stacking Blocks", area: "Fine Motor", ageGroup: "1 to 2 years",
    goal: "Improve hand and finger control", minutes: 10, frequency: "3 times a week",
    materials: ["4 to 6 large soft blocks"],
    steps: ["Sit on the floor with the child.", "Build a tower of two blocks and show it.", "Hand the child one block at a time.", "Clap when the tower stands, then let the child knock it down."],
    progress: "The child can stack two or three blocks."
  },
  {
    id: 7, title: "Name the Objects", area: "Speech", ageGroup: "2 to 3 years",
    goal: "Grow vocabulary", minutes: 10, frequency: "Every day",
    materials: ["5 familiar objects such as a cup, spoon, ball, shoe and toy car", "A basket"],
    steps: ["Put the objects in the basket.", "Take one out and say its name clearly.", "Ask the child to say the word or point to it.", "Praise every try, even a sound.", "Repeat with each object."],
    progress: "The child says or points to more of the names each week."
  },
  {
    id: 8, title: "Follow Simple Instructions", area: "Learning", ageGroup: "2 to 3 years",
    goal: "Understand and follow short requests", minutes: 10, frequency: "Every day",
    materials: ["A ball", "A basket"],
    steps: ["Say one short instruction, such as \"Give me the ball\".", "Point or show the action if the child is unsure.", "Wait a few seconds.", "Praise, then try another instruction."],
    progress: "The child follows a one step instruction with less help."
  },
  {
    id: 9, title: "Wash Hands Together", area: "Self Help", ageGroup: "2 to 3 years",
    goal: "Learn a daily routine", minutes: 5, frequency: "Before meals",
    materials: ["Soap", "Towel", "A small stool"],
    steps: ["Help the child up to the sink.", "Wet the hands together.", "Rub soap and sing a short song.", "Rinse and dry. Praise the child."],
    progress: "The child does more steps alone."
  },
  {
    id: 10, title: "Color Matching", area: "Learning", ageGroup: "3 to 5 years",
    goal: "Learn colors and matching", minutes: 15, frequency: "3 times a week",
    materials: ["Colored paper or cards", "3 colored cups"],
    steps: ["Put the cups in a row.", "Show a card and say its color.", "Help the child put the card in the matching cup.", "Add more colors slowly."],
    progress: "The child matches two or three colors without help."
  },
  {
    id: 11, title: "Drawing Lines", area: "Fine Motor", ageGroup: "3 to 5 years",
    goal: "Get ready for writing", minutes: 15, frequency: "3 times a week",
    materials: ["Thick crayons", "Large paper"],
    steps: ["Draw a straight line and let the child copy it.", "Try a line going up, then across.", "Draw a simple road and let the child follow it.", "Hang the drawing and praise the effort."],
    progress: "The child holds the crayon well and draws longer lines."
  },
  {
    id: 12, title: "Turn Taking Game", area: "Social", ageGroup: "3 to 5 years",
    goal: "Learn to wait and share", minutes: 10, frequency: "3 times a week",
    materials: ["A ball", "One other person"],
    steps: ["Sit facing each other on the floor.", "Roll the ball and say \"My turn\".", "Roll it to the child and say \"Your turn\".", "Keep the game short and fun."],
    progress: "The child waits for a turn and rolls the ball back."
  },
  {
    id: 13, title: "Buttoning Practice", area: "Self Help", ageGroup: "5+ years",
    goal: "Learn to dress without help", minutes: 10, frequency: "Every day",
    materials: ["A shirt with large buttons"],
    steps: ["Lay the shirt flat on a table.", "Show how to push the button through the hole.", "Help the child's hands do the first button.", "Let the child try the next button alone."],
    progress: "The child can do one or two buttons alone."
  },
  {
    id: 14, title: "Story Time Questions", area: "Speech", ageGroup: "5+ years",
    goal: "Build speaking and listening", minutes: 15, frequency: "Every day",
    materials: ["A picture book"],
    steps: ["Read one page slowly.", "Point to a picture and ask \"What is this?\".", "Wait and give the child time to answer.", "Say the full sentence back and move to the next page."],
    progress: "The child answers more questions and uses longer sentences."
  }
];
