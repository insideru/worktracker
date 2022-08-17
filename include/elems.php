<?php
require_once 'db.php';
require_once 'account.php';

global $account;
$account->sessionLogin();

if ($account->authenticated || $_POST["action"]=="login") {} else 
{
    die();
}

function addAttendance(string $date, string $start, string $end, int $edit = NULL) {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;
    global $account;

    if (!isset($edit)) {
        $query = "INSERT INTO {$schema}.attendance (collab_id, date, start, end) VALUES (:collab_id, :date, :start, :end)";
        $values = array(':collab_id' => $account->getCollabID(), ':date' => date("Y-m-d", strtotime($date)), ':start' => $start, ':end' => $end);
    } else {
        $query = "UPDATE {$schema}.attendance SET start=:start, end=:end WHERE collab_id=:collab_id AND date=:date";
        $values = array(':collab_id' => $account->getCollabID(), ':date' => date("Y-m-d", strtotime($date)), ':start' => $start, ':end' => $end);
    }
    
    /* Execute the query */
    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    
    /* Return the new ID */
    return "Success:" . $pdo->lastInsertId();
}

function deleteTimesheetEntry (string $date) {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;
    global $account;

    $query = "DELETE FROM {$schema}.timesheets WHERE collab_id = :collab_id AND date = :date; SET @reset = 0; UPDATE {$schema}.timesheets SET id = @reset:= @reset + 1; ALTER TABLE {$schema}.timesheets AUTO_INCREMENT = 1;";
    $values = array(':collab_id' => $account->getCollabID(), ':date' => date("Y-m-d", strtotime($date)));
    
    /* Execute the query */
    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }
    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    
    /* Return the new ID */
    return "Success!";
}

function addReport (string $date, int $currID, int $currPhase, int $currMilestone, int $curProgress) {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;
    global $account;

    /* Insert query template */
    $query = "SELECT * FROM {$schema}.progress WHERE (project_id = :project_id) AND (phase_id = :phase_id) AND (milestone_id = :milestone_id) AND (date = :date)";
    
    /* Values array for PDO */
    $values = array(':date' => date("Y-m-d", strtotime($date)), ':project_id' => $currID, ':phase_id' => $currPhase, ':milestone_id' => $currMilestone);

    /* Execute the query */
    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }
    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }

    $fields=array();

	while ($row = $res->fetch(PDO::FETCH_ASSOC)) 
    {
		array_push($fields, $row);
	}

	if (count($fields)>0) {
        $query = "UPDATE {$schema}.progress SET collab_id = :collab_id, progress = :progress WHERE (project_id = :project_id) AND (phase_id = :phase_id) AND (milestone_id = :milestone_id) AND (date = :date)";
    } else {
        $query = "INSERT INTO {$schema}.progress (collab_id, project_id, phase_id, milestone_id, date, progress) VALUES (:collab_id, :project_id, :phase_id, :milestone_id, :date, :progress)";
    }
    
    /* Values array for PDO */
    $values = array(':collab_id' => $account->getCollabID(), ':date' => date("Y-m-d", strtotime($date)), ':project_id' => $currID, ':phase_id' => $currPhase, ':milestone_id' => $currMilestone, ':progress' => $curProgress);

    /* Execute the query */
    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }
    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    
    /* Return the new ID */
    echo "Success:" . $pdo->lastInsertId();
}

function addTimesheetEntry(string $date, int $project_id, int $phase_id, int $milestone_id, int $activity_id, float $time) {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;
    global $account;

    /* Insert query template */
    $query = 'INSERT INTO '.$schema.'.timesheets (collab_id, date, project_id, phase_id, milestone_id, activity_id, time) VALUES (:collab_id, :date, :project_id, :phase_id, :milestone_id, :activity_id, :time)';
    
    /* Values array for PDO */
    $values = array(':collab_id' => $account->getCollabID(), ':date' => date("Y-m-d", strtotime($date)), ':project_id' => $project_id, ':phase_id' => $phase_id, ':milestone_id' => $milestone_id, ':activity_id' => $activity_id, ':time' => $time);
    
    /* Execute the query */
    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }
    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    
    /* Return the new ID */
    return "Success:" . $pdo->lastInsertId();
}

