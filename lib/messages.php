<?php
// messages.php
// The messages class collects all functions needed to send, read and delete mesagges in DeepskyLog from the database.
global $inIndex;
if ((! isset ( $inIndex )) || (! $inIndex))
	include "../../redirect.php";
class Messages {
	public function getNumberOfUnreadMails() {
		global $objDatabase, $loggedUser;
		if ($loggedUser) {
			return "" . count ( $this->getIdsNewMails ( $loggedUser ) ) . "/" . count ( $this->getIdsAllMails ( $loggedUser ) ) . "";
		} else {
			return "";
		}
	}
	public function getIdsNewMails($user) {
		global $objDatabase;
		$listOfAllMails = $this->getIdsAllMails ( $user );
		
		// Read mails should not be counted in the first part
		$readMails = $objDatabase->selectSingleArray ( "select id from messagesRead where receiver = \"" . $user . "\"", "id" );
		
		$cnt = 0;
		
		for($i = 0; $i < count ( $listOfAllMails ); $i ++) {
			if (! in_array ( $listOfAllMails [$i], $readMails )) {
				$listOfMails [$cnt] = $listOfAllMails [$i];
				$cnt ++;
			}
		}
		if (isset ( $listOfMails )) {
			// Swap the array
			$listOfMails = array_reverse ( $listOfMails );
			
			return $listOfMails;
		} else {
			return Array ();
		}
	}
	
	// Returns a list of all mails. The deleted mails are not included in the list of id's.
	public function getIdsAllMails($user) {
		global $objDatabase;
		$listOfAllMails = $objDatabase->selectSingleArray ( "select id from messages where receiver = \"" . $user . "\" or receiver = \"all\"", "id" );
		
		$listOfMails = Array ();
		
		// Removed mails should not be counted
		$removedMails = $objDatabase->selectSingleArray ( "select id from messagesDeleted where receiver = \"" . $user . "\"", "id" );
		
		$cnt = 0;
		
		for($i = 0; $i < count ( $listOfAllMails ); $i ++) {
			if (! in_array ( $listOfAllMails [$i], $removedMails )) {
				$listOfMails [$cnt] = $listOfAllMails [$i];
				$cnt ++;
			}
		}
		
		return $listOfMails;
	}
	public function getSubject($id) {
		global $objDatabase;
		return $objDatabase->selectSingleValue ( "select subject from messages where id = \"" . $id . "\"", "subject" );
	}
	public function getSender($id) {
		global $objDatabase;
		return $objDatabase->selectSingleValue ( "select sender from messages where id = \"" . $id . "\"", "sender" );
	}
	public function getReceiver($id) {
		global $objDatabase;
		return $objDatabase->selectSingleValue ( "select receiver from messages where id = \"" . $id . "\"", "receiver" );
	}
	public function getDate($id) {
		global $objDatabase;
		return $objDatabase->selectSingleValue ( "select date from messages where id = \"" . $id . "\"", "date" );
	}
	public function getContent($id) {
		global $objDatabase;
		return $objDatabase->selectSingleValue ( "select message from messages where id = \"" . $id . "\"", "message" );
	}
	public function removeAllMessages($id) {
		global $objDatabase;
		if ($id != "") {
			$allMessages = $this->getIdsAllMails ( $id );
			for($cnt = 0; $cnt < count ( $allMessages ); $cnt ++) {
				$objDatabase->execSQL ( "insert into messagesDeleted VALUES(\"" . $allMessages [$cnt] . "\", \"" . $id . "\")" );
			}
		}
	}
	public function getContentWithoutLinks($id) {
		global $objDatabase;
		$message = $objDatabase->selectSingleValue ( "select message from messages where id = \"" . $id . "\"", "message" );
		return strip_tags ( $message, '<br>' );
	}
	public function isRead($id, $receiver) {
		global $objDatabase;
		$read = $objDatabase->selectSingleValue ( "select id from messagesRead where id = \"" . $id . "\" and receiver = \"" . $receiver . "\"", "id" );
		if ($read == "") {
			return false;
		} else {
			return true;
		}
	}
	public function isDeleted($id, $receiver) {
		global $objDatabase;
		$deleted = $objDatabase->selectSingleValue ( "select id from messagesDeleted where id = \"" . $id . "\" and receiver = \"" . $receiver . "\"", "id" );
		if ($deleted == "") {
			return false;
		} else {
			return true;
		}
	}
	public function markMessageRead($id, $receiver) {
		global $objDatabase;
		$objDatabase->execSQL ( "insert into messagesRead VALUES(\"" . $id . "\", \"" . $receiver . "\")" );
	}
	public function validateDeleteMessage() {
		global $objDatabase, $loggedUser;
		if ($loggedUser != "") {
			$id = $_GET ["id"];
			$objDatabase->execSQL ( "insert into messagesDeleted VALUES(\"" . $id . "\", \"" . $loggedUser . "\")" );
		}
	}
	public function getReplyToSubject($id) {
		$subject = $this->getSubject ( $id );
		if (strpos ( $subject, "Re : " ) === false) {
			return "Re : " . $subject;
		} else {
			return $subject;
		}
	}
	public function getReplyToMessage($id) {
		$message = $this->getContent ( $id );
		// Replace <br /> by <br />>
		$message = str_replace ( "<br />", "", $message );
		$message = str_replace ( "\n", "\n> ", $message );
		return "> " . $message;
	}
	public function validateMessage() {
		global $loggedUser;
		if (! ($loggedUser))
			throw new Exception ( LangMessageNotLoggedIn );
		
		$this->sendMessage ( $loggedUser, $_POST ['receiver'], $_POST ['subject'], nl2br ( addslashes ( $_POST ['message'] ) ) );
	}
	
