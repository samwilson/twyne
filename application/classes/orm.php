<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Application-specific overriding of ORM class.
 *
 * @package UAM
 * @category Model
 * @author Sam Wilson
 */
class ORM extends Kohana_ORM
{

	/**
	 * Save all possible values from `$_POST` to this model.
	 *
	 * @return boolean
	 */
	public function save_from_post()
	{
		if (count($_POST) > 0)
		{
			//$this->_load_values($_POST);

			// Never save the PK
			if (isset($_POST[$this->_primary_key]))
			{
				$this->find($_POST[$this->_primary_key]);
				unset($_POST[$this->_primary_key]);
			}
			foreach ($_POST as $key => $val)
			{
				try
				{
					$val = (is_string($val) && empty($val)) ? NULl : $val;
					$this->$key = $val;
				} catch (Kohana_Exception $e)
				{
					// Throw away exceptions raised for non-existent keys.
				}
			}
			if ($this->check())
			{
				$this->save();
				return TRUE;
			}
		}
		return FALSE;
	}
}