function getTimesheets (string $date = NULL) {
	/* Global $pdo object */
    /** @var object $pdo */
	global $pdo;
	global $schema;
    global $account;

    if (!isset($date)) {
        $query = "SELECT * FROM {$schema}.timesheets WHERE (collab_id = :cid)";
        $values = array(':cid' => $account->getCollabID());
    } else {
        $query = "SELECT * FROM {$schema}.timesheets WHERE (collab_id = :cid) AND (date = '{$date}')";
        $values = array(':cid' => $account->getCollabID());
    }
	
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
		/* If there is a PDO exception, throw a standard exception */
		echo "Database error ".$e->getMessage();
	}

	$fields=array();

	while ($row = $res->fetch(PDO::FETCH_ASSOC)) 
    {
		array_push($fields, $row);
	}

	return $fields;
}

function getPontaje () {
	/* Global $pdo object */
    /** @var object $pdo */
	global $pdo;
	global $schema;
    global $account;

    $query = "SELECT * FROM {$schema}.attendance WHERE (collab_id = :cid)";
    $values = array(':cid' => $account->getCollabID());
	
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
		/* If there is a PDO exception, throw a standard exception */
		echo "Database error ".$e->getMessage();
	}

	$fields=array();

	while ($row = $res->fetch(PDO::FETCH_ASSOC)) 
    {
		array_push($fields, $row);
	}

	return $fields;
}

function deleteTimesheet (string $date) {
    /** @var object $pdo */
    global $pdo;
	global $schema;
    global $account;

	$query1 = 'DELETE FROM '.$schema.'.timesheets WHERE collab_id = :cid AND date = :date; DELETE FROM '.$schema.'.attendance WHERE collab_id = :cid AND date = :date';
	$values = array(':cid' => $account->getCollabID(), ':date' => date("Y-m-d", strtotime($date)));
	
	try
	{
		$res1 = $pdo->prepare($query1);
		$res1->execute($values);
        //$res2 = $pdo->prepare($query2);
		//$res2->execute($values);
	}
	catch (PDOException $e)
	{
		/* If there is a PDO exception, throw a standard exception */
		echo "Database error: ".$e->getMessage();
        die();
	}

    echo "Success!";
}

function verifyDate(string $date):bool {
	/* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
	global $schema;
    global $account;

	$query = 'SELECT date FROM '.$schema.'.attendance WHERE (collab_id = :cid)';
	$values = array(':cid' => $account->getCollabID());
	
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
	   /* If there is a PDO exception, throw a standard exception */
	   echo "Database error ".$e->getMessage();
	}
	
	while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
		$dbDate = $row['date'];
		//verificam daca se potriveste cookieul cu tokenul

		if ($dbDate==date("Y-m-d", strtotime($date))) {
			return FALSE;
			break;
		}
	}
	return TRUE;
}

function addHoliday(string $date, string $name) {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;

    /* Insert query template */
    $query = 'INSERT INTO '.$schema.'.holidays (date, name) VALUES (:date, :name)';
    
    /* Values array for PDO */
    $values = array(':date' => date("Y-m-d", strtotime($date)), ':name' => $name);
    
    /* Execute the query */
    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }
    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    
    /* Return the new ID */
    return "Success:" . $pdo->lastInsertId();
}

function getZileLibere() {
	/* Global $pdo object */
    /** @var object $pdo */
	global $pdo;
	global $schema;
    global $account;

	$query = 'SELECT zile_concediu, zile_report, zile_ramase FROM '.$schema.'.accounts WHERE collab_id = :cid';
	$values = array(':cid' => $account->getCollabID());
	
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
		/* If there is a PDO exception, throw a standard exception */
		echo "Database error ".$e->getMessage();
	}

	$row = $res->fetch(PDO::FETCH_ASSOC); 

	return $row;
}

