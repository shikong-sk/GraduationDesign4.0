<?php

session_start();

echo json_encode(array('identity' => $_SESSION['ms_identity']));