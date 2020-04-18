<?php

require_once(dirname(__FILE__) .'/../../../Class/GradeClass.php');

$grade = new GradeClass();

echo $grade->getGradeList($_POST["data"],$_POST["filter"]);