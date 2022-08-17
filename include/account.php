<?php
//https://alexwebdevelop.com/user-authentication/
session_start();

require_once 'db.php';
require_once 'account_class.php';

$account = new Account();

function newAccount(string $username, string $passwd, int $group, int $collab_id) {

    global $account;

    try
    {
        $newId = $account->addAccount($username, $passwd, $group, $collab_id);
    }
    catch (Exception $e)
    {
        /* Something went wrong: echo the exception message and die */
        echo $e->getMessage();
        die();
    }

    return 'The new account ID is ' . $newId;
}

function editAccount($id, $newEmail, $newName, $newPass) {
    
    global $account;

     try
    {
        //$account->editAccount($id, $newEmail, $newName, $newPass, TRUE);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
        die();
    }
    
    echo 'Account edit successful.';
}

function deleteAccount($accountId) {
    
    global $account;

    try
    {
        $account->deleteAccount($accountId);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
        die();
    }

    echo 'Account delete successful.';
}

function login($user, $pass, $member) {
    
    global $account;

    $login = FALSE;

    try
    {
        $login = $account->login($user, $pass, $member);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
        die();
    }

    if ($login)
    {
        echo 'Authentication successful.';
        //echo 'Account ID: ' . $account->getId() . '<br>';
        //echo 'Account name: ' . $account->getName() . '<br>';
    }
    else
    {
        echo 'Authentication failed.';
    }
}

function sessionLogin() {
    
    global $account;

    $login = FALSE;

    try
    {
        $login = $account->sessionLogin();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
        die();
    }

    if ($login)
    {
        return 1;
        //echo 'Authentication successful.';
        //echo 'Account ID: ' . $account->getId() . '<br>';
        //echo 'Account name: ' . $account->getName() . '<br>';
    }
    else
    {
        return 0;
        //echo 'Authentication failed.';
    }
}

function logout() {
    
    global $account;

    try {
        $login = $account->sessionLogin();

        if ($login) {
                $account->logout();
        }
    }
    
    catch (Exception $e)
        {
            echo $e->getMessage();
            die();
        }

}

function closeAllSessions() {
    
    global $account;

        try {
        $login = $account->sessionLogin();

        if ($login) {
                $account->closeOtherSessions();
        }
    }
    catch (Exception $e)
        {
            echo $e->getMessage();
            die();
        }
}

//function buildAccountObject():bool {
//        global $pdo;
//        global $schema;
//}

function guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function changePass(int $id, string $passwd) {
    global $account;

    try
    {
        $res = $account->changePassword($id, $passwd);
    }
    catch (Exception $e)
    {
        /* Something went wrong: echo the exception message and die */
        echo $e->getMessage();
        die();
    }

    return 'Success!' . $res;
}
?>