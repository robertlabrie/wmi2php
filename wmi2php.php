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
	$res = WMIEnumerate($computer,$cmd['--namespace'],$cmd['--class'],"property");
	foreach ($res as $r) { echo "$r\n"; }
	die();
}

if ($oper == "methods")
{
	$res = WMIEnumerate($computer,$cmd['--namespace'],$cmd['--class'],"method");
	foreach ($res as $r) { echo "$r\n"; }
	die();
}
if ($oper == "generate")
{
	if (!$cmd['--namespace'])
	{
		echo "usage php wmi2php.php generate --namespace=namespacename [--computer=computername] [--class=classname]\n";
		die();
	}
	if (isset($cmd['--class']))
	{
		$classes=Array($cmd['--class']);
	} 
	else 
	{ 
		$classes = WMIClasses($computer,$cmd['--namespace']);
	}
	$namespace = substr($cmd['--namespace'],strrpos($cmd['--namespace'],"/")+1);

	if (!file_exists($namespace))
	{
		mkdir($namespace);
	}
	foreach ($classes as $class)
	{
		$data = Generate_Overload($computer,$cmd['--namespace'],$class);
		file_put_contents("$namespace/$class.php",$data);
	}
	die();
}
echo "usage: wmi2php oper [--computer=computername] [additional parameters]";
function Generate_Overload($computer,$namespace,$class)
{
	$data = file_get_contents("SkeletonOverload.php");
	$data = str_replace("__classname",$class,$data);
	
	$properties = WMIEnumerate($computer,$namespace,$class,"property");
	$properties = "'" . implode("','",$properties) . "'";
	$properties = str_replace(",",",\n\t\t",$properties);
	$data = str_replace("__properties",$properties,$data);

	$methods = WMIEnumerate($computer,$namespace,$class,"method");
	$methods = "'" . implode("','",$methods) . "'";
	$methods = str_replace(",",",\n\t\t",$methods);
	$data = str_replace("__methods",$methods,$data);

	return $data;
}
function WMIEnumerate($computer,$namespace,$classname,$itemtype)
{
	$wmi = new COM("winmgmts://$computer/$namespace");
	$obj = $wmi->Get($classname);
	$out = Array();
	if ($itemtype == "method") { $col = $obj->Methods_; }
	if ($itemtype == "property") { $col = $obj->Properties_; }
	foreach ($col as $i)
	{
		array_push($out,$i->Name);
	}
	return $out;
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