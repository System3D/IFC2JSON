<?php 

require '../vendor/autoload.php'; 
require '../src/System3D/IFC2JSON/IFC2JSON.php'; 
use System3D\IFC2JSON\IFC2JSON;

$IFC2JSON = new IFC2JSON( "model.ifc", true );

print_r( $IFC2JSON->getJson() );