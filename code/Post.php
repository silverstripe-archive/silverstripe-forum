<?php

/**
 * Forum Post Object
 *
 * A SilverStripe Forum doesn't have 'Threads' as such rather a linkedlist
 * of forum posts. This is a single post object with all related fields 
 * and information.
 *
 * @package forum
 */

class Post extends DataObject {
	
	static $db = array(
		"Title" => "Varchar(255)",
		"Content" => "Text",
		"Status" => "Enum('Awaiting, Moderated, Rejected, Archived', 'Moderated')",
		"NumViews" => "Int",
		"IsSticky" => "Boolean",
		"IsReadOnly" => "Boolean",
		"IsGlobalSticky" => "Boolean"
	);
	
	static $default_sort = "LastEdited DESC";

	static $indexes = array(
		"SearchFields" => "fulltext (Title, Content)"
	);

	static $casting = array(
		"Updated" => "SSDatetime",
		"RSSContent" => "HTMLText",
		"RSSAuthor" => "Varchar",
		"Content" => "Text"
	);

	static $has_one = array(
		"Parent" => "Post",
		"Topic" => "Post", // Extra link to the top-level post
		"Forum" => "Forum",
		"Author" => "Member",
	);

	static $has_many = array(
		"Children" => "Post",
		"Attachments" => "Post_Attachment"
	);
	
	static $many_many = array();
	
	static $extensions = array(
		"Hierarchy",
	);
	
	static $defaults = array();

	/**
	 * Save the parent ID and the topic ID before
	 * writing this object to the database
	 */
	function onBeforeWrite() {
		if(!$this->ParentID && !$this->TopicID) {
			if($this->ID){
				$this->TopicID = $this->ID;
			}
		} elseif($this->ParentID && !$this->TopicID){
			$this->TopicID = $this->Parent->TopicID;
		}

		parent::onBeforeWrite();
	}

	/**
	 * Return the Authors Full Name
	 * @return String
	 */
	function AuthorFullName(){
		if($this->Author()->ID)
			return $this->Author()->FirstName." ".$this->Author()->Surname;
		else
			return _t('Forum.VISITOR');
	}

	function IsModerator(){
		return Member::currentUser()==$this->Forum()->Moderator();
	}

	/**
	 * Used in Post RSS Feed
	 */
	function AbsoluteLink() {
		return Director::absoluteURL($this->Link());
	}
	/**
	 * This lets you see a list of all files that have been attached so far.
	 *
	 * @return DataObjectSet|false
	 */
	function Attachments() {
		// Get all (if any) attachments for this post
		$allAttachments = DataObject::get("Post_Attachment", "`Post_Attachment`.PostID = '$this->ID'");

		if(!$allAttachments) return false;

		$doSet = new DataObjectSet();

		// Do some fancy post-pocessing - change the class if this is a Image so we can make some thumbnails and sane-sized images
		foreach($allAttachments as $singleAttachment) {
			if($singleAttachment->appCategory() == "image") {
				$obj = $singleAttachment->newClassInstance('Image');
				$doSet->push($obj);
			} else {
				$doSet->push($singleAttachment);
			}
		}

		return $doSet;
	}

	function ForumURLSegment(){
		return $this->Forum()->URLSegment;
	}

	function getTitle() {
		$title = $this->getField('Title');
		if(!$title && $this->TopicID && $this->Topic()) $title = sprintf(_t('Post.RESPONSE',"Re: %s",PR_HIGH,'Post Subject Prefix'),$this->Topic()->Title);

		return $title;
	}

	/**
	 * Return the last edited date, if it's different from created
	 */
	function Updated() {
		if($this->LastEdited != $this->Created) return $this->LastEdited;
	}

	/**
	 * Return a link to edit this post.
	 * 
	 * If the member is the owner of the post, they can
	 * edit their own profile, allowing them to see the link.
	 * If the user is an admin of this forum, (ADMIN permissions
	 * or a moderator) then they can edit too.
	 *
	 * @return string|null
	 */
	function EditLink() {
		$memberID = Member::currentUserID() ? Member::currentUserID() : 0;
		if(!$memberID) return null;

		$isOwner = ($memberID == $this->Author()->ID) ? true : false;
		
		if($isOwner || $this->Forum()->isAdmin()) {
			return "<a href=\"{$this->Forum()->Link()}editpost/{$this->ID}\">" . _t('Post.EDIT','Edit') . "</a>";
		}
	}

	/**
	 * Return a link delete this post.
	 * 
	 * If the member is an admin of this forum, (ADMIN permissions
	 * or a moderator) then they can delete the post.
	 *
	 * @return string|null
	 */
	function DeleteLink() {
		$id = " ";
		if($this->ParentID == 0) $id = " id=\"firstPost\" ";

		if($this->Forum()->isAdmin()) {
			return "<a".$id."class=\"deletelink\" rel=\"$this->ID\" href=\"{$this->Forum()->Link()}deletepost/{$this->ID}\">" . _t('Post.DELETE','Delete') ."</a>";
		}
	}
	
