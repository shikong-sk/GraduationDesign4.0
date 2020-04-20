<?php

session_start();

if (isset($_SESSION['ms_user']) && isset($_SESSION['ms_id']) && isset($_SESSION['ms_identity'])) {
    switch ($_SESSION['ms_identity']) {
        case 'Student':
            require_once(dirname(__FILE__) . '/../../Student/Logout/index.php');
            break;
        case 'Teacher':
            require_once(dirname(__FILE__) . '/../../Teacher/Logout/index.php');
            break;
    }
} else {
    echo json_encode(array('error' => '您尚未登录，无需进行此操作'), JSON_UNESCAPED_UNICODE);
}