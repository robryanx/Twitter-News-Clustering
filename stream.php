<?php

/* 
	Crowd Source Classifier Training
	Stream Generation Page (stream.php)
	26/08/10 Rob Ryan
*/

class stream extends page_base
{
	private $ajax;
	private $records = 10;

	public function __construct($ajax=0)
	{
		parent::__construct();
		
		$this->ajax = $ajax;
	}
	
	public function get_tweets($limit)
	{
		if($this->ajax == 0)
		{
			$tmpl_tweet_display = new XTemplate('template/tweet_display.xtpl');
		}
		
		// get current user totals
		// get a list of tweets voted on by the current user
		$voted_list = array();
		$rated = $no_news = $news = $news_op = 0;
		
		$this->db->query("SELECT * FROM `votes` WHERE `ip`='".$_SERVER['REMOTE_ADDR']."'");
		while($votes = $this->db->fetch_row())
		{
			$voted_list[$votes['tweet_id']] = 1;
			$rated++;
			
			switch($votes['vote'])
			{
				case 1:
					$no_news++;
				break;
				case 2:
					$news++;
				break;
				case 3:
					$news_op++;
				break;
			}
		}
		
		if(sizeof($voted_list) == 0)
		{
			$voted_list[0] = 1;
		}
		
		$this->db->query("SELECT * FROM `init_tweets` WHERE `id`NOT IN(".implode(",", array_keys($voted_list)).") ORDER BY RAND() LIMIT 20");
		while($tweet = $this->db->fetch_row())
		{
			$tmpl_tweet_display->assign('id', $tweet['id']);
			$tmpl_tweet_display->assign('tweet_text', $tweet['tweet']);
			$tmpl_tweet_display->assign('tweet_id', $tweet['twitter_id']);
			$tmpl_tweet_display->assign('user', $tweet['user']);
			$tmpl_tweet_display->assign('time', date("g:i a", $tweet['created']));
			$tmpl_tweet_display->assign('date', date("d/m/Y", $tweet['created']));
			$tmpl_tweet_display->parse("tweet_display.tweet_box");
		}
			
		if($this->ajax == 0)
		{
			$tmpl_tweet_display->assign('rated', $rated);
			$tmpl_tweet_display->assign('no_news', $no_news);
			$tmpl_tweet_display->assign('news', $news);
			$tmpl_tweet_display->assign('news_op', $news_op);
		
			$tmpl_tweet_display->parse("tweet_display");
			$this->template_out = &$tmpl_tweet_display;
		}
	}
}



?>