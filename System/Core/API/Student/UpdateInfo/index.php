<?php

require_once(dirname(__FILE__) . '/../../../Class/StudentClass.php');

$student = new StudentClass();

echo $student->updateInfo($_POST["data"],$_POST["info"]);