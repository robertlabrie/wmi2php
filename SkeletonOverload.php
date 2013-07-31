<?php
class <<classname>>
{
	private $properties = Array(
		<<properties>>
	);
	private $methods = Array(
		<<methods>>
	);
	private $obj;
	public function __construct($obj)
	{
		$this->obj = $obj;
	}
	public function __get($key)
	{
		if (in_array($key,$this->properties)) { return $this->node->$key; }
	}
	public function __set($key,$value)
	{
		if (in_array($key,$this->properties)) { $this->node->$key = $value; }
	}
	public function __call($key,$arguments)
	{
		if (in_array($key,$this->methods)) { return $this->node->$key($arguments); }
	}
}