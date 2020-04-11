<?php

require_once(dirname(__FILE__) .'/Class/Abstract/KeepAliveClass.php');

session_start();

class device extends KeepAliveClass{}


if(isset($_POST['id']))
{
    $d = new device($_POST['id']);
    $d->checkAlive();
}


//session_start();

//if(isset($_POST['id']))
//{
////    if(!$_SESSION['device']){
////        $_SESSION = $_POST['id'];
////    }
//}
//
////var_dump($_SESSION);