<?php

class Account {
    private $id;
	private $collabID;
    private $group;
	private $username;
    public $authenticated;
    private $token;
	public $permissions;
	private $firstName;
	private $lastName;

    public function __construct() {
        $this->id = NULL;
        $this->group = NULL;
		$this->username = NULL;
        $this->authenticated = FALSE;
        $this->token = NULL;
		$this->collabID = NULL;
		$this->permissions = NULL;
		$this->firstName = NULL;
		$this->lastName = NULL;
    }

    public function __destruct() {
		
	}

public function addAccount(string $username, string $passwd, int $group, int $collab_id): int {
	/* Global $pdo object */
	/** @var object $pdo */
    global $pdo;
    global $schema;
	
	/* Trim the strings to remove extra spaces */
	$username = trim($username);
	$passwd = trim($passwd);
	
	/* Check if the user name is valid. If not, throw an exception */
	if (!$this->isNameValid($username))
	{
		throw new Exception('Usernameul trebuie sa aiba cel putin 4 caractere alfanumerice!');
	}
	
	/* Check if the password is valid. If not, throw an exception */
	if (!$this->isPasswdValid($passwd))
	{
		throw new Exception('Parola trebuie sa aiba cel putin 6 caractere!');
	}
	
	/* Check if an account having the same name already exists. If it does, throw an exception */
	if (!is_null($this->getIdFromUsername($username)))
	{
		throw new Exception('Username-ul este deja inregistrat!');
	}
	
	/* Finally, add the new account */
	
	/* Insert query template */
	$query = 'INSERT INTO '.$schema.'.accounts (account_group, account_username, account_passwd, collab_id) VALUES (:group, :username, :passwd, :collab_id)';
	
	/* Password hash */
	$hash = password_hash($passwd, PASSWORD_DEFAULT);
	
	/* Values array for PDO */
	$values = array(':group' => $group, ':username' => $username, ':passwd' => $hash, ':collab_id' => $collab_id);
	
	/* Execute the query */
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
	   /* If there is a PDO exception, throw a standard exception */
	   throw new Exception($e->getMessage());
	}
	
	/* Return the new ID */
    return $pdo->lastInsertId();
}

/* A sanitization check for the account username */
public function isNameValid(string $name): bool {
	/* Initialize the return variable */
	$valid = TRUE;
	
	/* Example check: the length must be between 8 and 16 chars */
	$len = mb_strlen($name);
	
	if (($len < 4) || ($len > 40))
	{
		$valid = FALSE;
	}
	
	/* You can add more checks here 
	if (!ctype_alnum($name)) {
		$valid = FALSE;
	}
	*/
	
	return $valid;
}

/* A sanitization check for the account password */
public function isPasswdValid(string $passwd): bool
{
	/* Initialize the return variable */
	$valid = TRUE;
	
	/* Example check: the length must be between 8 and 16 chars */
	$len = mb_strlen($passwd);
	
	if (($len < 6) || ($len > 40))
	{
		$valid = FALSE;
	}
	
	/* You can add more checks here */
	
	return $valid;
}

/* Returns the account id having $name as name, or NULL if it's not found */
public function getIdFromUsername(string $username): ?int
{
	/* Global $pdo object */
	/** @var object $pdo */
    global $pdo;
    global $schema;
	
	/* Since this method is public, we check $name again here */
	if (!$this->isNameValid($username))
	{
		throw new Exception('Invalid user name');
	}
	
	/* Initialize the return value. If no account is found, return NULL */
	$id = NULL;
	
	/* Search the ID on the database */
	$query = 'SELECT account_id FROM '.$schema.'.accounts WHERE (account_username = :username)';
	$values = array(':username' => $username);
	
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
	   /* If there is a PDO exception, throw a standard exception */
	   throw new Exception($e->getMessage());
	}
	
	$row = $res->fetch(PDO::FETCH_ASSOC);
	
	/* There is a result: get it's ID */
	if (is_array($row))
	{
		$id = intval($row['account_id'], 10);
	}
	
	return $id;
}

public function changePassword(int $id, string $passwd)
{
	/* Global $pdo object */
	/** @var object $pdo */
    global $pdo;
    global $schema;
	
	/* Trim the strings to remove extra spaces */
	$passwd = trim($passwd);
	
	/* Check if the password is valid. */
	if (!$this->isPasswdValid($passwd))
	{
		throw new Exception('Parola trebuie sa aiba cel putin 6 caractere!');
	}
	
	/* Finally, edit the account */
	
	/* Edit query template */
	$query = 'UPDATE '.$schema.'.accounts SET account_passwd = :passwd WHERE account_id = :id';
	
	/* Password hash */
	$hash = password_hash($passwd, PASSWORD_DEFAULT);
	
	/* Int value for the $enabled variable (0 = false, 1 = true) */
	//$intEnabled = $enabled ? 1 : 0;
	
	/* Values array for PDO */
	$values = array(':passwd' => $hash, ':id' => $id);
	
	/* Execute the query */
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
	   /* If there is a PDO exception, throw a standard exception */
	   throw new Exception($e->getMessage());
	}
}

