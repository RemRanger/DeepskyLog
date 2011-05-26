<?php 
// sessions.php
// The session class collects all functions needed to add, remove and adapt DeepskyLog sessions from the database.

global $inIndex;
if((!isset($inIndex))||(!$inIndex)) include "../../redirect.php";

class Sessions
{
  public  function getSessionPropertiesFromId($id)                                   // returns the properties of the session with id
  { global $objDatabase;
    return $objDatabase->selectRecordArray("SELECT * FROM sessions WHERE id=\"".$id."\"");
  }
  public  function getSessionPropertyFromId($id,$property,$defaultValue='')          // returns the property of the given session
  { global $objDatabase; 
    return $objDatabase->selectSingleValue("SELECT ".$property." FROM sessions WHERE id = \"".$id."\"",$property,$defaultValue);
  }
  public  function validateSession() 
  { global $loggedUser;
		if(!($loggedUser))
			throw new Exception(LangMessageNotLoggedIn);

		// The observers
		$observers = Array();

		$count = array_count_values($_POST['addedObserver']);
		if (isset($_POST['deletedObserver'])) {
		  $countRemoved = array_count_values($_POST['deletedObserver']);
		} else {
		  $countRemoved = Array();
		}

		foreach( $count as $k => $v)
		{
		  $val = $v;
		  $val2 = 0;
		  if (array_key_exists($k, $countRemoved)) {
		    $val2 = $countRemoved[$k];
		  }
		  if (($val - $val2) == 1) {
		    $observers[] = $k;
		  }
		}

		$this->addSession($_POST['sessionname'], $_POST['beginday'], $_POST['beginmonth'], $_POST['beginyear'], 
		                  $_POST['beginhours'], $_POST['beginminutes'], $_POST['endday'], $_POST['endmonth'], 
		                  $_POST['endyear'], $_POST['endhours'], $_POST['endminutes'], $_POST['site'], $_POST['weather'], 
		                  $_POST['equipment'], $_POST['comments'], $_POST['description_language'], $observers);
  }
  
