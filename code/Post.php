<?php
class Post extends DataObject {
	static $db = array(
		"Title" => "Varchar(255)",
		"Content" => "Text",
		"Status" => "Enum('Awaiting, Moderated, Rejected', 'Moderated')",
		"NumViews" => "Int",
	);
	static $default_sort = "LastEdited DESC";
	
	static $indexes = array(
		"SearchFields" => "fulltext (Title, Content)"
	);
	
	static $casting = array(
		"Updated" => "Datetime",
		"RSSContent" => "HTMLText",
		"RSSAuthor" => "Varchar",
	);
	
	static $has_one = array(
		"Parent" => "Post",
		"Topic" => "Post", // Extra link to the top-level post
		"Forum" => "Forum",
		"Author" => "Member",
	);
	
	static $extensions = array(
		"Hierarchy",
	);
	
	function beforeWrite() {
		if($this->ParentID == 0) {
			$this->TopicID = $this->ID;
		} else {
			$this->TopicID = $this->Parent->TopicID;
		}
		parent::beforeWrite();
	}
	
	function AuthorFullName(){
		if($this->Author()->ID)
			return $this->Author()->FirstName." ".$this->Author()->Surname;
		else
			return 'a visitor';
	}
	
	function IsModerator(){
		return Member::currentUser()==$this->Forum()->Moderator();
	}
	
	/**
	 * This lets you see a list of all files that have been attached so far.
	 * 
	 * @return DataObjectSet|false
	 */
	function Attachments() {
		// Get all (if any) attachments for this post
		$allAttachments = DataObject::get("Post_Attachment", "`PostID` = '$this->ID'");
		if(!$allAttachments) return false;

		$doSet = new DataObjectSet();
		
		// Do some fancy post-pocessing - change the class if this is a Image so we can make some thumbnails and sane-sized images
		foreach($allAttachments as $singleAttachment) {
			if($singleAttachment->appCategory() == "image") {
				$obj = $singleAttachment->newClassInstance('Image');
				$obj->DownloadLink = $singleAttachment->DownloadLink(); // TODO This is kinda hacked in, investigate a better way to do this
				$doSet->push($obj);
			} else {
				$doSet->push($singleAttachment);
			}
		}
		
		return $doSet;
	}
	
	/*
	function Link($action = null){
		if(!$action)
			$action = 'show';
		$id = $this->ID;
		$url = $this->ForumURLSegment();
		return "$url/$action/$id";
	}
	*/
	
	function ForumURLSegment(){
		return $this->Forum()->URLSegment;
	}
	
	function util_isRoot() {
		return $this->ParentID == 0;
	}
	
	function getTitle() {
		$title = $this->getField('Title');
		if(!$title) $title = "Re: " . $this->Topic()->Title;

		return $title;
	}
	
	function LatestMember($limit = null) {
		return DataObject::get("Member", "", "`Member`.`ID` DESC", "", 1);
	}
	
	
	/**
	 * Return the last edited date, if it's different from created
	 */
	function Updated() {
		if($this->LastEdited != $this->Created) return $this->LastEdited;
	}
	
	function EditLink() {
	  if((Member::currentUser() && Member::currentUser()->ID == $this->Author()->ID) || (Member::currentUser() && Member::currentUser()->_isAdmin())) return "<a href=\"{$this->Forum()->Link()}editpost/{$this->ID}\">edit</a>";
	  else return false;
	}
	
	function DeleteLink() {
	  Requirements::javascript("forum/javascript/DeleteLink.js");
	  $id = " ";
	  if($this->ParentID == 0) $id = " id=\"firstPost\" ";
	  if(Member::currentUser() && Member::currentUser()->_isAdmin()) return "<a".$id."class=\"deletelink\" href=\"{$this->Forum()->Link()}deletepost/{$this->ID}\">delete</a>";
	  else return false;
	}

	function getAscendants(&$ascendants) {
	
		if($parent = $this->getParent()){
			array_push($ascendants, $parent);
			$parent->getAscendants($ascendants);
		}
		else{
			return $ascendants;
		}
	}
	
	function LatestPost() {
		$filter = "";
		if($this->ParentID != 0) {
			$parents = $this->getDescendantIDList();
			$parents[] = $this->ID;
			$filter = "AND ParentID IN (" . implode(",", $parents) . ")";
		}
		$posts = DataObject::get("Post", "TopicID = $this->TopicID $filter", "Created DESC", "", 1);
		if($posts) return $posts->First();
	}

