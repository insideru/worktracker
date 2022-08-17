<?php
require_once './include/account.php';
require_once './include/elems.php';

global $account;
$account->sessionLogin();

echo '<pre>'; print_r($account->permissions); echo '</pre>';

?>