public function changeGroup(int $id, int $newGroup)
{
	/* Global $pdo object */
	/** @var object $pdo */
    global $pdo;
    global $schema;
	
	/* Check if the ID is valid */
	if (!$this->isIdValid($id))
	{
		throw new Exception('Invalid account ID');
	}
	/* Finally, edit the account */
	
	/* Edit query template */
	$query = 'UPDATE '.$schema.'.accounts SET account_group = :group WHERE account_id = :id';
	
	/* Values array for PDO */
	$values = array(':group' => $newGroup, ':id' => $id);
	
	/* Execute the query */
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
	   /* If there is a PDO exception, throw a standard exception */
	   throw new Exception($e->getMessage());
	}
}

public function changeEnabled(int $id, bool $enabled)
{
	/* Global $pdo object */
	/** @var object $pdo */
    global $pdo;
    global $schema;
	
	/* Trim the strings to remove extra spaces 
	$email = trim($email);
	$name = trim($name);
	$passwd = trim($passwd); */
	
	/* Check if the ID is valid */
	if (!$this->isIdValid($id))
	{
		throw new Exception('Invalid account ID');
	}
	/* Finally, edit the account */
	
	/* Edit query template */
	$query = 'UPDATE '.$schema.'.accounts SET account_enabled = :enabled WHERE account_id = :id';
	
	/* Int value for the $enabled variable (0 = false, 1 = true) */
	$intEnabled = $enabled ? 1 : 0;
	
	/* Values array for PDO */
	$values = array(':enabled' => $intEnabled, ':id' => $id);
	
	/* Execute the query */
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
	   /* If there is a PDO exception, throw a standard exception */
	   throw new Exception($e->getMessage());
	}
}
public function isIdValid(int $id): bool
{
	/* Initialize the return variable */
	$valid = TRUE;
	
	/* Example check: the ID must be between 1 and 1000000 */
	
	/*if (($id < 1) || ($id > 1000000))
	{
		$valid = FALSE;
	}*/
	
	/* You can add more checks here */
	
	return $valid;
}

public function deleteAccount(int $id)
{
	/* Global $pdo object */
	/** @var object $pdo */
    global $pdo;
    global $schema;
	
	/* Check if the ID is valid */
	if (!$this->isIdValid($id))
	{
		throw new Exception('Invalid account ID');
	}
	
	/* Query template */
	$query = 'DELETE FROM '.$schema.'.accounts WHERE account_id = :id';
	
	/* Values array for PDO */
	$values = array(':id' => $id);
	
	/* Execute the query */
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
	   /* If there is a PDO exception, throw a standard exception */
	   throw new Exception('Database query error');
	}

	/* Delete the Sessions related to the account */
	$query = 'DELETE FROM '.$schema.'.sessions WHERE (account_id = :id)';

	/* Values array for PDO */
	$values = array(':id' => $id);

	/* Execute the query */
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
	   /* If there is a PDO exception, throw a standard exception */
	   throw new Exception($e->getMessage());
	}
}

public function login(string $username, string $passwd, int $remember): bool
{
	/* Global $pdo object */
	/** @var object $pdo */
    global $pdo;
    global $schema;	
	
	/* Trim the strings to remove extra spaces */
	$username = trim($username);
	$passwd = trim($passwd);
	
	/* Check if the user name is valid. If not, return FALSE meaning the authentication failed */
	if (!$this->isNameValid($username))
	{
		return FALSE;
	}
	
	/* Check if the password is valid. If not, return FALSE meaning the authentication failed */
	if (!$this->isPasswdValid($passwd))
	{
		return FALSE;
	}
	
	/* Look for the account in the db. Note: the account must be enabled (account_enabled = 1) */
	$query = 'SELECT * FROM '.$schema.'.accounts WHERE (account_username = :username) AND (account_enabled = 1)';
	
	/* Values array for PDO */
	$values = array(':username' => $username);
	
	/* Execute the query */
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
	   /* If there is a PDO exception, throw a standard exception */
	   throw new Exception($e->getMessage());
	}
	
	$row = $res->fetch(PDO::FETCH_ASSOC);
	
	/* If there is a result, we must check if the password matches using password_verify() */
	if (is_array($row))
	{
		if (password_verify($passwd, $row['account_passwd']))
		{
			/* Authentication succeeded. Set the class properties (id and group) */
			$this->id = intval($row['account_id']);
			$this->group = intval($row['account_group']);
			$this->collabID = intval($row['collab_id']);
			$this->username = $username;
			$this->authenticated = TRUE;
			/* Register the current Sessions on the database */
			$this->registerLoginSession();

			$current_time = time();
			$current_date = date("Y-m-d H:i:s", $current_time);
			// Set Cookie expiration for 1 month
			$cookie_expiration_time = $current_time + (30 * 24 * 60 * 60);  // for 1 month

			/* Daca e setat remember */
			if ($remember==1) {

	            $random_token = $this->getToken(16);
	            setcookie("wrkTracker", $random_token, $cookie_expiration_time);
	            
	            $random_token_hash = password_hash($random_token, PASSWORD_DEFAULT);

	            //echo $random_token . " || " . $random_token_hash;
	            
	            $expiry_date = date("Y-m-d H:i:s", $cookie_expiration_time);
	            
	            // Insert new token
				$query = 'UPDATE '.$schema.'.sessions SET account_token = :token WHERE (account_id = :id) AND (session_id = :sid)';
				$values = array(':token' => $random_token_hash, ':id' => $this->id, ':sid' => session_id());
				
				/* Execute the query */
				try
				{
					$res = $pdo->prepare($query);
					$res->execute($values);
				}
				catch (PDOException $e)
				{
				   /* If there is a PDO exception, throw a standard exception */
				   throw new Exception($e->getMessage());
				}
			}
					
			/* Finally, Return TRUE */
			return TRUE;
		}
	}
	
	/* If we are here, it means the authentication failed: return FALSE */
	return FALSE;
}

