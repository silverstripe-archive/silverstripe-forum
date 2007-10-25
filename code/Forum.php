<?php

class Forum extends Page {
	static $allowed_children = 'none';
	static $icon = "forum/images/treeicons/user";
	static $db = array(
		"Abstract" => "Text",
		"Type"=>"Enum(array('open', 'consultation'), 'open')",
		"RequiredLogin"=>"Boolean",
		"ForumViewers" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers', 'Anyone')",
		"ForumPosters" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers', 'Anyone')",
		"ForumViewersGroup" => "Int",
		"ForumPostersGroup" => "Int",
		
		"CanAttachFiles" => "Boolean",
		
		"ForumRefreshOn" => "Boolean",
		"ForumRefreshTime" => "Int"
	);

	static $has_one = array( 
		"Moderator" => "Member",
		"Group" => "Group"
	);
	
	static $defaults = array(
		"ForumViewers" => "Anyone",
		"ForumPosters" => "LoggedInUsers"
	);
	
	static $posts_per_page = 8;

	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		
		$code = "ACCESS_FORUM";
		
		if(! $forumGroup = DataObject::get_one("Group", "Code = 'forum-members'")) {
			$group = new Group();
			$group->Code = 'forum-members';
			$group->Title = "Forum Members";
			$group->write();
			
			Permission::grant( $group->ID, $code );
			Database::alteration_message("Forum Members group created","created");
		}
		else if( DB::query( "SELECT * FROM Permission WHERE `GroupID` = '$forumGroup->ID' AND `Code` LIKE '$code'" )->numRecords() == 0 ) {
			Permission::grant( $forumGroup->ID, $code );
		}
		
