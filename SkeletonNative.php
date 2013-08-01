<?php
class __classname
{
	protected $obj;
	__properties
	private $properties = Array(
		__properties_array
	);
	__methods
	public function __construct($obj)
	{
		$this->obj = $obj;
	}
	public function __get($key)
	{
		if (in_array($key,$this->properties)) { return $this->obj->$key; }
	}
	public function __set($key,$value)
	{
		if (in_array($key,$this->properties))
		{
			$this->obj->$key = $value;
			
			//try to read the object property back out to store in our local copy
			$this->$key = $this->obj->$key;
		}
	}
}