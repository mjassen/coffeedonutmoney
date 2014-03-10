<?php
/* 你好. Hello.
CoffeeDonutMoney. 咖啡甜甜圈錢.
2014-03-09
MIT license.
MIT 授權規定.

******
The MIT License (MIT)

Copyright (c) 2014 Morgan Jassen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
******


*/

/*
 * parts gotten from phpLiteAdmin v1.0
 */
 
// this php script expects a database coffeedonutmoney.sqlite in the same directory, with a table in it to hold the data.
// the query to create table in sqlite database: 
// CREATE TABLE coffeedonutmoney(date TEXT, balance TEXT, description TEXT)
 
//Edit the following variables to suit your needs

date_default_timezone_set('America/New_York');

//an array of databases that you want to manage by path name relative to this file
$databases = array
(
	"coffeedonutmoney.sqlite"
);

$table_name = 'coffeedonutmoney';

$database_name = "<a href='coffeedonutmoney.php'>coffeedonutmoney</a>";

//End of user-editable fields and beginning of user-unfriendly source code

ini_set("display_errors", 1);
error_reporting(E_STRICT | E_ALL);
session_start();

//build the basename of this file
$nameArr = explode("?", $_SERVER['PHP_SELF']);
$thisName = $nameArr[0];
$nameArr = explode("/", $thisName);
$thisName = $nameArr[sizeof($nameArr)-1];
define("PAGE", $thisName);
define("PROJECT", "phpLiteAdmin");

//the various possible error/warning messages (some are go-home errors, others are just warnings)
$errorMessages = array
(
	"Error: Your version of PHP, ".doubleval(phpversion())." does not contain the necessary packages - either PDO, SQLite3, or SQLite. You may not continue until you enable/intall these packages.", // 0
	"Error: The database file specified does not exist. You may not continue until you correctly reference the path to the database file.", // 1
	"Error: The database file cannot be read or written-to. Use chmod to alter the permissions.", // 2
	"Warning: The database file can be read but not written-to. Attempts to INSERT and UPDATE will cause errors.", // 3
	"used to be a password error msg.", // 4
	"also used to be a password error msg.", // 5
	"Error: You have not specified any databases.", //6
	"You should never see this message" //7
);
  
//loose functions

//added this function to provide consistent data quoting.
define("TICK", "'");
function quoteIt($s)
{
  $t = TICK;
  for($u = 0; $u < strlen($s); $u++)
  {
    if ($s[$u] == TICK) $t .= TICK;
    $t .= $s[$u];
  }
  $t .= TICK;
  return $t;
}

//don't know what this does but it's important
function endsIn64($s)
{
  if (strlen($s) < 3) return false;
  return substr($s, -2) === "64";
}

//error message function to show error on screen
function errorMsg($msg, $exit)
{
	echo "<div class='confirm' style='margin:15px;'>";
	echo $msg;
	echo "</div>";
	if($exit)
		exit();
}


//
// Database class
// Generic database class to manage interaction with database
//
class Database 
{
	protected $db; //reference to the DB object
	protected $type; //the extension for PHP that handles SQLite
	protected $name; //the filename of the database
	
	public function __construct($filename) 
	{
		$this->name = $filename;
		
		if(class_exists("PDO")) //first choice is PDO
		{
			$this->type = "PDO";
			$this->db = new PDO("sqlite:".$filename) or die("Error connecting to database");
		}
		else if(class_exists("SQLite3")) //second choice is SQLite3
		{
			$this->type = "SQLite3";
			$this->db = new SQLite3($filename) or die("Error connecting to database");
		}
		else if(class_exists("SQLite")) //third choice is SQLite, AKA SQlite2 - some features may be missing and cause problems, but it's better than nothing :/
		{
			$this->type = "SQLite";
			$this->db = new SQLite($filename) or die("Error connecting to database");	
		}
		else //none of the possible extensions are enabled/installed and thus, the application cannot work
		{
			die("Your installation of PHP does not include a valid SQLite3 or PDO extension required by the application.");	
		}
	}
	
	public function __destruct() 
	{
		$this->close();
	}
	
	//get the exact PHP extension being used for SQLite
	public function getType()
	{
		return $this->type;	
	}
	
	//get the filename of the database
	public function getName()
	{
		return $this->name;	
	}
	
	public function close() 
	{
		if($this->type=="PDO")
			$this->db = NULL;
		else if($this->type=="SQLite3")
			$this->db->close();
		else if($this->type=="SQLite")
			sqlite_close($this->db);
	}
	
	public function beginTransaction()  
	{
		$this->query("BEGIN");
	}
	
	public function commitTransaction() 
	{
		$this->query("COMMIT");
	}
	
	public function rollbackTransaction() 
	{
		$this->query("ROLLBACK");
	}
	
	//generic query wrapper
	public function query($query)
	{
		if($this->type=="SQLite")
			$result = sqlite_query($this->db, $query);
		else
			$result = $this->db->query($query);
		return $result;
	}
	