		if(!DataObject::get_one("ForumHolder")) {
			$forumholder = new ForumHolder();
			$forumholder->Title = "Forums";
			$forumholder->URLSegment = "forums";
			$forumholder->Content = "<p>Welcome to SilverStripe Forum Module! This is the default ForumHolder page. You can now add forums.</p>";
			$forumholder->Status = "Published";
			$forumholder->write();
			$forumholder->publish("Stage", "Live");
			Database::alteration_message("ForumHolder page created","created");

			$forum = new Forum();
			$forum->Title = "General Discussion";
			$forum->URLSegment = "general-discussion";
			$forum->ParentID = $forumholder->ID;
			$forum->Content = "<p>Welcome to SilverStripe Forum Module! This is the default Forum page. You can now add topics.</p>";
			$forum->Status = "Published";
			$forum->write();
			$forum->publish("Stage", "Live");
			Database::alteration_message("Forum page created","created");
			
		}
	}
	
	function getCMSFields() {
		Requirements::javascript("forum/javascript/ForumAccess.js");
		Requirements::css("forum/css/Forum_CMS.css");
	  
	  $fields = parent::getCMSFields();
		
		$fields->addFieldToTab("Root.Access", new HeaderField("Who can read the forum?", 2));
		$fields->addFieldToTab("Root.Access", new OptionsetField("ForumViewers", "", array(
		  "Anyone" => "Anyone",
		  "LoggedInUsers" => "Logged-in users",
		  "OnlyTheseUsers" => "Only these people (choose from list)"
		)));
		$fields->addFieldToTab("Root.Access", new DropdownField("ForumViewersGroup", "Group", Group::map()));
		$fields->addFieldToTab("Root.Access", new HeaderField("Who can post to the forum?", 2));
		$fields->addFieldToTab("Root.Access", new OptionsetField("ForumPosters", "", array(
		  "Anyone" => "Anyone",
		  "LoggedInUsers" => "Logged-in users",
		  "OnlyTheseUsers" => "Only these people (choose from list)"
		)));
		$fields->addFieldToTab("Root.Access", new DropdownField("ForumPostersGroup", "Group", Group::map()));
		// TODO Abstract this to the Permission class
		$fields->addFieldToTab("Root.Access", new OptionsetField("CanAttachFiles", "Can users attach files?", array(
			"1" => "Yes",
			"0" => "No"
		)));
		
		$fields->addFieldToTab("Root.Behaviour", new CheckboxField("ForumRefreshOn", "Refresh this forum"));
		$refreshTime = new NumericField("ForumRefreshTime", "Refresh every ");
		$refreshTime->setRightTitle(" seconds");
		$fields->addFieldToTab("Root.Behaviour", $refreshTime);
		
		// Without this line, some newer versions of SQL fail (ENUM's are broken)
		//$fields->addFieldToTab("Root.Access", new HiddenField("Type", "Type", "open"));
		
		return $fields;
	}
	
	public function Breadcrumbs($maxDepth = null, $unlinked = false, $stopAtPageType = false, $showHidden = false) {
       $page = $this;
       $nonPageParts = array();
       $parts = array();
       
       $controller = Controller::curr();
       $params = $controller->getURLParams();
       
       $SQL_id = $params['ID'];

       if(is_numeric($SQL_id)) {
           $topic = DataObject::get_by_id("Post", "$SQL_id");
           
           if($topic) {
               $nonPageParts[] = Convert::raw2xml($topic->getTitle());
           }
       }
       while(
           $page
           && (!$maxDepth || sizeof($parts) < $maxDepth)
       ) {
           if($showHidden || $page->ShowInMenus || ($page->ID == $this->ID)) {
               if($page->URLSegment == 'home') $hasHome = true;             
               if($nonPageParts) {
               		$parts[] = "<a href=\"" . $page->Link() . "\">" . Convert::raw2xml($page->Title) . "</a>";
               } else {
	               $parts[] = (($page->ID == $this->ID) || $unlinked) ? Convert::raw2xml($page->Title) : ("<a href=\"" . $page->Link() . "\">" . Convert::raw2xml($page->Title) . "</a>");           	
               }   
           }
           // $page->destroy(); // this casued problems; the page would have been previously referenced due to caching
           $page = $page->Parent;
       }
       
       return implode(" &raquo; ", array_reverse(array_merge($nonPageParts,$parts)));
	}
	
	function Post($id = null) {
		if($id == null) $id = Director::urlParam("ID");
		if(is_numeric($id)) return DataObject::get_by_id("Post", $id);
		// this is causing some errors, temporarily added is_numeric.
		// TODO FIXME!
	}
	
	function LatestPost() {
		if(is_numeric($this->ID)) {
			$posts = DataObject::get("Post", "ForumID = $this->ID", "Created DESC", "", 1);
			if($posts) return $posts->First();
		}
	}
	
	function NumTopics() {
		if(is_numeric($this->ID)) {
			return (int)DB::query("SELECT count(*) FROM Post WHERE ForumID = $this->ID AND ParentID = 0")->value();
		}
	}

	function NumPosts() {
		if(is_numeric($this->ID)) {
			return (int)DB::query("SELECT count(*) FROM Post WHERE ForumID = $this->ID")->value();
		}
	}

	function Topics() {
		if(Member::currentUser()==$this->Moderator()) {
			return DataObject::get("Post", "ForumID = $this->ID and ParentID = 0 and (Status = 'Moderated' or Status = 'Awaiting')");
		}
		return DataObject::get("Post", "ForumID = $this->ID and ParentID = 0 and Status = 'Moderated'");
	}
	
	function getTopicsByStatus($status){
		return DataObject::get("Post", "ForumID = $this->ID and ParentID = 0 and Status = '$status'");
	}
	
	function hasChildren() {
		return $this->NumPosts();
	}
	
	function getChildrenAsUL($attributes = "", $titleEval = '"<li>" . $child->Title', $extraArg = null, $limitToMarked = false, $rootCall = false){
		if($limitToMarked && $rootCall) {
			$this->markingFinished();
		}
		
		$children = $this->Topics();
		if($children) {
			if($attributes) {
				$attributes = " $attributes";
			}
			
			$output = "<ul$attributes>\n";
			foreach($children as $child) {
				if(!$limitToMarked || $child->isMarked()) {
					$foundAChild = true;
					$output .= eval("return $titleEval;") . "\n" . 
					$child->getChildrenAsUL("", $titleEval, $extraArg, false, false) . "</li>\n";
				}
			}
			
			$output .= "</ul>\n";
		}
		
		if(isset($foundAChild) && $foundAChild) {
			return $output;
		}
	}
	
	
	
	/**
	 * Checks to see if the currently logged in user has permissions to view this forum
	 * 
	 * @param string type
	 */
	function CheckForumPermissions($type = "view") {
	  switch($type) {
	    case "post":
	      // Check posting permissions
	      if($this->ForumPosters == "Anyone" || ($this->ForumPosters == "LoggedInUsers" && Member::currentUser()) || ($this->ForumPosters == "OnlyTheseUsers" && Member::currentUser() && Member::currentUser()->isInGroup($this->ForumPostersGroup))) return true;
	      else return false;
      break;  
	    case "view":
	    default:
	      // Check viewing forum permissions
		  if($this->ForumViewers == "Anyone" || ($this->ForumViewers == "LoggedInUsers" && Member::currentUser()) || ($this->ForumViewers == "OnlyTheseUsers" && Member::currentUser() && Member::currentUser()->isInGroup($this->ForumViewersGroup))) return true;
	      else return false;
	    break;
}
	}
}

class Forum_Controller extends Page_Controller {
	/**
	 * Return a list of all top-level topics in this forum
	 */
 	function init() {
 	  if(!$this->CheckForumPermissions("view")) {
 		  parent::init();
 		  $messageSet = array(
				'default' => "Enter your email address and password to view this forum.",
				'alreadyLoggedIn' => "I'm sorry, but you can't access this forum until you've logged in.  If you want to log in as someone else, do so below",
				'logInAgain' => "You have been logged out of the forums.  If you would like to log in again, enter a username and password below.",
			);

			Security::permissionFailure($this, $messageSet);
			exit;
 		}
 		
 		// Delete any posts that don't have a Title set (This cleans up posts created by the ReplyForm method that aren't saved)
 		$this->deleteUntitledPosts();
 		
 		// Log this visit to the ForumMember if they exist
 		// Some members (eg. Admins) are by default of the Member ClassName, rather than ForumMember, thus they don't have the necessary fields, hence the second check
 		$member = Member::currentUser();
 		if(isset($member)) {
 			$member->LastViewed = date("Y-m-d H:i:s");
 			$member->write();
 		}

 	  Requirements::javascript("jsparty/prototype.js");
 		Requirements::javascript("jsparty/behaviour.js");
 		Requirements::javascript("jsparty/tree/tree.js");
		Requirements::javascript("forum/javascript/Forum.js");
		
		// Refresh the forum every X seconds if requested
		// TODO Make this AJAX-friendly :>
		$time = $this->refreshTime();
		if(!in_array($this->urlParams['Action'], array('reply', 'editpost', 'starttopic')) && $time > 0) {
			$time *= 1000;
			Requirements::customScript(<<<JS
				setTimeout(function() { window.location.reload(); }, {$time});
JS
);
		}

		Requirements::css("jsparty/tree/tree.css");
		
		Requirements::themedCSS('Forum');

		RSSFeed::linkToFeed($this->Link("rss"), "Posts to the '$this->Title' forum");
		RSSFeed::linkToFeed($this->Parent->Link("rss"), "Posts to all forums");
		
		if(Director::is_ajax()) ContentNegotiator::allowXHTML();
		
		parent::init();
	}
	