private function registerLoginSession()
{
	/* Global $pdo object */
	/** @var object $pdo */
	global $pdo;
    global $schema;
    
	/* Check that a Session has been started */
	if (session_status() == PHP_SESSION_ACTIVE)
	{
		/* 	Use a REPLACE statement to:
			- insert a new row with the session id, if it doesn't exist, or...
			- update the row having the session id, if it does exist.
		*/
		$query = 'REPLACE INTO '.$schema.'.sessions (session_id, account_id, login_time) VALUES (:sid, :accountId, NOW())';
		$values = array(':sid' => session_id(), ':accountId' => $this->id);
		
		/* Execute the query */
		try
		{
			$res = $pdo->prepare($query);
			$res->execute($values);
		}
		catch (PDOException $e)
		{
		   /* If there is a PDO exception, throw a standard exception */
		   throw new Exception($e->getMessage());
		}
	}
}

public function sessionLogin(): bool
{
	/* Global $pdo object */
	/** @var object $pdo */
    global $pdo;
    global $schema;
	
	/* Check that the Session has been started */
	if (session_status() == PHP_SESSION_ACTIVE)
	{
		/* 
			Query template to look for the current session ID on the account_sessions table.
			The query also makes sure the Session is not older than 30 days
		*/
		$query = 'SELECT * FROM '.$schema.'.sessions, '.$schema.'.accounts, '.$schema.'.permissions, '.$schema.'.collaborators WHERE (sessions.session_id = :sid) ' . 
		'AND (sessions.login_time >= (NOW() - INTERVAL 30 DAY)) AND (sessions.account_id = accounts.account_id) ' . 
		'AND (accounts.account_enabled = 1) AND (permissions.id = accounts.account_group) AND (collaborators.id = accounts.collab_id)';
		
		/* Values array for PDO */
		$values = array(':sid' => session_id());
		
		/* Execute the query */
		try
		{
			$res = $pdo->prepare($query);
			$res->execute($values);
		}
		catch (PDOException $e)
		{
		   /* If there is a PDO exception, throw a standard exception */
		   throw new Exception($e->getMessage());
		}
		
		$row = $res->fetch(PDO::FETCH_ASSOC);
		//echo json_encode($row) . '<BR>';
		
		if (is_array($row))
		{
			$this->id = intval($row['account_id']);
			//echo $this->id . '<BR>';
			$this->group = intval($row['account_group']);
			//echo $this->group . '<BR>';
			$this->username = $row['account_username'];
			//echo $this->username . '<BR>';
			$this->authenticated = TRUE;
			$this->collabID = intval($row['collab_id']);
			//echo $this->collabID . '<BR>';
			$this->firstName = explode(" ", $row['name'])[0];
			$this->lastName = explode(" ", $row['name'])[1];
			$this->permissions = array('admin' => (int)$row['admin'], 'bonus' => (int)$row['bonus'], 'external' => (int)$row['external'], 'holiday' => (int)$row['holiday'], 'timesheet' => (int)$row['timesheet'], 'raport' => (int)$row['raport']);
			return TRUE;
		}

		//daca nu e sesiune, poate e cookie
		if (!empty($_COOKIE["wrkTracker"])) {
			//exista cookie, sa vedem daca e ce trebuie
			$accountToken = $_COOKIE["wrkTracker"];

			$query = 'SELECT * FROM '.$schema.'.sessions';
			
			/* Values array for PDO */
			//$values = array(':account_token' => $accountToken);

			try
			{
				$res = $pdo->prepare($query);
				$res->execute();
			}

			catch (PDOException $e)
			{
			   /* If there is a PDO exception, throw a standard exception */
			   throw new Exception('Database query error');
			}

			//iteram prin toate sesiunile
			while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
 				$hashedToken = $row['account_token'];
 				//verificam daca se potriveste cookieul cu tokenul
 				if (password_verify($accountToken, $hashedToken)) {
 					//modificam sesiunea
 					$query = 'UPDATE '.$schema.'.sessions SET session_id = :sid WHERE account_token = :stk';
					try {
						$res = $pdo->prepare($query);
						$values = array(':sid' => session_id(), ':stk' => $hashedToken);
						$res->execute($values);
					}

					catch (PDOException $e) {
						throw new Exception($e->getMessage());
					}
					//reincercam loginul cu sesiunea noua modificata
					if ($this->sessionLogin()) {
						return TRUE;
					}
					break;
 				}
			}
		}
	}
	
	/* If we are here, the authentication failed */
	return FALSE;
}

