<?php
/**************************VARIABLES*********************************************/
$APPLICATION_NAME = 'XLSX-PG-DataTrain';//define application
$access_FLAG = false;
$perm = '';
$ver = 'v.1.0';
$basedirCLN = '';
$filesize_quota = 52428800;//50mb = 50 * 1024 * 1024 b
/**************************VARIABLES*********************************************/
$tmp_link = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
require_once('php/showerrors.php');//show php errors
require_once('php/config.php');// session variables define
require_once('php/srvcommon.php'); // some classes to ease up things
$tmp_link = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
$app = New ApplicationSettings($_SESSION['cn'],$_SESSION['title'],$APPLICATION_NAME);//used for header draw
/* +++ LOCAL SYSTEM to provide access; use if you have any - otherwise just define $access_FLAG and $perm variables */
$dbconn = New DBConnection($_SESSION['db_server'],$_SESSION['db_name'],$_SESSION['db_user'],$_SESSION['db_pass']);
$app_access = New ApplicationAccess($_SESSION['uName'],$APPLICATION_NAME,$dbconn->getConnection());
/* +++ */
$role_array = $app_access->getRoles();
foreach($role_array as $curRole) {
    $access_FLAG = true;
    // echo $curRole;
    $perm .= $curRole.", ";
}
$perm = ($perm == '') ? $perm : substr($perm, 0, -2);//removing ', '

//
function pg_q($conn, $sql, $id = '') {
    $res = pg_query($conn, $sql) or die('query failed '.$id.'<br>'.$sql.'<br>'.pg_last_error_isy());
    return $res;
}
