<?php

/*
	TouchPoint Merchandising System
	Team Foxtrot 2009
	Version 1 (Created 14/05/09)
	db_mysql.php (mySQL Function Wrapper Class)
*/


class DB
{
	private $query;
	private $result;
	private $buffered_results = array();
	private $config;
	private $selected_db;
	private $prefix;
	private $query_log = array();
	private $use_apc = 0;
	private $current_database;
	private $previous_database;
	private $connection;
	
	public function __construct(&$config)
	{
		if(!empty($config['prefix']))
		{
			$this->prefix = $config['prefix'];
		}
		
		if(!empty($config['use_apc']))
		{
			$this->use_apc = $config['use_apc'];
		}
		
		$this->config = $config;
		$this->current_database = $config['database'];
		
		$this->db_connect();	
	}
	
	private function db_connect()
	{
		// Connect to the database
		if($this->connection)
		{
			mysql_close($this->connection);
		}
		
		@$this->connection = mysql_connect($this->config['db_host'], $this->config['db_username'], $this->config['db_password']);
		if(!$this->connection) 
		{ 
			echo "Connection Error- ".mysql_error();
		}
		
		@$this->selected_db = mysql_select_db($this->current_database, $this->connection);
		if(!$this->selected_db) 
		{ 
			echo "Select DB Error- ".mysql_error();
		}
	}
	
	public function ping()
	{
		if(!mysql_ping($this->connection))
		{
			$this->db_connect();
		}
	}
	
	public function change_database($database)
	{
		$this->previous_database = $this->current_database;
		$this->current_database = $database;
		
		@$this->selected_db = mysql_select_db($database, $this->connection);
		$error_check = mysql_error();
		if(!$this->selected_db) 
		{ 
			$this->error(2, $error_check);
		}	
	}
	
	public function revert()
	{
		$this->change_database($this->previous_database);
	}
	
	public function query($sql_query, $dispose = false, $count_query='')
	{
		$time_start = $this->make_microtime();
		
		if(!empty($this->result) && ($dispose == false))
		{
			// buffer the result
			array_push($this->buffered_results, $this->result);
		}
		
		if($dispose == false)
		{
			$this->query = $sql_query;
			$this->result = mysql_query($this->query, $this->connection);
			$error_check = mysql_error();
			$time_end = $this->make_microtime();
			array_push($this->query_log, array($this->query, ($time_end - $time_start)));
		}
		else
		{
			mysql_query($sql_query, $this->connection);
			$error_check = mysql_error();
			$time_end = $this->make_microtime();
			//array_push($this->query_log, array($sql_query, ($time_end - $time_start)));
		}
		
		
		
		if($error_check != "")
		{
			if(strstr($error_check, "server has gone away"))
			{
				$this->db_connect();
				$this->query($sql_query, $dispose);
			}
			else
			{
				if($dispose == false)
				{
					$this->error(2, $error_check, $this->query);
				}
				else
				{
					$this->error(2, $error_check, $sql_query);
				}
			}
		}
		else
		{
			if(!empty($count_query))
			{
				$time_start = $this->make_microtime();
				
				$count_query = mysql_query($count_query);
				$error_check = mysql_error();
				$time_end = $this->make_microtime();
				array_push($this->query_log, array($count_query, ($time_end - $time_start)));
		
				if($error_check != "")
				{
					$this->error(2, $error_check, $this->query);
					return false;
				}
				else
				{
					$count = $this->single_result('all_rows', $count_query);
					return $count;
				}
			}
		}
	}
	
	public function single_result($col, &$result = "")
	{
		if($result == "")
		{
			$result = $this->result;
		}
		
		@$value = mysql_result($result, 0, $col);
		$error_check = mysql_error();
		
		if($error_check != "")
		{
			$value = "";
			$this->error(3, $error_check);
		}	
		
		return $value;
	}
	
	public function fetch_row(&$result = "")
	{
		if($result == "")
		{
			$result = $this->result;
		}
		
		if((@$fetched = mysql_fetch_assoc($result)) !== false)
		{
			$error_check = mysql_error();
		
			if($error_check != "")
			{
				$fetched = "";
				$this->error(4, $error_check);
			}	
					
			return $fetched;
		}
		else if(sizeof($this->buffered_results) != 0)
		{
			$this->result = array_pop($this->buffered_results);
		}
	}
	
	public function fetch_obj_row(&$result = "")
	{
		if($result == "")
		{
			$result = $this->result;
		}
		
		if((@$fetched = mysql_fetch_object($result)) !== false)
		{
			$error_check = mysql_error();
		
			if($error_check != "")
			{
				$fetched = "";
				$this->error(4, $error_check);
			}	
		
			return $fetched;
		}
		else if(sizeof($this->buffered_results) != 0)
		{
			$this->result = array_pop($this->buffered_results);
		}
	}
	
	public function rows(&$result = "")
	{
		if($result == "")
		{
			$result = $this->result;
		}
		
		$num_rows = mysql_num_rows($result);
		$error_check = mysql_error();
		
		if($error_check != "")
		{
			$num_rows = "";
			$this->error(5, $error_check);
		}
		
		return $num_rows;
	}
	
	public function output_log()
	{
		$output = "<B>mySQL Query Usage</B><br />";
		foreach($this->query_log as $log)
		{
			$output .= "Query: ".$log[0]." -- Time Taken: ".$log[1]." seconds<br />";
		}
		
		return $output;
	}
	
	private function make_microtime()
	{
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		
		return $mtime; 
	}
	
	private function error($error_code, $message="", $query="")
	{
		switch($error_code)
		{
			case 1:
			
			break;
			case 2:
			
			break;
			case 3:
			
			break;
			default:
			
			break;
		}
		echo $message."<br />From Query: ".$query;
	}
}

?>