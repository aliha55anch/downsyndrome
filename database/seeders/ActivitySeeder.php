<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['Tummy Time Play', 'Motor skills', '0 to 6 months', 'Build neck and shoulder strength', ['Soft mat or blanket', 'A bright toy'], ['Place the baby on the mat on their tummy.', 'Put the toy in front of the baby.', 'Move the toy slowly from side to side.', 'Stop when the baby is tired or upset.']],
            ['Sing and Look', 'Communication and language', '0 to 6 months', 'Build early communication', ['A quiet place', 'Your voice'], ['Hold the baby so your faces are close.', 'Sing a short, simple song.', 'Pause and wait for the baby to look or make a sound.', 'Smile and repeat the song.']],
            ['Reach and Grab', 'Motor skills', '6 to 12 months', 'Practice reaching and holding', ['Light rattle', 'Soft ball'], ['Sit the baby with support.', 'Hold a toy a little way from the baby’s hand.', 'Let the baby reach and grab it.', 'Praise the baby and try the other hand.']],
            ['Peek a Boo', 'Social interaction', '6 to 12 months', 'Learn turn taking and eye contact', ['A small cloth'], ['Cover your face with the cloth.', 'Say “Where is mama?” or “Where is papa?”.', 'Pull the cloth away and say “Peek a boo!”.', 'Wait for a smile, then do it again.']],
            ['Walking With Support', 'Motor skills', '1 to 2 years', 'Build balance and leg strength', ['A push toy or stable chair', 'Open, safe floor space'], ['Clear the floor of anything sharp or slippery.', 'Let the child hold the push toy.', 'Walk beside the child for a few steps.', 'Cheer after each try and let the child rest.']],
            ['Stacking Blocks', 'Motor skills', '1 to 2 years', 'Improve hand and finger control', ['Four to six large soft blocks'], ['Sit on the floor with the child.', 'Build a tower of two blocks.', 'Hand the child one block at a time.', 'Praise the child and let them knock it down.']],
            ['Name the Objects', 'Communication and language', '2 to 3 years', 'Grow vocabulary', ['Five familiar objects', 'A basket'], ['Put the objects in the basket.', 'Take one out and say its name clearly.', 'Ask the child to say the word or point to it.', 'Praise every try and repeat with each object.']],
            ['Follow Simple Instructions', 'Cognitive development', '2 to 3 years', 'Understand and follow short requests', ['A ball', 'A basket'], ['Say one short instruction, such as “Give me the ball”.', 'Point or show the action if needed.', 'Wait a few seconds.', 'Praise the child and try another instruction.']],
            ['Wash Hands Together', 'Daily living skills', '2 to 3 years', 'Learn a daily routine', ['Soap', 'Towel', 'A small stool'], ['Help the child reach the sink safely.', 'Wet the hands together.', 'Rub soap while singing a short song.', 'Rinse and dry.']],
            ['Color Matching', 'Cognitive development', '3 to 5 years', 'Learn colors and matching', ['Colored paper or cards', 'Three colored cups'], ['Put the cups in a row.', 'Show a card and say its color.', 'Help the child put it in the matching cup.', 'Add more colors slowly.']],
            ['Drawing Lines', 'Motor skills', '3 to 5 years', 'Get ready for writing', ['Thick crayons', 'Large paper'], ['Draw a straight line and let the child copy it.', 'Try a line going up and across.', 'Draw a simple road to follow.', 'Praise the effort.']],
            ['Turn Taking Game', 'Social interaction', '3 to 5 years', 'Learn to wait and share', ['A ball', 'One other person'], ['Sit facing each other.', 'Roll the ball and say “My turn”.', 'Roll it to the child and say “Your turn”.', 'Keep the game short and fun.']],
            ['Buttoning Practice', 'Daily living skills', '5+ years', 'Learn to dress without help', ['A shirt with large buttons'], ['Lay the shirt flat.', 'Show how to push a button through the hole.', 'Help with the first button.', 'Let the child try the next button.']],
            ['Story Time Questions', 'Communication and language', '5+ years', 'Build speaking and listening', ['A picture book'], ['Read one page slowly.', 'Point to a picture and ask a simple question.', 'Wait and give time to answer.', 'Repeat the answer in a full sentence.']],
        ];

        foreach ($activities as [$title, $area, $ageGroup, $objective, $materials, $instructions]) {
            Activity::updateOrCreate(
                ['title' => $title],
                [
                    'short_description' => $objective,
                    'detailed_description' => 'A simple, family-led learning activity that can be adapted to the child’s comfort and interests.',
                    'age_group' => $ageGroup,
                    'development_area' => $area,
                    'difficulty' => 'Gentle start',
                    'estimated_minutes' => 10,
                    'materials' => implode(PHP_EOL, $materials),
                    'learning_objective' => $objective,
                    'instructions' => implode(PHP_EOL, $instructions),
                    'parent_tips' => 'Give the child time to respond, praise effort, and stop if the child becomes tired or uncomfortable.',
                    'disclaimer' => 'This is an educational activity and does not replace medical or therapeutic advice.',
                    'content_source' => 'Little Steps starter activity collection',
                    'reviewer_name' => null,
                    'status' => Activity::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'content_blocks' => [
                        ['type' => 'tips', 'text' => 'Follow the child’s pace and make the activity enjoyable.'],
                    ],
                ]
            );
        }
    }
}