  public  function addSession($sessionname, $beginday, $beginmonth, $beginyear, $beginhours, $beginminutes, $endday, 
                                $endmonth, $endyear, $endhours, $endminutes, $location, $weather, $equipment, $comments,
                                $language, $observers)
  { global $objDatabase, $loggedUser, $dateformat;
    // Make sure not to insert bad code in the database
    $name = html_entity_decode($sessionname, ENT_COMPAT, "ISO-8859-15");
		$name = preg_replace("/(\")/", "", $name);
		$name = preg_replace("/;/", ",", $name);

		$begindate = date('Y-m-d H:i:s', mktime($beginhours, $beginminutes, 0, $beginmonth, $beginday, $beginyear));
		$enddate = date('Y-m-d H:i:s', mktime($endhours, $endminutes, 0, $endmonth, $endday, $endyear));
		
		// Auto-generate the session name
		if ($name == "") {
		  if ($beginday == $endday && $beginmonth == $endmonth && $beginyear == $endyear) {
		    $name = LangSessionTitle1 . date($dateformat, mktime(0, 0, 0, $beginmonth, $beginday, $beginyear));
		  } else {  
		    $name = LangSessionTitle1 . date($dateformat, mktime(0, 0, 0, $beginmonth, $beginday, $beginyear))
		              . LangSessionTitle2 . date($dateformat, mktime(0, 0, 0, $endmonth, $endday, $endyear));
		  }
		}
		
		$weather = html_entity_decode($weather, ENT_COMPAT, "ISO-8859-15");
		$weather = preg_replace("/(\")/", "", $weather);
		$weather = preg_replace("/;/", ",", $weather);
		
    $equipment = html_entity_decode($equipment, ENT_COMPAT, "ISO-8859-15");
		$equipment = preg_replace("/(\")/", "", $equipment);
		$equipment = preg_replace("/;/", ",", $equipment);
		
    $comments = html_entity_decode($comments, ENT_COMPAT, "ISO-8859-15");
		$comments = preg_replace("/(\")/", "", $comments);
		$comments = preg_replace("/;/", ",", $comments);

    // First check whether the session already exists
		$sessions = $objDatabase->selectSingleArray("SELECT id from sessions where begindate=\"" . $begindate . "\" and enddate=\"" . $enddate . "\" and observerid=\"" . $loggedUser . "\";", "id");
		if (count($sessions) > 0) {
      // Update the session
		  $this->updateSession($sessions[0], $name, $begindate, $enddate, $location, $weather, $equipment, $comments, $language);

		  // First make sure to remove all old observations
		  $objDatabase->execSQL("DELETE from sessionObservations where sessionid=\"" . $sessions[0] . "\"");
		  // Add observations to the session
      $this->addObservations($sessions[0], $beginyear, $beginmonth, $beginday, $endyear, $endmonth, $endday, $observers);
      
      // Check if there is a new observer
		  $observersFromDatabase = $objDatabase->selectSingleArray("SELECT observer from sessionObservers where sessionid=\"" . $sessions[0] . "\";", "observer");
		  // Add the logged user to the list of the observers
		  $observersFromDatabase[] = $loggedUser;
		  $this->removeAllSessionObservations($sessions[0]);
		  for ($i = 0;$i < count($observers);$i++) {
		    if (!in_array($observers[$i], $observersFromDatabase)) {
		      // The observer is not in the database. We have to add a new user.
		      $this->addObserver($sessions[0], $observers[$i]);

          // Add observations to the session
          $this->addObservations($sessions[0], $beginyear, $beginmonth, $beginday, $endyear, $endmonth, $endday, $observers);
		    }
		  }
		} else {
		  // First add a new session with the observer which created the session (and set to active)
		  $objDatabase->execSQL("INSERT into sessions (name, observerid, begindate, enddate, locationid, weather, equipment, comments, language, active) VALUES(\"" . $name . "\", \""  . $loggedUser . "\", \"" . $begindate . "\", \"" . $enddate . "\", \"" . $location . "\", \"" . $weather . "\", \"" . $equipment . "\", \"" . $comments . "\", \"" . $language . "\", 1)");

		  // Get the id of the new session
		  $id = mysql_insert_id();

		  for ($i=1;$i<count($observers);$i++) {
		    // Add the observers to the sessionObservers table
		    $this->addObserver($id, $observers[$i]);

		    // Add the new session also for the other observers (and set to inactive)
        $objDatabase->execSQL("INSERT into sessions (name, observerid, begindate, enddate, locationid, weather, equipment, comments, language, active) VALUES(\"" . $name . "\", \"" . $observers[$i] . "\", \"" . $begindate . "\", \"" . $enddate . "\", \"" . $location . "\", \"" . $weather . "\", \"" . $equipment . "\", \"" . $comments . "\", \"" . $language . "\", 0)");
		    $newId = mysql_insert_id();
		    // Also add the extra observers to the sessionObservers table
		    for ($j=0;$j<count($observers);$j++) {
		      if ($j != $i) {
		        $objDatabase->execSQL("INSERT into sessionObservers (sessionid, observer) VALUES(\"" . $newId . "\", \"" . $observers[$j] . "\");");
		      }
		    }
      }

      // Add observations to the session
      $this->addObservations($id, $beginyear, $beginmonth, $beginday, $endyear, $endmonth, $endday, $observers);
		}
  }

