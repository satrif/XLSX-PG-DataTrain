<?php
function sql_escape($str) {
	return str_replace("'","''", htmlspecialchars($str));
}
function pg_last_error_isy(){
	if (isset($_SESSION['dev_flag']) && $_SESSION['dev_flag'] == true) {
		echo pg_last_error();
	} else {//$_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'];
		$tmp_link = explode('/',str_replace('/appl/actions/','',$_SERVER['REQUEST_URI']))[0];
		echo '<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
		echo "<head><title>DB error</title></head>";
		echo "<body><p>Ошибка в запросе к БД</p><p><a href='".$tmp_link."'>Вернуться на главную страницу</a></body></html>";
		die();
		// exit;
	}
}
class User {
    public string $uname;
    public string $department;

    public function __construct(string $uname, string $department) {
			$this->uname = $uname;
			$this->department = $department;
    }

    public function getUname(): string {
			return $this->uname;
    }

    public function getDepartment(): string {
			return $this->department;
    }
}

class ApplicationSettings extends User {
    public string $application;

    public function __construct(string $uname, string $department, string $application) {
			parent::__construct($uname, $department);
			$this->application = $application;
    }

    public function getApplication(): string {
			return $this->application;
    }

    public function drawTitle(string $perm = ''): void {
			$tmp_link = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			echo "<ul class='titler'><li>Signed in as: ".parent::getUname() . " (" . parent::getDepartment() . ")</li><li id='rightli' class='rightli'>" . $this->getApplication() . ((isset($perm) && $perm !== '') ? " | access: " . $perm : "") . "</li></ul>";
    }
}

class ApplicationAccess extends ApplicationSettings {
	private $conn;
	private ?array $role = array();

	public function __construct(string $uname, string $application, $conn) {
		$this->conn = $conn;
		$query = "select '".$application."->Operator' as rname;";
		$result = pg_query($conn, $query) or die('Query failed: ' . pg_last_error());
		while($row = pg_fetch_array($result, null, PGSQL_ASSOC)) {
			$tmp_val =  substr($row['rname'], strpos($row['rname'],'>')+1, strlen($row['rname']));
			if (!in_array($tmp_val, $this->role)) $this->role[] = $tmp_val;
		}
		pg_free_result($result);
	}

	public function getRoles() {
		return $this->role;
	}
}

class DBConnection {

	private $conn;

	public function __construct(string $host, string $dbname, string $dbuser, string $dbpassword) {
		$this->conn = pg_connect("host=".$host." dbname=".$dbname." user=".$dbuser." password=".$dbpassword) or die('Could not connect:' . pg_last_error());
	}

	public function getConnection() {
		return $this->conn;
	}
}
