<?php
// execute_query_objects.php
// executes the object query passed by setup_query_objects.php
if((array_key_exists('steps',$_SESSION))&&(array_key_exists("selObj",$_SESSION['steps'])))
  $step=$_SESSION['steps']["selObj"];
if(array_key_exists('multiplepagenr',$_GET))
  $min = ($_GET['multiplepagenr']-1)*$step;
elseif(array_key_exists('multiplepagenr',$_POST))
  $min = ($_POST['multiplepagenr']-1)*$step;
elseif(array_key_exists('min',$_GET))
  $min=$_GET['min'];
else
  $min = 0;
$link=$baseURL."index.php?indexAction=query_objects";
reset($_GET);
while(list($key,$value)=each($_GET))
	if(($key!='indexAction')&&($key!='multiplepagenr')&&($key!='sort')&&($key!='sortdirection')&&($key!='showPartOfs'))
    $link.='&amp;'.urlencode($key).'='.urlencode($value);
if(count($_SESSION['Qobj'])>1) //=============================================== valid result, multiple objects found
{ echo "<div id=\"main\">";
  $title="<h4>".LangSelectedObjectsTitle;
	if($showPartOfs)	
	  $title.=LangListQueryObjectsMessage10;
	else
    $title.=LangListQueryObjectsMessage11;
  if(array_key_exists('deepskylog_id', $_SESSION)&&$_SESSION['deepskylog_id']&&
	   array_key_exists('listname',$_SESSION)&&$_SESSION['listname']&&($_SESSION['listname']<>"----------")&&$myList)
    $title.="&nbsp;-&nbsp;<a href=\"".$link."&amp;min=".$min."&amp;addAllObjectsFromQueryToList=true\" title=\"".LangListQueryObjectsMessage5.$listname_ss."\">".LangListQueryObjectsMessage4."</a>";
  $title.="</h4>";
  list ($min,$max,$content) = $objUtil->printNewListHeader3($_SESSION['Qobj'],$link,$min,$step);
  $objPresentations->line(array($title,$content),"LR",array(70,30),30);
	$content2=$objUtil->printStepsPerPage3($link,"selObj",$step);
  if($showPartOfs)
    $objPresentations->line(array("<a href=\"".$link."&amp;showPartOfs=0\">".LangListQueryObjectsMessage12."</a>",$content2),"LR",array(70,30),20);
	else
    $objPresentations->line(array("<a href=\"".$link."&amp;showPartOfs=1\">".LangListQueryObjectsMessage13."</a>",$content2),"LR",array(70,30),25);
//	echo "<span style=\"text-align:right\">&nbsp;&nbsp;&nbsp;<a href=\"".$baseURL."index.php?indexAction=query_objects\">".LangExecuteQueryObjectsMessage1."</a></span>";  
  $link.="&amp;showPartOfs=".$showPartOfs;
	echo "<hr />";
	$_GET['min']=$min;
	$_GET['max']=$max;
	if($FF)
	if($FF)
	{ echo "<script type=\"text/javascript\">";
    echo "theResizeElement='obj_list';";
    echo "theResizeSize=70;";
    echo "</script>";
	}
	$objObject->showObjects($link, $min, $max,'',0, $step);
	echo "<hr />";
	$content1 =$objPresentations->promptWithLinkText(LangListQueryObjectsMessage14,LangListQueryObjectsMessage15,$baseURL."objects.pdf?SID=Qobj",LangExecuteQueryObjectsMessage4);
	$content1.="&nbsp;-&nbsp;";
	$content1.=$objPresentations->promptWithLinkText(LangListQueryObjectsMessage14,LangListQueryObjectsMessage15,$baseURL."objectnames.pdf?SID=Qobj",LangExecuteQueryObjectsMessage4b);
	$content1.="&nbsp;-&nbsp;";
	$content1.=$objPresentations->promptWithLinkText(LangListQueryObjectsMessage14,LangListQueryObjectsMessage15,$baseURL."objectsDetails.pdf?SID=Qobj&amp;sort=".$_SESSION['QobjSort'],LangExecuteQueryObjectsMessage4c);
	$content1.="&nbsp;-&nbsp;";
	$content1.="<a href=\"".$baseURL."objects.argo?SID=Qobj\" target=\"new_window\">".LangExecuteQueryObjectsMessage8."</a>";
	$content1.="&nbsp;-&nbsp;";
  if(array_key_exists('listname',$_SESSION)&&$_SESSION['listname']&&$myList)
	  $content1.="<a href=\"".$link."&amp;min=".$min."&amp;addAllObjectsFromQueryToList=true\" title=\"".LangListQueryObjectsMessage5.$_SESSION['listname']."\">".LangListQueryObjectsMessage4."</a>"."&nbsp;-&nbsp;";
	$content1.="<a href=\"".$baseURL."objects.csv?SID=Qobj\" target=\"new_window\">".LangExecuteQueryObjectsMessage6."</a>";
	$objPresentations->line(array($content1),"L",array(100),25);
  echo "</div>";
}
else // ========================================================================no results found
{ echo "<div id=\"main\">";
  echo "<h2>".LangSelectedObjectsTitle."</h2>";
  echo LangExecuteQueryObjectsMessage2;
  echo "<p>";
	echo "<a href=\"".$baseURL."index.php?indexAction=query_objects\">".LangExecuteQueryObjectsMessage2a."</a>";
	echo "</div>";
}

?>
