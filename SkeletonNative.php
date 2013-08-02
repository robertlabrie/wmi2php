<?php
namespace __namespace;
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
		$this->populateproperties();
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
	/**
	* populate the properties of the wrapper class with the properties of the native WMI class
	**/
	public function populateproperties()
	{
		foreach ($this->properties as $p)
		{
			$this->$p = $this->obj->$p;
		}
	}
}