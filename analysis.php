<?php

// analysis script

// load configuration file
include_once("config.php");

// load the database class
include_once("includes/db_mysql.php");
$db = new DB($config);

$class_lookup = array(1=>"no_news", 2=>"news", 3=>"news_op");
$class_mapping = array(1=>1, 2=>2, 3=>2);
$class_name_mapping = array("no_news"=>"no_news", "news"=>"news", "news_op"=>"news");

include_once("page_base.php");
include_once("classify.php");
include_once("stemmer.php");
include_once("cluster.php");


$cluster_main = new cluster();
$db->query("SELECT * FROM `init_tweets` WHERE `test`=1 ORDER BY RAND() LIMIT 10");
while($tweet = $db->fetch_row())
{
	$cluster_main->tf_idf($tweet['tweet'], $tweet['id']);
	echo "<br /><br />";
}

/*
$main_class = new classify(array(1=>"no_news", 2=>"news"), $class_mapping, false);
$main_class->add_tweets(array(), 591);

$class_totals = array(1=>0, 2=>0);
$news_items = array();
$missdirect = array();
$db->query("SELECT * FROM `init_tweets` WHERE `test`=1");
while($tweet = $db->fetch_row())
{
	$result = $main_class->classify_tweet($tweet['tweet']);
	if($class_lookup[$result] == $class_name_mapping[$tweet['class']])
	{
		$class_totals[$result]++;
		if($class_lookup[$result] == "news")
		{
			$news_items[] = $tweet;
		}
	}
	else
	{
		$missdirect[$class_name_mapping[$tweet['class']]."-".$class_lookup[$result]]++;
		if(($class_name_mapping[$tweet['class']] == "no_news") && ($class_lookup[$result] == "news"))
		{
			//echo $tweet['tweet']."<br />";
		}
	}
}

foreach($class_totals as $result=>$total)
{
	echo $class_lookup[$result]."==".$total."<br />";
}

foreach($missdirect as $key=>$val)
{
	echo $key."==".$val."<br />";
}

$missdirect = array();
$class_totals = array(2=>0, 3=>0);
$classify_news = new classify(array(2=>"news", 3=>"news_op"), array(1=>1, 2=>2, 3=>3), false);
$classify_news->add_tweets(array("news", "news_op"));
//$db->query("SELECT * FROM `init_tweets` WHERE `test`=1 AND `class`IN('news', 'news_op')");
//while($tweet = $db->fetch_row())
//{
foreach($news_items as $tweet)
{
	$result = $classify_news->classify_tweet($tweet['tweet']);
	if($class_lookup[$result] == $tweet['class'])
	{
		$class_totals[$result]++;
	} 
	else
	{
		$missdirect[$tweet['class']."-".$class_lookup[$result]]++;
	}
}
//}

echo "<br /><br />";
foreach($class_totals as $result=>$total)
{
	echo $class_lookup[$result]."==".$total."<br />";
}

foreach($missdirect as $key=>$val)
{
	echo $key."==".$val."<br />";
}
*/

// randomly select a test set
/*
$tweet_ids = array();
$db->query("SELECT id FROM `init_tweets` WHERE `class`='news_op' ORDER BY RAND() LIMIT 100");
while($tweet_id = $db->fetch_row())
{
	$tweet_ids[] = $tweet_id['id'];
}

$db->query("UPDATE `init_tweets` SET `test`=1 WHERE `id`IN(".implode(",", $tweet_ids).")");

*/
// define current classes
/*
$vote_counts = array();
$db->query("SELECT * FROM `votes`");
while($votes = $db->fetch_row())
{
	if(!isset($vote_counts[$votes['tweet_id']]))
		$vote_counts[$votes['tweet_id']] = array();
	
	$vote_counts[$votes['tweet_id']][] = $votes['vote'];
}


foreach($vote_counts as $tweet_id=>$votes)
{
	if(sizeof($votes) > 1)
	{
		$count = 0;
		for($i=0; $i<sizeof($votes); $i++)
		{
			$count += $votes[$i];
		}
		$vote = round(($count / sizeof($votes)), 0);
	}
	else
	{
		$vote = $votes[0];
	}
	
	$db->query("UPDATE `init_tweets` SET `class`='".$class_lookup[$vote]."' WHERE `id`=".$tweet_id);
}*/



/*$frequency_count = array();
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
	echo $key."==".$val."<br />";*/


?>