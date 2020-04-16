<?php

require_once(dirname(__FILE__) .'/../../../Class/ManagementClass.php');

$management = new ManagementClass();

echo $management->getTeacherList($_POST["data"],$_POST["filter"]);