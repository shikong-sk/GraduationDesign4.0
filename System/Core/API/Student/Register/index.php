<?php

require_once(dirname(__FILE__) .'/../../../Class/StudentClass.php');

$student = new StudentClass();

echo $student->register($_POST["data"]);