	private  function addObserver($id, $observer) 
	{  global $objDatabase, $objMessages, $loggedUser, $objObserver, $baseURL;
	   $objDatabase->execSQL("INSERT into sessionObservers (sessionid, observer) VALUES(\"" . $id . "\", \"" . $observer . "\");");
     
     $observername = $objObserver->getObserverProperty($loggedUser, "firstname") . " " . $objObserver->getObserverProperty($loggedUser, "name");
     $subject =  $observername . LangAddSessionMessageTitle;
     $sessionname = $this->getSessionPropertyFromId($id, "name");
     $content = $observername . LangAddSessionMessage1 . $sessionname . LangAddSessionMessage2;
     $content .= "<br /><br />" . LangAddSessionMessage3 . "<a href=\"" . $baseURL . "index.php?indexAction=add_session\">" . LangAddSessionMessage4;
     $content .= "<br /><br />" . LangMessagePublicList5 . "<a href=\"" . $baseURL . 
	    						"index.php?indexAction=new_message&amp;receiver=" . urlencode($loggedUser) . 
	    						"&amp;subject=Re:%20" . urlencode($sessionname) . "\">" . $observername . "</a>";
     $content .= "<br /><br />Zend een bericht naar " . $observername;
     $objMessages->sendMessage($loggedUser, $observer, $subject, $content);
	}
	
  private  function addObservations($id, $beginyear, $beginmonth, $beginday, $endyear, $endmonth, $endday, $observers)
	{ global $objDatabase;
	  $begindate = sprintf("%4d%02d%02d", $beginyear, $beginmonth, $beginday);
    $enddate = sprintf("%4d%02d%02d", $endyear, $endmonth, $endday);

    // Add all observations to the sessionObservations table
		for ($i=0;$i<count($observers);$i++) {
		  // Select the observations of the observers in this session 
		  $obsids = $objDatabase->selectSingleArray("SELECT id from observations where observerid=\"" . $observers[$i] . "\" and date>=\"" . $begindate . "\" and date<=\"" . $enddate . "\";", "id");

		  for ($cnt=0;$cnt<count($obsids);$cnt++) {
		    // Add the observations to the sesionObservations table
		    $objDatabase->execSQL("INSERT into sessionObservations (sessionid, observationid) VALUES(\"" . $id . "\", \"" . $obsids[$cnt] . "\");");
		  }
		}
	}
		  
	private  function updateSession($id, $name, $begindate, $enddate, $location, $weather, $equipment, $comments, $language)
  { global $objDatabase;
    // Here we change the session
		$objDatabase->execSQL("UPDATE sessions set name=\"" . $name . "\" where id=\"" . $id . "\";");
		$objDatabase->execSQL("UPDATE sessions set begindate=\"" . $begindate . "\" where id=\"" . $id . "\";");
		$objDatabase->execSQL("UPDATE sessions set enddate=\"" . $enddate . "\" where id=\"" . $id . "\";");
		$objDatabase->execSQL("UPDATE sessions set locationid=\"" . $location . "\" where id=\"" . $id . "\";");
		$objDatabase->execSQL("UPDATE sessions set weather=\"" . $weather . "\" where id=\"" . $id . "\";");
		$objDatabase->execSQL("UPDATE sessions set equipment=\"" . $equipment . "\" where id=\"" . $id . "\";");
		$objDatabase->execSQL("UPDATE sessions set comments=\"" . $comments . "\" where id=\"" . $id . "\";");
		$objDatabase->execSQL("UPDATE sessions set language=\"" . $language . "\" where id=\"" . $id . "\";");
		$objDatabase->execSQL("UPDATE sessions set active=\"1\" where id=\"" . $id . "\";");
  }
  
  public  function removeAllSessionObservations($sessionid) 
  { global $objDatabase;
    $objDatabase->execSQL("DELETE FROM sessionObservations WHERE sessionid=\"". $sessionid . "\"");
  }
  
  public  function getListWithInactiveSessions($userid) 
  { global $objDatabase;
    return $objDatabase->selectRecordsetArray("SELECT id from sessions where observerid = \"" . $userid . "\" and active = \"0\";");
  }
  
  public  function getObservers($id) 
  { global $objDatabase;
    return $objDatabase->selectRecordsetArray("SELECT observer from sessionObservers where sessionid = \"" . $id . "\";");
  }
  
