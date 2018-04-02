<?php

session_start();
$username=$_SESSION['loggedin_username'];
$isloggedin=$_SESSION['isloggedin'];

if((!$isloggedin)){
        header("Location: ..");
        exit;// this is only exit to redirect to loginform, when not logged in.
};

phpinfo();
