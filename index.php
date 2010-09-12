<?php
ob_start("ob_gzhandler");

/* 
	Crowd Source Classifier Training
	Index Page (index.php)
	26/08/10 Rob Ryan
*/


// load configuration file
include_once("config.php");

// load the database class
include_once("includes/db_mysql.php");
$db = new DB($config);

include_once("includes/xtemplate.class.php");

// get the header and footer templates 
$tmpl_header = new XTemplate('template/header.xtpl');
$tmpl_footer = new XTemplate('template/footer.xtpl');

// include the js templates class
include_once("js_ajax_templates.php");
$js_tmpl = new js_templates($tmpl_header);

include_once("page_base.php");

$page = $_GET['page'];

switch($page)
{
	default:
		$page = "tweet_display";
		include_once("stream.php");
		$main_class = new stream();
		$main_class->get_tweets();
	break;
}

// include global js
if(is_array($config['global_js']) && (sizeof($config['global_js']) != 0))
{
	foreach($config['global_js'] as $js_includes)
	{
		$tmpl_header->assign('include_path', $js_includes);
		$tmpl_header->parse('header.javascript_includes');
	}
}

// include page specific js
if(is_array($main_class->js_includes) && (sizeof($main_class->js_includes) != 0))
{
	foreach($main_class->js_includes as $js_includes)
	{
		$tmpl_header->assign('include_path', $js_includes);
		$tmpl_header->parse('header.javascript_includes');
	}
}

// check for js passthrough varibles 
$js_compiled = array();
if(method_exists($main_class, 'get_passthrough_js_varibles'))
{
	if(($js_passthrough = $main_class->get_passthrough_js_varibles()) != false)
	{
		foreach($js_passthrough as $key=>$val)
		{	
			if(is_array($val) && (sizeof($val) != 0))
			{
				array_push($js_compiled, $key." = new Array(".sizeof($val).");");
				foreach($val as $key2=>$val2)
				{
					array_push($js_compiled, $key."['".$key2."'] = '".$val2."';");
				}
			}
			else if(!is_array($val))
			{
				array_push($js_compiled, "var ".$key." = '".$val."';");
			}
		}
	}
}

if(sizeof($js_compiled) != 0)
{
	$js_comp = implode("\n", $js_compiled);
	$tmpl_header->assign('pass_through', $js_comp);
}

// check for onload javascript execution
if(method_exists($main_class, 'get_onload_js'))
{
	if($main_class->get_onload_js() !== false)
	{
		$tmpl_header->assign('onload_functions', $main_class->get_onload_js());
	}
}

$tmpl_header->assign('page_title', $main_class->get_page_title());
$tmpl_header->parse("header");

$tmpl_footer->assign('date_time_gmt', date($settings['long_date_format'], (time()+$timezone_offset_seconds)));
$tmpl_footer->parse("footer");
			
$tmpl_header->out("header");
if(is_object($main_class->get_template_out()))
{
	$main_class->get_template_out()->out($page);
}
$tmpl_footer->out("footer");

?>