function addDaysoff (string $startDate, string $endDate, int $number) {
	/* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;
    global $account;
	//update accounts set `zile_ramase` = `zile_concediu`-5 where guid="6f0c7834-4a68-433f-bc06-e8e1fd38d33a"

    /* Insert query template */
    $query = 'INSERT INTO '.$schema.'.daysoff (collab_id, startdate, enddate) VALUES (:collab_id, :startdate, :enddate); UPDATE '.$schema.'.accounts SET zile_ramase = zile_ramase - :number WHERE collab_id= :cid';
    
    /* Values array for PDO */
    $values = array(':collab_id' => $account->getCollabID(), ':startdate' => date("Y-m-d", strtotime($startDate)), ':enddate' => date("Y-m-d", strtotime($endDate)), ':number' => $number, ':cid' => $account->getCollabID());
    
    /* Execute the query */
    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }
    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    
    /* Return the new ID */
    return "Success:" . $pdo->lastInsertId();
}

function deleteDaysoff (string $startDate, int $number) {
	/* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;
    global $account;
	//update accounts set `zile_ramase` = `zile_concediu`-5 where guid="6f0c7834-4a68-433f-bc06-e8e1fd38d33a"

    /* Insert query template */
    $query = 'DELETE FROM '.$schema.'.daysoff WHERE collab_id = :collab_id AND startdate = :startdate; UPDATE '.$schema.'.accounts SET zile_ramase = zile_ramase + :number WHERE collab_id= :collab_id';
    
    /* Values array for PDO */
    $values = array(':collab_id' => $account->getCollabID(), ':startdate' => date("Y-m-d", strtotime($startDate)), ':number' => $number);
    
    /* Execute the query */
    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }
    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    
    /* Return the new ID */
    return "Success!";
}

function getDaysoff() {
    /** @var object $pdo */
    global $pdo;
	global $schema;
    global $account;

	$query = 'SELECT * FROM '.$schema.'.daysoff WHERE (collab_id = :cid)';
	$values = array(':cid' => $account->getCollabID());
	
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
		/* If there is a PDO exception, throw a standard exception */
		echo "Database error ".$e->getMessage();
	}

	$fields=array();

	while ($row = $res->fetch(PDO::FETCH_ASSOC)) 
    {
		array_push($fields, $row);
	}

	return $fields;
}

function getIDfromGUID (string $guid) {
	/* Global $pdo object */
    /** @var object $pdo */
	global $pdo;
	global $schema;

	$query = 'SELECT collab_id FROM '.$schema.'.accounts WHERE (guid = :guid)';
	$values = array(':guid' => $guid);
	
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
		/* If there is a PDO exception, throw a standard exception */
		echo "Database error ".$e->getMessage();
	}

	$row = $res->fetch(PDO::FETCH_ASSOC); 

	return $row['collab_id'];
}

function renameName (string $table, string $oldName, string $newName) {
	/* Global $pdo object */
    /** @var object $pdo */
	global $pdo;
	global $schema;

	$query = 'UPDATE '.$schema.'.'.$table.' SET name = :newName WHERE name = :oldName';
	$values = array(':newName' => $newName, ':oldName' => $oldName);
	
	try
	{
		$res = $pdo->prepare($query);
		$res->execute($values);
	}
	catch (PDOException $e)
	{
		/* If there is a PDO exception, throw a standard exception */
		echo "Database error ".$e->getMessage();
		exit();
	}
	echo "Success!";
}

function getActivities() {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;

    $query = 'SELECT * FROM '. $schema . '.activities ORDER BY name ASC';

    try
    {
        $res = $pdo->prepare($query);
        $res->execute();
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    $fields=array();

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        array_push($fields, $row);
    }
    return $fields;
}

