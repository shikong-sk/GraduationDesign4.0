<?php

session_start();

switch ($_SESSION['ms_identity']) {
    case 'Student':
        require_once(dirname(__FILE__) . '/../../Student/GetUserInfo/index.php');
        break;
    case 'Teacher':
        require_once(dirname(__FILE__) . '/../../Teacher/GetUserInfo/index.php');
        break;
}