	/**
	 * Deletes any post where `Title` IS NULL and `Content` IS NULL - 
	 * these will be posts that have been created by the ReplyForm method 
	 * but not modified by the postAMessage method.
	 * 
	 * Has a time limit - posts can exist in this state for 30 minutes
	 * before they are deleted - this is so anybody uploading attachments
	 * has time to do so.
	 */
	function deleteUntitledPosts() {
		DB::query("DELETE FROM Post WHERE `Title` IS NULL AND `Content` IS NULL AND `Created` < NOW() - INTERVAL 15 MINUTE");
	}
	
	/**
	 * protected int function refreshTime(void): Checks if this forum should refresh every X seconds.
	 * @return int 0 if the forum shouldn't refresh, the number of seconds if it should refresh
	 */
	protected function refreshTime() {
		/** Ensure refresher is on **/
		if(!$this->ForumRefreshOn) return 0;
		
		/** Ensure the input is valid **/
		if(!ctype_digit((string)$this->ForumRefreshTime) || (int)$this->ForumRefreshTime < 1) return 0;

		return $this->ForumRefreshTime;
	}
	
	function CurrentMember() {
		if($Member = Member::currentUser()) return $Member;
		else {
			Session::set("BackURL", Director::absoluteBaseURL().$this->urlParams['URLSegment'].'/'.$this->urlParams['Action'].'/'.$this->urlParams['ID'].'/');
			return false;
		}
	}

	/**
	 * Checks to see if the currently logged in user has permissions to view this forum
	 * 
	 * @param string type
	 */
	function CheckForumPermissions($type = "view") {
	  switch($type) {
	    case "post":
	      // Check posting permissions
	      if($this->ForumPosters == "Anyone" || ($this->ForumPosters == "LoggedInUsers" && Member::currentUser()) || ($this->ForumPosters == "OnlyTheseUsers" && Member::currentUser() && Member::currentUser()->isInGroup($this->ForumPostersGroup))) return true;
	      else return false;
      break;  
	    case "view":
	    default:
	      // Check viewing forum permissions
	      
	      if($this->ForumViewers == "Anyone" || ($this->ForumViewers == "LoggedInUsers" && Member::currentUser()) || ($this->ForumViewers == "OnlyTheseUsers" && Member::currentUser() && Member::currentUser()->isInGroup($this->ForumViewersGroup))) return true;
	      else return false;
	    break;
	  }
	}
	
	/**
	 * Return the topic tree beneath the root-post, as a nested <ul>
	 */
	 
	function TopicTree() {
		if($this->Mode() == "threaded")
			$result = $this->TopicTree_FullyThreaded();
		else
			$result = $this->TopicTree_Flat();
		return $result;
	}
	
	function getTopicTree($postID=null) {
		if($_REQUEST['mode']) $this->setMode($_REQUEST['mode']);
		if($postID == null);
			$postID = $this->urlParams['ID'];
			
		if($postID != null && $post = $this->Post($postID)){
			return $this->renderWith("ForumRightHand");
		}
		else{
			if($this->ViewMode()=='Edit')
				return "<div id=\"Root\">".$this->ReplyForm()->forTemplate()."</div>";			
			else
				return "<div id=\"Root\">".$this->ReplyForm_Preview()->forTemplate()."</div>";
		}
	}
		
	function TopicTree_FullyThreaded($postID=null) {
		
		if($postID == null);
			$postID = $this->urlParams['ID'];
			
		if($postID&&$post = $this->Post($postID)){
			if(!$post->TopicID) user_error("Post #$postID doesn't have a Topic ID", E_USER_ERROR);
			
			$root = $this->Root($postID);

			if(!$root) user_error("Topic #$post->TopicID can't be found.", E_USER_ERROR);
			if(!Director::is_ajax()) $root = $post;
			if($root->Status == 'Moderated'){
				if($this->Moderator() == Member::currentUser()){
					$root->setMarkingFilter("Status", array("Moderated", "Awaiting"));
				}else{
					$root->setMarkingFilter("Status", "Moderated");
				}
		
				$root->markPartialTree(null);
				
				$subTree = $root->getChildrenAsUL("id=\"childrenof-$root->ID\" class=\"Root tree\"", 
						' "<li id=\"post-$child->ID\" class=\"$child->class $child->Status\">" . ' . 
						' "<a  title=\"by ".$child->AuthorFullName()." - $child->Created\" href=\"" . Director::link("$extraArg->URLSegment", "show", $child->ID) . "\" >" . $child->Title . "</a>" ',
						$this, true);
		
				return $subTree.($subTree?$this->CheckboxForMode():"");
			}
		}
	}

