<?php
ob_start("ob_gzhandler");

/* 
	Crowd Source Classifier Training
	Ajax Loader Page (ajax_loader.php)
	26/08/10 Rob Ryan
*/


// load configuration file
include_once("config.php");

// load the database class
include_once("includes/db_mysql.php");
$db = new DB($config);

include_once("includes/xtemplate.class.php");
include_once("page_base.php");

$action = $_GET['action'];
$json_data = $_GET['json_string'];

switch($action)
{
	case "vote":
		include_once("stream.php");
		$main_class = new stream(1);
		$main_class->add_vote($json_data);
	break;
	case "get_tweets":
		include_once("stream.php");
		$main_class = new stream(1);
		$main_class->get_tweets($json_data);
	break;
	default:
	
	break;
}


?>