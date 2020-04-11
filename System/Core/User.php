<?php

session_start();

require_once(dirname(__FILE__) .'/Class/SqlHelper.php');

require_once(dirname(__FILE__) . '/Class/Abstract/UserClass.php');

$User = new User();

header('Content-Type:application/json; charset=utf-8');

if(isset($_POST['login']) &&  isset($_POST['userName']) && isset($_POST['password']))
{
    die($User->login($_POST['userName'],$_POST['password']));
}

if(isset($_POST['register']) && isset($_POST['userName']) && isset($_POST['password']) && isset($_POST['contact']) && isset($_POST["gender"]) && isset($_POST["name"]) && isset($_POST['email']) && isset($_POST["idCard"]) && isset($_POST["cardType"]) && isset($_POST['userImg']))
{
    die($User->register($_POST['userName'],$_POST['password'],$_POST['contact'],$_POST['gender'],$_POST['name'],$_POST['email'],$_POST['idCard'],$_POST['cardType'],$_POST['userImg']));
}

if(isset($_REQUEST['logout']))
{
    die($User->logout());
}

if(isset($_POST['updateUser']) && isset($_POST['password']) && isset($_POST["contact"]) && isset($_POST['email']) && isset($_POST["gender"]) && isset($_POST["name"]) && isset($_POST["idCard"]) && isset($_POST['cardType']) && isset($_POST['userImg']) ){
    die($User->updateUser($_POST["password"],$_POST["contact"],$_POST["email"],$_POST["gender"],$_POST["name"],$_POST["idCard"],$_POST["cardType"],$_POST["userImg"]));
}

if(isset($_POST['getUserInfo']))
{
    die($User->getUserInfo());
}

if(isset($_POST['getPermission']))
{
    die(json_encode(Array($User->getPermission()->fetch_assoc()['permission']),JSON_UNESCAPED_UNICODE));
}

die('<h1>ForBidden</h1>');