	/** Return a ascendants tree from root to the given post
		* Only for none ajax version
		*/
	function AscendantsThreading(){
		
		if($postID = $this->urlParams['ID']&&$post = $this->Post($postID)){
			$ascendants=array();
			$post->getAscendants($ascendants);
			
			if($ascendants){
				$ascendants = array_reverse($ascendants);

				
				if($this->Mode()=='threaded'){
					$ret = "";
					foreach($ascendants as $ascendant){
						$ret .="<ul><li id=\"post-$ascendant->ID\" class=\"$ascendant->class\"><a title=\"by ".$ascendant->AuthorFullName()." - $ascendant->Created\" href=\"".$ascendant->Link()."\">$ascendant->Title</a></li>";
					}
					foreach($ascendants as $ascendant){
						$ret .="</ul>";
					}
				}else{
					$ret = "<ul>";
					foreach($ascendants as $ascendant){
						$ret .= "<li id=\"post-$ascendant->ID\" class=\"$ascendant->class\"><a title=\"by ".$ascendant->AuthorFullName()." - $ascendant->Created\" href=\"".$ascendant->Link()."\">$ascendant->Title</a></li>";
					}
					$ret .= "</ul>";
				}
			}
			return $ret;
		}
	}
	
	
	/**
	 * Return the topic flat list of all thread under the root
	 */
	function TopicTree_Flat($postID=null) {
		if($postID == null)
			$postID = $this->urlParams['ID'];
		if($postID&&$post = $this->Post($postID)){
			if(!$post->TopicID) user_error("Post #$postID doesn't have a Topic ID", E_USER_ERROR);
			
			$root = $this->Root($postID);

			if(!$root) user_error("Topic #$post->TopicID can't be found.", E_USER_ERROR);
			
			if(!Director::is_ajax()) $root = $post;
			
			if($this->Moderator() == Member::currentUser()){
				$subFlatNodes = DataObject::get("Post", "TopicID = $root->TopicID and ParentID <> 0 and (Status = 'Moderated' or Status = 'Awaiting')");
			}else{
				$subFlatNodes = DataObject::get("Post", "TopicID = $root->TopicID and ParentID <> 0 and Status = 'Moderated'");
			}

			$subTree = "<ul id=\"childrenof-$root->ID\" class=\"Root tree\">";
			foreach($subFlatNodes as $node){
				$subTree .="<li id=\"post-$node->ID\" class=\"$node->class $node->Status\">";
				$subTree .= "<a title=\"by ".$node->AuthorFullName()." - $node->Created\" href=\"".$node->Link()."\">$node->Title</a></li>";
			}
			$subTree .= "</ul>";
			return $subTree.($subTree?$this->CheckboxForMode():"");
		}
	}
	
	function StartTopicLink(){
		return Director::Link($this->URLSegment, 'starttopic');
	}
	
	function CheckboxForMode(){
		if(Session::get('forumInfo.mode') == 'threaded')
			return '<div id="Mode">Arranged By: Latest on Top <input name="Mode" type="checkbox" value="flat" /></div>';
		else
			return '<div id="Mode">Arranged By: Converstation <input name="Mode" type="checkbox" value="threaded" /></div>';
	}
	function Mode() {
		if(!Session::get('forumInfo.mode')) {
			Session::set('forumInfo.mode', 'threaded');
		}

		return Session::get('forumInfo.mode');
	}
	

	function setMode($val){
		Session::set('forumInfo.mode', $val);
	}
	
		
	function ViewMode(){
		if(!Session::get('forumInfo.viewmode')) {
			Session::set('forumInfo.viewmode', 'Edit');
		}

		return Session::get('forumInfo.viewmode');
	}
	
	function setViewMode($val){
		Session::set('forumInfo.viewmode', $val);
	}
	
	function setPostVar($val) {
			$currentID = $val['Parent'];
/*			$val['Title']=Badwords::Moderate($val['Title']);
			$val['Content']=Badwords::Moderate($val['Content']);*/
			Session::set("forumInfo.{$currentID}.postvar", $val);
	}
	
	/**
	 * Return the detail of the root-post, suitable for access as <% control Post %>
	 */
	 
	function Root($id = null) {
		$post = $this->Post($id);
		return DataObject::get_by_id("Post", $post->TopicID);
		
	}
	function Post($id = null) {
		if($id == null) $id = $this->urlParams['ID'];
		if($id && is_numeric($id)) return DataObject::get_by_id("Post", $id);
	}
	
	function Posts() {
		$SQL_id = Convert::raw2sql($this->urlParams['ID']);
		$numPerPage = Forum::$posts_per_page;
		
		// If showPost is set, set $_GET['start'] to expose that particular post.
		if(isset($_GET['showPost']) && !isset($_GET['start'])) {
			$allIDs = DB::query("SELECT ID FROM Post WHERE TopicID = '$SQL_id' ORDER BY Created")->column();
			if($allIDs) {
				$foundPos = array_search($_GET['showPost'], $allIDs);
				$_GET['start'] = floor($foundPos / $numPerPage) * $numPerPage;
			}
		}
		
		if(!isset($_GET['start'])) {
			$_GET['start'] = 0;
		}
		return DataObject::get("Post", "TopicID = '$SQL_id'", "Created" ,"", (int)$_GET['start'] . ", $numPerPage");
	}	
	
