<?php

// analysis script

// load configuration file
include_once("config.php");

// load the database class
include_once("includes/db_mysql.php");
$db = new DB($config);

$frequency_count = array();
$db->query("SELECT v.*, t.tweet FROM `votes` v
	 		LEFT JOIN `init_tweets` t ON(v.tweet_id=t.id)	
			WHERE v.vote IN(3)");
while($tweets = $db->fetch_row())
{
	$words = explode(" ", $tweets['tweet']);
	foreach($words as $word)
	{
		if(empty($frequency_count[$word]))
		{
			$frequency_count[$word] = 1;
		}
		else
		{
			$frequency_count[$word]++;
		}
	}
}

arsort($frequency_count);
foreach($frequency_count as $key=>$val)
	echo $key."==".$val."<br />";


?>