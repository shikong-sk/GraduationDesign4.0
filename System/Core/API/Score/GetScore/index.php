<?php

require_once(dirname(__FILE__) .'/../../../Class/ScoreClass.php');

$score = new ScoreClass();

echo $score->getScoreList($_POST["data"],$_POST["filter"]);