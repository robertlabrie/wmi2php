<?php
/*
wmi2php by robert.labrie@gmail.com

This is essentially a re-implementation of ScriptomaticV2.hta which exports PHP class files for WMI classes
*/
parse_str(implode('&', array_slice($argv, 1)), $cmd);
$oper = $argv[1];
if (isset($cmd['--computer'])) { $computer  = $cmd['--computer']; } else { $computer = "."; }

if ($oper == "namespace")
{
	$spaces = Array();
	WMINameSpaces($spaces,$computer,"root");
	foreach ($spaces as $space)
	{
		echo "$space\n";
	}
	die();
}

if ($oper == "classes")
{
	if (!$cmd['--namespace'])
	{
		echo "usage php wmi2php.php classes --namespace=namespacename [--computer=computername]\n";
		die();
	}
	$classes = WMIClasses($computer,$cmd['--namespace']);
	foreach ($classes as $class)
	{
		echo "$class\n";
	}
	die();
}

if ($oper == "properties")
{
	
}

function WMIEnumerate($computer,$namespace,$classname,$itemtype)
{
	$obj = new COM("winmgmts://$computer/$namespace");
}
function WMINameSpaces(&$spaces,$computer,$name)
{
	$obj = new COM("winmgmts://$computer/$name");
	$items = $obj->InstancesOf("__NAMESPACE");
	foreach ($items as $item)
	{
		array_push($spaces,$name . "/" . $item->name);
		WMINameSpaces($spaces,$computer,$name . "/" . $item->name);
	}
}

function WMIClasses($computer,$namespace)
{
	$obj = new COM("winmgmts://$computer/$namespace");
	$out = Array();
	foreach ($obj->SubclassesOf() as $class)
	{
		//echo $class->name;
		//echo var_export($class,true);
		$dict = Array();
		foreach ($class->Qualifiers_ as $qualifier)
		{
			//echo $qualifier->name . "\n";
			array_push($dict,$qualifier->name);
		}
		if (in_array("dynamic",$dict))
		{
			array_push($out,$class->Path_->class);
		}
		
	}
	return $out;
}