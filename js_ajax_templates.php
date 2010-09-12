<?php
	
/*
	TouchPoint Merchandising System
	Team Foxtrot 2009
	Versoin 1 (Created 07/08/09)
	js_ajax_templates.php (Javascript Templates Loader File)
*/
	
class js_templates
{
	private $templates = array();
	private $template_header;
	
	public function __construct(&$header)
	{
		$this->template_header = $header; 
	}
	
	public function load_template($template, $block)
	{
		$new_template = new XTemplate('template/'.$template.'.xtpl');
		$block_name = strrev($block);
		$block_name = substr($block_name, 0, strpos($block_name, "."));
		$block_name = strrev($block_name);
		
		$template_hold = $new_template->blocks[$block];
		
		// remove blocks from the template 
		$template_lines = explode("\n", $template_hold);
		$template_lines_parsed = array();
		for($i=0; $i<sizeof($template_lines); $i++)
		{
			if(!strstr($template_lines[$i], "BLOCK"))
				array_push($template_lines_parsed, $template_lines[$i]);
		}
		
		$this->templates[$block_name] = implode("\n", $template_lines_parsed);
	}
	
	public function create_js_from_blocks()
	{
		$all_block = "";
		foreach($this->templates as $blockname => $value)
		{
			$temp_block = str_replace('"', '\"', $value);
			$temp_block = str_replace("\r\n", "\\\r\n", $temp_block);
			
			$temp_block = "var ".$blockname."_template = \"".$temp_block."\";";
			$all_block .= $temp_block;
		}

		$this->write_blocks_to_js($all_block);
	}
	
	private function write_blocks_to_js($block)
	{
		$this->template_header->assign('js_templates', $block);
	}	
}

?>