	// Returns a list of all read mails.
	public function getIdsReadMails($user) {
		global $objDatabase;
		$listOfAllMails = $this->getIdsAllMails ( $user );
		$listOfNewMails = $this->getIdsNewMails ( $user );
		
		$listOfReadMails = Array ();
		
		$cnt = 0;
		
		for($i = 0; $i < count ( $listOfAllMails ); $i ++) {
			if (! in_array ( $listOfAllMails [$i], $listOfNewMails )) {
				$listOfReadMails [$cnt] = $listOfAllMails [$i];
				$cnt ++;
			}
		}
		
		// Swap the array
		$listOfReadMails = array_reverse ( $listOfReadMails );
		
		return $listOfReadMails;
	}
	public function sendMessage($sender, $receiver, $subject, $message) {
		global $objDatabase;
		$date = $mysqldate = date ( 'Y-m-d H:i:s' );
		
		$objDatabase->execSQL ( "INSERT into messages (sender, receiver, subject, message, date) VALUES(\"" . $sender . "\", \"" . $receiver . "\", \"" . $subject . "\", '" . $message . "', \"" . $date . "\")" );
	}
	public function showListMails($newMails, $readMails) {
		global $baseURL, $baseURL, $objPresentations, $objObserver, $dateformat, $loggedUser, $objUtil;
		
		// Add the button to select which columns to show
		$objUtil->addTableColumSelector ();
		
		echo "<table class=\"table table-condensed table-striped table-hover tablesorter custom-popup\">\n";
		
		// Making the header for the mails
		echo "<thead><tr>";
		echo "<th>";
		echo LangMessageSubject;
		echo "</th>";
		echo "<th>";
		echo LangMessageSender;
		echo "</th>";
		
		echo "<th>";
		echo LangMessageDate;
		echo "</th>";
		
		echo "</tr></thead>";
		echo "<tbody>";
		// Combining all mails
		$allMails = array_merge ( $newMails, $readMails );
		
		// Showing the mails, loop over the id's of the combined array of new and read mails.
		for($cnt = 0; $cnt < count ( $allMails ); $cnt ++) {
			if ($loggedUser == "") {
				echo "<tr table-no-border>";
			} else {
				echo "<tr>";
			}
			echo "<td>";
			
			if (! in_array ( $allMails [$cnt], $readMails )) {
				echo "<span class=\"label label-success\">New</span>&nbsp;";
			}
			echo "<a href = \"" . $baseURL . "index.php?indexAction=view_message&amp;id=" . $allMails [$cnt] . "\">" . $this->getSubject ( $allMails [$cnt] ) . "</a>";
			echo "</td>";
			$senderId = $this->getSender ( $allMails [$cnt] );
			if ($senderId == "DeepskyLog") {
				$senderName = $senderId;
			} else {
				$senderName = $objObserver->getObserverProperty ( $senderId, "firstname" ) . "&nbsp;" . $objObserver->getObserverProperty ( $senderId, "name" );
				$senderName = "<a href=\"" . $baseURL . "index.php?indexAction=detail_observer&amp;user=" . $senderId . "\">" . $senderName . "</a>";
			}
			echo "<td>" . $senderName . "</td>";
			
			// Use the date format from databaseInfo
			$phpdate = strtotime ( $this->getDate ( $allMails [$cnt] ) );
			echo "<td>" . date ( $dateformat . " G:i:s", $phpdate ) . "</td>";
			
			echo "</tr>";
		}
		echo "</tbody>
           </table>";
		
		echo $objUtil->addTablePager ();
		
		echo $objUtil->addTableJavascript ();
	}
}
?>
