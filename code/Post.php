<?php

class Post extends DataObject {
	static $db = array(
		"Title" => "Varchar(255)",
		"Content" => "Text",
		"Status" => "Enum('Awaiting, Moderated, Rejected, Archived', 'Moderated')",
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

	static $extensions = array(
		"Hierarchy",
	);


	function hasChildren(){
		$children = $this->Children();
		return($children&&$children->count());

	}


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


	function util_isRoot() {
		return $this->ParentID == 0;
	}


	function getTitle() {
		$title = $this->getField('Title');
		if(!$title && $this->Topic()) $title = sprintf(_t('Post.RESPONSE',"Re: %s",PR_HIGH,'Post Subject Prefix'),$this->Topic()->Title);

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
	  if((Member::currentUser() && Member::currentUser()->ID == $this->Author()->ID) ||
			 (Member::currentUser() && Member::currentUser()->isAdmin())) {
			return "<a href=\"{$this->Forum()->Link()}editpost/{$this->ID}\">" . _t('Post.EDIT','edit') . "</a>";
		}
	  else {
			return false;
		}
	}


	function DeleteLink() {
	  $id = " ";
	  if($this->ParentID == 0)
			$id = " id=\"firstPost\" ";

	  if(Member::currentUser() && Member::currentUser()->isAdmin())
			return "<a".$id."class=\"deletelink\" rel=\"$this->ID\" href=\"{$this->Forum()->Link()}deletepost/{$this->ID}\">" . _t('Post.DELETE','delete') ."</a>";
	  else
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
		return DataObject::get("Post", "TopicID = $this->TopicID AND ParentID <> 0 AND Status = 'Moderated'");
	}


	function LatestPost() {
		$filter = "";
		if($this->ParentID != 0) {
			$parents = $this->getDescendantIDList();
			$parents[] = $this->ID;
			$filter = "AND ParentID IN (" . implode(",", $parents) . ")";
		}
		$posts = DataObject::get("Post", "TopicID = $this->TopicID $filter",
														 "Created DESC", "", 1);
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


	function RSSContent() {
		$parser = new BBCodeParser($this->Content);
		$html = $parser->parse();
		if($this->Topic()) $html .= '<br><br>' . sprintf(_t('Post.POSTEDTO',"Posted to: %s"),$this->Topic()->Title);
		return $html;
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


	function getCMSFields(){ //Topic is-a Post, so here we are getting all the posts for that topic
		$authors = DataObject::get("Member");

		$ret = new FieldSet(
			new TabSet(_t('Post.MAIN','Main'),
				new Tab(_t('Post.TOPICDETAILS','Topic Details'),
					new ReadonlyField("ID", _t('Post.TOPICINTERNALID','Topic Internal ID')),
					new ReadonlyField("Created", _t('Post.TOPICCREATED','Topic Created')),
					new ReadonlyField("LastEdited", _t('Post.TOPICLASTEDIT','Topic Last Edited')),
					new TextField("Title", _t('Post.TITLE','Title')),
					new TextareaField("Content", _t('Post.CONTENT','Content')),
					new DropdownField("Status", _t('Post.STATUS','Status'), array(
						'Awaiting' => _t('Post.AWAITING','Awaiting'),
						'Moderated' => _t('Post.MODERATED','Moderated'),
						'Rejected' => _t('Post.REJECTED','Rejected'),
						'Archived' => _t('Post.ARCHIVED','Archived')
					)),
					new DropdownField("AuthorID", _t('Post.AUTHOR','Author'), $authors->map())
				),
				new Tab(_t('Post.ACTIVEPOSTS','Active Posts'),
					$activePosts = new ComplexTableField(
						$controller = null,
						$name = "ActivePosts",
						$sourceClass = "Post",
						$fieldList = array(
							"Created"=> _t('Post.CREATED','Created'),
							"LastEdited" => _t('Post.LASTEDIT','Last Edited'),
							"Title" => _t('Post.TITLE'),
							"Status" => _t('Post.STATUS'),
							"Content" => _t('Post.CONTENT')
						),
						$fieldList = "getCMSFields_forPopup",
						$sourceFilter = "TopicID = '$this->ID' AND ParentID <> 0 AND Status = 'Moderated'",
						"Created DESC"
					)
				),

				new Tab(_t('Post.AWAITINGPOSTS','Awaiting Posts'),
					$awaitingPosts = new ComplexTableField(
						$controller = null,
						$name = "AwaitingPosts",
						$sourceClass = "Post",
						$fieldList = array(
							"Created"=> _t('Post.CREATED','Created'),
							"LastEdited" => _t('Post.LASTEDIT'),
							"Title" => _t('Post.TITLE'),
							"Status" => _t('Post.STATUS'),
							"Content" => _t('Post.CONTENT')
						),
						$fieldList = "getCMSFields_forPopup",
						$sourceFilter = "TopicID = '$this->ID' AND ParentID <> 0 AND Status = 'Awaiting'",
						"Created DESC"
					)
				),
				new Tab(_t('Post.REJECTEDPOSTS','Rejected Posts'),
					$rejectedPosts = new ComplexTableField(
						$controller = null,
						$name = "RejectedPosts",
						$sourceClass = "Post",
						$fieldList = array(
							"Created"=> _t('Post.CREATED'),
							"LastEdited" => _t('Post.LASTEDIT'),
							"Title" => _t('Post.TITLE'),
							"Content" => _t('Post.CONTENT')
						),
						$fieldList = "getCMSFields_forPopup",
						$sourceFilter = "TopicID = '$this->ID' AND ParentID <> 0 AND Status = 'Rejected'",
						"Created DESC"
					)
				)
			)
		);
		$activePosts->setFieldCasting(
			array(
				"Content" => "Text->LimitWordCountPlainText(20)"
			)
		);
		$activePosts->setPermissions(
			array("show", "edit")
		);

		$awaitingPosts->setFieldCasting(
			array(
				"Content" => "Text->LimitWordCountPlainText(20)"
			)
		);
		$awaitingPosts->setPermissions(
			array("add", "edit", "show")
		);

		$rejectedPosts->setFieldCasting(
			array(
				"Content" => "Text->LimitWordCountPlainText(20)"
			)
		);
		$rejectedPosts->setPermissions(
			array("show", "delete")
		);

		return $ret;
	}


	function getCMSFields_forPopup(){
		$authors = DataObject::get("Member");

		$topicID = $this->TopicID
			?$this->TopicID
			:$this->ParentID;

		$postsExceptMyselft = DataObject::get("Post",
			"TopicID = '$topicID' AND (ParentID <> 0 AND ID <> '$this->ID' OR ParentID = 0) AND Status = 'Moderated'");

		if(!$postsExceptMyselft||!$postsExceptMyselft->count()) {
			$postsExceptMyselft = new DataObjectSet();
		}
		$ret = new FieldSet(
			new DropdownField("AuthorID", sprintf(_t('Post.POSTEDBY',"Posted By %s"),$authors->map()) ),
			new DropdownField("ParentID", sprintf(_t('Post.POSTREPLIEDTO',"Post Replied To %s"),$postsExceptMyselft->map())),
			new TextField("Title", _t('Post.TITLE')),
			new TextareaField("Content", _t('Post.CONTENT')),
			new DropdownField("Status", _t('Post.STATUS'),
				array(
					"Awaiting"=> _t('Post.AWAITING'),
					"Moderated"=> _t('Post.MODERATED'),
					"Rejected"=> _t('Post.REJECTED')
				)
			),
			new HiddenField("TopicID", "", $topicID)
		);

		return $ret;
	}


	function getCMSActions(){
		return new FieldSet(
			new FormAction('save', _t('Post.SAVE','Save'), 'ajaxAction-save'),
			new FormAction("archive", _t('Post.ARCHIVE','Archive'), 'ajaxAction->archive')
		);
	}


	function LimitWordCountPlainText($numWords){
		/*debug::show($this->Countent.LimitWordCountPlainText($numWords));
		die*/
		return $this->Countent;
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
			"`TopicID` = '$post->TopicID' AND `MemberID` != '$post->AuthorID' AND `Member`.`LastViewed` > `Post_Subscription`.`LastSent`",
			null, "LEFT JOIN Member ON `Post_Subscription`.`MemberID` = `Member`.`ID`");

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

				// Set the LastSent field for this subscription to prevent >1 email
				// from being sent before the user views the thread
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
}

?>