	function NumPosts() {
		$filter = "";
		if($this->ParentID != 0) {
			$parents = $this->getDescendantIDList();
			$parents[] = $this->ID;
			$filter = "AND (ID = $this->ID OR ParentID IN (" . implode(",", $parents) . "))";
		}
		
		return (int)DB::query("SELECT count(*) FROM Post WHERE TopicID = $this->TopicID $filter")->value();
	}
	
	function RSSContent() {
		$parser = new BBCodeParser($this->Content);
		return $parser->parse() . '<br><br>Posted to: ' . $this->Topic()->Title;
	}
	function RSSAuthor() {
		$author = $this->Author();
		return "$author->FirstName $author->Surname";
	}
	
	function NumReplies() {
		return $this->NumPosts() - 1;
	}
	
	/**
	 * Increment the NumViews value by 1.  Write just that number straight to the database
	 */
	function incNumViews() {
		$this->NumViews++;
		$SQL_numViews = Convert::raw2sql($this->NumViews);
		DB::query("UPDATE Post SET NumViews = '$SQL_numViews' WHERE ID = $this->ID");
	}
	
	/*
	 * Return a link to show this post
	 */
	function Link() {
		$baseLink = $this->Forum()->Link();
		if($this->ParentID == 0) return $baseLink . "show/" . $this->ID;
		else return $baseLink . "show/" . $this->TopicID  . '?showPost=' . $this->ID;
	}
}

/**
 * Topic Subscription: Allows members to subscribe to any number of topics 
 * and receive email notifications when these topics are replied to.
 */
class Post_Subscription extends DataObject {
	static $db = array(
		"LastSent" => "SSDatetime"
	);
	
	static $has_one = array(
		"Topic" => "Post",
		"Member" => "Member"
	);
	
	/**
	 * Checks to see if a Member is already subscribed to this thread
	 * 
	 * @param int $topic The ID of the topic to check
	 * @param int $memberID The ID of the currently logged in member (Defaults to Member::currentUserID())
	 * @return bool true if they are subscribed, false if they're not
	 */
	static function already_subscribed($topicID, $memberID = null) {
		if(!$memberID) $memberID = Member::currentUserID();
		$SQL_topicID = Convert::raw2sql($topicID);
		$SQL_memberID = Convert::raw2sql($memberID);
		
		if(DB::query("SELECT ID FROM Post_Subscription WHERE `TopicID` = '$SQL_topicID' AND `MemberID` = '$SQL_memberID'")->value()) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Notifys everybody that has subscribed to this topic that a new post has been added.
	 * To get emailed, people subscribed to this topic must have visited the forum since the last time they received an email
	 * 
	 * @param Post $post The post that has just been added
	 */
	static function notify(Post $post) {
		// Get all people subscribed to this topic, not including the post author, who have visited the forum since the last time they got sent an email
		$list = DataObject::get("Post_Subscription", "`TopicID` = '$post->TopicID' AND `MemberID` != '$post->AuthorID' AND `Member`.`LastViewed` > `Post_Subscription`.`LastSent`", null, "LEFT JOIN Member ON `Post_Subscription`.`MemberID` = `Member`.`ID`");

		if($list) {
			foreach($list as $obj) {
				$SQL_id = Convert::raw2sql((int)$obj->MemberID);
				
				// Get the members details
				$member = DataObject::get_one("Member", "`Member`.`ID` = '$SQL_id'");
				
				// Create the email and send it out
				$email = new ForumMember_TopicNotification;
				$email->populateTemplate($member);
				$email->populateTemplate($post);
				$email->send();
				
				// Set the LastSent field for this subscription to prevent >1 email from being sent before the user views the thread
				$obj->LastSent = date("Y-m-d H:i:s");
				$obj->write();
			}
		}
	}
}

/**
 * Attachments for posts (one post can have many attachments)
 */
class Post_Attachment extends File {
	static $has_one = array(
		"Post" => "Post"
	);
	
	/**
	 * Allows the user to download a file without right-clicking
	 */
	function download() {
		$SQL_ID = Convert::raw2sql($this->urlParams['ID']);
		if(is_numeric($SQL_ID)) {
			$file = DataObject::get_by_id("Post_Attachment", $SQL_ID);

			HTTP::sendFileToBrowser(file_get_contents($file->getFullPath()), $file->Name);
		}
		
		// Missing something or hack attempt
		Director::redirectBack();
	}
	
	/**
	 * Returns a download link
	 */
	function DownloadLink() {
		return "$this->class/download/$this->ID/";
	}
}
?>