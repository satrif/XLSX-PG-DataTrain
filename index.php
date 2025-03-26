<meta http-equiv="content-type" content="text/html; charset=utf-8">
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
ini_set('memory_limit', '512M');
$g = 0;
$h = 0;
function htmlClean($str){//to assign classes by column name
    return str_replace('/','__',str_replace('.','-',str_replace(' ','_',$str)));
}
include 'SimpleXLSX.php';//include xlsx parse class
$oper=isset($_GET['oper'])?(($_GET['oper']=="")?"-1":$_GET['oper']):"-1";//site view switch
require_once('datatrain_permissions.php');//access check
if(!$access_FLAG) die('No access, role required: "'.$APPLICATION_NAME.'-->Operator"');
?>
        <head>
            <link href="<?=$basedirCLN?>css/Common.css" type="text/css" rel="stylesheet" />           
            <script type="text/javascript" src="<?=$basedirCLN?>js/jquery-latest.min.js"></script>
            <script src="<?=$basedirCLN?>js/jquery-ui-latest.custom.min.js" ></script>
            <link rel="stylesheet" href="<?=$basedirCLN?>css/jquery-ui-latest.custom.css" type="text/css" media="screen">
        </head>
        <body>
            <header><?=$app->drawTitle($perm)?></header>
        <?
