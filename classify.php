<?php

class classify extends page_base
{
	private $classes = array(1=>"no_news", 2=>"news", 3=>"news_op");
	private $class_mapping = array();
	
	private $class_count = array(1=>0, 2=>0, 3=>0);
	private $tweet_count = 0;
	
	private $class_token_count = array(1=>0, 2=>0, 3=>0);
	private $token_count = 0;	
	
	private $index = array();
	private $prior = array(1=>.33, 2=>.33, 3=>.33);
	
	private $tokens = array();
	private $tokens_all = array();
	
	private $debug;

	private $st = array("i", "a", "all", "also","am", "an", "and", "any","anything","anyway", "are", "at", "be", "been", "but", "by", 
	"can", "cannot", "cant", "de", "do", "done", "else", "etc", "even", "ever", "every", "from", "get", "give", "go", "had", "has", "hasnt", "have", "he", "her", "here", "hers", 
	"herself", "him", "himself", "his", "how", "is", "ie", "if", "in", "it", "into", "its", "know", "ltd", "may", "me", "most", "much", "must", "my", "myself",
	"no", "noone", "nor", "not", "of", "often", "on", "onto", "or", "other", "our", "ours", "re", "same", "see", "seem", "she", "should", "so", "some", "such", "than", "that", "start",
	"the", "them", "then", "there", "these", "they", "to", "too", "up", "us", "was", "we", "well", "were", "what", "when", "which", "who",
	"why", "will", "with", "would", "yet", "you", "your", "rt", "u", "for", "as");
	private $stopwords = array();

	public function __construct($classes, $class_mapping, $debug = false)
	{
		foreach($this->st as $s)
		{
			$this->stopwords[$s] = 1;
		}
		
		$this->classes = $classes;
		$this->class_mapping = $class_mapping;
		$this->debug = $debug;
		
		parent::__construct();
	}	

	public function add_tweets($classes = array(), $limiter=0)
	{
		$class_extra = "";
		if(sizeof($classes) != 0)
		{
			// only add tweets for certain classes
			$class_extra = " AND `class`IN('".implode("','", $classes)."')";
		}
		
		// create a class bias
		$class_limit = array();
		
		// get tweets along with their vote
		$tweet_index = array();
		$this->db->query("SELECT * FROM `init_tweets` WHERE `test`=0".$class_extra);
		while($tweet = $this->db->fetch_row())
		{
			$tweet_index[$tweet['id']] = $tweet;
		}		
		
		$this->db->query("SELECT * FROM `votes`");
		while($vote = $this->db->fetch_row())
		{
			if(!isset($tweet_index[$vote['tweet_id']]['vote']))
				$tweet_index[$vote['tweet_id']]['vote'] = array();
				
			$tweet_index[$vote['tweet_id']]['vote'][] = $this->class_mapping[$vote['vote']];
		}
		
		foreach($tweet_index as $tweet)
		{
			if(isset($tweet['vote']))
			{
				if(sizeof($tweet['vote']) > 1)
				{
					$count = 0;
					for($i=0; $i<sizeof($tweet['vote']); $i++)
					{
						$count += $tweet['vote'][$i];
					}
					$vote = round(($count / sizeof($tweet['vote'])), 0);
				}
				else
				{
					$vote = $tweet['vote'][0];
				}
				
				$class_limit[$vote]++;
				if(($class_limit[$vote] <= $limiter) || ($limiter == 0)) 
				{
					$this->tweet_count++;
					$this->class_count[$vote]++;
					
					$tokens = $this->tokenise($tweet['tweet']);
					foreach($tokens as $token)
					{
						if(!isset($this->stopwords[$token]))
						{
							if(!isset($this->index[$token][$vote])) 
								$this->index[$token][$vote] = 0;
							
							$this->index[$token][$vote]++;
							$this->class_token_count[$vote]++;
							$this->token_count++;
							
							$this->tokens[$vote][$token]++;
							$this->tokens_all[$token]++;
						}
					}
				}
			}
		}
		
		if($this->debug == true)
		{
			// arrange index by appearance
			$relative = array();
			foreach($this->tokens_all as $token=>$count)
			{
				for($i=1; $i<=3; $i++)
				{
					if(isset($this->tokens[$i][$token]) && ($this->tokens[$i][$token] > 10))
					{
						$relative[$i][$token] = $this->tokens[$i][$token] / $count;
					}
				}
			}
			
			for($i=1; $i<=3; $i++)
			{
				arsort($relative[$i]);
				foreach($relative[$i] as $token=>$count)
				{
					if(!isset($this->stopwords[$token]))
					{
						echo $token."==".$count."<br />";
					}
				}
				echo "<br /><br />";
			}
		}
	}
	
