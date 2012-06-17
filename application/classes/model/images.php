<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Images extends ORM {

	protected $_table_name = 'images';

	protected $_has_many = array(
		'tags'=>array(
			'model'=>'tags',
			'through'=>'image_tags',
			'far_key'=>'tag_id',
			'foreign_key'=>'image_id'
		),
	);

	protected $_belongs_to = array(
		'author'=>array('model'=>'People'),
		'licence'=>array('model'=>'Licences'),
		'auth_level'=>array('model'=>'AuthLevels'),
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
		$this->save();

		$dest_filename = DATAPATH."/images/full/$this->id.jpg";
		// Create destination directory if neccessary.
		$dest_dir = dirname($dest_filename);
		File::check_directory($dest_dir);
		rename($fullname, $dest_filename);

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

		$full = realpath(DATAPATH.'images/full/'.$this->id.'.jpg');
		if (!$full)
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
			$image = Image::factory($full, 'Imagick');
			$image->resize(80, 80);
			if (!$image->save($thumb))
			{
				throw new Kohana_Exception("Could not save thumbnail version of $this->id.");
			}
		}

		// Make view-size
		$viewdir = DATAPATH.'images/view';
		File::check_directory($viewdir);
		$view = $viewdir.'/'.$this->id.'.jpg';
		if (!file_exists($view) || $force)
		{
			$image = Image::factory($full, 'Imagick');
			$image->resize(500, 500);
			if (!$image->save($view))
			{
				throw new Kohana_Exception("Could not save 'view' version of $this->id.");
			}
		}
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