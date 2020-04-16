<?php

require_once(dirname(__FILE__) .'/../../../Class/MajorClass.php');

$major = new MajorClass();

echo $major->deleteMajor($_POST["data"]);