if($oper=="2") {//3. completed
    echo "операция завершена";
    die();
}
if($oper=="1") {//2. operate with file
    if (!isset($_FILES["userfile"])) {
        header("location: " . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . "/?oper=-1"); exit;
    }
    $resulty = $_FILES["userfile"];
    $resultun = $_FILES["userfile"]["name"];
} else {//1. select file
    echo "\r\n".'<link rel="stylesheet" href="'.$basedirCLN.'css/bootstrap.min.css" type="text/css" />'."\r\n";
    echo "<form class='uplf' name=\"sndfrm\" action=\"?oper=1\" method=\"POST\" enctype=\"multipart/form-data\">";
    ?>

    <input type="hidden" name="MAX_FILE_SIZE" value="<?=$filesize_quota?>" />
    <div class="row">
        <div class="container col-4">
            <div class="row">
                <div class="col text-right"><p>Attach file (max <?=round((($filesize_quota / 1024) / 1024), 0)?> MB):</p></div>
                <div class="col"><input class='form-control' name="userfile" type="file" required /></div>
            </div>
            <div class="row">
                <div class="col text-right"><p>Sheet number: </p></div>
                <div class="col"><input class='form-control' name="listnr" value=1 required /></div>
            </div>
            <div class="row">
                <div class="col text-right"><p>DB name:</p></div>
                <div class="col"><input class='form-control' name="dbname" required /></div>
            </div>
            <div class="row">
                <div class="col text-right"><p>Table name:</p></div>
                <div class="col"><input class='form-control' name="tablename" required /></div>
            </div>
            <div class="row">
                <div class="col text-right"><p>Sheet and table columns aligned:</p></div>
                <div class="col"><input type="checkbox" class='form-control' name="noname" /></div>
            </div>
        </div>
        <div class="col"><input class='btn btn-success' type="submit" value="Read file" /></div>
    </div>
    <?
    echo "</form>";

}
if(isset($resulty))//if file recieved
{   //screen blocker
    echo '<div id="screenBlock" style="opacity: 0.7; width: 99vw; height: 99vh;">
            <h1 style="padding:2em;font-size:4em;color:white;background-color:black;position:absolute;top:35vh;left:32vw;text-align:center;"><p>Loading...</p></h1>
        </div>';
    echo "<table width=100% style='position: abosolute;'>
            <tr>
                <td width=30%>";//description

   $uploaddir = "temp/"; //upload are save into this folder
    echo "<div class='head1'>
        <p class='uplh'>Upload Status:</p>
        <p class='uplp'>";
    $uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
    if (is_uploaded_file($_FILES["userfile"]["tmp_name"]))
    {
        echo "$resultun is uploaded!<br>";
    } else
    {
        echo "$resultun is not uploaded!<br>".$_FILES['userfile']['error']."<br>";
    }
    if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
        echo "File uploaded successfully.<br>";
    } else {
        echo "Unable to upload a file!<br>".$uploadfile."<br><a href='/appl/actions/datatrain'>Repeat</a>";
    }
    echo "</p>
        <p class='infoh'>Data Info</p>";
    if ($xlsx = SimpleXLSX::parse($uploaddir.$_FILES["userfile"]["name"])){//parse xlsx
        $db = isset($_POST['dbname']) ? $_POST['dbname'] : die('no DB!');
        $tbl = isset($_POST['tablename']) ? $_POST['tablename'] : die('no tablename!');
        $listnr = isset($_POST['listnr']) ? intval($_POST['listnr']) - 1 : 0;
        $noname = isset($_POST['noname']) ? 1 : 0;
        //reading sheet
        $res= $xlsx->rows($listnr);
        $cnt = count($res) - 1;
        echo "<p class='infoh'>File: ".$resultun."</p>
            <p class='infop'>Sheet: <span class='arrow_red'>>></span>".$xlsx->sheetNames()[$listnr]."<span class='arrow_red'><<</span> | Rows: ".$cnt."</p>
            <p class='infoh'>Upload to <span style='color:blue'><b>".$tbl." @ ".$db."</b></span></p>";
        $g = 1;
        $h = 1;
        //getting table column preperties
        $curr_conn = New DBConnection($_SESSION['db_server'],$db,$_SESSION['db_user'],$_SESSION['db_pass']);
        $conn = $curr_conn->getConnection();
        $sql_col_get = "select column_name as col, ordinal_position, data_type from information_schema.columns where table_name ilike '".$tbl."' order by ordinal_position;";
        $stmt_col_get = pg_q($conn, $sql_col_get);//get table cols
        $col_sel = '<option value=-1>Select...</option>';
        $col_monitor = '';
        $table_col_arr = array();
        $date_flag = false;//to check if date column exists
        $ts_flag = false;//to check if timestamp column exists
        while($row_col_get = pg_fetch_array( $stmt_col_get, null, PGSQL_ASSOC)) {
            $col_sel .= "<option class='".htmlClean($row_col_get['col'])."' data_type='".$row_col_get['data_type']."' value=".htmlClean($row_col_get['col']).">".$row_col_get['col']."</option>";
            $col_monitor .= "<div colname='".$row_col_get['col']."' data_type='".$row_col_get['data_type']."' class='".htmlClean($row_col_get['col'])." inline'>".$row_col_get['col']."</div>";
            $table_col_arr[] = $row_col_get['col'];
            if(strpos($row_col_get['data_type'],'date') !== false) $date_flag = true;
            if(strpos($row_col_get['data_type'],'timestamp') !== false) $ts_flag = true;
        }
            ?></div>
                <div <?=($date_flag || $ts_flag)?'':'hidden'?>>
                    <table border=0 cellspacing=0 cellpadding=0>
                        <tr>
                            <td <?=($date_flag)?'':'hidden'?>>
                                <p class="noM">Date format</p>
                                <input id='date_format' value='dd.mm.yyyy' />
                            </td>
                            <td style='padding-left: .5em' <?=($ts_flag)?'':'hidden'?>>
                                <p class="noM">Timestamp format</p>
                                <input id='timestamp_format' value='dd.mm.yyyy hh24:mi:ss' />
                            </td>
                        </tr>
                        <tr><td colspan=2>Example:</td></tr>
                        <tr>
                            <td colspan=2>2020-12-31 23:59:59</td>
                        </tr>
                        <tr>
                            <td colspan=2>yyyy-mm-dd hh24:mi:ss</td>
                        </tr>
                    </table>
                </div>
            </td>
            <td style='background-color: #f3ffd0;vertical-align: top;' width=50%>
                <p>Upload progress:</p>
                <div style="position: relative;overflow-y: scroll;" id='visual_result_container'>
                    <div id="myProgress">
                        <div id="myBar"></div>
                    </div>
                </div>
            </td>
            <td width=20% align=right id='finisher' style='background-color: #f3ffd0;'>
                <table border=0>
                    <tr>
                        <td></td>
                        <td><a class='a-button' title='Select file' style='cursor: pointer;' border=0 onclick="if(confirm('Начать заново?')) location.replace('?oper=-1');">Select file &#x21bb;</a></td>
                    </tr>
                </table>
                <div id='todbbut'>
                    <table border=0>
                        <tr>
                            <td></td>
                            <td><a class='a-button' title='Upload to DB' style='cursor: pointer;' border=0 onclick="gatherer();">Upload to DB &#x2699;</a></td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
    <p style='font-size: 1.5em; font-weight: bold; text-align: left;margin:0;background-color:lightgray;color:darkblue;'>To make `insert` into table select the desired columns, to make `update` select columns and assign a key:</p>
    <? 
        echo "<div class=\"centr\">".$col_monitor."</div>";//setting up columns from table (DB)
        echo "<table width=100% align=center border=1>";
        $file_head_arr = array();
        for ($i=0; $i <= $cnt; $i++)// show file contents
        {
                if ($i == 0) {//1st row = headers
                    $h = 1;
                    echo "<tr><td bgcolor=lightgray width=5%>##</td>";
                    $res_j_cnt = count($res[$i]);
                    for ($j = 0; $j < $res_j_cnt; $j++) {//making checkbox for column, selection of column, key checkbox
                        echo "<td width=" . strlen($res[$i][$j]) . "% class=heading id=HEAD_t_" . $h . " bgcolor=lightgray>";
                        echo "<input class='colchecker' id=HEAD_" . $h . " onclick=\"trig_col('" . $h . "');\" type=checkbox />" . $res[$i][$j];
                        echo "<input class='keymaker' onclick=\"make_key('" . $h . "', $(this));\" type=checkbox /><span id='keyid_".$h."' class='keylabel'>Key?</span>";
                        echo "<br><select attr_id='".$h."' class='heading_sel' id='HEAD_sel_" . $h . "' hidden>" . $col_sel . "</select>";
                        echo "</td>";
                        $h++;
                        $file_head_arr[] = array($res[$i][$j], $h);
                    }
                    echo "</tr>";
                    if ($i != 0) {
                        $g++;
                    }
                } else {//show only 1st 4 rows and last 4 rows, other are hidden
                    if($i == ($cnt-4)) echo "<tr><td colspan=".$h.">--------------------</td></tr>";
                    $h = 1;
                    echo "<tr".(($i<5 || $i >= ($cnt-4)) ? "" : " style='display:none;'")."><td width=5%>" . $g . "</td>";
                    $res_j_cnt = count($res[$i]);
                    for ($j = 0; $j < $res_j_cnt; $j++) {
                        echo "<td width=" . strlen($res[$i][$j]) . "% " . (($i != 0) ? "id=" . $g . "-" . $h : "") . " bgcolor=white>" . str_replace("'","''",$res[$i][$j]);
                        echo "</td>";
                        $h++;
                    }
                    echo "</tr>";
                    if ($i != 0) {
                        $g++;
                    }
                }
        }
        echo "</table>";
        unlink($uploaddir.$_FILES["userfile"]["name"]);//removing file as it was read and not needed anymore
    } else echo "</div></td></tr></table></td></tr></table>";
}
?>
</body>
<script>
    function block_screen(cb) {
		$('<div id="screenBlock"><h1 style="padding:2em;font-size:4em;color:white;background-color:black;position:absolute;top:35vh;left:32vw;text-align:center;"><p>Loading...</p></h1></div>').appendTo('body');
		$('#screenBlock').css( { opacity: 0, width: $(document).width(), height: $(document).height() } );
		$('#screenBlock').addClass('blockDiv');
		$('#screenBlock').animate({opacity: 0.7}, 200);
        setTimeout(cb, 1000, true);
	}

	function unblock_screen() {
		$('#screenBlock').animate({opacity: 0}, 200, function() {
			$('#screenBlock').remove();
		});
	}
    var row_cnt = '<? echo ($g-1); ?>';
    var cell_cnt = '<? echo ($h-1); ?>';
    $(document).ready(function(){
        <? //auto map db-table columns to xlsx-table columns
        if (isset($file_head_arr) && isset($table_col_arr)){
            if($noname == 1) {//by raw position - 1,2,3...,n
                $nn = 1;
                foreach($file_head_arr as $fh) {
                    if(isset($table_col_arr[$nn]) && $table_col_arr[$nn] !== '') {
                        ?>
                        trig_col('<?=$fh[1]-1?>', '<?=$table_col_arr[$nn]?>');
                        <?
                        $nn++;
                    }
                }
            } else {// if xlsx column names matches db table columns names
                foreach($file_head_arr as $fh) {
                    foreach($table_col_arr as $tc) {
                        if ($fh[0] == $tc) {
                            ?>
                            trig_col('<?=$fh[1]-1?>', '<?=htmlClean($fh[0])?>');

                            <?
                        }
                    }
                }
            }
        }
        ?>
        unblock_screen();
    });
    function make_key(a, that) {//assign a key (required for update type)
        if(that.is(':checked')){
            if ($('.key').length > 1) {
                event.preventDefault();
                alert('Key must be only one!');
                return;
            }
            if(!($('#HEAD_sel_'+a ).hasClass('key'))) {
                $('.keymaker').hide();
                $('.keylabel').hide();
                $('#HEAD_sel_'+a ).addClass('key');
                that.show();
                $('#keyid_'+a).html('is KEY');
                $('#keyid_'+a).css('background-color','green');
                $('#keyid_'+a).css('padding-left','.5em');
                $('#keyid_'+a).show();
                // console.log('here');
            }
        } else {
            $('.heading_sel').removeClass('key');
            $('.keymaker').show();
            $('.keylabel').show();
            $('.keylabel').html('Key?');
            $('.keylabel').css('background-color','transparent');
            $('.keylabel').css('padding','0');
        }
    }
    function trig_col(a, b = ''){//show-hide db table column in selects, mark it in column list
        if (b != '') {
            $('#HEAD_'+a).prop( "checked", true );
            $('#HEAD_sel_'+a).show();
            $('#HEAD_sel_'+a).val(b);
            $('option.'+b).hide();
            $('.'+b).css('background-color','green');
            // $('#HEAD_sel_'+a).change();
        } else {
            if($('#HEAD_'+a).is(':checked')){
                $('#HEAD_sel_'+a).show();
                
            } else {
                $('option.'+$('#HEAD_sel_'+a).val()).show();
                $('.'+$('#HEAD_sel_'+a).val()).css('background-color','transparent');
                $('#HEAD_sel_'+a).val(-1);
                $('#HEAD_sel_'+a).hide();
            }
        }
    }

    var previous;
    $(".heading_sel").on('focus', function () {//hide show select=option items
        previous = this.value;
    }).change(function() {
        $('option.'+previous).show();
        $('.'+previous).css('background-color','transparent');
        $('option.'+$(this).val()).hide();
        $('.'+$(this).val()).css('background-color','green');
        previous = this.value;
    });

    function run_the_load(type, str, ij,cur_iter_mem, cb) {//insert row into table
        $.ajax({
            url: 'datatrain_handler.php',
            type: 'POST',
            dataType: "html",
            data: {
                typ : type,
                str : str
            },
            success: function (response)
            {
                let flagok = true;
                if (response.substr(0,5) == "query") {
                    flagok = false;
                    console.warn('query error', response);
                }
                cb(flagok, ij,cur_iter_mem, response);
            },
            cache: false,
            error: function(XMLHttpRequest, textStatus, exception) {msg="Operation is not succeeded."; alert(msg);},
            async: true
        });
    }
    function gatherer(){//main function data gatherer
        if (confirm('Upload data?')){
            block_screen(function(fl) {
                if (fl) gather(function(fl,cnt, type, res){
                    if (fl) {
                        $('#todbbut').html('<span style="padding: 2px; margin: 2px; font-size: 25px;background-color: green;font-weight: bold;color: whitesmoke;">DONE: '+(cnt)+' row(s) '+((type=='ins')?'inserted':'updated')+'.</span>');
                    } else {
                        console.warn('error in request');
                        console.log(res);
                        unblock_screen();
                    }
                });
            });
        }
    }
    async function gather(cb) {//gather all rows into table
        let i = 1;
        let flag = 0;
        let ins = false;
        let passedFlag = true;
        let passedRes = '';
        let row_iter = 15;//amount of rows to be imported/updated into db
        let elem_pb = document.getElementById("myBar");
        //check columns filled and if key is assigned
        if ($('.key').length > 1) {
            alert('Only one key is required!');
            cb(false,'need only one key!');
            return;
        }
        if ($('.key').length == 0) {
            ins = true;
        }
        $('div.inline').each(function (ii, elem) {
            if ($(this).css('background-color') == "rgb(0, 128, 0)") {
                flag++;
            }
        });
        query_string = "";
        value_string = "";
        let tmp_val_part = '';
        let debug_flag = false;
        let rows = ((row_cnt % row_iter == 0) ? 0 : 1) + Math.floor(row_cnt / row_iter);
        let cur_iter_row = 0;
        let act_cur_iter_row = 0;
        let cur_iter_mem = 0;
        let single_progress_bar_percent = (row_iter >= row_cnt) ? 100 : (row_iter * row_cnt) / 100;
        let elem_pb_width = 0;
        let width = 0;
        if (flag >= 1) {
            for (ij = 0; ij < rows;ij++) {
                query_string = '';
                debug_flag = false;
                i = (cur_iter_row == 0) ? 1 : cur_iter_row;
                cur_iter_mem = i;
                cur_iter_row = (((ij+1) * row_iter) > row_cnt) ? row_cnt : ((ij+1) * row_iter);
                act_cur_iter_row = (cur_iter_row == row_cnt) ? cur_iter_row : (cur_iter_row-1);
                while (i <= act_cur_iter_row) {//row_cnt
                    q_part_string = "";
                    j = 1;
                    while (j <= cell_cnt) {
                        if ($('#HEAD_' + j).is(':checked') && !$('#HEAD_sel_' + j).hasClass('key')) {
                            switch ($('#HEAD_sel_' + j + ' option:selected').attr('data_type')) {
                                    case 'integer':
                                    case 'real':
                                    case 'float':
                                    case 'numeric':
                                        tmp_val_part = (($('#' + i + '-' + j).text() == '') ? "null" : "" + $('#' + i + '-' + j).text() + "");
                                        break;
                                    case 'date':
                                        tmp_val_part = (($('#' + i + '-' + j).text() == '') ? "null" : "to_date('" + $('#' + i + '-' + j).text() + "', '"+$('#date_format').val()+"')");
                                        break;
                                    case 'timestamp without time zone':
                                    case 'timestamp with time zone':
                                        tmp_val_part = (($('#' + i + '-' + j).text() == '') ? "null" : "to_timestamp('" + $('#' + i + '-' + j).text() + "', '"+$('#timestamp_format').val()+"')");
                                        break;
                                    default:
                                        tmp_val_part = (($('#' + i + '-' + j).text() == '') ? "''" : "'" + $('#' + i + '-' + j).text() + "'");
                                        break;
                                }
                            if (ins) {
                                q_part_string += tmp_val_part + ",";
                                if (i == 1) value_string += '"'+$('#HEAD_sel_' + j + ' option:selected').text() + '",';
                            } else {
                                query_string += $('#HEAD_sel_' + j).val() + "@=@" + tmp_val_part + '@__@';
                            }
                        }
                        j++;
                    }
                    if (!ins) { 
                        query_string = query_string.substring(0, query_string.length-4) + "%%@@%%" + $('.key').val() + "@=@" + $('#' + i + '-' + $('.key').attr("attr_id")).text() + "@%@@%@";
                    } else {
                        query_string += "(" + q_part_string.substring(0, q_part_string.length-1) + "),";
                    }
                    i++;
                }
                if (!ins) { 
                    query_string = query_string.substring(0, query_string.length-6) + '__@@%@@__' + '<?=isset($tbl)?$tbl:'notableerror'?>' + '__@@%@@__' + '<?=isset($db)?$db:'nodberror'?>';
                } else {
                    query_string = query_string.substring(0, query_string.length-1) + "%%@@%%" + value_string.substring(0,value_string.length-1) + "%%@@%%" + "<?=isset($tbl)?$tbl:'notableerror'?>" + "%%@@%%" + "<?=isset($db)?$db:'nodberror'?>";
                }
                    await run_the_load(((ins) ? 'ins' : 'upd'), query_string, act_cur_iter_row, cur_iter_mem, function(fl, iter, cntr, res) {
                    passedFlag = fl;
                    if (fl) {
                        elem_pb_width += single_progress_bar_percent;
                        elem_pb.style.width = elem_pb_width + "%";
                    } else {
                        console.warn('warining',res);
                        return cb(false, res);
                    }
                    if(elem_pb_width >= 100) unblock_screen();
                    // for(n=cntr;n<=iter;n++){
                    //     $('#'+n+'-1').parent('tr').css('display','none'); 
                    //     // $('#'+n+'-1').parent('tr').children('td').css('background','green'); 
                    // }
                    passedRes = res;
                });	//run ajax
            }
            cb(passedFlag, act_cur_iter_row, ((ins) ? 'ins' : 'upd'), passedRes);
        } else {
            alert('Select the columns from the list!');
            cb(false, act_cur_iter_row, ((ins) ? 'ins' : 'upd'), 'Select the columns from the list');
            return;
        }
    }
</script>
</html>
