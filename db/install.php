<?php

function xmldb_bookit_install()
{
  global $DB;

  $category = new \mod_bookit\local\entity\category('Rooms', 'Examrooms');
  $category->save(2);

  $resourceNames = ['Exam room 2', 'Exam room 3', 'Exam room 4', 'Exam room 5', 'Exam room 6', 'Exam room 7', 'Exam room 8', 'Exam room 9', 'Exam room 10', 'Exam room 11'];
  $eventNames = [
    'Exam Mathematics',
    'Exam Philosophy',
    'Exam Physics',
    'Exam Chemistry',
    'Exam Biology',
    'Exam Computer Science',
    'Exam History',
    'Exam Literature',
    'Exam Art',
    'Exam Sociology'
  ];

  // Get the start of the week
  $startOfWeek = strtotime("last monday midnight");

  for ($i = 0; $i < 10; $i++) {
    $resource = new \mod_bookit\local\entity\resource($resourceNames[$i], 'Capacity 255 seats', 1, $category->id);
    $resource->save(2);

    // Add random day of the week and time for each event between 07:00 and 20:00
    $eventTime = $startOfWeek + (rand(0, 6) * 24 * 60 * 60) + (rand(7, 20) * 60 * 60);

    $event = new \mod_bookit\local\entity\event(
      $eventNames[$i],
      20241,
      'IT',
      $eventTime,
      $eventTime + 7200,
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
      null,
      2
    );
    $event->save(2);

    $DB->insert_record('bookit_event_resources', [
      'eventid' => $event->id,
      'resourceid' => $resource->id,
      'amount' => 1,
      'usermodified' => 2,
      'timecreated' => time(),
      'timemodified' => time(),
    ]);
  }
}
