<?php

require_once(dirname(__FILE__) .'/../../../Class/ClassClass.php');

$class = new ClassClass();

echo $class->addClass($_POST["data"]);