	public function classify_tweet($tweet)
	{
		for($i=1; $i<=sizeof($this->classes); $i++)
		{
			$this->prior[$i] = $this->class_count[$i] / $this->tweet_count;
		}

		$tokens = $this->tokenise($tweet);
        $classScores = array();
		$classNonZero = array();
		
		foreach($this->classes as $class=>$name) 
		{
			$classScores[$class] = 1;
			$nonzero = 0;
			foreach($tokens as $token) 
			{
				if(!isset($this->stopwords[$token]))
				{
					$count = isset($this->index[$token][$class]) ? $this->index[$token][$class] : 0;
					
					if($this->debug == true)
						echo $token."==".$class."==".$count."<br />";
					
					if($count != 0)
					{
						$nonzero = 1;
					}
					$classScores[$class] *= ($count + 1) / ($this->class_token_count[$class] + $this->token_count);
				}
			}
			$classScores[$class] = $this->prior[$class] * $classScores[$class];
			
			if($nonzero == 0)
			{
				$classNonZero[$class] = 0;
			}
        }
        
		if($this->debug == true)
			var_dump($classScores);
		   
        arsort($classScores);
		
		if($this->debug == true)
			echo key($classScores);
		
		
		foreach($classScores as $class=>$score)
		{
			if(!isset($classNonZero[$class]))
			{
				return $class;
			}
		}
        return 0;
	}
	
	private function tokenise($tweet) 
	{
        $tweet = strtolower($tweet);
		$tweet = $this->clean($tweet);
		
        preg_match_all('/[a-zA-Z0-9#]+/', $tweet, $matches);
        return $matches[0];
    }
	
	private function clean($tweet)
	{
		$tweet = $this->cleaner($tweet);
		return $tweet;
	}
	
