<?php 

require '../src/System3D/IFC2JSON/IFC2JSON.php'; 
use System3D\IFC2JSON\IFC2JSON;

$IFC2JSON 	= new IFC2JSON( "model.ifc" );

echo $IFC2JSON->getJson();

echo "<br/>";

echo $IFC2JSON->getJson( "model2.ifc" );