public function saveSession() {
	
}

public function logout()
{
	/* Global $pdo object */
	/** @var object $pdo */
    global $pdo;
    global $schema;	
	
	// If there is no logged in user, do nothing 
	if (is_null($this->id))
	{
		return;
	}
	
	/* Reset the account-related properties */
	$this->id = NULL;
	$this->username = NULL;
	$this->group = NULL;
	$this->authenticated = FALSE;
	$this->collabID = NULL;
	$this->firstName = NULL;
	$this->lastName = NULL;
	$this->permissions = NULL;
	
	/* If there is an open Session, remove it from the account_sessions table */
	if (session_status() == PHP_SESSION_ACTIVE)
	{
		/* Delete query */
		$query = 'DELETE FROM '.$schema.'.sessions WHERE (session_id = :sid)';
		
		/* Values array for PDO */
		$values = array(':sid' => session_id());
		
		/* Execute the query */
		try
		{
			$res = $pdo->prepare($query);
			$res->execute($values);
		}
		catch (PDOException $e)
		{
		   /* If there is a PDO exception, throw a standard exception */
		   throw new Exception($e->getMessage());
		}

		if (isset($_COOKIE['wrkTracker'])) {
    		unset($_COOKIE['wrkTracker']);
    		setcookie('wrkTracker', '', time() - 3600, '/'); // empty value and old timestamp
		}
	}
}

public function isAuthenticated(): bool
{
	return $this->authenticated;
}

public function getId()
{
	return $this->id;
}

public function getCollabID()
{
	return $this->collabID;
}

public function getGroup()
{
	return $this->group;
}

public function getEmail(): string
{
	return $this->email;
}

public function getFirstName() {
	return $this->firstName;
}

public function getLasttName() {
	return $this->lastName;
}

public function closeOtherSessions()
{
	/* Global $pdo object */
	/** @var object $pdo */
    global $pdo;
    global $schema;
	
	/* If there is no logged in user, do nothing */
	if (is_null($this->id))
	{
		return;
	}
	
	/* Check that a Session has been started */
	if (session_status() == PHP_SESSION_ACTIVE)
	{
		/* Delete all account Sessions with session_id different from the current one */
		$query = 'DELETE FROM '.$schema.'.sessions WHERE (session_id != :sid) AND (account_id = :account_id)';
		
		/* Values array for PDO */
		$values = array(':sid' => session_id(), ':account_id' => $this->id);
		
		/* Execute the query */
		try
		{
			$res = $pdo->prepare($query);
			$res->execute($values);
		}
		catch (PDOException $e)
		{
		   /* If there is a PDO exception, throw a standard exception */
		   throw new Exception($e->getMessage());
		}

		if (isset($_COOKIE['wrkTracker'])) {
    		unset($_COOKIE['wrkTracker']);
    		setcookie('wrkTracker', '', time() - 3600, '/'); // empty value and old timestamp
		}
	}
}

public function getToken($length)
{
    $genToken = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
    $codeAlphabet .= "0123456789";
    $max = strlen($codeAlphabet) - 1;
    for ($i = 0; $i < $length; $i ++) {
        $genToken .= $codeAlphabet[$this->cryptoRandSecure(0, $max)];
    }
    return $genToken;
}

public function cryptoRandSecure($min, $max)
{
    $range = $max - $min;
    if ($range < 1) {
        return $min; // not so random...
    }
    $log = ceil(log($range, 2));
    $bytes = (int) ($log / 8) + 1; // length in bytes
    $bits = (int) $log + 1; // length in bits
    $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
    do {
        $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
        $rnd = $rnd & $filter; // discard irrelevant bits
    } while ($rnd >= $range);
    return $min + $rnd;
}
}
?>