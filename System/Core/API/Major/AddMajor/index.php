<?php

require_once(dirname(__FILE__) .'/../../../Class/MajorClass.php');

$major = new MajorClass();

echo $major->addMajor($_POST["data"]);