<?php

// new_observation.php
// GUI to add a new observation to the database 
// Version 0.4: 2005/05/29, JV 

// include statements
// $$ ok

// Code cleanup - removed by David on 20080704
//include_once "../common/control/ra_to_hms.php";
//include_once "../common/control/dec_to_dm.php";
include_once "../lib/observations.php";

include_once "../lib/objects.php";
include_once "../lib/observers.php";
include_once "../lib/util.php";
include_once "../lib/lists.php";

$util = new Util();
$util->checkUserInput();
$objects = new Objects;
$observer = new Observers;
$list = new Lists;

if(array_key_exists('listname',$_SESSION) && ($list->checkList($_SESSION['listname'])==2)) $myList=True; else $myList = False;

echo("<div id=\"main\">\n");
if(array_key_exists('object', $_GET) && $_GET['object'])
{
  $seen = "<a href=\"deepsky/index.php?indexAction=detail_object&object=" . urlencode($_GET['object']) . "\" title=\"" . LangObjectNSeen . "\">-</a>";
  $seenDetails = $objects->getSeen($_GET['object']);
  if(substr($seenDetails,0,1)=="X") // object has been seen already
  {
    $seen = "<a href=\"deepsky/index.php?indexAction=result_selected_observations&object=" . urlencode($_GET['object']) . "\" title=\"" . LangObjectXSeen . "\">" . $seenDetails . "</a>";
  }
  if(array_key_exists('deepskylog_id', $_SESSION) && ($_SESSION['deepskylog_id']!=""))
  {
    if (substr($seenDetails,0,1)=="Y") // object has been seen by the observer logged in
      $seen = "<a href=\"deepsky/index.php?indexAction=result_selected_observations&object=" . urlencode($_GET['object']) . "\" title=\"" . LangObjectYSeen . "\">" . $seenDetails . "</a>";
  }  
  echo("<h2>");
  echo (LangNewObservationTitle . "&nbsp;" . $_GET['object']);
	echo "&nbsp;:&nbsp;" . $seen;
  echo("</h2>\n");
	$id = $_GET['object'];
  // check if an observation has already been submitted during this session
  // not correct as this form is processed twice (after looking up of object)
  // and $_GET['new'] is not passed again...
  // to be corrected
  $_SESSION['backlink'] = "new_observation.php";

//  echo("<ol><li value=\"2\">" . LangNewObservationSubtitle2 . "</li></ol>");
//  echo("<p></p>");
	echo "<table width=\"100%\"><tr>";
	echo("<td width=\"25%\" align=\"left\">");
	if($seen!="<a href=\"deepsky/index.php?indexAction=detail_object&object=" . urlencode($_GET['object']) . "\" title=\"" . LangObjectNSeen . "\">-</a>")
	  echo("<a href=\"deepsky/index.php?indexAction=result_selected_observations&object=" . urlencode($_GET['object']) . "\">" . LangViewObjectObservations . " " . $_GET['object']);
	echo("</td><td width=\"25%\" align=\"center\">");
  if (array_key_exists('deepskylog_id', $_SESSION) && ($_SESSION['deepskylog_id']!=""))
    echo("<a href=\"deepsky/index.php?indexAction=add_observation&object=" . 
		     urlencode($_GET['object']) . 
				 "\">" . LangViewObjectAddObservation . 
				 $_GET['object'] . "</a>");
	echo("</td>");
	if($myList)
	{
    echo("<td width=\"25%\" align=\"center\">");
    if($list->checkObjectInMyActiveList($_GET['object']))
      echo("<a href=\"deepsky/index.php?indexAction=detail_object&amp;object=" . $_GET['object'] . "&amp;removeObjectFromList=" . urlencode($_GET['object']) . "\">" . $_GET['object'] . LangListQueryObjectsMessage3 . $_SESSION['listname'] . "</a>");
    else
      echo("<a href=\"deepsky/index.php?indexAction=detail_object&amp;object=" . $_GET['object'] . "&amp;addObjectToList=" . urlencode($_GET['object']) . "&amp;showname=" . $_GET['object'] . "\">" . $_GET['object'] . LangListQueryObjectsMessage2 . $_SESSION['listname'] . "</a>");
	  echo("</td>");
	}	
	echo("</tr>");
	echo("</table>");
	
	$objects->showObject($id);
						
  echo("<ol><li value=\"3\">" . LangNewObservationSubtitle3 . "</li></ol>");
  echo("<p></p><form action=\"deepsky/control/validate_observation.php\" method=\"post\" enctype=\"multipart/form-data\">");
	echo("<table id=\"content\">");
	// LOCATION
	echo("<tr>");
	include_once "../lib/locations.php";
  $locations = new Locations;
  echo("<tr><td class=\"fieldname\" align=\"right\">" . LangViewObservationField4 . "&nbsp;*</td><td><select name=\"site\" style=\"width: 147px\">");
  $sites = $locations->getSortedLocationsList("name", $_SESSION['deepskylog_id']);

  for ($i = 0;$i < count($sites);$i++)
  {
    $sitename = $sites[$i][1];
    if(array_key_exists('savedata', $_SESSION) && ($_SESSION['savedata'] == "yes")) // multiple observations
    {
      if(array_key_exists('location', $_SESSION) && ($_SESSION['location'] == $sites[$i][1])) // location equals session location
      {
        print("<option selected=\"selected\" value=\"".$sites[$i][0]."\">$sitename</option>\n");
      }
      else
      {
        print("<option value=\"".$sites[$i][0]."\">$sitename</option>\n");
      }
    }
    else // first observation of session
    {
      if($observer->getStandardLocation($_SESSION['deepskylog_id']) == $sites[$i][0]) // location equals standard location
      {
        print("<option selected=\"selected\" value=\"".$sites[$i][0]."\">$sitename</option>\n");
      }
      else
      {
        print("<option value=\"".$sites[$i][0]."\">$sitename</option>\n");
      }
    }
  }
  echo("</select></td><td class=\"explanation\"><a href=\"common/add_site.php\">" . LangChangeAccountField7Expl ."</a></td>");
	echo("</tr>");
	
	
	//DATE  / TIME
	echo("<tr>");
	echo("<td class=\"fieldname\" align=\"right\">" . LangViewObservationField5 . "&nbsp;*</td>");
	echo("<td> <input type=\"text\" class=\"inputfield\" maxlength=\"2\" size=\"3\" name=\"day\"");
  if(array_key_exists('savedata', $_SESSION) && array_key_exists('day', $_SESSION) && ($_SESSION['savedata'] == "yes" && $_SESSION['day'] != ""))
    echo(" value=\"" . $_SESSION['day'] . "\" />");
  else
    echo(" value=\"\" />");
  echo("&nbsp;&nbsp;<select name=\"month\">");
  echo ("<option value=\"\"></option>");
  echo ("<option value=\"1\""); if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "1" && $_SESSION['savedata'] == "yes"))  echo (" selected=\"selected\">" . LangNewObservationMonth1 . "</option>");  else echo (">" . LangNewObservationMonth1 . "</option>");
  echo ("<option value=\"2\""); if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "2" && $_SESSION['savedata'] == "yes"))  echo (" selected=\"selected\">" . LangNewObservationMonth2 . "</option>");  else echo (">" . LangNewObservationMonth2 . "</option>");
  echo ("<option value=\"3\""); if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "3" && $_SESSION['savedata'] == "yes"))  echo (" selected=\"selected\">" . LangNewObservationMonth3 . "</option>");  else echo (">" . LangNewObservationMonth3 . "</option>");
  echo ("<option value=\"4\""); if( array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "4" && $_SESSION['savedata'] == "yes"))  echo (" selected=\"selected\">" . LangNewObservationMonth4 . "</option>");  else echo (">" . LangNewObservationMonth4 . "</option>");
  echo ("<option value=\"5\""); if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "5" && $_SESSION['savedata'] == "yes"))  echo (" selected=\"selected\">" . LangNewObservationMonth5 . "</option>");  else echo (">" . LangNewObservationMonth5 . "</option>");
  echo ("<option value=\"6\""); if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "6" && $_SESSION['savedata'] == "yes"))  echo (" selected=\"selected\">" . LangNewObservationMonth6 . "</option>");  else echo (">" . LangNewObservationMonth6 . "</option>");
  echo ("<option value=\"7\""); if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "7" && $_SESSION['savedata'] == "yes"))  echo (" selected=\"selected\">" . LangNewObservationMonth7 . "</option>");  else echo (">" . LangNewObservationMonth7 . "</option>");
  echo ("<option value=\"8\""); if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "8" && $_SESSION['savedata'] == "yes"))  echo (" selected=\"selected\">" . LangNewObservationMonth8 . "</option>");  else echo (">" . LangNewObservationMonth8 . "</option>");
  echo ("<option value=\"9\""); if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "9" && $_SESSION['savedata'] == "yes"))  echo (" selected=\"selected\">" . LangNewObservationMonth9 . "</option>");  else echo (">" . LangNewObservationMonth9 . "</option>");
  echo ("<option value=\"10\"");if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "10" && $_SESSION['savedata'] == "yes")) echo (" selected=\"selected\">" . LangNewObservationMonth10 . "</option>"); else echo (">" . LangNewObservationMonth10 . "</option>");
  echo ("<option value=\"11\"");if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "11" && $_SESSION['savedata'] == "yes")) echo (" selected=\"selected\">" . LangNewObservationMonth11 . "</option>"); else echo (">" . LangNewObservationMonth11 . "</option>");
  echo ("<option value=\"12\"");if (array_key_exists('month', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['month'] == "12" && $_SESSION['savedata'] == "yes")) echo (" selected=\"selected\">" . LangNewObservationMonth12 . "</option>"); else echo (">" . LangNewObservationMonth12 . "</option>");
  echo("</select>&nbsp;&nbsp<input type=\"text\" class=\"inputfield\" maxlength=\"4\" size=\"4\" name=\"year\"");
  if(array_key_exists('year', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['savedata'] == "yes" && $_SESSION['year'] != ""))
    echo ("value=\"" . $_SESSION['year'] . "\" />");
  else
    echo ("value=\"\" />");
  echo("</td><td class=\"explanation\">".LangViewObservationField10."</td>");
  echo("<td class=\"fieldname\" align=\"right\">");
  if ($observer->getUseLocal($_SESSION['deepskylog_id']))
    echo(LangViewObservationField9lt);
  else
    echo(LangViewObservationField9);
  echo ("</td><td><input type=\"text\" class=\"inputfield\" maxlength=\"2\" size=\"2\" name=\"hours\" ");
  if(array_key_exists('hours', $_SESSION) && ($_SESSION['hours'] != ""))
    echo ("value=\"" . $_SESSION['hours'] . "\"");
  echo("value=\"\" />&nbsp;&nbsp;<input type=\"text\" class=\"inputfield\" maxlength=\"2\" size=\"2\" name=\"minutes\" ");
  if(array_key_exists('minutes', $_SESSION) && ($_SESSION['minutes'] != ""))
    echo ("value=\"" . $_SESSION['minutes'] . "\"");
  echo("value=\"\" /></td><td class=\"explanation\">".LangViewObservationField11."</td>");
	echo("</tr>");
  
  //LIMITING MAG / SEEING
  echo("<tr>");
   // LIMITING MAG
	echo("<td class=\"fieldname\" align=\"right\">" . LangViewObservationField7 . "</td>
	          <td><input type=\"text\" class=\"inputfield\" maxlength=\"3\" name=\"limit\" size=\"3\"");
  if(array_key_exists('limit', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['savedata'] == "yes" && $_SESSION['limit'] != ""))
    echo (" value=\"" . sprintf("%1.1f", $_SESSION['limit']) . "\" />");
  else
    echo (" value=\"\" />");
	echo("</td>");
	echo("<td></td>");
  // SEEING
  echo("<td class=\"fieldname\" align=\"right\">" . LangViewObservationField6 . "</td>");
	echo("<td><select name=\"seeing\" style=\"width: 147px\">");
	echo("<option value=\"-1\"></option>");
  echo("<option value=\"1\""); if(array_key_exists('seeing', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['savedata'] == "yes" && $_SESSION['seeing'] == 1)) echo " selected=\"selected\""; echo (">".SeeingExcellent."</option>");  // EXCELLENT
  echo("<option value=\"2\""); if(array_key_exists('seeing', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['savedata'] == "yes" && $_SESSION['seeing'] == 2)) echo " selected=\"selected\""; echo (">".SeeingGood."</option>");       // GOOD
  echo("<option value=\"3\""); if(array_key_exists('seeing', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['savedata'] == "yes" && $_SESSION['seeing'] == 3)) echo " selected=\"selected\""; echo (">".SeeingModerate."</option>");   // MODERATE
  echo("<option value=\"4\""); if(array_key_exists('seeing', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['savedata'] == "yes" && $_SESSION['seeing'] == 4)) echo " selected=\"selected\""; echo (">".SeeingPoor."</option>");       // POOR
  echo("<option value=\"5\""); if(array_key_exists('seeing', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['savedata'] == "yes" && $_SESSION['seeing'] == 5)) echo " selected=\"selected\""; echo (">".SeeingBad."</option>");        // BAD
  echo("</select></td>");
	echo("<td></td>");		
	echo("</tr>");
	
  echo("<tr><td>&nbsp;</td></tr>"); 

  // INSTRUMENT / FILTER
  echo("<tr>");
  // INSTRUMENT
  include_once "../lib/instruments.php";
  $instruments = new Instruments;
  $instr = $instruments->getSortedInstrumentsList("name", $_SESSION['deepskylog_id'], false, InstrumentsNakedEye);
  echo("<td class=\"fieldname\" align=\"right\">" . LangViewObservationField3 . "&nbsp;*</td>");
	echo("<td> <select name=\"instrument\" style=\"width: 250px\">\n");
  echo("<option value=\"\"></option>\n"); // include empty instrument
  while(list ($key, $value) = each($instr)) // go through instrument array
  {
    $instrumentname = $value[1];
    $val = $value[0];
    if(array_key_exists('savedata', $_SESSION) && ($_SESSION['savedata'] == "yes")) // multiple observations
    {
      if($val == $_SESSION['instrument']) // instrument of previous observation
        print("<option selected=\"selected\" value=\"$val\">");
      else
        print("<option value=\"$val\">");
    }
    elseif($observer->getStandardTelescope($_SESSION['deepskylog_id']) == $val) // not executed when previous observation
      print("<option selected=\"selected\" value=\"$val\">");
    else // first observation of session and not the standard instrument
      print("<option value=\"$val\">");
    echo("$instrumentname</option>\n");
  }
  echo("</select></td>");
	echo("<td class=\"explanation\"><a href=\"common/add_instrument.php\">" . LangChangeAccountField8Expl . "</a></td>");
  // FILTER
  // create object
  include_once "../lib/filters.php";
  $filters = new Filters;
  $filts = $filters->getSortedFiltersList("name", $_SESSION['deepskylog_id'], false);
	echo("<td class=\"fieldname\" align=\"right\">" . LangViewObservationField31 . "&nbsp;</td>");
	echo("<td> <select name=\"filter\" style=\"width: 147px\">\n");
  echo("<option value=\"\"></option>\n"); // include empty filter
  while(list ($key, $value) = each($filts)) // go through instrument array
  {
    $filtername = $filters->getFilterName($value);
    $val = $value;
    print("<option value=\"$val\">");
    echo("$filtername</option>\n");
  }
  echo("</select></td>");
	echo("<td class=\"explanation\"><a href=\"common/add_filter.php\">" . LangViewObservationField31Expl . "</a>");
	echo("</td>");
	echo("</tr>");
	
  // EYEPIECE / LENS
	echo("<tr>");
  // EYEPIECE
  include_once "../lib/eyepieces.php";
  $eyepieces = new Eyepieces;
  $eyeps = $eyepieces->getSortedEyepiecesList("focalLength", $_SESSION['deepskylog_id'], false);
  echo("<td class=\"fieldname\" align=\"right\">" . LangViewObservationField30 . "&nbsp;</td>");
	echo("<td> <select name=\"eyepiece\" style=\"width: 147px\">\n");
  echo("<option value=\"\"></option>\n"); // include empty instrument
  while(list ($key, $value) = each($eyeps)) // go through instrument array
  {
    $eyepiecename = $eyepieces->getEyepieceName($value);
    $val = $value;
    print("<option value=\"$val\">");
    echo("$eyepiecename</option>\n");
  }
  echo("</select></td>");
	echo("<td class=\"explanation\"><a href=\"common/add_eyepiece.php\">" . LangViewObservationField30Expl . "</a>");
  echo("</td>");
  // LENS
  include_once "../lib/lenses.php";
  $lenses = new Lenses;
  $lns = $lenses->getSortedLensesList("name", $_SESSION['deepskylog_id'], false);
  echo("<td class=\"fieldname\" align=\"right\">" . LangViewObservationField32 . "&nbsp;</td>");
	echo("<td> <select name=\"lens\" style=\"width: 147px\">\n");
  echo("<option value=\"\"></option>\n"); // include empty lens
  while(list ($key, $value) = each($lns)) // go through instrument array
  {
    $lensname = $lenses->getLensName($value);
    $val = $value;
    print("<option value=\"$val\">");
    echo("$lensname</option>\n");
  }
  echo("</select></td>");
	echo("<td class=\"explanation\"><a href=\"common/add_lens.php\">" . LangViewObservationField32Expl . "</a>");
  echo("</td>");
	echo("</tr>");
	
	
  echo("<tr><td>&nbsp;</td></tr>");

	
	
  // VISIBILITY / DRAWING
	echo("<tr>");
	// Visibility of observations
  echo("<td class=\"fieldname\" align=\"right\">" . LangViewObservationField22 . "</td>
	           <td><select name=\"visibility\"><option value=\"0\"></option>");
	             // Very simple, prominent object
	             echo("<option value=\"1\">".LangVisibility1."</option>");
	             // Object easily percepted with direct vision
	             echo("<option value=\"2\">".LangVisibility2."</option>");
	             // Object perceptable with direct vision
	             echo("<option value=\"3\">".LangVisibility3."</option>");
	             // Averted vision required to percept object
	             echo("<option value=\"4\">".LangVisibility4."</option>");
	             // Object barely perceptable with averted vision
	             echo("<option value=\"5\">".LangVisibility5."</option>");
	             // Perception of object is very questionable
	             echo("<option value=\"6\">".LangVisibility6."</option>");
	             // Object definitely not seen
	             echo("<option value=\"7\">".LangVisibility7."</option>");
	             echo("</select></td>");
	echo("<td></td>");
	//DRAWING
  echo("<td class=\"fieldname\" align=\"right\">".LangViewObservationField12."</td>");
	echo("<td colspan=\"2\"><input type=\"file\" name=\"drawing\" /></td>");
	echo("</tr>");
	
 
  // DESCRIPTION
  echo("<tr>");
	echo("<td class=\"fieldname\" align=\"right\">" . LangViewObservationField8 . "&nbsp;*");
	echo("<br>");
	echo("<a href=\"http://www.deepsky.be/beschrijfobjecten.php\" target=\"new_window\">" . LangViewObservationFieldHelpDescription . "</a></td>");
	echo("<td width=\"100%\" colspan=\"5\">");
	echo("<textarea name=\"description\" class=\"description\">");
  // keep description after wrong observation
  if(array_key_exists('description', $_SESSION) && ($_SESSION['description'] != ""))
    echo $_SESSION['description'];
  echo("</textarea>");
	echo("</td>");
	echo("</tr>");
  
	echo("<tr>");
	echo("<td></td>");
	echo("<td>");
	echo("<input type=\"submit\" name=\"addobservation\" value=\"".LangViewObservationButton1."\" />&nbsp;");
	echo("<input type=\"submit\" name=\"clearfields\" value=\"".LangViewObservationButton2."\" /></td>");
  echo("<td align=\"right\">");
	 // Language of observation
  if(array_key_exists('language', $_SESSION) && array_key_exists('savedata', $_SESSION) && ($_SESSION['savedata'] == "yes"))
    $current_language = $_SESSION['language'];
  else
    $current_language = $observer->getObservationLanguage($_SESSION['deepskylog_id']);
  echo("<td class=\"fieldname\" align=\"right\">" . LangViewObservationField29 . "&nbsp;*</td><td>");
  $language = new Language(); 
  $allLanguages = $language->getAllLanguages($observer->getLanguage($_SESSION['deepskylog_id']));
  echo("<select name=\"description_language\" style=\"width: 147px\">");
  while(list ($key, $value) = each($allLanguages))
    if($current_language == $key)
      print("<option value=\"".$key."\" selected=\"selected\">".$value."</option>\n");
    else
      print("<option value=\"".$key."\">".$value."</option>\n");
  echo("</select></td>");
	echo("</tr>");
  echo("</table>");
	echo("<input type=\"hidden\" name=\"observedobject\" value=\"" . $id . "\"></form>");
}
else // no object found or not pushed on search button yet
{
  echo("<h2>");
  echo (LangNewObservationTitle);
  echo("</h2>\n");
  // upper form
  echo("<form action=\"deepsky/control/validate_search_object.php\" method=\"post\" enctype=\"multipart/form-data\">\n");
  echo("<ol><li value=\"1\">" . LangNewObservationSubtitle1a . "");
  echo(LangNewObservationSubtitle1abis);
  echo("<a href=\"deepsky/index.php?indexAction=add_csv\">" . LangNewObservationSubtitle1b . "</a>");
  echo("</li></ol>");
  echo("<table width=\"100%\" id=\"content\">\n");
  // OBJECT NAME 
  echo("<tr>\n");
  echo("<td class=\"fieldname\">");
  echo LangQueryObjectsField1;
  echo("</td>\n<td colspan=\"2\">\n");
  echo("<select name=\"catalogue\">\n");
  echo("<option value=\"\"></option>"); // empty field
  $catalogs = $objects->getCatalogues();
  while(list($key, $value) = each($catalogs))
    echo("<option value=\"$value\">$value</option>\n");
  echo("</select>\n");
  echo("<input type=\"text\" class=\"inputfield\" maxlength=\"255\" name=\"number\" size=\"50\" value=\"\" /></td>");
  echo("<td><input type=\"submit\" name=\"objectsearch\" value=\"" . LangNewObservationButton1 . "\" />\n</td>");
	echo("</tr>");
	echo("</table>");
	echo("</form>");
  // end upper form
  $_SESSION['backlink'] = "new_observation.php";
}
echo("</div>\n</div>\n</body>\n</html>");
?>