	//wrapper for an INSERT and returns the ID of the inserted row
	public function insert($query)   
	{
		$result = $this->query($query);
		if($this->type=="PDO")
			return $this->db->lastInsertId();
		else if($this->type=="SQLite3")
			return $this->db->lastInsertRowID();
		else if($this->type=="SQLite")
			return sqlite_last_insert_rowid($this->db);
	}
	
	//returns an array for SELECT
	public function select($query) 
	{
		$result = $this->query($query);
		if($this->type=="PDO")
			return $result->fetch();
		else if($this->type=="SQLite3")
			return $result->fetchArray();
		else if($this->type=="SQLite")
			return sqlite_fetch_array($result);
	}
	
	//returns an array of arrays after doing a SELECT
	public function selectArray($query)
	{
		$result = $this->query($query);
		if($this->type=="PDO")
			return $result->fetchAll();
		else if($this->type=="SQLite3")
		{
			$arr = array();
			$i = 0;
			while($res = $result->fetchArray())
			{ 
				$arr[$i] = $res;
				$i++;
			} 
			return $arr;	
		}
		else if($this->type=="SQLite")
			return sqlite_fetch_all($result);
	}
	
	//get number of rows in table
	public function numRows($table)
	{
		$result = $this->select("SELECT Count(*) FROM ".$table);
		return $result[0];
	}
}

//
// View class
// Various functions to visually represent the database
//
class View
{
	protected $db;
	
	public function __construct($db) 
	{
		$this->db = $db;
	}
	

	//generate the insert row view
	public function generateInsert($table)
	{
		$query = "PRAGMA table_info('".$table."')";
		$result = $this->db->selectArray($query);
		
		echo "<form action='".PAGE."?table=".$table."&insert=1' method='post'>";
		echo "<table border='0' cellpadding='2' cellspacing='1'>";
		echo "<tr>";
		echo "<td class='tdheader'>Field</td>";
		//echo "<td class='tdheader'>Type</td>";
		echo "<td class='tdheader'>Value</td>";
		echo "</tr>";
		
		for($i=0; $i<sizeof($result); $i++)
		{
		
		if ($i == 0) //if its the first row which i want to have the datestamp in it
		{
					$tdWithClass = "<td class='td" . ($i%2 ? "1" : "2") . "'>";
			echo "<tr>";
			//for ($k=1; $k<3; $k++)
			for ($k=1; $k<2; $k++)
				echo $tdWithClass . $result[$i][$k]."</td>";
      	echo $tdWithClass;
			// -g-> Changed to allow for big amounts of text.
			echo date('Y-m-d H:i:s')."<input type='hidden' name='".$result[$i][1]."' value='".date('Y-m-d H:i:s')."' style='width:100px;'/> ";
			//date('YmdHis');
			//echo '<textarea name="'.$result[$i][1].'" wrap="hard" rows="3" cols="15"></textarea>';
			echo "</td>";
			echo "</tr>";
		
		}else{ //else its not the first row which i want to have the datestamp in it
			$tdWithClass = "<td class='td" . ($i%2 ? "1" : "2") . "'>";
			echo "<tr>";
			//for ($k=1; $k<3; $k++)
			for ($k=1; $k<2; $k++)
				echo $tdWithClass . $result[$i][$k]."</td>";
      	echo $tdWithClass;
			// -g-> Changed to allow for big amounts of text.
			echo "<input type='text' name='".$result[$i][1]."' style='width:100px;'/> ";
			//echo '<textarea name="'.$result[$i][1].'" wrap="hard" rows="3" cols="15"></textarea>';
			echo "</td>";
			echo "</tr>";
		}
			
			
		}
		echo "</table>";
		echo "<input type='submit' value='Insert'/>";
		echo "</form>";
	}
	
	
	//generate the view rows view
	public function generateView($table, $numRows, $startRow, $sort, $order)
	{
		$_SESSION['numRows'] = $numRows;
		$_SESSION['startRow'] = $startRow;

		// -g-> We need to get the ROWID to be able to find the row again. I put it at the end of the list of fields so that the numbering remained unchanged.
		$query = "SELECT *, ROWID FROM ".$table;
		$queryDisp = "SELECT * FROM ".$table;
		$queryAdd = "";
		//if($sort!=NULL)
		//$queryAdd .= " ORDER BY ".$sort;
			$queryAdd .= " ORDER BY date";
		//if($order!=NULL)
			//$queryAdd .= " ".$order;
			$queryAdd .= " DESC";
		$queryAdd .= " LIMIT ".$startRow.", ".$numRows;
		$query .= $queryAdd;
		$queryDisp .= $queryAdd;
		
		$startTime = microtime(true);
		$arr = $this->db->selectArray($query);
		$endTime = microtime(true);
		$time = round(($endTime - $startTime), 4);
		$total = $this->db->numRows($table);
		
		//if(!(sizeof($arr)>0))
		//{
		//	echo "<br/><br/>This table is empty.";
		//	return;
		//}
		if(sizeof($arr)>0)
		{
			echo "<br/>";
			echo "Showing rows ".$startRow." - ".($startRow + sizeof($arr)-1)." of ".$total."<br/>";
			echo "<br/>";
		}
		else
		{
			echo "<br/><br/>This table is empty.";
			return;
		}
		
		//echo "<form action='".PAGE."?edit=1&table=".$table."' method='post' name='checkForm'>";
		echo "<table border='0' cellpadding='2' cellspacing='1'>";
		$query = "PRAGMA table_info('".$table."')";
		$result = $this->db->selectArray($query);
		$rowidColumn = sizeof($result);
		
		echo "<tr>";
		//echo "<td colspan='3'>";
		echo "</td>";
		
		for($i=0; $i<sizeof($result); $i++)
		{
			echo "<td class='tdheader'>";
			echo "<a href='".PAGE."?table=".$table."&sort=".$result[$i][1];
			$orderTag = ($sort==$result[$i][1] && $order=="ASC") ? "DESC" : "ASC";
			echo "&order=".$orderTag;
			echo "'>".$result[$i][1]."</a>";
			if($sort==$result[$i][1])
				echo (($order=="ASC") ? " <b>&uarr;</b>" : " <b>&darr;</b>");
			echo "</td>";
		}
		echo "</tr>";
		
		for($i=0; $i<sizeof($arr); $i++)
		{
			// -g-> $pk will always be the last column in each row of the array because we are doing a "SELECT *, ROWID FROM ..."
			$pk = $arr[$i][$rowidColumn];
			$tdWithClass = "<td class='td".($i%2 ? "1" : "2")."'>";
			echo "<tr>";

			for($j=0; $j<sizeof($result); $j++)
			{
				echo $tdWithClass;
				// -g-> although the inputs do not interpret HTML on the way "in", when we print the contents of the database the interpretation cannot be avoided.
				$inBase64 = endsIn64($arr[$i][$j]);
				echo $inBase64 ? base64_decode($arr[$i][$j]) : $arr[$i][$j];
				echo "</td>";
			}
			echo "</tr>";
		}
		echo "</table>";

	}
	

}

