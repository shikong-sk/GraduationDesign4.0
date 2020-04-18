<?php

require_once(dirname(__FILE__) .'/../../../Class/GradeClass.php');

$grade = new GradeClass();

echo $grade->addGrade($_POST["data"]);