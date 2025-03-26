<?php
require_once('datatrain_permissions.php');

$typ = 	isset($_POST['typ'])?$_POST['typ']:die("no type defined!");
$str = 	isset($_POST['str'])?$_POST['str']:die("no str defined!");
switch($typ) {
    case 'ins':
        $strings_arr = explode("%%@@%%", $str);//parsing str - [0] vals [1] cols [2] tbl [3] db name
        $curr_conn = New DBConnection($_SESSION['db_server'],$strings_arr[3],$_SESSION['db_user'],$_SESSION['db_pass']);
        $conn = $curr_conn->getConnection();
        $sql = "insert into ".$strings_arr[2]."(".$strings_arr[1].") VALUES ".$strings_arr[0].";"; 
        $quer = pg_q( $conn, $sql);
        break;
    case 'upd':
        $main_q_arr = explode('__@@%@@__', $str);//parsing str - [0] update cols [1] tbl [2] db
        $strings_arr = explode('@%@@%@', $main_q_arr[0]); //parsing each row for update per row
        $curr_conn = New DBConnection($_SESSION['db_server'],$main_q_arr[2],$_SESSION['db_user'],$_SESSION['db_pass']);
        $conn = $curr_conn->getConnection();
        $sql = '';
        foreach($strings_arr as $str_row) {
            $sql .= "UPDATE ".$main_q_arr[1]." SET ";
            $parts = explode('%%@@%%', $str_row);
            $colsvals = explode('@__@',$parts[0]);
            $req_arr = array();
            foreach($colsvals as $cv) {
                $curr_pair = explode('@=@',$cv);
                $req_arr[] = $curr_pair[0] . " = " . $curr_pair[1] . "";
            }
            $sql .= implode(',',$req_arr);
            $where_pair = explode('@=@',$parts[1]);
            $sql .= " WHERE ".implode(" = ", $where_pair)."; ";
            
        }
        // die($sql);
        $quer = pg_q( $conn, $sql);
        break;
}

echo ($quer) ? 'ok' : 'bad';