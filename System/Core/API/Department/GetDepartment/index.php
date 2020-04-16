<?php

require_once(dirname(__FILE__) .'/../../../Class/DepartmentClass.php');

$department = new DepartmentClass();

echo $department->getDepartmentList($_POST["data"],$_POST["filter"]);