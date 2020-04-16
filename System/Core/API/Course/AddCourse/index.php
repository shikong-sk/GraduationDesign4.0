<?php

require_once(dirname(__FILE__) .'/../../../Class/CourseClass.php');

$course = new CourseClass();

echo $course->addCourse($_POST["data"]);