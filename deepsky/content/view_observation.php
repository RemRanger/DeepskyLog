<?php // view_observation.php - view information of observation 
if(!($observationid=$objUtil->checkGetKey('observation')))   
   throw new Exception ("No observation defined in view_observation.php");
else if(!($object=$GLOBALS['objObservation']->getDsObservationProperty($observationid,'objectname')))    // check if observation exists
   throw new Exception ("No observed object found in view_observation.php");
else
{
echo "<div id=\"main\">";
$object_ss = stripslashes($object);
$seen = "<a target=\"_top\" href=\"".$baseURL."index.php?indexAction=detail_object&amp;object=".urlencode($object)."\" title=\"".LangObjectNSeen."\">-</a>";
$seenDetails=$objObject->getSeen($object);
if (substr($seenDetails,0,1)=="X")
	$seen="<a target=\"_top\" href=\"".$baseURL . "index.php?indexAction=result_selected_observations&amp;object=".urlencode($object)."\" title=\"".LangObjectXSeen."\">".$seenDetails."</a>";
if($loggedUser)
	if(substr($seenDetails,0,1)=="Y")
		$seen="<a target=\"_top\" href=\"".$baseURL."index.php?indexAction=result_selected_observations&amp;object=".urlencode($object)."\" title=\"".LangObjectYSeen."\">".$seenDetails."</a>";
$objPresentations->line(array("<h4>".LangViewObjectTitle."&nbsp;-&nbsp;".$object_ss."&nbsp;-&nbsp;".LangOverviewObjectsHeader7."&nbsp;:&nbsp;".$seen."</h4>"),
                        "L",array(100),30);
$topline="&nbsp;-&nbsp;"."<a href=\"".$baseURL."index.php?indexAction=detail_object&amp;object=".urlencode($object)."\">".LangViewObjectViewNearbyObject." ".$object_ss."</a>";
if(substr($objObject->getSeen($object),0,1)!='-')
  $topline.="&nbsp;-&nbsp;<a target=\"_top\" href=\"".$baseURL."index.php?indexAction=result_selected_observations&amp;object=".urlencode($object)."\">".LangViewObjectObservations."&nbsp;".$object_ss."</a>";
if($loggedUser)
	$topline.="&nbsp;-&nbsp;"."<a target=\"_top\" href=\"" . $baseURL . "index.php?indexAction=add_observation&amp;object=" . urlencode($object) . "\">" . LangViewObjectAddObservation . $object_ss . "</a>";
if($myList) 
{ if ($objList->checkObjectInMyActiveList($object))
		$topline.="&nbsp;-&nbsp;"."<a target=\"_top\" href=\"" . $baseURL . "index.php?indexAction=result_selected_observations&amp;object=" . urlencode($object) . "&amp;removeObjectFromList=" . urlencode($object) . "\">" . $object_ss . LangListQueryObjectsMessage3 . $listname_ss . "</a>";
	else
		$topline.="&nbsp;-&nbsp;"."<a target=\"_top\" href=\"" . $baseURL . "index.php?indexAction=result_selected_observations&amp;object=" . urlencode($object) . "&amp;addObjectToList=" . urlencode($object) . "&amp;showname=" . urlencode($object) . "\">" . $object_ss . LangListQueryObjectsMessage2 . $listname_ss . "</a>";
}
$topline.="&nbsp;-&nbsp;".$objPresentations->getDSSDeepskyLiveLinks($object);
$objPresentations->line(array(substr($topline,13)),"L",array(100),20);
echo "<hr />";
$objObject->showObject($object);
$content='';
if($loggedUser)                  // LOGGED IN
{ if($_GET['dalm']!="D")
	  $content ="<a href=\"".$baseURL."index.php?indexAction=detail_observation&amp;observation=".$observationid."&amp;dalm=D\" title=\"".LangDetail."\">".LangDetailText."</a>"."&nbsp;";
	if($_GET["dalm"]!="AO")
	  $content.="<a href=\"".$baseURL."index.php?indexAction=detail_observation&amp;observation=".$observationid."&amp;dalm=AO\" title=\"".LangAO."\">".LangAOText."</a>"."&nbsp;";
  if ($objObservation->getObservationsUserObject($loggedUser, $object)>0)
  { if($_GET['dalm']!="MO")
	    $content.="<a href=\"".$baseURL."index.php?indexAction=detail_observation&amp;observation=".$observationid."&amp;dalm=MO\" title=\"".LangMO."\">".LangMOText."</a>"."&nbsp;";
	  if($_GET['dalm']!="LO")
	    $content.="<a href=\"".$baseURL."index.php?indexAction=detail_observation&amp;observation=".$observationid."&amp;dalm=LO\" title=\"".LangLO."\">".LangLOText."</a>"."&nbsp;";
  }
  $content.=LangOverviewObservationsHeader5a;
  $objPresentations->line(array($content),"L",array(100),20);
  echo "<hr />";
}
$objObservation->showObservation($_GET['observation']);
if($_GET['dalm']=="AO") $AOid = $GLOBALS['objObservation']->getAOObservationsId($object, $_GET['observation']);
elseif($_GET['dalm']=="MO") $AOid = $GLOBALS['objObservation']->getMOObservationsId($object, $_SESSION['deepskylog_id'], $_GET['observation']);
elseif($_GET['dalm']=="LO") $AOid = array($GLOBALS['objObservation']->getLOObservationId($object, $_SESSION['deepskylog_id'], $_GET['observation']));
else $AOid=array();
while(list($key, $LOid) = each($AOid)) 
  $objObservation->showObservation($LOid);
echo "</div>";
}
?>