	/**
	 * Return recent posts in this forum or topic
	 */	
	function RecentPosts($topicID = null, $limit = null) {
		if($topicID) {
			$SQL_topicID = Convert::raw2sql($topicID);
			$filter =  " AND TopicID = '$SQL_topicID'";
		}
		return DataObject::get("Post", "ForumID = '$this->ID' $filter", "Created DESC", "", $limit);
	}
	
	function PostStatus(){
		return $this->Post()->Status;
	}
	
	function BBTags() {
		return BBCodeParser::usable_tags();
	}
	
	/* 
	 * Section for dealing with reply form
	 */
	function ReplyForm(){			
			// Check forum posting permissions
			if(!$this->CheckForumPermissions("post")) {
			  $messageSet = array(
				'default' => "You'll need to login before you can post to that forum. Please do so below.",
				'alreadyLoggedIn' => "I'm sorry, but you can't post to this forum until you've logged in.  If you want to log in as someone else, do so below. If you're logged in and you still can't post, you don't have the correct permissions to post.",
				'logInAgain' => "You have been logged out of the forums.  If you would like to log in again to post, enter a username and password below.",
				);
	
				Security::permissionFailure($this, $messageSet);
				exit;
			}
			
			Requirements::javascript("forum/javascript/Forum_reply.js");

	    if(!$this->currentPost){
				$this->currentPost = $this->Post($this->urlParams['ID']);
			}
			
			// Create a new Post object for this reply. protip: This is dumb :(
	  	$post = new Post;
	  	$post->write();
			
			// See if this user has already subscribed
			if($this->currentPost) $subscribed = Post_Subscription::already_subscribed($this->currentPost->TopicID);
			else $subscribed = false;
			
			
			$fields = new FieldSet(
				new TextField("Title", "Title", $this->currentPost ? "Re: " . $this->currentPost->Title : "" ),
				new TextareaField("Content", "Content"),
				new LiteralField("BBCodeHelper", "<div class=\"BBCodeHint\">[ <a href=\"?\" id=\"BBCodeHint\">View Formatting Help</a> ]</div>"),
				new CheckboxField("TopicSubscription", "Subscribe to this topic (Receive email notifications when a new reply is added)", $subscribed),
				new HiddenField("Parent", "", $this->currentPost ? $this->currentPost->ID : "" ),
				new HiddenField("PostID", "", $post->ID)
			);
			
			// Check if we can attach files to this forum's posts
			if($this->canAttach()) {
				$fields->push($attachmentField = new AttachmentField("PostAttachment", "Upload Files", "Post_Attachment"));
				$attachmentField->setExtraData(array(
					// TODO Fix this!
					"PostID" => $post->ID
				));
			}
			
			$actions = 	new FieldSet(
					// new FormAction("preview", "Preview"),
					new FormAction("postAMessage", "Post")
			);
			
			$required = new RequiredFields("Title", "Content");
			$replyform = new Form($this, "ReplyForm", $fields, $actions, $required);
			$currentID = $this->currentPost ? $this->currentPost->ID : "";
			if(Session::get("forumInfo.{$currentID}.postvar") != null){
				$_REQUEST = Session::get("forumInfo.{$currentID}.postvar");
				Session::clear("forumInfo.{$currentID}.postvar");
			}
			
			$replyform ->loadDataFrom($_REQUEST);
			
			return $replyform;
	}
	

	function preview($data, $form){
		$this->setViewMode($data['action_preview']);
		$this->setPostVar($data);
		Director::redirectBack();
	}
	
	function edit($data, $form){
		$this->setViewMode($data['action_preview']);
		Director::redirectBack();
	}
	
