<?php

require_once(dirname(__FILE__) .'/../../../Class/ManagementClass.php');

$management = new ManagementClass();

echo $management->addTeacher($_POST["data"]);