<?php

require_once(dirname(__FILE__) .'/../../../Class/TeacherClass.php');

$teacher = new TeacherClass();

echo $teacher->updateInfo($_POST["data"],$_POST["info"]);