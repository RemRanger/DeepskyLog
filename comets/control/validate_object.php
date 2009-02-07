<?php

// validate_object.php
// checks if the add new comet form is correctly filled in
// and eventually adds the comet to the database

// Version 0.1: 20050921, WDM


include_once "lib/cometobjects.php";
include_once "lib/observers.php";
include_once "lib/setup/vars.php";
include_once "lib/util.php";

$util = new Utils();
$util->checkUserInput();

if ($_POST['newobject']) // pushed add new object button
{

  // check if required fields are filled in

  if (!$_POST['name'])
  {
    $entryMessage = LangValidateObjectMessage1;
    $_GET['indexAction']='default_action';
  }
  else // all required fields filled in
  {
    $objects = new CometObjects();
    // control if object doesn't exist yet
    $name = $_POST['name'];
    $query1 = array("name" => $name);
	  if(count($objects->getObjectFromQuery($query1, "name")) > 0) // object already exists
    {
    $entryMessage = LangValidateObjectMessage2;
    $_GET['indexAction']='default_action';
          }
    else
    {
    // fill database
      $id = $objects->addObject($name);
      if($_POST['icqname'])
      {
        $objects->setIcqName($id, $_POST['icqname']);
      }
      $_GET['indexAction']='comets_detail_object';
      $_GET['object']=$id;
    }
  }
}
elseif ($_POST['clearfields']) // pushed clear fields button
{
   $_GET['indexAction'] = 'comets_add_object';
}
?>
