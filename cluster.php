<?php

class cluster extends page_base
{
	private $base_term_vector = array();
	private $total_documents = 0;
	private $document_frequency = array();
	private $tweet_term_vectors = array();
	private $idf_store = array();
	
	public function __construct()
	{
		parent::__construct();
		
		$this->init_term_vector();
	}
	
	private function init_term_vector()
	{
		$this->db->query("SELECT tweet FROM `init_tweets`");
		while($tweets = $this->db->fetch_row())
		{
			$tweet_tokens = $this->tokenise($tweets['tweet']);
			$term_watch = array();
			$this->total_documents++;
			
			foreach($tweet_tokens as $token)
			{
				if(!isset($term_watch[$token]))
				{
					$term_watch[$token] = 1;
					if(!isset($this->base_term_vector[$token]))
					{
						$this->base_term_vector[$token] = 0;
						$this->document_frequency[$token] = 1;
					}
					else
					{
						$this->document_frequency[$token]++;
					}
				}
			}
		}
	}
	
	public function tf_idf($tweet, $tweet_id)
	{
		$this->tweet_term_vectors[$tweet_id] = $this->base_term_vector;
		
		$tweet_tokens = $this->tokenise($tweet);
		$token_count = sizeof($tweet_tokens);
		
		$term_count = array();
		
		foreach($tweet_tokens as $token)
		{
			$term_count[$token]++;
			
			if(!isset($this->idf_store[$token]))
			{
				// calculate current term idf
				$this->idf_store[$token] = log($this->total_documents / $this->document_frequency[$token]);
			}
		}
		
		foreach($term_count as $token=>$count)
		{
			$tf = $count / $token_count;
			$this->tweet_term_vectors[$tweet_id][$token] = ($tf * $this->idf_store[$token]);
			echo $token."==".$this->tweet_term_vectors[$tweet_id][$token]."<br />";
		}
		
		return $this->tweet_term_vectors[$tweet_id];
	}
	
	public function cosine_similarity()
	{
		
	}
	
	private function tokenise($tweet) 
	{
        $tweet = strtolower($tweet);
		
        preg_match_all('/[a-zA-Z0-9#.]+/', $tweet, $matches);
        return $matches[0];
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
		  //$U[$k] = "lllinklll";
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