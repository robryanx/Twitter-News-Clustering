<?php

abstract class page_base
{
	protected $template_out;
	protected $page_title;
	protected $load_js;
	protected $js_passthrough = array();
	protected $settings;
	protected $db;
	protected $js_tmpl;
	public $js_includes = array();
	
	public function __construct()
	{
		global $settings, $db, $js_tmpl; 
				
		$this->settings = &$settings;
		$this->db = &$db;
		$this->js_tmpl = &$js_tmpl;
	}
	
	public function get_page_title()
	{
		return $this->page_title;
	}
	
	public function get_template_out()
	{
		return $this->template_out;
	}
	
	public function get_passthrough_js_varibles()
	{
		if(sizeof($this->js_passthrough) == 0)
		{
			return false;
		}
		else
		{
			return $this->js_passthrough;
		}		
	}
}

?>