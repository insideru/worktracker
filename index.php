<?php
require_once './include/account.php';

global $account;
$account->sessionLogin();

if ($account->authenticated) {
    //user e logat
    if (isset($_GET['page'])) {
        if ($_GET['page']=="dashboard"){
            require 'proto-dashboard.html';
            die;
        } else if ($_GET['page']=="calendar"){
            require 'proto-calendar.html';
            die;
        } else if ($_GET['page']=="ponteaza"){
            require 'proto-pontaj.html';
            die;
        } else if ($_GET['page']=="concediu"){
            require 'proto-concediu.html';
            die;
        } else if ($_GET['page']=="admin"){
            require "proto-admin.html";
            die;
        } else if ($_GET['page']=="newProject"){
            require "proto-newproj.html";
            die;
        } else if ($_GET['page']=="reports"){
            require "proto-raport.html";
            die;
        } else if ($_GET['page']=="logout"){
            logout();
            header("Location: /");
            die;
        }
    }
    require 'proto-dashboard.html';
    }
    else {
        //echo "Nu exista user logat";
        require 'login.html';
    }
?>