	function postAMessage($data, $form){	
		if($this->replyModerate()=='pass'){

			if($data['Parent']) $parent = DataObject::get_by_id('Post', $data['Parent']);
			
			// Make sure we have this posts ID, we create the new Post in Forum::ReplyForm() now to allow us to add attachments properly.
			// TODO This is dumb
			if($data['PostID']) $post = DataObject::get_by_id('Post', Convert::raw2sql($data['PostID']));
			else user_error('A valid post was not specified. We pass the Post ID through now, creating a blank post on ReplyForm. Dumb, but necessary for uploading attachments.', E_USER_ERROR);
			
			if(isset($parent)) $currentID = $parent->ID;
			else $currentID = 0;
			
			if(Session::get("forumInfo.{$currentID}.postvar") != null) {
				$data = array_merge($data, Session::get("forumInfo.{$currentID}.postvar"));
				Session::clear("forumInfo.{$currentID}.postvar");
			}
			
			$this->setViewMode("Edit");
			$this->setPostVar(null);
			if($data) {
				foreach($data as $key => $val) {
					$post->setField($key, $val);
				}
			}
			$post->ParentID = $data['Parent'];
			
			$post->TopicID = isset($parent) ? $parent->TopicID : "";
			if($member = Member::currentUser());
			$post->AuthorID = $member->ID;
			$post->ForumID = $this->ID;
			$post->write();
			
			if($post->ParentID == 0){
				$post->TopicID = $post->ID;
				$post->write(); // Extra write() that we can't avoid because we need to set $post->ID which is only created when the object is written to the database
			}
			
			// This is either a new thread or a new reply to an existing thread. We've already created the Post object, so supress the Last Edited message by setting Created and Last Edited to the same date- & time-stamp.
			// We need to bypass $post->write(), because DataObject sets LastEdited internally
			DB::query("UPDATE Post SET Created = LastEdited WHERE ID = '$post->ID'");
			
			// MDP 2007-03-24 Added thread subscription
			// Send any notifications that need to be sent
			Post_Subscription::notify($post);
			
			// Add a topic subscription entry if required
			if(isset($data['TopicSubscription'])) {
				// Ensure this user hasn't already subscribed
				if(!Post_Subscription::already_subscribed($post->TopicID)) {
					// Create a new topic subscription for this member
					$obj = new Post_Subscription;
					$obj->TopicID = $post->TopicID;
					$obj->MemberID = Member::currentUserID();
					$obj->LastSent = date("Y-m-d H:i:s"); // The user was last notified right now
					$obj->write();
				}
			} else {
				// See if the member wanted to remove themselves
				if(Post_Subscription::already_subscribed($post->TopicID)) {
					// Remove the member
					$SQL_memberID = Member::currentUserID();
					DB::query("DELETE FROM Post_Subscription WHERE `TopicID` = '$post->TopicID' AND `MemberID` = '$SQL_memberID'");
				}
			}
			
			if(Director::is_ajax()){
				$post = $this->Post($post->ID);
				if($post->ParentID != 0)
					echo "<li id=\"post-$post->ID\" class=\"$post->class $post->Status\"><a href=\"" . $post->Link(). "\" title=\"by ".$post->AuthorFullName()." - at $post->Created \">".$post->Title."</a></li>";
				else{
					$this->urlParams['ID']=$post->ID;
					echo $this->renderWith('ForumRightHand');
					$this->urlParams['ID']=null;
				}
			}else{
				Director::redirect($this->Link() . 'show/' . $post->TopicID . '?showPost=' . $post->ID);
			}
		}
	}
	
	function reject(){
		$post=$this->Post();
		$post->Status = 'Rejected';
		$post->write();
		return "rejected";
	}
	
	function accept(){
		$post=$this->Post();
		$post->Status = 'Moderated';
		$post->write();
		return "<li id=\"post-$post->ID\" class=\"$post->class $post->Status\"><a href=\"" . $post->Link(). "\" title=\"by ".$post->AuthorFullName()." - at $post->Created \">".$post->Title."</a></li>";
	}
	
	
	function ReplyForm_Preview(){
			if(!$this->currentPost){
				$this->currentPost = $this->Post($this->urlParams['ID']);
			}
			$member=Member::currentUser();
			if($member)
				$who = $member->Nickname;
			else
				$who = "a visitor";
			$now=strftime("%Y-%m-%d %H:%M:%S", time());

			$fields = new FieldSet(
				$title = new FormField("Title", ""),
				$content = new FormField("Content", ""),
				new HiddenField("Parent", "", $this->currentPost->ID),
				new	FormField("Whowhen", "", "--- by ".$who." - ".$now)
			);
			
			$content->dontEscape = true;
			$title->dontEscape = true;
			$content->reserveNL = true;
			
			$actions = 	new FieldSet(
					new FormAction("edit", "Edit")
			);
			$replyform = new Form($this, "ReplyForm_Preview", $fields, $actions);
			
			$currentID = $this->currentPost->ID;
			if(Session::get("forumInfo.{$currentID}.postvar") != null){
				$_REQUEST = Session::get("forumInfo.{$currentID}.postvar");
			}
			
			//$titleOK = Badwords::Moderate($_REQUEST['Title']);
			//$contentOK = Badwords::Moderate($_REQUEST['Content']);
			
			/*if(!$titleOK||!$contentOK){
				$_SESSION['ReplyForm_Preview']['message'] = 'You have used inappropriate language in your post. Please alter the words highlighted.';
				$_SESSION['ReplyForm_Preview']['type'] = 'bad';
			}
			else{*/
				$replyform ->Actions()->push(new FormAction("postAMessage", "Post"));
			//}
			$replyform ->loadDataFrom($_REQUEST);
			return $replyform;
	}
	
	/**
	 * Return the detail of a single post to the ajax handler.
	 * Returns a single <div> tag for insertion into the HTML.
	 */
	function getpost() {
		$id = $_REQUEST['id'];
		$post = $this->Post($id);
		return $post->renderWith('PostDetail');
	}
	
	/**
	 * Return a replyform to the ajax handler that called it.
	 * Contains form.innerHTML; doesn't include the form tag itself.
	 */
	private $currentPost;
	function getreplyform() {
		if($_REQUEST['id'] == 'preview') unset($_REQUEST['id']);
		
		$post = $this->Post($_REQUEST['id']);
		$this->currentPost = $post;
		$currentID = $this->currentPost->ID;
		if($_REQUEST['reply'])
			Session::clear("forumInfo.{$currentID}.postvar");
		if($_REQUEST['preview'])
		{
			$this->setPostVar($_REQUEST);
			$content = $this->ReplyForm_Preview()->forTemplate();
		}	else if($_REQUEST['edit']||$_REQUEST['reply']){
			$content = $this->ReplyForm()->forTemplate();
		}
		
		$content = eregi_replace('</?form[^>]*>','', $content);

		ContentNegotiator::allowXHTML();
		return $content;
	}
	
