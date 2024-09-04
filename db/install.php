<?php
function xmldb_bookit_install()
{
  global $DB;

  $category = new \mod_bookit\local\entity\category('Rooms', 'Examrooms');
  $category->save(2);

  $resources = [];
  $events = [];

  $subjects = ['Mathematics', 'Physics', 'Chemistry', 'Biology', 'Computer Science', 'History', 'Geography', 'English Literature', 'Psychology', 'Sociology'];

  for ($i = 1; $i <= 10; $i++) {
    $resource = new \mod_bookit\local\entity\resource('Exam room ' . $i, 'Capacity 255 seats', 1, $category->id);
    $resource->save(2);
    $resources[] = $resource;

    // Generate random date and time in the current week between 07:00 and 20:00
    $dayOfWeek = rand(0, 5); // Changed from 0-6 to 0-5 to exclude Sundays
    $hour = rand(7, 20);
    $minute = rand(0, 59);
    $second = rand(0, 59);
    $startDate = strtotime("last Monday +$dayOfWeek days $hour:$minute:$second"); // Changed "this Monday" to "last Monday"
    $endDate = $startDate + 7200; // Add 2 hours to start date

    $subject = $subjects[array_rand($subjects)]; // Select a random subject

    $event = new \mod_bookit\local\entity\event(
      'Exam ' . $subject,
      20241,
      'IT',
      $startDate,
      $endDate,
      90,
      85,
      '3 Zeitverlängerungen',
      1,
      2,
      'Prof. Superprof',
      'superprof@example.com',
      4,
      'Internal lorem ipsum',
      'Lorem Ipsum dolor...',
      'Susi Support',
      [
        (object) ['resourceid' => $resource->id, 'amount' => 2]
      ],
      null,
      2
    );
    $event->save(2);
    $events[] = $event;
  }
}
