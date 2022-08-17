<?php
require_once './include/account.php';
require_once './include/elems.php';
require_once './include/admin_elems.php';

global $account;
try {
    $account->sessionLogin();
} catch (Exception $e) {
}
$isLogin = false;
if (isset($_POST['action'])) {
    if ($_POST['action']=='login') { $isLogin = true; }
}
if (!$account->authenticated && !$isLogin) { die (); }

if (isset($_POST['action'])) {
    if ($_POST['action']=="login") {
        if (!empty($_POST["email"])) {
            return login($_POST["email"], $_POST["password"], $_POST["remember"]);
        } else {
            die("no email");
        }
    }

    if ($_POST["action"]=="addTimesheet") {
        $ziua = date("Y-m-d", strtotime($_POST["ziua"]));
        $oraVenire = $_POST["oraVenire"];
        $oraPlecare = $_POST["oraPlecare"];
        $timesheet = $_POST["timesheet"];
        $editTimesheet = $_POST['edit'];
        if (!verifyDate($ziua) && !$editTimesheet) {
            echo "Failure:Pentru data selectata exista deja un pontaj!";
            return 0;
        }
        if (!$editTimesheet) {
            addAttendance($ziua, $oraVenire, $oraPlecare);
        } else {
            addAttendance($ziua, $oraVenire, $oraPlecare, 1);
            deleteTimesheetEntry($_POST["ziua"]);
        }
        foreach ($timesheet as $timesheetProject) {
            $currID = $timesheetProject['id'];
            $currPhase = $timesheetProject['phase'];
            $currMilestone = $timesheetProject['milestone'];
            foreach($timesheetProject as $key => $value) {
                if ($key!="id" && $key!="phase" && $key!="milestone" && $value!=0.0) {
                    addTimesheetEntry($ziua, $currID, $currPhase, $currMilestone, $key, $value);
                }
            }
        }
        echo "Success:Pontaj adaugat cu succes!";
    }

    if ($_POST["action"]=="addDaysoff") {
        echo addDaysoff($_POST["startdate"], $_POST["enddate"], $_POST["number"]);
    }

    if ($_POST["action"]=="reName") {
        echo renameName($_POST["table"], $_POST["oldname"], $_POST["newname"]);
    }

    if ($_POST["action"]=="getTimesheets") {
        $response["timesheets"] = getTimesheets();
        $response["daysoff"] = getDaysoff();
        $response["holidays"] = getHolidays();
        $response["projects"] = getProjects();
        $response["activities"] = getActivities();
        $response['phases'] = getProjectPhases(0);
        $response['milestones'] = getProjectMilestones(0);
        echo json_encode($response);

    }

    if ($_POST["action"]=="deleteTimesheets") {
        deleteTimesheet($_POST["date"]);
    }

    if ($_POST["action"]=="logout") {
        logout();
        echo "logged out";
    }

    if ($_POST["action"]=="addToDB") {
        switch ($_POST['type']) {
            case "addProjCategory":
                echo addProjCat($_POST['name']);
                break;
            case "addCollabCategory":
                echo addCollabCat($_POST['name']);
                break;
            case "addClient":
                echo addClient($_POST['name']);
                break;
            case "addCollab":
                echo addCollab($_POST['name'], $_POST['category']);
                break;
            case "addProject":
                //echo addProject($_POST['name'], $_POST['category'], $_POST['client']);
                if ((int)$_POST['proj_id'] != 0) {
                    $res = addProject(json_decode($_POST['info']), (int)$_POST['proj_id']);
                    $id = $_POST['proj_id'];
                } else {
                    $res = addProject(json_decode($_POST['info']));
                    $id = substr($res,8);
                    if (!is_numeric((int)$id)) {
                        echo $id - $res;
                        break;
                    }
                }
                $details = strlen($_POST['details'])==1?(int)$_POST['details']:json_decode($_POST['details']);
                if ($details !== 0) {
                    $res = addProjectDetails((int)$id, $details);
                    if (substr($res, 0, 8) != "Success!") {
                        echo $res;
                        break;
                    }
                }
                $phases = strlen($_POST['phases'])==1?(int)$_POST['phases']:json_decode($_POST['phases']);
                if ($phases !== 0) {
                    $res = addProjectPhases((int)$id, $phases);
                    if (substr($res, 0, 8) != "Success!") {
                        echo $res;
                        break;
                    }
                }
                $milestones = strlen($_POST['milestones'])==1?(int)$_POST['milestones']:json_decode($_POST['milestones']);
                if ($milestones !== 0) {
                    $res = addProjectMilestones((int)$id, $milestones);
                    if (substr($res, 0, 8) != "Success!") {
                        echo $res;
                        break;
                    }
                }
                echo("Success:$id");
                break;
            case "addActivity":
                echo addActivity($_POST['name'], $_POST['category']);
                break;
            case "addHoliday":
                echo addHoliday($_POST["data"], $_POST['name']);
                break;
        }
    }

    if ($_POST["action"]=="saveTemplate") {
        echo saveTemplate((int)$_POST["type"], $_POST["name"], $_POST["data"]);
    }

    if ($_POST["action"]=="deleteTemplate") {
        echo deleteTemplate((int)$_POST["type"], $_POST["name"]);
    }

    if ($_POST["action"]=="changeProjState") {
        echo changeProjectState((int)$_POST["proj_id"]);
    }

    if ($_POST["action"]=="changeProjExternal") {
        echo changeProjExternal((int)$_POST["proj_id"]);
    }

    if ($_POST["action"]=="setProjectBudget") {
        echo setProjectBudget((int)$_POST["proj_id"], (int)$_POST['new_budget']);
    }

    if ($_POST["action"]=="setProjectDeadline") {
        echo setProjectDeadline((int)$_POST["proj_id"], $_POST['deadline']);
    }

    if ($_POST["action"]=="setProjectStartDate") {
        echo setProjectStartDate((int)$_POST["proj_id"], $_POST['startdate']);
    }

    if ($_POST["action"]=="changeUserState") {
        echo changeUserState((int)$_POST["user_id"]);
    }

    if ($_POST["action"]=="changePass") {
        echo changePass((int)$_POST["userid"], $_POST["newpass"]);
    }

    if ($_POST["action"]=="changePermissionItem") {
        echo changePermissionItem((int)$_POST["row"], $_POST["column"]);
    }

    if ($_POST["action"]=="addPermissionsGroup") {
        echo addPermissionsGroup();
    }

    if ($_POST["action"]=="addNewUser") {
        $response = array();
        //$response["newAccount"] = (object) ['message' => newAccount($_POST["username"], $_POST["passwd"], $_POST["group"], $_POST["collab_id"])];
        $response["newAccount"] = newAccount($_POST["username"], $_POST["passwd"], $_POST["group"], $_POST["collab_id"]);
        $response["accounts"] = getAccounts();
        echo json_encode($response);
    }

    if ($_POST["action"]=="getPontajInfo") {
        $response = array();
        $response['attendance'] = getAttendance($_POST['date']);
        $response['timesheet'] = getTimesheets($_POST['date']);
        echo json_encode($response);
    }

    if ($_POST["action"]=="changeAccountDetails") {
        echo changeAccountDetails($_POST['id'], $_POST['column'], $_POST['value']);
    }

    if ($_POST["action"]=="getPhasesAndMilestones") {
        $response = array();
        $response['phases'] = getProjectPhases((int)$_POST['proj_id']);
        $response['milestones'] = getProjectMilestones((int)$_POST['proj_id']);
        echo json_encode($response);
    }

    if ($_POST["action"]=="getPhases") {
        $response = getProjectPhases((int)$_POST['proj_id']);
        echo json_encode($response);
    }

    if ($_POST["action"]=="getMilestones") {
        $response = getProjectMilestones((int)$_POST['proj_id']);
        echo json_encode($response);
    }

    if ($_POST["action"]=="addSalary") {
        echo addSalary((int)$_POST['collab_id'], (int)$_POST['hourly'], (int)$_POST['monthly'], $_POST['date']);
    }

    if ($_POST["action"]=="addReport") {
        $reports = $_POST['report'];
        foreach ($reports as $report) {
            $currID = $report['id'];
            $currPhase = $report['phase'];
            $currMilestone = $report['milestone'];
            $curProgress = $report['progress'];
            echo addReport(date("Y-m-d", strtotime($_POST["date"])), $currID, $currPhase, $currMilestone, $curProgress);
        }
    }

    if ($_POST["action"]=="modifySalary") {
        echo modifySalary((int)$_POST['id'], (int)$_POST['hourly'], (int)$_POST['monthly'], $_POST['date']);
    }

    if ($_POST["action"]=="deleteDayoff") {
        echo deleteDaysoff($_POST['date'], (int)$_POST['days']);
    }
}

