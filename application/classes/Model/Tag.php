<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Tag extends ORM {

	protected $_table_name = 'tags';
	
	protected $_table_columns = array('id'=>array(), 'name'=>array());

	protected static $_currently_selected;

	protected $_has_many = array(
		'images'=>array('through'=>'tags_to_images', 'far_key'=>'image', 'foreign_key'=>'tag'),
	);

	public function get_list($quoted)
	{
		$out = array();
		$tags = $this->order_by('name')->find_all();
		foreach ($tags as $tag)
		{
			$out[] = ($quoted) ? addcslashes($tag->name, '"') : $tag->name;
		}
		$glue = ($quoted) ? '", "' : ', ';
		return implode($glue, $out);
	}

	static public function parse($string, $remove = FALSE)
	{
		preg_match_all('/([-+][0-9]+)/', $string, $matches);
		//echo Kohana_Debug::vars($matches);
		$tags = array();
		foreach ($matches[0] as $tag)
		{
			$id = substr($tag, 1);
			if ($id != $remove)
			{
				$tags["$id"] = substr($tag,0,1);
			}
		}
		ksort($tags);
		return $tags;
	}

	public function url($existing, $remove = FALSE)
	{
		$result = '';
		foreach ($this->parse($existing, $remove) as $tag=>$sign)
		{
			$result .= $sign.$tag;
		}
		return URL::site("tags/$result");
	}

}
