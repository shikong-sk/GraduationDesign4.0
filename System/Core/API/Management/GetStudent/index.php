<?php

require_once(dirname(__FILE__) .'/../../../Class/ManagementClass.php');

$management = new ManagementClass();

echo $management->getStudentList($_POST["data"],$_POST["filter"]);