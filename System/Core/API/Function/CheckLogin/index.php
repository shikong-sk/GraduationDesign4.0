<?php

session_start();

if(isset($_SESSION['ms_user']) && isset($_SESSION['ms_id'])){
    echo json_encode(Array("loginStatus"=>true));
}
else{
    echo json_encode(Array("loginStatus"=>false));
}