	function replyModerate(){
		/*if(!Badwords::Moderate($_REQUEST['Title'])||!Badwords::Moderate($_REQUEST['Content'])){
			if($_REQUEST['ajax']){
				return 'fail';
			}else{
				$_REQUEST['action_preview'] = 'Preview';
				$_REQUEST['Title'] = strip_tags($_REQUEST['Title']);
				$_REQUEST['Content'] = strip_tags($_REQUEST['Content']);
				$this->preview($_REQUEST, null);
				return false;
			}
		}else{*/
			if(Director::is_ajax()){
				return 'pass';
			}else{
				return true;
			}
		// }
	}
	
	function ReplyLink() {
		return $this->Link() . "reply/" . $this->urlParams['ID'];
	}
	
	/**
	 * Show will redirect to either flat or threaded, whatever the user's preference is
	 */
	function show() {
	  $url = $this->Link() . 'flat/' . $this->urlParams['ID'];
		if(isset($_REQUEST['showPost'])) $url .= '?showPost=' . $_REQUEST['showPost'];
		Director::redirect($url);
	}
	
	function flat() {
		RSSFeed::linkToFeed($this->Link("rss") . '/' . $this->urlParams['ID'], "Posts to the '" . $this->Post()->Title . "' topic");
		
		$SQL_id = Convert::raw2sql($this->urlParams['ID']);
		if(is_numeric($SQL_id)) {
			$topic = DataObject::get_by_id("Post", $SQL_id);
			if($topic) $topic->incNumViews();
		}
		return array();
	}
	
	function rss() {
		$rss = new RSSFeed($this->RecentPosts($this->urlParams['ID'], 10), $this->Link(), "Forum posts to '$this->Title'", "", "Title", "RSSContent", "RSSAuthor");
		$rss->outputToBrowser();
	}
	
	function starttopic() {
		return array(
			'Subtitle' => 'Start a new topic',
			'Abstract' => DataObject::get_one("ForumHolder")->ForumAbstract
		);
	}
	
	function getSubtitle() {
		return $this->Title;
	}
	function getAbstract() {
		return DataObject::get_one("ForumHolder")->HolderAbstract;
	}
	function TotalPosts() {
		return DB::query("SELECT COUNT(*) FROM Post WHERE ForumID = $this->ID")->value();
	}
	function TotalTopics() {
		return DB::query("SELECT COUNT(*) FROM Post WHERE ParentID = 0 AND ForumID = $this->ID")->value();
			}
	function TotalAuthors() {
		return DB::query("SELECT COUNT(DISTINCT AuthorID) FROM Post WHERE ForumID = $this->ID")->value();
	}
	
	function Forums() {
		return DataObject::get("Forum");
	}
	
	static $lastForumAccessed;
	static function getLastForumAccessed() {
		if(self::$lastForumAccessed) return DataObject::get_by_id("Forum", self::$lastForumAccessed);
		else {
			$forums = DataObject::get("Forum","","","", 1);
			return $forums->First();
		}
		
	}
	
	function editpost() {
	  return array(
			'Subtitle' => 'Edit a post'
		);
	}
	
	function EditPostForm() {
		
	  // See if this user has already subscribed
	  if($this->currentPost) $subscribed = Post_Subscription::already_subscribed($this->currentPost->TopicID);
	  else $subscribed = false;
	  
	  Requirements::javascript("forum/javascript/Forum_reply.js");
	  		
	  return new Form($this, "EditPostForm", new FieldSet(
	    new TextField("Title", "Title", $this->currentPost ? $this->currentPost->Title : "" ),
	    new TextareaField("Content", "Content", 5, 40, $this->currentPost ? $this->currentPost->Content : "" ),
	    
	    new LiteralField("BBCodeHelper", "<div class=\"BBCodeHint\">[ <a href=\"?\" id=\"BBCodeHint\">View Formatting Help</a> ]</div>"),
		new CheckboxField("TopicSubscription", "Subscribe to this topic (Receive email notifications when a new reply is added)", $subscribed),
	
	    new HiddenField("ID", "ID", $this->currentPost ? $this->currentPost->ID : "" )
	  ), new FieldSet(
	    new FormAction("editAMessage", "Edit")
	  ), new RequiredFields(
	    "Title",
	    "Content"
	  ));
	}
	
	function EditForm() {
	  /**
	   * TODO Add in user authentication checking - user must either be the author of the post or a CMS admin
	   * TODO Add some nicer default CSS for this form into forum/css/Forum.css
	   */
	  // Get the current post if we haven't found it yet
	  if(!$this->currentPost){
			$this->currentPost = $this->Post($this->urlParams['ID']);
	    if(!$this->currentPost) {
			  return array(
			    "Content" => "<p class=\"message bad\">The current post couldn't be found in the database. Please go back to the thread you were editing and try to edit the post again. If this error persists, please email the administrator.</p>"
			  );
			}
		}
		
		// User authentication
	  if(Member::currentUser() && (Member::currentUser()->_isAdmin() || Member::currentUser()->ID == $this->currentPost->AuthorID)) {
		  return $this->EditPostForm();
	  } else {
	    return "You don't have the correct permissions to edit this post.";
	  }
	}
	