function getProjectProgressReport(int $proj, int $phase, int $milestone) {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;

    $query = "SELECT progress FROM ${schema}.progress WHERE (project_id = {$proj} AND phase_id = {$phase} AND milestone_id = {$milestone}) ORDER BY date DESC LIMIT 1";

    try
    {
        $res = $pdo->prepare($query);
        $res->execute();
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    $retVal = 0;

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $retVal = $row['progress'];
    }
    return $retVal;
}

function getProjects() {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;
    global $account;
    $account->sessionLogin();

    $query = 'SELECT * FROM '. $schema . '.projects ORDER BY active DESC, start_date ASC';

    try
    {
        $res = $pdo->prepare($query);
        $res->execute();
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    $fields=array();

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        if (($account->permissions['external'] && $row['external']) || $account->permissions['admin'] || !$row['external']) {
            array_push($fields, $row);
        }
    }
    return $fields;
}

function getProjectInfo(int $project_id) {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;

    if ($project_id == 0) {
        $query = 'SELECT * FROM '. $schema . '.project_info';
        $values = array();
    } else {
        $query = 'SELECT * FROM '. $schema . '.project_info WHERE proj_id = :proj_id';
        $values = array(':proj_id' => $project_id);
    }

    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    $fields=array();

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        array_push($fields, $row);
    }
    return $fields;
}

function getProjectPhases(int $project_id) {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;

    if ($project_id == 0) {
        $query = 'SELECT * FROM '. $schema . '.project_phases';
        $values = array();
    } else {
        $query = 'SELECT * FROM '. $schema . '.project_phases WHERE proj_id = :proj_id';
        $values = array(':proj_id' => $project_id);
    }

    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    $fields=array();

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        array_push($fields, $row);
    }
    return $fields;
}

function getProjectMilestones(int $project_id) {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;

    if ($project_id == 0) {
        $query = 'SELECT * FROM '. $schema . '.project_milestones';
        $values = array();
    } else {
        $query = 'SELECT * FROM '. $schema . '.project_milestones WHERE proj_id = :proj_id';
        $values = array(':proj_id' => $project_id);
    }

    try
    {
        $res = $pdo->prepare($query);
        $res->execute($values);
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    $fields=array();

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        array_push($fields, $row);
    }
    return $fields;
}

function getHolidays() {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;

    $query = 'SELECT * FROM '. $schema . '.holidays ORDER BY date ASC';

    try
    {
        $res = $pdo->prepare($query);
        $res->execute();
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    $fields=array();

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        array_push($fields, $row);
    }
    return $fields;
}

function getCollabs() {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;

    $query = 'SELECT * FROM '. $schema . '.collaborators ORDER BY name ASC';

    try
    {
        $res = $pdo->prepare($query);
        $res->execute();
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    $fields=array();

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        array_push($fields, $row);
    }
    return $fields;
}

function getProgress(int $project_id = NULL) {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;

    if (!isset($project_id)) {
        $query = "SELECT * FROM $schema.progress ORDER BY date ASC";
    } else {
        $query = "SELECT * FROM $schema.progress WHERE project_id = $project_id ORDER BY date ASC";
    }
    try
    {
        $res = $pdo->prepare($query);
        $res->execute();
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    $fields=array();

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        array_push($fields, $row);
    }
    return $fields;
}

function getSettings() {
    /* Global $pdo object */
    /** @var object $pdo */
    global $pdo;
    global $schema;

    $query = "SELECT name, value FROM $schema.settings";
    try
    {
        $res = $pdo->prepare($query);
        $res->execute();
    }

    catch (PDOException $e)
    {
        /* If there is a PDO exception, throw a standard exception */
        echo "Database error".$e->getMessage();
        die();
    }
    $fields=array();

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        array_push($fields, $row);
    }
    return $fields;
}

?>