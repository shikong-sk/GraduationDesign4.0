<?php

require_once(dirname(__FILE__) .'/../../../Class/MajorClass.php');

$major = new MajorClass();

echo $major->getMajorList($_POST["data"],$_POST["filter"]);