if (isset($_REQUEST["r"])) {

    if ($_REQUEST["r"]=="getProjDetails") {
        $response = array();
        $response["basic"] = getProjectBasic($_REQUEST["proj"]);
        $response["info"] = getProjectInfo($_REQUEST["proj"]);
        $response["phases"] = getProjectPhases($_REQUEST["proj"]);
        $response["milestones"] = getProjectMilestones($_REQUEST["proj"]);
        echo json_encode($response);
    }

    if ($_REQUEST["r"]=="getProjReports") {
        echo getProjectProgressReport($_REQUEST["proj"], $_REQUEST["phase"], $_REQUEST["milestone"]);
    }

    if ($_REQUEST["r"]=="init") {
        //trimitem toate datele de initalizare tabele
        $response = array();
        if ($account->permissions['admin']) {
            $response["clients"] = getClients();
            $response["collabCats"] = getCollabCat();
            $response["projCats"] = getProjCat();
            $response["collabs"] = getCollabs();
            $response["projects"] = getProjects();
            $response["activities"] = getActivities();
            $response["holidays"] = getHolidays();
            $response["accounts"] = getAccounts();
            $response["timesheets"] = getAllTimesheets();
            $response["salaries"] = getSalaries();
            $response["permissions"] = getPermissions();
            $response["daysoff"] = getAllDaysOff();
            $response["templates"] = getTemplates();
            $response["attendance"] = getAttendance();
            $response['phases'] = getProjectPhases(0);
            $response['milestones'] = getProjectMilestones(0);
            $response['progress'] = getProgress();
        } else {
            $response["collabs"] = getCollabs();
            $response["projects"] = getProjects();
            $response["activities"] = getActivities();
            $response["timesheets"] = getAllTimesheets();
            $response['phases'] = getProjectPhases(0);
            $response['milestones'] = getProjectMilestones(0);
            $response['progress'] = getProgress();
        }
        echo json_encode($response);
    }

    if ($_REQUEST["r"]=="holidays") {
        $response = array();
        $response["holidays"] = getHolidays();
        $response["daysoff"] = getDaysoff();
        $response["pontaje"] = getPontaje();
        echo json_encode($response);
    }

    if ($_REQUEST["r"]=="concediu") {    
        $response = array();
        $response["holidays"] = getHolidays();
        $response["concediu"] = getZileLibere();
        echo json_encode($response);
    }

    if ($_REQUEST["r"]=="deleteHoliday") {    
        $date=$_GET['date'];
        echo deleteHoliday($date);
    }

    if ($_REQUEST["r"]=="getSettings") {
        echo json_encode(getSettings());
    }
}