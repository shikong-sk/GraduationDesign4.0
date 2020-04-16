<?php

require_once(dirname(__FILE__) .'/../../../Class/ClassClass.php');

$class = new ClassClass();

echo $class->getClassList($_POST["data"],$_POST["filter"]);