	/**
	 * Return a link to mark this post as spam.
	 * used for the spamprotection module
	 */
	function MarkAsSpamLink() {
		if(class_exists('SpamProtectorManager') && $member = Member::currentUser()) {
		 	if($member->ID != $this->AuthorID)
				return "<a href=\"{$this->Forum()->Link()}markasspam/{$this->ID}\" class='markAsSpamLink' rel=\"$this->ID\">". _t('Post.MARKASSPAM', 'Mark as Spam') ."</a>";
		}
	}
	
	function ReplyLink() {
		$url = $this->Link('reply');
		return "<a href=\"$url\">" . _t('Post.REPLYLINK','Post Reply') . "</a>";
	}
	
	function ShowLink() {
		$url = $this->Link('show');
		return "<a href=\"$url\">" . _t('Post.SHOWLINK','Show Thread') . "</a>";
	}
	
	/** 
	 * Are Forum Signatures on Member profiles allowed
	 * 
	 * @return Boolean
	 */
	 function DisplaySignatures() {
		$forumHolder = DataObject::get_one("ForumHolder");
		if($forumHolder && $forumHolder->DisplaySignatures == true) {
			return true;
		}
		return false;
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


	function getAllPostsUnderThisTopic() {
		return DataObject::get("Post", "TopicID = $this->TopicID AND ParentID <> 0 AND Status = 'Moderated'", "Created DESC");
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
			$filter = "AND (ID = $this->ID OR ParentID IN (" .
			          implode(",", $parents) . "))";
		}

		return (int)DB::query("SELECT count(*) FROM Post WHERE TopicID = $this->TopicID $filter")->value();
	}


	function getRSSContent() {
		$parser = new BBCodeParser($this->Content);
		$html = $parser->parse();
		if($this->Topic()) $html .= '<br><br>' . sprintf(_t('Post.POSTEDTO',"Posted to: %s"),$this->Topic()->Title);
		$html .= " ". $this->ShowLink() . " | " .$this->ReplyLink();
		return $html;
	}

	
	function getRSSAuthor() {
		$author = $this->Author();
		return $author->Nickname;
	}


	function NumReplies() {
		return $this->NumPosts() - 1;
	}

	/**
	 * Check if they have visited this thread before. If they haven't increment 
	 * the NumViews value by 1 and set visited to true.
	 */
	function incNumViews() {
		if(Session::get('ForumViewed-' . $this->ID)) return false;

		Session::set('ForumViewed-' . $this->ID, 'true');
		$this->NumViews++;
		$SQL_numViews = Convert::raw2sql($this->NumViews);
		DB::query("UPDATE Post SET NumViews = '$SQL_numViews' WHERE ID = $this->ID");
	}

	/*
	 * Return a link to show this post
	 * @return String
	 */
	function Link($action = "show") {
		$baseLink = $this->Forum()->Link();
		if($this->ParentID == 0) return $baseLink . "show/" . $this->ID .'#post' . $this->ID;
		
		$count = 0;
		$posts = DataObject::get("Post", "TopicID = '$this->TopicID' AND Status = 'Moderated' AND ID < $this->ID");
		
		if($posts) $count = floor($posts->Count()/Forum::$posts_per_page) * Forum::$posts_per_page;
		
		return $baseLink . $action ."/" . $this->TopicID  . '?start='.$count.'#post' . $this->ID;
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
		$list = DataObject::get("Post_Subscription",
			"`TopicID` = '$post->TopicID' AND `MemberID` != '$post->AuthorID'", null, "LEFT JOIN Member ON `Post_Subscription`.`MemberID` = `Member`.`ID`");
		if($list) {
			foreach($list as $obj) {
				$SQL_id = Convert::raw2sql((int)$obj->MemberID);

				// Get the members details
				$member = DataObject::get_one("Member", "`Member`.`ID` = '$SQL_id'");
				
				$email = new Email();
				$email->setFrom(Email::getAdminEmail());
				$email->setTo($member->Email);
				$email->setSubject('New reply for ' . $post->Title);
				$email->setTemplate('ForumMember_TopicNotification');
				$email->populateTemplate($member);
				$email->populateTemplate($post);
				$email->populateTemplate(array(
					'UnsubscribeLink' => Director::absoluteBaseURL() . $post->Forum()->URLSegment . '/unsubscribe/' . $post->TopicID
				));
				$email->send();
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
			$response = SS_HTTPRequest::send_file(file_get_contents($file->getFullPath()), $file->Name);
			$response->output();
		}

		// Missing something or hack attempt
		return Director::redirectBack();
	}
}

?>