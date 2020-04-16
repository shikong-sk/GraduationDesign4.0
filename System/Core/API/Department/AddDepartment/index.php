<?php

require_once(dirname(__FILE__) .'/../../../Class/DepartmentClass.php');

$department = new DepartmentClass();

echo $department->addDepartment($_POST["data"]);