  public  function showInactiveSessions($userid) 
  { global $baseURL,$loggedUser,$objUtil,$objLocation,$objPresentations,$loggedUserName, $objObserver;
    $sessions = $this->getListWithInactiveSessions($userid);
    if($sessions!=null)
   {
     echo "<table>";
     echo "<tr class=\"type3\">";
     echo "<td class=\"centered\">" . LangAddSessionField1 ."</td>";
     echo "<td class=\"centered\">" . LangAddSessionField2a ."</td>";
     echo "<td class=\"centered\">" . LangAddSessionField3a ."</a></td>";
     echo "<td class=\"centered\">" . LangAddSessionField4a ."</a></td>";
     echo "<td class=\"centered\">" . LangAddSessionField5a ."</a></td>";
     echo "<td></td>";
     echo "<td></td>";
     echo "</tr>";
     $count = 0;
     while(list($key,$value) = each($sessions))
     { $session=$this->getSessionPropertiesFromId($value['id']);
       echo "<tr class=\"type".(2-($count%2))."\">";
       echo "<td>".$session['name']."</td>";
       echo "<td>".$session['begindate']."</td>";
       echo "<td>".$session['enddate']."</td>";
       echo "<td>".$objLocation->getLocationPropertyFromId($session['locationid'], "name")."</td>";
       echo "<td>";
       $observers = $this->getObservers($value['id']);
       if (count($observers) > 0) {
         for ($cnt = 0;$cnt < count($observers) - 1;$cnt++) {
           print $objObserver->getObserverProperty($observers[$cnt]['observer'], "firstname") . " " . 
             $objObserver->getObserverProperty($observers[$cnt]['observer'], "name") . " - ";
         }
         print $objObserver->getObserverProperty($observers[count($observers) - 1]['observer'], "firstname") . " " . 
             $objObserver->getObserverProperty($observers[count($observers) - 1]['observer'], "name");
       }
       echo "</td>";
		   echo "<td>";
		   // TODO : Remove the session
       echo("<a href=\"".$baseURL."index.php?indexAction=validate_delete_eyepiece&amp;eyepieceid=" . urlencode($value['id']) . "\">" . LangRemove . "</a>");
       echo "</td>";
       echo "<td>";
		   // TODO : Add the session
       echo("<a href=\"".$baseURL."index.php?indexAction=validate_delete_eyepiece&amp;eyepieceid=" . urlencode($value['id']) . "\">" . LangAddSessionButton . "</a>");
       echo "</td></tr>";
       $count++;
     }
     echo "</table>";
     echo "<hr />";
   }
 }
//{  public  function getNumberOfUnreadMails()
//  { global $objDatabase, $loggedUser;
//  	if($loggedUser) {
//  	  return " (" . count($this->getIdsNewMails($loggedUser)) . "/" . count($this->getIdsAllMails($loggedUser)) . ")";
//  	} else {
//  	  return "";
//  	}
//  }
//  
//  public  function getIdsNewMails($user)
//  { global $objDatabase;
//    $listOfAllMails = $this->getIdsAllMails($user);
//
//    // Read mails should not be counted in the first part
//    $readMails = $objDatabase->selectSingleArray("select id from messagesRead where receiver = \"".$user."\"", "id");
//
//    $cnt = 0;
//
//    for ($i = 0;$i < count($listOfAllMails);$i++) {
//      if (!in_array($listOfAllMails[$i], $readMails)) {
//        $listOfMails[$cnt] = $listOfAllMails[$i];
//        $cnt++;
//      }
//    }
//    if (isset($listOfMails)) {
//      // Swap the array
//      $listOfMails = array_reverse($listOfMails);
//      
//      return $listOfMails;
//    } else {
//      return Array();
//    } 
//  }
//  
//  // Returns a list of all mails. The deleted mails are not included in the list of id's. 
//  public  function getIdsAllMails($user)
//  { global $objDatabase;
//    $listOfAllMails = $objDatabase->selectSingleArray("select id from messages where receiver = \"".$user."\" or receiver = \"all\"", "id");
//
//    $listOfMails = Array();
//
//    // Removed mails should not be counted
//    $removedMails = $objDatabase->selectSingleArray("select id from messagesDeleted where receiver = \"".$user."\"", "id");
//
//    $cnt = 0;
//    
//    for ($i = 0;$i < count($listOfAllMails);$i++) {
//      if (!in_array($listOfAllMails[$i], $removedMails)) {
//        $listOfMails[$cnt] = $listOfAllMails[$i];
//        $cnt++;
//      }
//    }
//    
//    return $listOfMails;
//  }
// 
//  public  function getSubject($id)
//  { global $objDatabase;
//    return $objDatabase->selectSingleValue("select subject from messages where id = \"" . $id . "\"", "subject");
//  }
//
//  public  function getSender($id)
//  { global $objDatabase;
//    return $objDatabase->selectSingleValue("select sender from messages where id = \"" . $id . "\"", "sender");
//  }
//
//  public  function getReceiver($id)
//  { global $objDatabase;
//    return $objDatabase->selectSingleValue("select receiver from messages where id = \"" . $id . "\"", "receiver");
//  }
//
//  public  function getDate($id)
//  { global $objDatabase;
//    return $objDatabase->selectSingleValue("select date from messages where id = \"" . $id . "\"", "date");
//  }
//  
//  public  function getContent($id)
//  { global $objDatabase;
//    return $objDatabase->selectSingleValue("select message from messages where id = \"" . $id . "\"", "message");
//  }
//
//  public  function removeAllMessages($id)
//  { global $objDatabase;
//    if ($id != "") {
//      $allMessages = $this->getIdsAllMails($id);
//      for ($cnt=0;$cnt<count($allMessages);$cnt++) {
//        $objDatabase->execSQL("insert into messagesDeleted VALUES(\"" . $allMessages[$cnt] . "\", \"" . $id . "\")");
//      }
//    }
//  }
//
//  public  function getContentWithoutLinks($id)
//  { global $objDatabase;
//    $message = $objDatabase->selectSingleValue("select message from messages where id = \"" . $id . "\"", "message");
//    return strip_tags($message, '<br>');
//  }
//
//  public  function isRead($id, $receiver)
//  { global $objDatabase;
//    $read = $objDatabase->selectSingleValue("select id from messagesRead where id = \"" . $id . "\" and receiver = \"" . $receiver . "\"", "id");
//    if ($read == "") {
//      return false;
//    } else {
//      return true;
//    }
//  }
//
//  public  function isDeleted($id, $receiver)
//  { global $objDatabase;
//    $deleted = $objDatabase->selectSingleValue("select id from messagesDeleted where id = \"" . $id . "\" and receiver = \"" . $receiver . "\"", "id");
//    if ($deleted == "") {
//      return false;
//    } else {
//      return true;
//    }
//  }
//  
//  public  function markMessageRead($id, $receiver)
//  { global $objDatabase;
//    $objDatabase->execSQL("insert into messagesRead VALUES(\"" . $id . "\", \"" . $receiver . "\")");
//  }
//
//  public  function validateDeleteMessage()
//  { global $objDatabase, $loggedUser;
//    if ($loggedUser != "") {
//      $id = $_GET["id"];
//      $objDatabase->execSQL("insert into messagesDeleted VALUES(\"" . $id . "\", \"" . $loggedUser . "\")");
//    }
//  }
//  
//  public  function getReplyToSubject($id) 
//  { $subject = $this->getSubject($id);
//    if (strpos($subject, "Re : ") === false) {
//      return "Re : " . $subject;
//    } else {
//      return $subject;
//    }
//  }
//
//  public  function getReplyToMessage($id)
//  { $message = $this->getContent($id);
//    // Replace <br /> by <br />> 
//    $message = str_replace("<br />", "", $message);
//    $message = str_replace("\n", "\n> ", $message);
//    return "> " . $message;
//  }
//
//
//  // Returns a list of all read mails.  
//  public  function getIdsReadMails($user)
//  { global $objDatabase;
//    $listOfAllMails = $this->getIdsAllMails($user);
//    $listOfNewMails = $this->getIdsNewMails($user);
//    
//    $listOfReadMails = Array();
//
//    $cnt = 0;
//    
//    for ($i = 0;$i < count($listOfAllMails);$i++) {
//      if (!in_array($listOfAllMails[$i], $listOfNewMails)) {
//        $listOfReadMails[$cnt] = $listOfAllMails[$i];
//        $cnt++;
//      }
//    }
//    
//    // Swap the array
//    $listOfReadMails = array_reverse($listOfReadMails);
//
//    return $listOfReadMails;
//  }
//
//  public  function showListMails($newMails, $readMails, $min, $max, $link2, $step=25) 
//	{ global $baseURL, $baseURL, $objPresentations, $objObserver, $dateformat, $loggedUser; 
//    echo "<table id=\"showListMailsTable\">\n";
//    echo "<tr class=\"type30\">";
//
//    // Making the header for the mails
//    echo "<td class=\"verticalaligntop;\">";         
//	  echo "<table><tr>";
//	  echo "<td class=\"centered width100pct\">";           
//	  echo LangMessageSubject;
//	  echo "</td></tr>";
//	  echo "</table>";
//	  echo "</td>";        
//    
//    echo "<td class=\"verticalaligntop;\">";         
//	  echo "<table>";
//	  echo "<tr><td class=\"centered width100pct\">";           
//	  echo LangMessageSender;
//	  echo "</td></tr>";        
//	  echo "</table>";
//	  echo "</td>";
//
//	  echo "<td class=\"verticalaligntop;\">";         
//	  echo "<table>";
//	  echo "<tr><td class=\"centered width100pct\">";           
//	  echo LangMessageDate;
//	  echo "</td></tr>";        
//	  echo "</table>";
//	  echo "</td>";        
//	  
//		echo "</tr>";
//
//		$countline = 0; // counter for altering table colors
//
//		// Combining all mails
//		$allMails = array_merge($newMails, $readMails);
//
//		// Showing the mails, loop over the id's of the combined array of new and read mails.
//	  for ($cnt = 0;$cnt < count($allMails);$cnt++)
//		{
//		  if ($cnt >= $min && $cnt < $max) {
//		    $countline++;
//		    // Use the different colors for different lines, also make new mails green
//		    if ($loggedUser == "") {
//		      if ($countline % 2 == 0) {
//		        echo "<tr class=\"height5px type20\">";
//		      } else {
//		        echo "<tr class=\"height5px type10\">";
//		      }
//	      } else {
//	        if (in_array($allMails[$cnt], $readMails)) {
//	          if ($countline % 2 == 0) {
//	            echo "<tr class=\"height5px type20\">";
//	          } else {
//	            echo "<tr class=\"height5px type10\">";
//	          }
//	        } else {
//	          // New mails are shown on a green background
//	          echo "<tr class=\"height5px typeGreen\">";
//	        }
//	      }
//	      echo "<td>";
//
//			  echo "<a href = \"" . $baseURL . "index.php?indexAction=view_message&amp;id=" . $allMails[$cnt] . "\">" . $this->getSubject($allMails[$cnt]) . "</a>";
//			  echo "</td>";
//			  $senderId = $this->getSender($allMails[$cnt]);
//			  if ($senderId == "DeepskyLog") {
//			    $senderName = $senderId;
//			  } else {
//			    $senderName = $objObserver->getObserverProperty($senderId, "firstname") . "&nbsp;" . $objObserver->getObserverProperty($senderId, "name");
//			    $senderName = "<a href=\"" . $baseURL . "index.php?indexAction=detail_observer&amp;user=" . $senderId . "\">" . $senderName . "</a>";
//			  }
//			  echo "<td>" . $senderName . "</td>";
//
//			  // Use the date format from databaseInfo
//			  $phpdate = strtotime($this->getDate($allMails[$cnt]));
//			  echo "<td>" . date($dateformat . " G:i:s", $phpdate) . "</td>";
//			
//			  echo "</tr>";
//		  }
//		}
//		echo "</table>";
//	}
}
?>