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
	
	public function add_vote($json_data)
	{
		$data = json_decode($json_data, true);
		$vote = (int)$data['vote'];
		$tweet_id = (int)$data['tweet_id'];
		
		$return = array();
		$return['error'] = 0;
		$return['success'] = 0;
		$return['vote'] = $vote;
		
		// check to see if a tweet exists
		$this->db->query("SELECT id FROM `init_tweets` WHERE `id`=".$tweet_id);
		if($this->db->rows() != 1)
		{
			$return['error'] = 1;
			$return['error_message'] = "The selected Tweet no longer exists.";
		}
		
		// check to see that the tweet hasn't already been voted on by the user
		$this->db->query("SELECT vote_id FROM `votes` WHERE `ip`='".$_SERVER['REMOTE_ADDR']."' AND `tweet_id`=".$tweet_id);
		if($this->db->rows() > 0)
		{
			$return['error'] = 1;
			$return['error_message'] = "You have already voted on this tweet.";
		}
		
		if(($vote < 0) || ($vote > 3))
		{
			$return['error'] = 1;
			$return['error_message'] = "Incorrect voting code.";
		}
		
		if($return['error'] == 0)
		{
			$this->db->query("INSERT INTO `votes` (`tweet_id`, `vote`, `ip`, `time`)
									VALUES(".$tweet_id.", ".$vote.", '".$_SERVER['REMOTE_ADDR']."', ".(time()+$this->settings['timezone_offset']).")");			
			$return['success'] = 1;
			
			// get new vote totals
			$return['total'] = 0;
			$this->db->query("SELECT COUNT(*) as votes, vote FROM `votes` GROUP BY `vote`");
			while($get_totals = $this->db->fetch_row())
			{
				$return['total_'.$get_totals['vote']] = $get_totals['votes'];
				$return['total'] += $get_totals['votes'];
			}
		}
		
		echo json_encode($return);
	}
	
	public function get_tweets($json_data=10)
	{
		if($this->ajax == 0)
		{
			array_push($this->js_includes, "js/stream.js");
			$tmpl_tweet_display = new XTemplate('template/tweet_display.xtpl');
			$limit = (int)$json_data;
			
			$this->js_tmpl->load_template('tweet_display', 'tweet_display.tweet_box');
			$this->js_tmpl->create_js_from_blocks();
		}
		else
		{
			$data = json_decode($json_data, true);
			$limit = $data['limit'];
			
			$return = array();
			$return['success'] = 0;
		}
		
		// get current user totals
		// get a list of tweets voted on by the current user
		$user_voted_list = array();
		$voted_list = array();
		$rated = $no_news = $news = $news_op = 0;
		$total_rated = $total_no_news = $total_news = $total_news_op = 0;
		
		// WHERE `ip`='".."'
		$this->db->query("SELECT * FROM `votes`");
		while($votes = $this->db->fetch_row())
		{
			if($votes['ip'] == $_SERVER['REMOTE_ADDR'])
			{
				if(empty($user_votes_list[$votes['tweet_id']]))
				{
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
				
				$user_voted_list[$votes['tweet_id']] = 1;
			}
			
			$total_rated++;
			switch($votes['vote'])
			{
				case 1:
					$total_no_news++;
				break;
				case 2:
					$total_news++;
				break;
				case 3:
					$total_news_op++;
				break;
			}
			$voted_list[$votes['tweet_id']] = 1;
		}
		
		if(sizeof($voted_list) == 0)
		{
			$voted_list[0] = 1;
		}

		// check how many tweets with no votes 
		$this->db->query("SELECT id FROM `init_tweets`");
		$total_tweets = $this->db->rows();
		
		if(($total_tweets - sizeof($voted_list)) > 1000)
		{
			$extra = " AND `id`NOT IN(".implode(",", array_keys($voted_list)).")";
		}
		else
		{
			$extra = "";
		}
		
		$classify = new classify(array(1=>"no_news", 2=>"news", 3=>"news_op"), array(), false);
		$classify->add_tweets();
		$type = array(1=>"Not News", 2=>"News Rp", 3=>"News Op");

		$return_tweets = array();
		if(sizeof($user_voted_list) != 0)
		{
			$user_extra = "AND `id`NOT IN(".implode(",", array_keys($user_voted_list)).")"; 
		}
		else
		{
			$user_extra = "";
		}
		$this->db->query("SELECT * FROM `init_tweets` WHERE 1 ".$user_extra.$extra." AND `guess`NOT IN(0,1) ORDER BY RAND() LIMIT ".$limit);
		while($tweet = $this->db->fetch_row())
		{
			
			$return_tweet = array(
				"id"=>$tweet['id'],
				"tweet_text"=>$tweet['tweet'],
				"tweet_id"=>$tweet['twitter_id'],
				"user"=>$tweet['user'],
				"time"=>date("g:i a", $tweet['created']),
				"date"=>date("d/m/Y", $tweet['created']),
				"guess"=>$type[$classify->classify_tweet($tweet['tweet'])]
			);
			
			if($this->ajax == 1)
			{
				$this->fill($return_tweet, $return_tweets);
			}
			else
			{
				$this->fill($return_tweet, $tmpl_tweet_display);
			}
		}
			
		if($this->ajax == 0)
		{
			// create a toplist
			$this->db->query("SELECT COUNT(*) as vote_total, ip FROM `votes` GROUP BY `ip` ORDER BY `vote_total` DESC LIMIT 10");
			while($users = $this->db->fetch_row())
			{
				$tmpl_tweet_display->assign('ip', $users['ip']);
				$tmpl_tweet_display->assign('num_classified', $users['vote_total']);
				
				if($users['ip'] == $_SERVER['REMOTE_ADDR'])
				{
						$tmpl_tweet_display->assign('you', "(You)");
				}
				else
				{
					$tmpl_tweet_display->assign('you', "");
				}
				
				$tmpl_tweet_display->parse("tweet_display.top_list");
			}			
			
			$tmpl_tweet_display->assign('rated', $rated);
			$tmpl_tweet_display->assign('no_news', $no_news);
			$tmpl_tweet_display->assign('news', $news);
			$tmpl_tweet_display->assign('news_op', $news_op);
			
			$tmpl_tweet_display->assign('total_rated', $total_rated);
			$tmpl_tweet_display->assign('total_no_news', $total_no_news);
			$tmpl_tweet_display->assign('total_news', $total_news);
			$tmpl_tweet_display->assign('total_news_op', $total_news_op);
		
			$tmpl_tweet_display->parse("tweet_display");
			$this->template_out = &$tmpl_tweet_display;
		}
		else
		{
			$return['success'] = 1;
			$return['tweets'] = $return_tweets;
			
			echo json_encode(utf8json($return));
		}
	}	

	private function fill($values, &$collect)
	{
		if($this->ajax == 0)
		{
			foreach($values as $key=>$val)
			{
				$collect->assign($key, $val);
			}
			$collect->parse("tweet_display.tweet_box");
		}
		else
		{
			$collect[] = $values;
		}
	}
}



?>