	private function containsTLD($string) 
	{
		preg_match("/(AC($|\/)|\.AD($|\/)|\.AE($|\/)|\.AERO($|\/)|\.AF($|\/)|\.AG($|\/)|\.AI($|\/)|\.AL($|\/)|\.AM($|\/)|\.AN($|\/)|\.AO($|\/)|\.AQ($|\/)|\.AR($|\/)|\.ARPA($|\/)|\.AS($|\/)|\.ASIA($|\/)|\.AT($|\/)|\.AU($|\/)|\.AW($|\/)|\.AX($|\/)|\.AZ($|\/)|\.BA($|\/)|\.BB($|\/)|\.BD($|\/)|\.BE($|\/)|\.BF($|\/)|\.BG($|\/)|\.BH($|\/)|\.BI($|\/)|\.BIZ($|\/)|\.BJ($|\/)|\.BM($|\/)|\.BN($|\/)|\.BO($|\/)|\.BR($|\/)|\.BS($|\/)|\.BT($|\/)|\.BV($|\/)|\.BW($|\/)|\.BY($|\/)|\.BZ($|\/)|\.CA($|\/)|\.CAT($|\/)|\.CC($|\/)|\.CD($|\/)|\.CF($|\/)|\.CG($|\/)|\.CH($|\/)|\.CI($|\/)|\.CK($|\/)|\.CL($|\/)|\.CM($|\/)|\.CN($|\/)|\.CO($|\/)|\.COM($|\/)|\.COOP($|\/)|\.CR($|\/)|\.CU($|\/)|\.CV($|\/)|\.CX($|\/)|\.CY($|\/)|\.CZ($|\/)|\.DE($|\/)|\.DJ($|\/)|\.DK($|\/)|\.DM($|\/)|\.DO($|\/)|\.DZ($|\/)|\.EC($|\/)|\.EDU($|\/)|\.EE($|\/)|\.EG($|\/)|\.ER($|\/)|\.ES($|\/)|\.ET($|\/)|\.EU($|\/)|\.FI($|\/)|\.FJ($|\/)|\.FK($|\/)|\.FM($|\/)|\.FO($|\/)|\.FR($|\/)|\.GA($|\/)|\.GB($|\/)|\.GD($|\/)|\.GE($|\/)|\.GF($|\/)|\.GG($|\/)|\.GH($|\/)|\.GI($|\/)|\.GL($|\/)|\.GM($|\/)|\.GN($|\/)|\.GOV($|\/)|\.GP($|\/)|\.GQ($|\/)|\.GR($|\/)|\.GS($|\/)|\.GT($|\/)|\.GU($|\/)|\.GW($|\/)|\.GY($|\/)|\.HK($|\/)|\.HM($|\/)|\.HN($|\/)|\.HR($|\/)|\.HT($|\/)|\.HU($|\/)|\.ID($|\/)|\.IE($|\/)|\.IL($|\/)|\.IM($|\/)|\.IN($|\/)|\.INFO($|\/)|\.INT($|\/)|\.IO($|\/)|\.IQ($|\/)|\.IR($|\/)|\.IS($|\/)|\.IT($|\/)|\.JE($|\/)|\.JM($|\/)|\.JO($|\/)|\.JOBS($|\/)|\.JP($|\/)|\.KE($|\/)|\.KG($|\/)|\.KH($|\/)|\.KI($|\/)|\.KM($|\/)|\.KN($|\/)|\.KP($|\/)|\.KR($|\/)|\.KW($|\/)|\.KY($|\/)|\.KZ($|\/)|\.LA($|\/)|\.LB($|\/)|\.LC($|\/)|\.LI($|\/)|\.LK($|\/)|\.LR($|\/)|\.LS($|\/)|\.LT($|\/)|\.LU($|\/)|\.LV($|\/)|\.LY($|\/)|\.MA($|\/)|\.MC($|\/)|\.MD($|\/)|\.ME($|\/)|\.MG($|\/)|\.MH($|\/)|\.MIL($|\/)|\.MK($|\/)|\.ML($|\/)|\.MM($|\/)|\.MN($|\/)|\.MO($|\/)|\.MOBI($|\/)|\.MP($|\/)|\.MQ($|\/)|\.MR($|\/)|\.MS($|\/)|\.MT($|\/)|\.MU($|\/)|\.MUSEUM($|\/)|\.MV($|\/)|\.MW($|\/)|\.MX($|\/)|\.MY($|\/)|\.MZ($|\/)|\.NA($|\/)|\.NAME($|\/)|\.NC($|\/)|\.NE($|\/)|\.NET($|\/)|\.NF($|\/)|\.NG($|\/)|\.NI($|\/)|\.NL($|\/)|\.NO($|\/)|\.NP($|\/)|\.NR($|\/)|\.NU($|\/)|\.NZ($|\/)|\.OM($|\/)|\.ORG($|\/)|\.PA($|\/)|\.PE($|\/)|\.PF($|\/)|\.PG($|\/)|\.PH($|\/)|\.PK($|\/)|\.PL($|\/)|\.PM($|\/)|\.PN($|\/)|\.PR($|\/)|\.PRO($|\/)|\.PS($|\/)|\.PT($|\/)|\.PW($|\/)|\.PY($|\/)|\.QA($|\/)|\.RE($|\/)|\.RO($|\/)|\.RS($|\/)|\.RU($|\/)|\.RW($|\/)|\.SA($|\/)|\.SB($|\/)|\.SC($|\/)|\.SD($|\/)|\.SE($|\/)|\.SG($|\/)|\.SH($|\/)|\.SI($|\/)|\.SJ($|\/)|\.SK($|\/)|\.SL($|\/)|\.SM($|\/)|\.SN($|\/)|\.SO($|\/)|\.SR($|\/)|\.ST($|\/)|\.SU($|\/)|\.SV($|\/)|\.SY($|\/)|\.SZ($|\/)|\.TC($|\/)|\.TD($|\/)|\.TEL($|\/)|\.TF($|\/)|\.TG($|\/)|\.TH($|\/)|\.TJ($|\/)|\.TK($|\/)|\.TL($|\/)|\.TM($|\/)|\.TN($|\/)|\.TO($|\/)|\.TP($|\/)|\.TR($|\/)|\.TRAVEL($|\/)|\.TT($|\/)|\.TV($|\/)|\.TW($|\/)|\.TZ($|\/)|\.UA($|\/)|\.UG($|\/)|\.UK($|\/)|\.US($|\/)|\.UY($|\/)|\.UZ($|\/)|\.VA($|\/)|\.VC($|\/)|\.VE($|\/)|\.VG($|\/)|\.VI($|\/)|\.VN($|\/)|\.VU($|\/)|\.WF($|\/)|\.WS($|\/)|\.XN--0ZWM56D($|\/)|\.XN--11B5BS3A9AJ6G($|\/)|\.XN--80AKHBYKNJ4F($|\/)|\.XN--9T4B11YI5A($|\/)|\.XN--DEBA0AD($|\/)|\.XN--G6W251D($|\/)|\.XN--HGBK6AJ7F53BBA($|\/)|\.XN--HLCJ6AYA9ESC7A($|\/)|\.XN--JXALPDLP($|\/)|\.XN--KGBECHTV($|\/)|\.XN--ZCKZAH($|\/)|\.YE($|\/)|\.YT($|\/)|\.YU($|\/)|\.ZA($|\/)|\.ZM($|\/)|\.ZW)/i", $string, $M);
		$has_tld = (count($M) > 0) ? true : false;
		return $has_tld;
	}

	private function cleaner($url) {
	  $U = explode(' ',$url);

	  $W =array();
	  foreach ($U as $k => $u) {
		if($u{0} == '@')
		{
			unset($U[$k]);
		} else if (stristr($u,".")) { //only preg_match if there is a dot    
		  if ($this->containsTLD($u) === true) {
		  $U[$k] = "lllinklll";
		  return $this->cleaner( implode(' ',$U));
		  } 
		} else {
		   // stemmer
			//$t = $u;
			//$U[$k] = PorterStemmer::Stem($u);
				//echo $t."==".$u."<br />";
		}
	  }
	  return implode(' ',$U);
	}

	
}

?>