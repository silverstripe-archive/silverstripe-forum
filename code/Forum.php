<?php

/**
 * Class that represents a forum
 */
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


	/**
	 * Add default records to database
	 *
	 * This function is called whenever the database is built, after the
	 * database tables have all been created.
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		$code = "ACCESS_FORUM";

		if(!$forumGroup = DataObject::get_one("Group", "Code = 'forum-members'")) {
			$group = new Group();
			$group->Code = 'forum-members';
			$group->Title = "Forum Members";
			$group->write();

			Permission::grant( $group->ID, $code );
			Database::alteration_message("Forum Members group created","created");
		}
		else if(DB::query(
			"SELECT * FROM Permission WHERE `GroupID` = '$forumGroup->ID' AND `Code` LIKE '$code'")
				->numRecords() == 0 ) {
			Permission::grant($forumGroup->ID, $code);
		}

		if(!DataObject::get_one("ForumHolder")) {
			$forumholder = new ForumHolder();
			$forumholder->Title = "Forums";
			$forumholder->URLSegment = "forums";
			$forumholder->Content = "<p>Welcome to SilverStripe Forum Module! " .
				"This is the default ForumHolder page. You can now add forums.</p>";
			$forumholder->Status = "Published";
			$forumholder->write();
			$forumholder->publish("Stage", "Live");
			Database::alteration_message("ForumHolder page created","created");

			$forum = new Forum();
			$forum->Title = "General Discussion";
			$forum->URLSegment = "general-discussion";
			$forum->ParentID = $forumholder->ID;
			$forum->Content = "<p>Welcome to SilverStripe Forum Module! This " .
				"is the default Forum page. You can now add topics.</p>";
			$forum->Status = "Published";
			$forum->write();
			$forum->publish("Stage", "Live");

			Database::alteration_message("Forum page created","created");
		}
	}


	/**
	 * Returns a FieldSet with which to create the CMS editing form
	 *
	 * @return FieldSet The fields to be displayed in the CMS.
	 */
	function getCMSFields() {
		Requirements::javascript("forum/javascript/ForumAccess.js");
		Requirements::css("forum/css/Forum_CMS.css");

	  $fields = parent::getCMSFields();

		$fields->addFieldToTab("Root.Access", new HeaderField("Who can read the forum?", 2));
		$fields->addFieldToTab("Root.Access",
			new OptionsetField("ForumViewers", "", array(
				"Anyone" => "Anyone",
				"LoggedInUsers" => "Logged-in users",
				"OnlyTheseUsers" => "Only these people (choose from list)")
			)
		);
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

	/**
	 * Create breadcrumbs
	 *
	 * @param int $maxDepth Maximal lenght of the breadcrumb navigation
	 * @param bool $unlinked Set to TRUE if the breadcrumb should consist of
	 *                       links, otherwise FALSE.
	 * @param bool $stopAtPageType Currently not used
	 * @param bool $showHidden Set to TRUE if also hidden pages should be
	 *                         displayed
	 * @return string HTML code to display breadcrumbs
	 */
	public function Breadcrumbs($maxDepth = null,
															$unlinked = false,
															$stopAtPageType = false,
															$showHidden = false) {
		$page = $this;
		$nonPageParts = array();
		$parts = array();

		$controller = Controller::currentController();
		$params = $controller->getURLParams();

		$SQL_id = $params['ID'];
		if(is_numeric($SQL_id)) {
			$topic = DataObject::get_by_id("Post", "$SQL_id");

			if($topic) {
				$nonPageParts[] = Convert::raw2xml($topic->getTitle());
			}
		}

		while($page && (!$maxDepth || sizeof($parts) < $maxDepth)) {
			if($showHidden || $page->ShowInMenus || ($page->ID == $this->ID)) {
				if($page->URLSegment == 'home')
					$hasHome = true;

				if($nonPageParts) {
					$parts[] = "<a href=\"" . $page->Link() . "\">" .
						Convert::raw2xml($page->Title) . "</a>";
				} else {
					$parts[] = (($page->ID == $this->ID) || $unlinked)
						? Convert::raw2xml($page->Title)
						: ("<a href=\"" . $page->Link() . "\">" .
							 Convert::raw2xml($page->Title) . "</a>");
				}
			}
			// this casued problems; the page would have been previously
			// referenced due to caching
			// $page->destroy();
			$page = $page->Parent;
		}

		return implode(" &raquo; ",
									 array_reverse(array_merge($nonPageParts,$parts)));
	}


	/**
	 * Get a posting by ID
	 *
	 * @param int $id ID of the posting to retrieve, if set to null, the URL
	 *                parameter ID will be used instead.
	 * @return Post Returns the desired posting or FALSE if not found.
	 * @todo This is causing some errors, temporarily added is_numeric.
	 */
	function Post($id = null) {
		if($id == null)
			$id = Director::urlParam("ID");

		if(is_numeric($id))
			return DataObject::get_by_id("Post", $id);
		// this is causing some errors, temporarily added is_numeric.
		// TODO FIXME!
	}


	/**
	 * Get the latest posting of the forum
	 *
	 * @return Post Returns the latest posting or nothing on no posts.
	 * @todo This is causing some errors, temporarily added is_numeric.
	 */
	function LatestPost() {
		if(is_numeric($this->ID)) {
			$posts = DataObject::get("Post", "ForumID = $this->ID",
															 "Created DESC", "", 1);
			if($posts)
				return $posts->First();
		}
	}


	/**
	 * Get the number of total topics (threads)
	 *
	 * @return int Returns the number of topics (threads)
	 */
	function NumTopics() {
		if(is_numeric($this->ID)) {
			return (int)DB::query("SELECT count(*) FROM Post WHERE ForumID = $this->ID AND ParentID = 0")->value();
		}
	}


	/**
	 * Get the number of total posts
	 *
	 * @return int Returns the number of posts
	 */
	function NumPosts() {
		if(is_numeric($this->ID)) {
			return (int)DB::query("SELECT count(*) FROM Post WHERE ForumID = $this->ID")->value();
		}
	}


	/**
	 * Returns the topics (first posting of each thread) for this forum
	 */
	function Topics() {
		if(Member::currentUser()==$this->Moderator() && is_numeric($this->ID)) {
			$statusFilter = "(`Post`.Status IN ('Moderated', 'Awaiting')";
		} else {
			$statusFilter = "`Post`.Status = 'Moderated'";
		}
		
		if(isset($_GET['start']) && is_numeric($_GET['start'])) $limit = Convert::raw2sql($_GET['start']) . ", 30";
		else $limit = 30;
			
		return DataObject::get("Post", "`Post`.ForumID = $this->ID and `Post`.ParentID = 0 and $statusFilter", "max(PostList.Created) DESC",
			"INNER JOIN `Post` AS PostList ON PostList.TopicID = `Post`.TopicID", $limit
		);
	}


	function getTopicsByStatus($status){
		if(is_numeric($this->ID)) {
			$status = Convert::raw2sql($status);
			return DataObject::get("Post", "ForumID = $this->ID and ParentID = 0 and Status = '$status'");
		}
	}

	function hasChildren() {
		return $this->NumPosts();
	}

	function getChildrenAsUL($attributes = "", $titleEval = '"<li>" . $child->Title',
													 $extraArg = null, $limitToMarked = false,
													 $rootCall = false){
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
	 * Checks to see if the currently logged in user has a certain permissions
	 * for this forum
	 *
	 * @param string type Permission to check
	 * @return bool Returns TRUE if the user has the permission, otherwise
	 *              FALSE.
	 */
	function CheckForumPermissions($type = "view") {
		switch($type) {
			// Check posting permissions
			case "post":
				if($this->ForumPosters == "Anyone" ||
					 ($this->ForumPosters == "LoggedInUsers" && Member::currentUser())
					 || ($this->ForumPosters == "OnlyTheseUsers" &&
					 Member::currentUser() &&
							Member::currentUser()->isInGroup($this->ForumPostersGroup)))
					return true;
				else
					return false;
				break;

			// Check viewing forum permissions
			case "view":
			default:
				if($this->ForumViewers == "Anyone" ||
					 ($this->ForumViewers == "LoggedInUsers" && Member::currentUser())
					 || ($this->ForumViewers == "OnlyTheseUsers" &&
					 Member::currentUser() &&
							Member::currentUser()->isInGroup($this->ForumViewersGroup)))
					return true;
				else
					return false;
			break;
		}
	}
}



/**
 * The forum controller class
 */
class Forum_Controller extends Page_Controller {

	/**
	 * Last accessed forum
	 */
	static $lastForumAccessed;


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
			return;
 		}

 		// Delete any posts that don't have a Title set (This cleans up posts
		// created by the ReplyForm method that aren't saved)
 		$this->deleteUntitledPosts();

 		// Log this visit to the ForumMember if they exist
 		$member = Member::currentUser();
 		if(isset($member)) {
 			$member->LastViewed = date("Y-m-d H:i:s");
 			$member->write();
 		}

 	  Requirements::javascript("jsparty/prototype.js");
 		Requirements::javascript("jsparty/behaviour.js");
 		//Requirements::javascript("jsparty/tree/tree.js");
		Requirements::javascript("forum/javascript/Forum.js");
		if($this->OpenIDAvailable())
			Requirements::javascript("forum/javascript/Forum_openid_description.js");

		// Refresh the forum every X seconds if requested
		// TODO Make this AJAX-friendly :>
		$time = $this->refreshTime();
		if(!in_array($this->urlParams['Action'],
								 array('reply', 'editpost', 'starttopic')) &&
			 ($time > 0)) {
			$time *= 1000;
			Requirements::customScript(<<<JS
				setTimeout(function() { window.location.reload(); }, {$time});
JS
);
		}

		Requirements::css("jsparty/tree/tree.css");

		Requirements::themedCSS('Forum');

		RSSFeed::linkToFeed($this->Link("rss"), "Posts to the '$this->Title' forum");
		if($this->Parent) RSSFeed::linkToFeed($this->Parent->Link("rss"), "Posts to all forums");

		if(Director::is_ajax())
			ContentNegotiator::allowXHTML();

		parent::init();
	}


	/**
	 * Is OpenID support available?
	 *
	 * This method checks if the {@link OpenIDAuthenticator} is available and
	 * registered.
	 *
	 * @return bool Returns TRUE if OpenID is available, FALSE otherwise.
	 */
	function OpenIDAvailable() {
		if(class_exists('Authenticator') == false)
			return false;

		return Authenticator::is_registered("OpenIDAuthenticator");
	}


	/**
	 * Get the URL for the login action
	 *
	 * @return string URL to the login action
	 */
	function LoginURL() {
		return $this->Link("login");
	}


	/**
	 * The login action
	 *
	 * It simple sets the return URL and forwards to the standard login form.
	 */
	function login() {
		Session::set('Security.Message.message',
								 'Please enter your credentials to access the forum.');
		Session::set('Security.Message.type', 'status');
		Session::set("BackURL", $this->Link());
		Director::redirect('Security/login');
	}


	/**
	 * Deletes any post where `Title` IS NULL and `Content` IS NULL -
	 * these will be posts that have been created by the ReplyForm method
	 * but not modified by the postAMessage method.
	 *
	 * Has a time limit - posts can exist in this state for 24 hours
	 * before they are deleted - this is so anybody uploading attachments
	 * has time to do so.
	 */
	function deleteUntitledPosts() {
		DB::query("DELETE FROM Post WHERE `Title` IS NULL AND `Content` IS NULL AND `Created` < NOW() - INTERVAL 24 HOUR");
	}


	/**
	 * Checks if this forum should refresh every X seconds
	 *
	 * @return int Returns 0 if the forum shouldn't refresh, otherwise the
	 *             number of seconds if it should refresh
	 */
	protected function refreshTime() {
		/** Ensure refresher is on **/
		if(!$this->ForumRefreshOn)
			return 0;

		/** Ensure the input is valid **/
		if(!ctype_digit((string)$this->ForumRefreshTime) ||
			 (int)$this->ForumRefreshTime < 1)
			return 0;

		return $this->ForumRefreshTime;
	}


	/**
	 * Get the currently logged in member
	 *
	 * @return Member Returns the currently logged in member or FALSE.
	 *
	 * @todo Check (and explain) why BackURL is set if the user isn't logged
	 *       in
	 */
	function CurrentMember() {
		if($Member = Member::currentUser()) {
			return $Member;
		} else {
			Session::set("BackURL", Director::absoluteBaseURL() .
									 $this->urlParams['URLSegment'] . '/' .
									 $this->urlParams['Action'] . '/' .
									 $this->urlParams['ID'] . '/');
			return false;
		}
	}


	/**
	 * Checks to see if the currently logged in user has a certain permissions
	 * for this forum
	 *
	 * @param string type
	 */
	function CheckForumPermissions($type = "view") {
	  switch($type) {
			// Check posting permissions
			case "post":
				if($this->ForumPosters == "Anyone" ||
					 ($this->ForumPosters == "LoggedInUsers" && Member::currentUser()) ||
					 ($this->ForumPosters == "OnlyTheseUsers" && Member::currentUser() &&
							Member::currentUser()->isInGroup($this->ForumPostersGroup)))
					return true;
				else
					return false;
				break;

			// Check viewing forum permissions
			case "view":
			default:
				if($this->ForumViewers == "Anyone" ||
					 ($this->ForumViewers == "LoggedInUsers" && Member::currentUser()) ||
					 ($this->ForumViewers == "OnlyTheseUsers" && Member::currentUser() &&
							Member::currentUser()->isInGroup($this->ForumViewersGroup)))
					return true;
				else
					return false;
			break;
		}
	}


	/**
	 * Return the topic tree beneath the root-post, as a nested list
	 *
	 * @return string HTML code for the topic tree
	 */
	function TopicTree() {
		if($this->Mode() == "threaded")
			$result = $this->TopicTree_FullyThreaded();
		else
			$result = $this->TopicTree_Flat();
		return $result;
	}


	/**
	 * Get topic tree
	 *
	 * @return string Returns the HTML code for the topic tree
	 *
	 * @todo Add more explanation for this method
	 */
	function getTopicTree($postID = null) {
		if($_REQUEST['mode'])
			$this->setMode($_REQUEST['mode']);

		if($postID == null);
			$postID = $this->urlParams['ID'];

		if(($postID != null) && ($post = $this->Post($postID))) {
			return $this->renderWith("ForumRightHand");
		}
		else {
			if($this->ViewMode() == 'Edit')
				return "<div id=\"Root\">" . $this->ReplyForm()->forTemplate() . "</div>";
			else
				return "<div id=\"Root\">" . $this->ReplyForm_Preview()->forTemplate() . "</div>";
		}
	}


	/**
	 * Get topic tree (fully threaded)
	 *
	 * @param int $postID ID of the posting or NULL if the URL parameter ID
	 *                    should be used
	 * @return string Returns the HTML code for the topic tree.
	 */
	function TopicTree_FullyThreaded($postID = null) {
		if($postID == null);
			$postID = $this->urlParams['ID'];

		if($postID && ($post = $this->Post($postID))) {
			if(!$post->TopicID)
				user_error("Post #$postID doesn't have a Topic ID", E_USER_ERROR);

			$root = $this->Root($postID);

			if(!$root)
				user_error("Topic #$post->TopicID can't be found.", E_USER_ERROR);

			if(!Director::is_ajax())
				$root = $post;

			if($root->Status == 'Moderated') {
				if($this->Moderator() == Member::currentUser()) {
					$root->setMarkingFilter("Status", array("Moderated", "Awaiting"));
				} else {
					$root->setMarkingFilter("Status", "Moderated");
				}

				$root->markPartialTree(null);

				$subTree = $root->getChildrenAsUL("id=\"childrenof-$root->ID\" class=\"Root tree\"",
						' "<li id=\"post-$child->ID\" class=\"$child->class $child->Status\">" . ' .
						' "<a  title=\"by ".$child->AuthorFullName()." - $child->Created\" href=\"" . Director::link("$extraArg->URLSegment", "show", $child->ID) . "\" >" . $child->Title . "</a>" ',
						$this, true);

				$subTree .= ($subTree)
					? $this->CheckboxForMode()
					: "";

				return $subTree;
			}
		}
	}


	/**
	 * Return a ascendants tree from root to the given post
	 *
	 * Only for none ajax version
	 *
	 * @return string Returns the HTML code for the ascendants tree from the
	 *                root to the given post
	 */
	function AscendantsThreading() {
		if(($postID = $this->urlParams['ID']) && ($post = $this->Post($postID))) {
			$ascendants=array();
			$post->getAscendants($ascendants);

			if($ascendants){
				$ascendants = array_reverse($ascendants);

				if($this->Mode()=='threaded') {
					$ret = "";
					foreach($ascendants as $ascendant) {
						$ret .= "<ul><li id=\"post-$ascendant->ID\" class=\"$ascendant->class\"><a title=\"by " .
							$ascendant->AuthorFullName() . " - $ascendant->Created\" href=\"" .
							$ascendant->Link() . "\">$ascendant->Title</a></li>";
					}
					foreach($ascendants as $ascendant) {
						$ret .="</ul>";
					}
				} else {
					$ret = "<ul>";
					foreach($ascendants as $ascendant) {
						$ret .= "<li id=\"post-$ascendant->ID\" class=\"$ascendant->class\"><a title=\"by " .
							$ascendant->AuthorFullName() . " - $ascendant->Created\" href=\"" .
							$ascendant->Link() . "\">$ascendant->Title</a></li>";
					}
					$ret .= "</ul>";
				}
			}
			return $ret;
		}
	}


	/**
	 * Return the topic flat list of all threads under the root
	 *
	 * @param int $postID ID of the posting or NULL if the URL parameter ID
	 *                    should be used
	 * @return string Returns the HTML code of the topic list
	 */
	function TopicTree_Flat($postID = null) {
		if($postID == null)
			$postID = $this->urlParams['ID'];

		if($postID && ($post = $this->Post($postID))) {
			if(!$post->TopicID)
				user_error("Post #$postID doesn't have a Topic ID", E_USER_ERROR);

			$root = $this->Root($postID);

			if(!$root)
				user_error("Topic #$post->TopicID can't be found.", E_USER_ERROR);

			if(!Director::is_ajax())
				$root = $post;

			if($this->Moderator() == Member::currentUser()) {
				$subFlatNodes = DataObject::get("Post",
					"TopicID = $root->TopicID and ParentID <> 0 and (Status = 'Moderated' or Status = 'Awaiting')");
			} else {
				$subFlatNodes = DataObject::get("Post",
					"TopicID = $root->TopicID and ParentID <> 0 and Status = 'Moderated'");
			}

			$subTree = "<ul id=\"childrenof-$root->ID\" class=\"Root tree\">";
			foreach($subFlatNodes as $node) {
				$subTree .= "<li id=\"post-$node->ID\" class=\"$node->class $node->Status\">";
				$subTree .= "<a title=\"by " . $node->AuthorFullName() .
					" - $node->Created\" href=\"" . $node->Link() .
					"\">$node->Title</a></li>";
			}
			$subTree .= "</ul>";
			$subTree .= ($subTree)
				? $this->CheckboxForMode()
				: "";

			return $subTree;
		}
	}


	/**
	 * Get the link for the "start topic" action
	 *
	 * @return string Link for the start topic action
	 */
	function StartTopicLink(){
		return Director::Link($this->URLSegment, 'starttopic');
	}


	/**
	 * Get a checkbox for mode
	 *
	 * @return string Returns the HTML code for a checkbox to change the forum
	 *                mode (flat <-> threaded).
	 */
	function CheckboxForMode(){
		if(Session::get('forumInfo.mode') == 'threaded')
			return '<div id="Mode">Arranged By: Latest on Top <input name="Mode" type="checkbox" value="flat" /></div>';
		else
			return '<div id="Mode">Arranged By: Converstation <input name="Mode" type="checkbox" value="threaded" /></div>';
	}


	/**
	 * Get the forum mode (threaded or flat)
	 *
	 * @return string Returns the forum mode (threaded or flat).
	 */
	function Mode() {
		if(!Session::get('forumInfo.mode')) {
			Session::set('forumInfo.mode', 'threaded');
		}

		return Session::get('forumInfo.mode');
	}


	/**
	 * Set the forum mode (threaded or flat)
	 *
	 * @param string $val The forum mode (threaded or flat).
	 */
	function setMode($val) {
		Session::set('forumInfo.mode', $val);
	}


	/**
	 * Get the view mode
	 *
	 * @return string Returns the view mode
	 */
	function ViewMode() {
		if(!Session::get('forumInfo.viewmode')) {
			Session::set('forumInfo.viewmode', 'Edit');
		}

		return Session::get('forumInfo.viewmode');
	}


	/**
	 * Set the view mode
	 *
	 * @param string $val The desired mode (e.g. Edit).
	 */
	function setViewMode($val) {
		Session::set('forumInfo.viewmode', $val);
	}


	/**
	 * Set postvar
	 *
	 * This function is used to store the user submitted data for example when
	 * previewing a post.
	 *
	 * @param mixed $val The desired value of postvar
	 */
	function setPostVar($val) {
			$currentID = $val['Parent'];
			/*$val['Title']=Badwords::Moderate($val['Title']);
			$val['Content']=Badwords::Moderate($val['Content']);*/
			Session::set("forumInfo.{$currentID}.postvar", $val);
	}


	/**
	 * Return the detail of the root-post, suitable for access as
	 * <% control Post %>
	 *
	 * @param int $id ID of the posting or NULL
	 * @return Post Returns the root posting.
	 */
	function Root($id = null) {
		$post = $this->Post($id);
		return DataObject::get_by_id("Post", $post->TopicID);
	}


	/**
	 * Get a posting
	 *
	 * @param int $id ID of the posting or NULL if the URL parameter ID should
	 *                be used
	 * @return Post Returns the desired posting.
	 */
	function Post($id = null) {
		if($id == null)
			$id = $this->urlParams['ID'];

		if($id && is_numeric($id))
			return DataObject::get_by_id("Post", $id);
	}


	/**
	 * Get posts to display
	 *
	 * This method assumes an URL parameter "ID" which contaings the topic ID.
	 *
	 * @return DataObjectSet Returns the posts.
	 */
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
		return DataObject::get("Post", "TopicID = '$SQL_id'", "Created" , "",
													 (int)$_GET['start'] . ", $numPerPage");
	}


	/**
	 * Return recent posts in this forum or topic
	 *
	 * @param int $topicID ID of the relevant topic (set to NULL for all
	 *                     topics)
	 * @param int $limit Max. number of posts to return
	 * @param int $lastVisit Optional: Unix timestamp of the last visit (GMT)
	 * @param int $lastPostID Optional: ID of the last read post
	 * @return DataObjectSet Returns the posts.
	 */
	function RecentPosts($topicID = null, $limit = null, $lastVisit = null,
											 $lastPostID = null) {
		if($topicID) {
			$SQL_topicID = Convert::raw2sql($topicID);
			$filter =  " AND TopicID = '$SQL_topicID'";
		} else {
			$filter = "";
		}

		if($lastVisit)
			$lastVisit = @date('Y-m-d H:i:s', $lastVisit);
		$lastPostID = (int)$lastPostID;
		if($lastPostID <= 0)
			$lastPostID = false;

		if($lastVisit)
			$filter .= " AND Created > '$lastVisit'";

		if($lastPostID)
			$filter .= " AND ID > $lastPostID";

		return DataObject::get("Post", "ForumID = '$this->ID' $filter",
													 "Created DESC", "", $limit);
	}


	/**
	 * Are new posts available?
	 *
	 * @param int $lastVisit Unix timestamp of the last visit (GMT)
	 * @param int $lastPostID ID of the last read post
	 * @param int $topicID ID of the relevant topic (set to NULL for all
	 *                     topics)
	 * @param array $data Optional: If an array is passed, the timestamp of
	 *                    the last created post and it's ID will be stored in
	 *                    it (keys: 'last_id', 'last_created')
	 * @return bool Returns TRUE if there are new posts available, otherwise
	 *              FALSE.
	 */
	public function NewPostsAvailable($lastVisit, $lastPostID,
																		$topicID = null, array
																		&$data = null) {
		if(is_numeric($topicID)) {
			$SQL_topicID = Convert::raw2sql($topicID);
			$filter =  "AND TopicID = '$SQL_topicID'";
		} else {
			$filter = "";
		}

		$version = DB::query("SELECT max(ID) as LastID, max(Created) " .
			"as LastCreated FROM Post WHERE ForumID = $this->ID $filter")->first();

		if($version == false)
			return false;

		if($data) {
			$data['last_id'] = (int)$version['LastID'];
			$data['last_created'] = strtotime($version['LastCreated']);
		}

		$lastVisit = (int) $lastVisit;
		if($lastVisit <= 0)
			$lastVisit = false;

		$lastPostID = (int)$lastPostID;
		if($lastPostID <= 0)
			$lastPostID = false;

		if(!$lastVisit && !$lastPostID)
			return true; // no check possible!

		if($lastVisit && (strtotime($version['LastCreated']) > $lastVisit))
			return true;

		if($lastPostID && ((int)$version['LastID'] > $lastPostID))
			return true;

		return false;
	}


	/**
	 * Get the status of a posting
	 *
	 * This method assumes that the URL parameter "ID" is available.
	 */
	function PostStatus() {
		return $this->Post()->Status;
	}


	/**
	 * Get the usable BB codes
	 *
	 * @return DataObjectSet Returns the usable BB codes
	 * @see BBCodeParser::usable_tags()
	 */
	function BBTags() {
		return BBCodeParser::usable_tags();
	}


	/**
	 * Section for dealing with reply form
	 *
	 * @return Form Returns the reply form
	 */
	function ReplyForm() {
		// Check forum posting permissions
		if(!$this->CheckForumPermissions("post")) {
			$messageSet = array(
			'default' => "You'll need to login before you can post to that forum. Please do so below.",
			'alreadyLoggedIn' => "I'm sorry, but you can't post to this forum until you've logged in.  If you want to log in as someone else, do so below. If you're logged in and you still can't post, you don't have the correct permissions to post.",
			'logInAgain' => "You have been logged out of the forums.  If you would like to log in again to post, enter a username and password below.",
			);

			Security::permissionFailure($this, $messageSet);
			return;
		}

		Requirements::javascript("forum/javascript/Forum_reply.js");

		if(!$this->currentPost) {
			$this->currentPost = $this->Post($this->urlParams['ID']);
		}

		// Create a new Post object for this reply. protip: This is dumb :(
		$post = new Post;
		$post->write();

		// See if this user has already subscribed
		if($this->currentPost)
			$subscribed = Post_Subscription::already_subscribed($this->currentPost->TopicID);
		else
			$subscribed = false;


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
			$fields->push($attachmentField = new AttachmentField("PostAttachment",
																													 "Upload Files",
																													 "Post_Attachment"));
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
		$currentID = $this->currentPost
			? $this->currentPost->ID
			: "";

		if(Session::get("forumInfo.{$currentID}.postvar") != null) {
			$_REQUEST = Session::get("forumInfo.{$currentID}.postvar");
			Session::clear("forumInfo.{$currentID}.postvar");
		}

		$replyform->loadDataFrom($_REQUEST);

		return $replyform;
	}


	/**
	 * Preview a posting
	 *
	 * @param array $data The user submitted data
	 * @param Form $form The used form
	 */
	function preview($data, $form) {
		$this->setViewMode($data['action_preview']);
		$this->setPostVar($data);
		Director::redirectBack();
	}


	/**
	 * Edit a posting
	 *
	 * @param array $data The user submitted data
	 * @param Form $form The used form
	 */
	function edit($data, $form) {
		$this->setViewMode($data['action_preview']);
		Director::redirectBack();
	}


	/**
	 * Add the title and the content to a previously created post
	 *
	 * The post is either a new thread or a new reply to an existing thread.
	 * The Post object was already created.
	 *
	 * @param array $data The user submitted data
	 * @param Form $form The used form
	 */
	function postAMessage($data, $form) {
		if($this->replyModerate() == 'pass') {

			if($data['Parent'])
				$parent = DataObject::get_by_id('Post',
																				Convert::raw2sql($data['Parent']));

			// Make sure we have this posts ID, we create the new Post in
			// Forum::ReplyForm() now to allow us to add attachments properly.
			// TODO This is dumb
			if($data['PostID']) {
				$post = DataObject::get_by_id('Post',
																			Convert::raw2sql($data['PostID']));
			}
			else {
				user_error('A valid post was not specified. We pass the Post ID ' .
									 'through now, creating a blank post on ReplyForm. ' .
									 'Dumb, but necessary for uploading attachments.',
									 E_USER_ERROR);
			}

			if(isset($parent))
				$currentID = $parent->ID;
			else
				$currentID = 0;

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

			$post->TopicID = isset($parent)
				? $parent->TopicID
				: "";

			if($member = Member::currentUser())
				$post->AuthorID = $member->ID;
			$post->ForumID = $this->ID;
			$post->write();

			if($post->ParentID == 0) {
				$post->TopicID = $post->ID;
				// Extra write() that we can't avoid because we need to set
				// $post->ID which is only created when the object is written to the
				// database
				$post->write();
			}

			// This is either a new thread or a new reply to an existing thread.
			// We've already created the Post object, so supress the Last Edited
			// message by setting Created and Last Edited to the same time-stamp.
			// We need to bypass $post->write(), because DataObject sets
			// LastEdited internally
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

			if(Director::is_ajax()) {
				$post = $this->Post($post->ID);
				if($post->ParentID != 0) {
					echo "<li id=\"post-$post->ID\" class=\"$post->class $post->Status\"><a href=\"" .
						$post->Link() . "\" title=\"by " . $post->AuthorFullName() .
						" - at $post->Created \">" . $post->Title . "</a></li>";
				} else {
					$this->urlParams['ID']= $post->ID;
					echo $this->renderWith('ForumRightHand');
					$this->urlParams['ID'] = null;
				}
			} else {
				Director::redirect($this->Link() . 'show/' . $post->TopicID .
													 '?showPost=' . $post->ID);
			}
		}
	}


	/**
	 * Reject a post
	 *
	 * This method assumes that the URL parameter "ID" is available.
	 *
	 * @return string Returns always "rejected".
	 */
	function reject() {
		$post = $this->Post();
		$post->Status = 'Rejected';
		$post->write();
		return "rejected";
	}


	/**
	 * Accept a post
	 *
	 * This method assumes that the URL parameter "ID" is available.
	 *
	 * @return string Returns the HTML code for a status message.
	 */
	function accept() {
		$post = $this->Post();
		$post->Status = 'Moderated';
		$post->write();
		return "<li id=\"post-$post->ID\" class=\"$post->class $post->Status\"><a href=\"" .
			$post->Link() . "\" title=\"by " . $post->AuthorFullName() .
			" - at $post->Created \">" . $post->Title . "</a></li>";
	}


	/**
	 * The "preview" version of the reply form
	 *
	 * @return Form Returns the reply form.
	 */
	function ReplyForm_Preview() {
		if(!$this->currentPost) {
			$this->currentPost = $this->Post($this->urlParams['ID']);
		}

		$member = Member::currentUser();
		if($member)
			$who = $member->Nickname;
		else
			$who = "a visitor";

		$now = strftime("%Y-%m-%d %H:%M:%S", time());

		$fields = new FieldSet(
			$title = new FormField("Title", ""),
			$content = new FormField("Content", ""),
			new HiddenField("Parent", "", $this->currentPost->ID),
			new	FormField("Whowhen", "", "--- by " . $who . " - " . $now)
		);

		$content->dontEscape = true;
		$title->dontEscape = true;
		$content->reserveNL = true;

		$actions = new FieldSet(
			new FormAction("edit", "Edit")
		);
		$replyform = new Form($this, "ReplyForm_Preview", $fields, $actions);

		$currentID = $this->currentPost->ID;
		if(Session::get("forumInfo.{$currentID}.postvar") != null) {
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


	private $currentPost;
	/**
	 * Return a replyform to the ajax handler that called it.
	 * Contains form.innerHTML; doesn't include the form tag itself.
	 */
	function getreplyform() {
		if($_REQUEST['id'] == 'preview')
			unset($_REQUEST['id']);

		$post = $this->Post($_REQUEST['id']);
		$this->currentPost = $post;
		$currentID = $this->currentPost->ID;
		if($_REQUEST['reply'])
			Session::clear("forumInfo.{$currentID}.postvar");

		if($_REQUEST['preview']) {
			$this->setPostVar($_REQUEST);
			$content = $this->ReplyForm_Preview()->forTemplate();
		}	else if($_REQUEST['edit']||$_REQUEST['reply']) {
			$content = $this->ReplyForm()->forTemplate();
		}

		$content = eregi_replace('</?form[^>]*>','', $content);

		ContentNegotiator::allowXHTML();
		return $content;
	}


	/**
	 * This method only returns a string or a boolean at the moment
	 *
	 * @return bool|string Returns "pass" if the current HTTP-Request is an
	 *                     "Ajax-Request" or TRUE otherwise.
	 */
	function replyModerate() {
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
			if(Director::is_ajax()) {
				return 'pass';
			} else {
				return true;
			}
		// }
	}


	/**
	 * Get the link for the reply action
	 *
	 * @return string URL for the reply action
	 */
	function ReplyLink() {
		return $this->Link() . "reply/" . $this->urlParams['ID'];
	}


	/**
	 * Show will redirect to flat
	 */
	function show() {
	  $url = $this->Link() . 'flat/' . $this->urlParams['ID'];
		if(isset($_REQUEST['showPost']))
			$url .= '?showPost=' . $_REQUEST['showPost'];
		Director::redirect($url);
	}


	/**
	 * "Flat" display mode
	 *
	 * @return array Returns an empty array
	 */
	function flat() {
		RSSFeed::linkToFeed($this->Link("rss") . '/' . $this->urlParams['ID'],
												"Posts to the '" . $this->Post()->Title . "' topic");

		$SQL_id = Convert::raw2sql($this->urlParams['ID']);
		if(is_numeric($SQL_id)) {
			$topic = DataObject::get_by_id("Post", $SQL_id);
			if($topic)
				$topic->incNumViews();
		}
		return array();
	}


	/**
	 * Get the RSS feed
	 *
	 * This method outputs the RSS feed to the browser. If the URL parameter
	 * "ID" is set it will output only posts for that topic ID.
	 */
	function rss() {
		HTTP::set_cache_age(3600); // cache for one hour

		$data = array('last_created' => null,
									'last_id' => null);

    if(!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
			 !isset($_SERVER['HTTP_IF_NONE_MATCH'])) {

			// just to get the version data..
			$this->NewPostsAvailable(null, null, $this->urlParams['ID'], $data);

      // No information provided by the client, just return the last posts
			$rss = new RSSFeed($this->RecentPosts($this->urlParams['ID'], 10),
												 $this->Link(),
												 "Forum posts to '$this->Title'", "", "Title",
												 "RSSContent", "RSSAuthor",
												 $data['last_created'], $data['last_id']);
			$rss->outputToBrowser();

    } else {
			// Return only new posts, check the request headers!
			$since = null;
			$etag = null;

			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
				// Split the If-Modified-Since (Netscape < v6 gets this wrong)
				$since = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
				// Turn the client request If-Modified-Since into a timestamp
				$since = @strtotime($since[0]);
				if(!$since)
					$since = null;
			}

			if(isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
				 is_numeric($_SERVER['HTTP_IF_NONE_MATCH'])) {
				$etag = (int)$_SERVER['HTTP_IF_NONE_MATCH'];
			}

			if($this->NewPostsAvailable($since, $etag, $this->urlParams['ID'],
																	$data)) {
				HTTP::register_modification_timestamp($data['last_created']);
				$rss = new RSSFeed($this->RecentPosts($this->urlParams['ID'], 50,
																							$since, $etag),
													 $this->Link(),
													 "Forum posts to '$this->Title'", "", "Title",
													 "RSSContent", "RSSAuthor", $data['last_created'],
													 $data['last_id']);
				$rss->outputToBrowser();
			} else {
				if($data['last_created'])
					HTTP::register_modification_timestamp($data['last_created']);

				if($data['last_id'])
					HTTP::register_etag($data['last_id']);

				// There are no new posts, just output an "304 Not Modified" message
				HTTP::add_cache_headers();
				header('HTTP/1.1 304 Not Modified');
			}
		}
	}


	/**
	 * Start topic action
	 *
	 * @return array Returns an array to render the start topic page
	 */
	function starttopic() {
		return array(
			'Subtitle' => 'Start a new topic',
			'Abstract' => DataObject::get_one("ForumHolder")->ForumAbstract
		);
	}


	/**
	 * Get the forum title
	 *
	 * @return string Returns the forum title
	 */
	function getSubtitle() {
		return $this->Title;
	}


	/**
	 * Get the forum holders' abstract
	 *
	 * @return string Returns the holders' abstract
	 * @see ForumHolder::getAbstract()
	 */
	function getAbstract() {
		return DataObject::get_one("ForumHolder")->HolderAbstract;
	}


	/**
	 * Get the number of total posts
	 *
	 * @return int Returns the number of posts
	 */
	function TotalPosts() {
		return DB::query("SELECT COUNT(*) FROM Post WHERE ForumID = $this->ID")->value();
	}


	/**
	 * Get the number of total topics (threads)
	 *
	 * @return int Returns the number of topics (threads)
	 */
	function TotalTopics() {
		return DB::query("SELECT COUNT(*) FROM Post WHERE ParentID = 0 AND ForumID = $this->ID")->value();
	}


	/**
	 * Get the number of distinct authors
	 *
	 * @return int Returns the number of distinct authors
	 */
	function TotalAuthors() {
		return DB::query("SELECT COUNT(DISTINCT AuthorID) FROM Post WHERE ForumID = $this->ID")->value();
	}


	/**
	 * Get the forums
	 */
	function Forums() {
		return DataObject::get("Forum");
	}


	/**
	 * Get the last accessed forum
	 *
	 * @return Forum Returns the last accessed forum
	 */
	static function getLastForumAccessed() {
		if(self::$lastForumAccessed)
			return DataObject::get_by_id("Forum", self::$lastForumAccessed);
		else {
			$forums = DataObject::get("Forum", "", "", "", 1);
			return $forums->First();
		}

	}


	/**
	 * Edit post action
	 *
	 * @return array Returns an array to render the edit post page
	 */
	function editpost() {
	  return array(
			'Subtitle' => 'Edit a post'
		);
	}


	/**
	 * Factory method for the edit post form
	 *
	 * @return Form Returns the edit post form
	 */
	function EditPostForm() {
	  // See if this user has already subscribed
	  if($this->currentPost)
			$subscribed = Post_Subscription::already_subscribed($this->currentPost->TopicID);
	  else
			$subscribed = false;

	  Requirements::javascript("forum/javascript/Forum_reply.js");

	  return new Form($this, "EditPostForm",
			new FieldSet(
				new TextField("Title", "Title", ($this->currentPost)
												? $this->currentPost->Title
												: "" ),
				new TextareaField("Content", "Content", 5, 40, ($this->currentPost)
														? $this->currentPost->Content
														: "" ),

				new LiteralField("BBCodeHelper", "<div class=\"BBCodeHint\">[ <a href=\"?\" id=\"BBCodeHint\">View Formatting Help</a> ]</div>"),
				new CheckboxField("TopicSubscription", "Subscribe to this topic (Receive email notifications when a new reply is added)", $subscribed),
				new HiddenField("ID", "ID", ($this->currentPost)
													? $this->currentPost->ID
													: "" )
			),
			new FieldSet(
				new FormAction("editAMessage", "Edit")
			),
			new RequiredFields("Title", "Content"));
	}


	/**
	 * Get the post edit form if the user has the necessary permissions
	 *
	 * @return string|array|Form Returns the edit post form or an error
	 *                           message.
	 *
	 * @todo Add in user authentication checking - user must either be the
	 *       author of the post or a CMS admin
	 * @todo Add some nicer default CSS for this form into forum/css/Forum.css
	 */
	function EditForm() {
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
	  if(Member::currentUser() &&
			 (Member::currentUser()->_isAdmin() ||
				Member::currentUser()->ID == $this->currentPost->AuthorID)) {
		  return $this->EditPostForm();
	  } else {
	    return "You don't have the correct permissions to edit this post.";
	  }
	}


	/**
	 * Edit a post
	 *
	 * @param array $data The user submitted data
	 * @param Form $form The used form
	 * @return array Returns an array to display an error message if the post
	 *               wasn't found, otherwise this method won't return
	 *               anything.
	 */
	function editAMessage($data, $form) {
	  // Get the current post if we haven't found it yet
	  if(!$this->currentPost) {
			$this->currentPost = $this->Post(Convert::raw2sql($data['ID']));
			if(!$this->currentPost) {
			  return array(
			    "Content" => "<p class=\"message bad\">The current post " .
						"couldn't be found in the database. Please go back to the " .
						"thread you were editing and try to edit the post again. " .
						"If this error persists, please email the administrator.</p>"
			  );
			}
		}

		// User authentication
	  if(Member::currentUser() &&
			 (Member::currentUser()->_isAdmin() ||
				Member::currentUser()->ID == $this->currentPost->AuthorID)) {
		  // Convert the values to SQL-safe values
	    $data['ID'] = Convert::raw2sql($data['ID']);
		  $data['Title'] = Convert::raw2sql($data['Title']);
	    $data['Content'] = Convert::raw2sql($data['Content']);

	    // Save form data into the post
	    $form->saveInto($this->currentPost);
	    $this->currentPost->write();

			if($data['ID'])
				$post = DataObject::get_by_id('Post', Convert::raw2sql($data['ID']));

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

			Director::redirect($this->Link() . 'show/' .
												 $this->currentPost->TopicID . '?showPost=' .
												 $this->currentPost->ID . '#post' .
												 $this->currentPost->ID);
	  } else {
	    $messageSet = array(
				'default' => "Enter your email address and password to edit this post.",
				'alreadyLoggedIn' => "I'm sorry, but you can't edit this post until you've logged in.  You need to be either an administrator or the author of the post in order to edit it.",
				'logInAgain' => "You have been logged out of the forums.  If you would like to log in again, enter a username and password below.",
			);

			Security::permissionFailure($this, $messageSet);
			return;
	  }
	}


	/**
	 * Delete a post
	 *
	 * @return array Returns an array to display an error message if the post
	 *               wasn't found or an success message.
	 *               If the user isn't logged in, this method won't return
	 *               anything but redirect the user to the login page.
	 */
	function deletepost() {
	  if(Member::currentUser() && Member::currentUser()->_isAdmin()) {
		  // Get the current post if we haven't found it yet
		  if(!$this->currentPost) {
				$this->currentPost = $this->Post($this->urlParams['ID']);
		    if(!$this->currentPost) {
				  return array(
				    "Content" => "<p class=\"message bad\">The current post " .
							"couldn't be found in the database. Please go back to the " .
							"thread you were editing and try to edit the post again. " .
							"If this error persists, please email the administrator.</p>"
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

		  // Also, delete any posts where this post was the parent (that is,
			// $this->currentPost is the first post in a thread
		  if($this->currentPost && $this->currentPost->ParentID == 0) {
		    $dependentPosts = DataObject::get("Post",
					"`Post`.`TopicID` = '" .
					Convert::raw2sql($this->currentPost->OldID) . "'");
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
		    	"Content" => "<p class=\"message good\">The specified thread " .
						"was successfully deleted.</p>"
		  	);
		  } else {
		  	Director::redirect($this->urlParams['URLSegment'] . "/flat/" .
													 $this->currentPost->TopicID . "/");
		  }

	  } else {
     	Session::set("BackURL", $this->Link());
	    Director::redirect("Security/login");
	  }
	}


	/**
	 * Get the latest members
	 *
	 * @param int $limit Number of members to return
	 */
	function LatestMember($limit = null) {
		return DataObject::get("Member", "", "`Member`.`ID` DESC", "", 1);
	}


	/**
	 * Get a list of currently online users (last 15 minutes)
	 */
	function CurrentlyOnline() {
		return DataObject::get("Member", "LastVisited > NOW() - INTERVAL 15 MINUTE", "FirstName, Surname",
			"INNER JOIN Group_Members ON Group_Members.GroupID IN (1,2,3) AND Group_Members.MemberID = Member.ID");
	}


	/**
	 * Can we attach files to topics/posts inside this forum?
	 *
	 * @return bool Set to TRUE if the user is allowed to, to FALSE if they're
	 *              not
	 */
	function canAttach() {
		return $this->CanAttachFiles ? true : false;
	}


	/**
	 * Get the forum holder's URL segment
	 */
	function ForumHolderURLSegment() {
		return DataObject::get_by_id("ForumHolder", $this->ParentID)->URLSegment;
	}
}

?>