// here begins the HTML.
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
  <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;">
<title><?php echo PROJECT ?></title>
  <style type="text/css">	
  #wrapper {
    max-width: 400px;
  }
  </style>

<style type="text/css">

/* table rows */
.td2 { background-color:#ddd; }
/* odd rows */
.td1 { background-color:#eee; }
/* table header */
.tdheader { background-color:#bbb;}

</style>
</head>
<body>
<div id="wrapper">
<?php

	if(sizeof($databases)>0)
		$DBFilename = $databases[0];
	else
		errorMsg($errorMessages[6], true);
		
	if(isset($_POST['database_switch']))
	{
		$_SESSION['DBFilename'] = $_POST['database_switch'];
		$DBFilename = $_POST['database_switch'];
	}
	if(isset($_SESSION['DBFilename']))
		$DBFilename = $_SESSION['DBFilename'];
		
	//First, check that the right classes are available
	if(!class_exists("PDO") && !class_exists("SQLite3") && !class_exists("SQLite"))
		errorMsg($errorMessages[0], true);


	
	$db = new Database($DBFilename); //create the Database object
	$dbView = new View($db);
	
	//Switch board for various operations a user could have requested
	if(isset($_POST['createtable'])) //ver 1.1 bug fix - check for $_POST variables before $_GET variables 
	{
	  // Not sure what is happening here... gkf
	}

	else if(isset($_GET['insert'])) //insert record into table
	{
		$query = "INSERT INTO ".$_GET['table']." (";
		$inBase64 = array();
		$i = 0;
		foreach($_POST as $vblname => $value)
		{
			$inBase64[$i++] = endsIn64($vblname);
			$query .= $vblname.",";
		}
		$query = substr($query, 0, sizeof($query)-2);
		$query .= ") VALUES (";
		$i = 0;
		foreach($_POST as $vblname => $value)
		{
			if($value=="")
				$query .= "NULL,";
			else
				$query .= quoteIt($inBase64[$i++] ? base64_encode($value) : $value) . ",";
		}
		$query = substr($query, 0, sizeof($query)-2);
		$query .= ")";
		$db->query($query);
	}
	echo "<div id='container'>";

	echo "<div id='content'>";
	echo "<div id='contentInner'>";


	$view = "browse";
		
	$table = $table_name;
	
	echo "<div id='main'>";

	echo $database_name;

	$dbView->generateInsert($table);
	
		$_SESSION['startRow'] = 0;
		
		$_SESSION['numRows'] = 30;

		
	if(!isset($_GET['sort']))
		$_GET['sort'] = NULL;
	if(!isset($_GET['order']))
		$_GET['order'] = NULL;
	$dbView->generateView($table, $_SESSION['numRows'], 0, $_GET['sort'], $_GET['order']);

	
	echo "</div>";


	echo "</div>";
	echo "<br/>";

	echo "</div>";
	echo "</div>";
	$db->close(); //close the database

echo "</div></body>";
echo "</html>";
