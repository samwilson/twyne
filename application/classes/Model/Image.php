<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Image extends ORM {

	protected $_table_name = 'images';

	protected $_table_columns = array(
		'id' => array(),
		'date_and_time' => array(),
		'caption' => array(),
		'auth_level_id' => array(),
		'author_id' => array(),
		'licence_id' => array(),
		'mime_type' => array(),
	);

	protected $_has_many = array(
		'tags'=>array(
			'model'=>'Tag',
			'through'=>'image_tags',
			'far_key'=>'tag_id',
			'foreign_key'=>'image_id'
		),
	);

	protected $_belongs_to = array(
		'author'=>array('model'=>'Person'),
		'licence'=>array(),
		'auth_level'=>array('model'=>'AuthLevel'),
	);

	public function year()
	{
		return substr($this->date_and_time, 0, 4);
	}

	public function month_name()
	{
		if ($this->month_number() == '00')
		{
			return 'unknown';
		}
		else
		{
			//exit('date: '.substr($this->date_and_time, 0, 10));
			return date('F', strtotime(substr($this->date_and_time, 0, 10)));
		}
	}

	public function month_number()
	{
		$month_number = substr($this->date_and_time, 5, 2);
		return $month_number;
	}

	/**
	 * Get a list of filenames currently in the 'pending' directory.
	 * 
	 * @return array Array of strings
	 */
	public function get_pending()
	{
		$images_in_dir = DATAPATH.'images'.DIRECTORY_SEPARATOR.'IN';
		$out = array();
		if (is_dir($images_in_dir)) {
			foreach (scandir($images_in_dir) as $file)
			{
				if (!is_file($images_in_dir.'/'.$file))
				{
					continue;
				}
				$out[] = $file;
			}
		}
		return $out;
	}

	/**
	 * Import the given image into the system.
	 *
	 * @param string $fullname Full filesystem path to file to import.
	 */
	public function import($fullname)
	{
		$caption = basename($fullname);
		$this->date_and_time = '0000-00-00 00:00:00';
		$exif_data = @exif_read_data($fullname, 'IFD0', 0);
		if ($exif_data)
		{
			if (isset($exif_data['DateTimeOriginal']))
			{
				$this->date_and_time = $exif_data['DateTimeOriginal'];
			}
			elseif (isset($exif_data['DateTime']))
			{
				$this->date_and_time = $exif_data['DateTime'];
			}
			elseif (isset($exif_data['CreateDate']))
			{
				$this->date_and_time = $exif_data['CreateDate'];
			}
		}
		elseif (preg_match('|([0-9]{4}-[0-9]{2}-[0-9]{2}).(([0-9]{2})([0-9]{2}))?(.*)jpg|i', $caption, $date_matches) > 0)
		{
			$hour = (isset($date_matches[3])) ? $date_matches[3] : '00';
			$minute = (isset($date_matches[4])) ? $date_matches[4] : '00';
			$this->date_and_time = $date_matches[1]." $hour:$minute";
			$caption = (isset($date_matches[5])) ? trim($date_matches[5]) : $caption;
		}
		$this->caption = str_replace('_', ' ', $caption);
		$this->mime_type = File::mime($fullname);
		$this->save();

		$dest_filename = DATAPATH."/images/full/$this->id.".File::ext_by_mime($this->mime_type);
		// Create destination directory if neccessary.
		$dest_dir = dirname($dest_filename);
		File::check_directory($dest_dir);
		rename($fullname, $dest_filename);
		chmod($dest_filename, 0660);

		$this->make_smaller_versions();
	}

	public function delete()
	{
		foreach (array('full', 'view', 'thumb') as $size)
		{
			$filename = DATAPATH."/images/$size/$this->id.jpg";
			if (file_exists($filename))
			{
				if (!unlink($filename))
				{
					//throw new Kohana_Exception("Could not delete $filename.", 'error');
				}
				else
				{
					//throw new Kohana_Exception("Deleted $filename.", 'success');
				}
			}
			else
			{
				//throw new Kohana_Exception("$filename does not exist.");
			}
		}
		return parent::delete();
	}

	public function make_smaller_versions($force = FALSE)
	{
		$ext = File::ext_by_mime($this->mime_type);
		$full = DATAPATH.'images/full/'.$this->id.'.'.$ext;
		if (!realpath($full))
		{
			$msg = "Full version of $this->id not found (at $full).";
			throw new Kohana_Exception($msg);
		}

		// Make thumbnail
		$thumbdir = DATAPATH.'images/thumb';
		File::check_directory($thumbdir);
		$thumb = $thumbdir.'/'.$this->id.'.jpg';
		if (!file_exists($thumb) || $force)
		{
			$image = Image::factory($full);
			$image->resize(80, 80);
			if (!$image->save($thumb))
			{
				throw new Kohana_Exception("Could not save thumbnail version of $this->id.");
			}
		}
		chmod($thumb, 0600);

		// Make view-size
		$viewdir = DATAPATH.'images/view';
		File::check_directory($viewdir);
		$view = $viewdir.'/'.$this->id.'.jpg';
		if (!file_exists($view) || $force)
		{
			$image = Image::factory($full);
			$image->resize(500, 500);
			if (!$image->save($view))
			{
				throw new Kohana_Exception("Could not save 'view' version of $this->id.");
			}
		}
		chmod($view, 0600);
	}

	public function rotate($degrees)
	{
		$full = realpath(DATAPATH.'images/full/'.$this->id.'.jpg');
		$image = Image::factory($full, 'Imagick');
		$image->rotate($degrees);
		if (!$image->save($full))
		{
			throw new Kohana_Exception("Could not save full-size, rotated version of $this->id.");
		}
		$this->make_smaller_versions(TRUE);
	}

}
