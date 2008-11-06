<?php
include '../common/head.php';                               // HTML head
$head = new head();
$head->printHeader($browsertitle);
$head->printMenu();
$head->printMeta("DeepskyLog");
include '../common/menu/headmenu.php';                      // HEAD MENU
menu($title);                                               // SUBTITLE
include '../common/menu/login.php';
include '../'.$_SESSION['module'].'/menu/search.php';       // SEARCH MENU
if(array_key_exists('deepskylog_id', $_SESSION) && $_SESSION['deepskylog_id']) // LOGGED IN
{ include '../'.$_SESSION['module'].'/menu/change.php';    // CHANGE MENU
  include '../'.$_SESSION['module'].'/menu/location.php';
  include '../'.$_SESSION['module'].'/menu/instrument.php';
  include '../common/menu/help.php';                       // HELP MENU 
  if(array_key_exists('admin', $_SESSION)&&($_SESSION['admin']=='yes'))
    include '../common/menu/admin.php';                    // ADMINISTRATION MENU
  include '../common/menu/out.php';                        // LOG OUT MENU 
}
else
{ include '../common/menu/help.php';                       // HELP MENU 
  include '../common/menu/languagemenu.php';               // LANGUAGE MENU 
}
include '../common/menu/endmenu.php';                                // END MENU
?>