	function editAMessage($data, $form) {
	  // Get the current post if we haven't found it yet
	  if(!$this->currentPost){
			$this->currentPost = $this->Post(Convert::raw2sql($data['ID']));
			if(!$this->currentPost) {
			  return array(
			    "Content" => "<p class=\"message bad\">The current post couldn't be found in the database. Please go back to the thread you were editing and try to edit the post again. If this error persists, please email the administrator.</p>"
			  );
			}
		}

		// User authentication
	  if(Member::currentUser() && (Member::currentUser()->_isAdmin() || Member::currentUser()->ID == $this->currentPost->AuthorID)) {
		  // Convert the values to SQL-safe values
	    $data['ID'] = Convert::raw2sql($data['ID']);
		  $data['Title'] = Convert::raw2sql($data['Title']);
	    $data['Content'] = Convert::raw2sql($data['Content']);
	
	    // Save form data into the post
	    $form->saveInto($this->currentPost);
	    $this->currentPost->write();

	  if($data['ID']) $post = DataObject::get_by_id('Post', Convert::raw2sql($data['ID']));
	  
	  // MDP 2007-03-24 Added thread subscription
	  // Send any notifications that need to be sent
	  Post_Subscription::notify($post);
			
	  // Add a topic subscription entry if required
	  if(isset($data['TopicSubscription'])) {
		// Ensure this user hasn't already subscribed
		if(!Post_Subscription::already_subscribed($post->TopicID)) {
			// Create a new topic subscription for this member
			$obj = new Post_Subscription;
			$obj->TopicID = $post->TopicID;
			$obj->MemberID = Member::currentUserID();
			$obj->LastSent = date("Y-m-d H:i:s"); // The user was last notified right now
			$obj->write();
		}
	  } else {
		// See if the member wanted to remove themselves
		if(Post_Subscription::already_subscribed($post->TopicID)) {
			// Remove the member
			$SQL_memberID = Member::currentUserID();
			DB::query("DELETE FROM Post_Subscription WHERE `TopicID` = '$post->TopicID' AND `MemberID` = '$SQL_memberID'");
		}
	  }
	  	    
	    Director::redirect($this->Link() . 'show/' . $this->currentPost->TopicID . '?showPost=' . $this->currentPost->ID . '#post' . $this->currentPost->ID);
	  } else {
	    $messageSet = array(
				'default' => "Enter your email address and password to edit this post.",
				'alreadyLoggedIn' => "I'm sorry, but you can't edit this post until you've logged in.  You need to be either an administrator or the author of the post in order to edit it.",
				'logInAgain' => "You have been logged out of the forums.  If you would like to log in again, enter a username and password below.",
			);

			Security::permissionFailure($this, $messageSet);
			exit;
	  }
	}
	
	function deletepost() {
	  if(Member::currentUser() && Member::currentUser()->_isAdmin()) {
		  // Get the current post if we haven't found it yet
		  if(!$this->currentPost){
				$this->currentPost = $this->Post($this->urlParams['ID']);
		    if(!$this->currentPost) {
				  return array(
				    "Content" => "<p class=\"message bad\">The current post couldn't be found in the database. Please go back to the thread you were editing and try to edit the post again. If this error persists, please email the administrator.</p>"
				  );
				}
			}

	    // Delete the post in question
      if($this->currentPost) {
      	// Delete attachments (if any) from this post
      	if($attachments = $this->currentPost->Attachments()) {
      		foreach($attachments as $file) {
      			$file->delete();
      			$file->destroy();
      		}
      	}
      	
      	$this->currentPost->delete();
      }
      
		  // Also, delete any posts where this post was the parent (that is, $this->currentPost is the first post in a thread
		  if($this->currentPost && $this->currentPost->ParentID == 0) {
		    $dependentPosts = DataObject::get("Post", "`Post`.`TopicID` = '".Convert::raw2sql($this->currentPost->OldID)."'");
		    if($dependentPosts) {
          foreach($dependentPosts as $post) {
            // Delete attachments (if any) from this post
		      	if($attachments = $post->Attachments()) {
		      		foreach($attachments as $file) {
		      			$file->delete();
		      			$file->destroy();
		      		}
		      	}
		      	
		      	// Delete the post
            $post->delete();
          }
		    }
		    return array(
		    	"Content" => "<p class=\"message good\">The specified thread was successfully deleted.</p>"
		  	);
		  } else {
		  	Director::redirect($this->urlParams['URLSegment']."/flat/".$this->currentPost->TopicID."/");
		  }
		  
		  
	    
	  } else {
      	Session::set("BackURL", $this->Link());
	    Director::redirect("Security/login");
	  }
	}
	
	function LatestMember($limit = null) {
		return DataObject::get("Member", "", "`Member`.`ID` DESC", "", 1);
	}
	
	function CurrentlyOnline() {
		return DataObject::get("Member", "LastVisited > NOW() - INTERVAL 15 MINUTE", "FirstName, Surname",
			"INNER JOIN Group_Members ON Group_Members.GroupID IN (1,2,3) AND Group_Members.MemberID = Member.ID");
	}
	
	/**
	 * Can we attach files to topics/posts inside this Forum?
	 * 
	 * @return bool true if the user is allowed to, false if they're not
	 */
	function canAttach() {
		return $this->CanAttachFiles ? true : false;
	}
	
	function ForumHolderURLSegment() {
		return DataObject::get_by_id( "ForumHolder", $this->ParentID )->URLSegment;
	}
}
?>