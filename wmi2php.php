<?php
/*
wmi2php by robert.labrie@gmail.com

This is essentially a re-implementation of ScriptomaticV2.hta which exports PHP class files for WMI classes
*/
parse_str(implode('&', array_slice($argv, 1)), $cmd);
$oper = $argv[1];
if (isset($cmd['--computer'])) { $computer  = $cmd['--computer']; } else { $computer = "."; }

if ($oper == "namespaces")
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
	if (isset($cmd['--output'])) { $output  = $cmd['--output']; } else { $output = $namespace; }
	if (isset($cmd['--mode'])) { $mode  = $cmd['--mode']; } else { $mode = "overload"; }
	if (!file_exists($output))
	{
		mkdir($output);
	}
	foreach ($classes as $class)
	{
		if ($mode == "overload")
		{
			$data = GenerateOverload($computer,$cmd['--namespace'],$class);
			file_put_contents("$output/$class.php",$data);
		}
		if ($mode == "native")
		{
			$data = GenerateNative($computer,$cmd['--namespace'],$class);
			file_put_contents("$output/$class.php",$data);
		}
	}
	die();
}
echo <<<EOT
usage: wmi2php oper [--computer=computername] [additional parameters]

If computer is not specified, the local system will be used.

namespaces [--computer=computername]
Prints a list of WMI namespaces
example: wmi2php.php namespaces

classes --namespace=namespace [--computer=computername]
Prints a list of WMI classes for a given namespace.
example: wmi2php.php classes --namespace=root/CIMV2

properties --namespace=namespace --class=classname [--computer=computername]
Prints a list of properties for the given WMI object
example: wmi2php.php properties --namespace=root/CIMV2 --class=Win32_ComputerSystem

methods --namespace=namespace --class=classname [--computer=computername]
Prints a list of methods for the given WMI object
example: wmi2php.php methods --namespace=root/CIMV2 --class=Win32_ComputerSystem

generate --namespace=namespace [--class=classname] [--output=path]
	[--computer=computername] [--mode=overload]
Generates a PHP class file for the given namespace. If class is not s file is
generated for every class in the namespace. If output is not specified, the
namespace is used as output dir. If mode is not specified, the overload
skeleton is used
example: wmi2php.php generate --namespace=root/CIMV2 --class=Win32_ComputerSystem
EOT;
function GenerateOverload($computer,$namespace,$class)
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
function GenerateNative($computer,$namespace,$class)
{
	$data = file_get_contents("SkeletonNative.php");
	$data = str_replace("__classname",$class,$data);
	$methodskel = 'public function __method() { return $this->obj->__method(); }';
	$propertyskel = 'private $__property;';
	//first do the property validation array
	$properties = WMIEnumerate($computer,$namespace,$class,"property");
	$properties = "'" . implode("','",$properties) . "'";
	$properties = str_replace(",",",\n\t\t",$properties);
	$data = str_replace("__properties_array",$properties,$data);

	//now do this discreet properties
	$properties = WMIEnumerate($computer,$namespace,$class,"property");
	$propertyout = "";
	foreach ($properties as $property)
	{
		$propertyout .= str_replace("__property",$property,$propertyskel) . "\n\t";
	}
	$data = str_replace("__properties",$propertyout,$data);
	
	//do the methods
	$methods = WMIEnumerate($computer,$namespace,$class,"method");
	$methodout = "";
	foreach ($methods as $method)
	{
		$methodout .= str_replace("__method",$method,$methodskel) . "\n\t";
	}
	$data = str_replace("__methods",$methodout,$data);
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