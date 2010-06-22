<?php
/**
 * Forum represents a collection of posts related to threads
 * 
 * @package forum
 */
class Forum extends Page {

	static $allowed_children = 'none';

	static $icon = "forum/images/treeicons/user";

	static $db = array(
		"Abstract" => "Text",
		"Type"=>"Enum(array('open', 'consultation'), 'open')",
		"RequiredLogin"=>"Boolean",
		"ForumViewers" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers', 'Anyone')",
		"ForumPosters" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers, NoOne', 'Anyone')",
		"ForumViewersGroup" => "Int",
		"ForumPostersGroup" => "Int",

		"CanAttachFiles" => "Boolean",

		"ForumRefreshOn" => "Boolean",
		"ForumRefreshTime" => "Int"
	);

	static $has_one = array(
		"Moderator" => "Member",
		"Group" => "Group",
		"Category" => "ForumCategory"
	);
	
	static $many_many = array(
		'Moderators' => 'Member'
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

		if(!$forumGroup = DataObject::get_one("Group", "\"Group\".\"Code\" = 'forum-members'")) {
			$group = new Group();
			$group->Code = 'forum-members';
			$group->Title = "Forum Members";
			$group->write();

			Permission::grant( $group->ID, $code );
			if(method_exists('DB', 'alteration_message')) DB::alteration_message(_t('Forum.GROUPCREATED','Forum Members group created'),"created"); 
		}
		else if(DB::query(
			"SELECT * FROM \"Permission\" WHERE \"GroupID\" = '$forumGroup->ID' AND \"Code\" LIKE '$code'")
				->numRecords() == 0 ) {
			Permission::grant($forumGroup->ID, $code);
		}
		if(!$category = DataObject::get_one("ForumCategory")) {
			$category = new ForumCategory();
			$category->Title = _t('Forum.DEFAULTCATEGORY', 'General');
			$category->write();
		}
		if(!DataObject::get_one("ForumHolder")) {
			$forumholder = new ForumHolder();
			$forumholder->Title = "Forums";
			$forumholder->URLSegment = "forums";
			$forumholder->Content = "<p>"._t('Forum.WELCOMEFORUMHOLDER','Welcome to SilverStripe Forum Module! This is the default ForumHolder page. You can now add forums.')."</p>";
			$forumholder->Status = "Published";
			$forumholder->write();
			$forumholder->publish("Stage", "Live");
			if(method_exists('DB', 'alteration_message')) DB::alteration_message(_t('Forum.FORUMHOLDERCREATED','ForumHolder page created'),"created");
			$forum = new Forum();
			$forum->Title = _t('Forum.TITLE','General Discussion');
			$forum->URLSegment = "general-discussion";
			$forum->ParentID = $forumholder->ID;
			$forum->Content = "<p>"._t('Forum.WELCOMEFORUM','Welcome to SilverStripe Forum Module! This is the default Forum page. You can now add topics.')."</p>";
			$forum->Status = "Published";
			$forum->CategoryID = $category->ID;
			$forum->write();
			$forum->publish("Stage", "Live");

			if(method_exists('DB', 'alteration_message')) DB::alteration_message(_t('Forum.FORUMCREATED','Forum page created'),"created");
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
	
		$fields->addFieldToTab("Root.Access", new HeaderField(_t('Forum.ACCESSREAD','Who can read the forum?'), 2));
		$fields->addFieldToTab("Root.Access",
			new OptionsetField("ForumViewers", "", array(
				"Anyone" => _t('Forum.READANYONE','Anyone'),
				"LoggedInUsers" => _t('Forum.READLOGGEDIN','Logged-in users'),
				"OnlyTheseUsers" => _t('Forum.READLIST','Only these people (choose from list)'))
			)
		);
		$fields->addFieldToTab("Root.Access", new DropdownField("ForumViewersGroup", "Group", Group::map()));
		$fields->addFieldToTab("Root.Access", new HeaderField(_t('Forum.ACCESSPOST','Who can post to the forum?'), 2));
		$fields->addFieldToTab("Root.Access", new OptionsetField("ForumPosters", "", array(
		  	"Anyone" => _t('Forum.READANYONE'),
		  	"LoggedInUsers" => _t('Forum.READLOGGEDIN'),
		  	"OnlyTheseUsers" => _t('Forum.READLIST'),
			"NoOne" => _t('Forum.READNOONE', 'Nobody. Make Forum Read Only')
		)));
		$fields->addFieldToTab("Root.Access", new DropdownField("ForumPostersGroup", "Group", Group::map()));
		// TODO Abstract this to the Permission class
		$fields->addFieldToTab("Root.Access", new OptionsetField("CanAttachFiles", _t('Forum.ACCESSATTACH','Can users attach files?'), array(
			"1" => _t('Forum.YES','Yes'),
			"0" => _t('Forum.NO','No')
		)));

		$fields->addFieldToTab("Root.Behaviour", new CheckboxField("ForumRefreshOn", _t('Forum.REFRESHFORUM','Refresh this forum')));
		$refreshTime = new NumericField("ForumRefreshTime", _t('Forum.REFRECHTIME','Refresh every '));
		$refreshTime->setRightTitle(_t('Forum.SECONDS',' seconds'));
		$fields->addFieldToTab("Root.Behaviour", $refreshTime);

		$fields->addFieldToTab("Root.Category",
			new HasOneCTFWithDefaults(
				$this,
				'Category',
				'ForumCategory',
				array(
					'Title' => 'Title'
				),
				'getCMSFields_forPopup',
				"ForumHolderID={$this->ParentID}",
				null,
				null,
				array("ForumHolderID" => $this->ParentID)
			)
		);
/*
		$fields->addFieldToTab("Root.Category",
			new HasOneComplexTableField(
				$this,
				'Category',
				'ForumCategory',
				array(
					'Title' => 'Title'
				),
				'getCMSFields_forPopup',
				"ForumHolderID={$this->ParentID}"
			)
		);
*/		
		// TagField comes in it's own module.
		// If it's installed, use it to select moderators for this forum
		if(class_exists('TagField')) {
			$fields->addFieldToTab(
				'Root.Content.Main',
				new TagField(
					'Moderators',
					_t('MODERATORS', 'Moderators for this forum'),
					null,
					'Forum',
					'Nickname'
				),
				'Content'
			);
		}

		return $fields;
	}

	/**
	 * Return true if user is an "admin" of this forum or is a moderator.
	 * The user can either have ADMIN permissions {@link Permission} or be
	 * a moderator of this forum (their member ID is in the Moderators
	 * many many relation ID list).
	 * 
	 * @see ForumRole->isModeratingForum()
	 * 
	 * @return boolean
	 */
	function isAdmin() {
		if(!Member::currentUserID()) return false;
		$member = Member::currentUser();
		
		$isModerator = $member->isModeratingForum($this);
		$isAdmin = $member->isAdmin();
		
		return ($isAdmin || $isModerator) ? true : false;
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
	public function Breadcrumbs($maxDepth = null,$unlinked = false, $stopAtPageType = false,$showHidden = false) {
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

		return implode(" &raquo; ", array_reverse(array_merge($nonPageParts,$parts)));
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

		if($id == null) {
			$params = Controller::curr()->getURLParams();
			
			$id = (isset($params)) ? $params['ID'] : false;
		}

		return (is_numeric($id)) ? DataObject::get_by_id("Post", $id) : false;
	}


	/**
	 * Get the latest posting of the forum
	 *
	 * @return Post Returns the latest posting or nothing on no posts.
	 * @todo This is causing some errors, temporarily added is_numeric.
	 */
	function LatestPost() {
		if(is_numeric($this->ID)) {
			$posts = DataObject::get("Post", "ForumID = $this->ID", "Created DESC", "", 1);
			if($posts)
				return $posts->First();
		}
	}


	/**
	 * Get the number of total topics (threads) in this Forum
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
	 * @return DataObjectSet
	 */
	function Topics() {
		if(Member::currentUser()==$this->Moderator() && is_numeric($this->ID)) {
			$statusFilter = "(`Post`.Status IN ('Moderated', 'Awaiting')";
		} else {
			$statusFilter = "`Post`.Status = 'Moderated'";
		}
		
		if(isset($_GET['start']) && is_numeric($_GET['start'])) $limit = Convert::raw2sql($_GET['start']) . ", 30";
		else $limit = 30;

		return DataObject::get("Post", "`Post`.ForumID = $this->ID AND `Post`.ParentID = 0 AND `Post`.IsGlobalSticky = 0 AND `Post`.IsSticky = 0 AND $statusFilter", "max(PostList.Created) DESC",
			"INNER JOIN `Post` AS PostList ON PostList.TopicID = `Post`.TopicID", $limit
		);
	}
	
	/**
	 * Return the Sticky Threads
	 * @return DataObjectSet
	 */
	function StickyTopics() {
		$standard = DataObject::get("Post", "`Post`.ForumID = $this->ID AND `Post`.ParentID = 0 AND `Post`.IsSticky = 1", "max(PostList.Created) DESC",
			"INNER JOIN `Post` AS PostList ON PostList.TopicID = `Post`.TopicID"
		);
		// We have to join posts through their forums to their holders, and then restrict the holders to just the parent of this forum.
		$global = DataObject::get("Post", "`Post`.ParentID = 0 AND `Post`.IsGlobalSticky = 1 AND ForumHolderPage.ID='{$this->ParentID}'", "max(PostList.Created) DESC",
			"INNER JOIN `Post` AS PostList ON PostList.TopicID = `Post`.TopicID INNER JOIN " . ForumHolder::baseForumTable() . " ForumPage on `Post`.ForumID=ForumPage.ID
			INNER JOIN SiteTree_Live ForumHolderPage on ForumPage.ParentID=ForumHolderPage.ID"
		);
		if($global) {
			$global->merge($standard);
			return $global;
		}
		return $standard;
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

	/**
	 * Checks to see if the currently logged in user has a certain permissions
	 * for this forum
	 *
	 * @param string type Permission to check
	 * @return bool Returns TRUE if the user has the permission, otherwise
	 *              FALSE.
	 */
	function CheckForumPermissions($type = "view") {
		$member = Member::currentUser();
		
		switch($type) {
			// Check posting permissions
			case "starttopic":
				if($this->ForumPosters == "Anyone" || ($this->ForumPosters == "LoggedInUsers" && $member) 
					|| ($this->ForumPosters == "OnlyTheseUsers" && $member && $member->inGroup($this->ForumPostersGroup))) {
						// now check post can write
							return true;
				} else {
					return false;
				}
			break;
			case "post":
				if($this->ForumPosters == "Anyone" || ($this->ForumPosters == "LoggedInUsers" && $member) 
					|| ($this->ForumPosters == "OnlyTheseUsers" && $member && $member->inGroup($this->ForumPostersGroup))) {
						// now check post can write

						if($this->Post() && (!$this->Post()->IsReadOnly || $member->isAdmin()))	{
							return true;
						}
						else {
							return false;
						}
					}
			
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
							Member::currentUser()->inGroup($this->ForumViewersGroup)))
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
	 * Current Post
	 */
	private $currentPost;

	/**
	 * Return a list of all top-level topics in this forum
	 */
	function init() {
		//if($this->action == 'rss') Security::use_base_auth_for_regular_login();
		parent::init();
		if(Director::redirected_to()) return;
		
 	  	if(!$this->CheckForumPermissions("view")) {
 		  	$messageSet = array(
				'default' => _t('Forum.LOGINDEFAULT','Enter your email address and password to view this forum.'),
				'alreadyLoggedIn' => _t('Forum.LOGINALREADY','I\'m sorry, but you can\'t access this forum until you\'ve logged in.  If you want to log in as someone else, do so below'),
				'logInAgain' => _t('Forum.LOGINAGAIN','You have been logged out of the forums.  If you would like to log in again, enter a username and password below.')
			);

			Security::permissionFailure($this, $messageSet);
			return;
 		}

 		// Delete any posts that don't have a Title set (This cleans up posts
		// created by the ReplyForm method that aren't saved)
 		$this->deleteUntitledPosts();

 		// Log this visit to the ForumMember if they exist
 		$member = Member::currentUser();
 		if($member) {
 			$member->LastViewed = date("Y-m-d H:i:s");
 			$member->write();
 		}
		
		Requirements::javascript("forum/javascript/jquery.js"); 
		Requirements::javascript("forum/javascript/forum.js");
		Requirements::javascript("forum/javascript/jquery.MultiFile.js");

		Requirements::themedCSS('Forum');

		RSSFeed::linkToFeed($this->Link("rss"), sprintf(_t('Forum.RSSFORUM',"Posts to the '%s' forum"),$this->Title)); 
	 	RSSFeed::linkToFeed($this->Parent->Link("rss"), _t('Forum.RSSFORUMS','Posts to all forums'));
	 	
	 	// Icky hack to set this page ShowInCategories so we can determine if we need to show in category mode or not.
	 	$holderPage = $this->Parent;
		if($holderPage) $this->ShowInCategories = $holderPage->ShowInCategories;
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
		Session::set('Security.Message.message', _t('Forum.CREDENTIALS','Please enter your credentials to access the forum.'));
		Session::set('Security.Message.type', 'status');
		Session::set("BackURL", $this->Link());
		Director::redirect('Security/login');
	}


	/**
	 * Deletes any post where `Title` IS NULL and `Content` IS NULL -
	 * these will be posts that have been created by the method
	 * but not modified by the postAMessage method.
	 *
	 * Has a time limit - posts can exist in this state for 24 hours
	 * before they are deleted - this is so anybody uploading attachments
	 * has time to do so.
	 */
	function deleteUntitledPosts() {
		$datetime = method_exists(DB::getConn(), 'datetimeIntervalClause') ? DB::getConn()->datetimeIntervalClause('now', '-24 Hours') : 'NOW() - INTERVAL 24 HOUR';
		DB::query("DELETE FROM Post WHERE `Title` IS NULL AND `Content` IS NULL AND `Created` < " . $datetime);
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
	  	$forum = DataObject::get_by_id("Forum", $this->ID);
		return $forum->CheckForumPermissions($type);
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
	 * Subscribe to thread link
	 * 
	 * @return String
	 */
	function SubscribeLink() {
		if(Post_Subscription::already_subscribed($this->urlParams['ID'])) {
			return true;
		}
		return false;
	}
	
	/**
	 * Subscribe a user to a thread given by an ID.
	 * 
	 * Designed to be called via AJAX so return true / false
	 * @return Boolean | Redirection for non AJAX requests
	 */
	function subscribe() {
		if(Member::currentUser() && !Post_Subscription::already_subscribed($this->urlParams['ID'])) {
			$obj = new Post_Subscription;
			$obj->TopicID = $this->urlParams['ID'];
			$obj->MemberID = Member::currentUserID();
			$obj->LastSent = date("Y-m-d H:i:s"); // The user was last notified right now
			$obj->write();
			if($this->isAjax()) return true;
			return Director::redirectBack();
		}
		return false;
	}
	
	/**
	 * Unsubscribe a user from a thread by an ID
	 *
	 * Designed to be called via AJAX so return true / false
	 * @return Boolean | Redirection for non AJAX requests
	 */
	function unsubscribe() {
		$loggedIn = Member::currentUserID() ? true : false;
		if(!$loggedIn) Security::permissionFailure($this, _t('LOGINTOUNSUBSCRIBE', 'To unsubscribe from that thread, please log in first.'));
		
		if(Member::currentUser() && Post_Subscription::already_subscribed($this->urlParams['ID'])) {
			$SQL_memberID = Member::currentUserID();
			$topicID = (int) $this->urlParams['ID'];
			DB::query("DELETE FROM Post_Subscription WHERE `TopicID` = '$topicID' AND `MemberID` = '$SQL_memberID'");
			if($this->isAjax()) return true;
			return Director::redirectBack();
		}
		
		return false;
	}
	
	/**
	 * Mark a post as spam. Requires the use of the {@link spamprotection}
	 * module. Called via ajax / url form the post view.
	 * 
	 * Must be logged in and have the correct permissions to do mark
	 * @return bool
	 */
	function markasspam() {
		if(class_exists('SpamProtectorManager')) {
			if($this->isAdmin()) {
				// Get the current post if we haven't found it yet
			  	if(!$this->currentPost) {
					$this->currentPost = $this->Post($this->urlParams['ID']);
			    	if(!$this->currentPost) {
						return false;
					}
				}

		    	// Delete the post in question
	      		if($this->currentPost) {
					SpamProtectorManager::send_feedback($this->currentPost, 'spam');
				 	$this->deletepost();
					return true;
				}
			}
		}
		return false;
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
	function Posts($order = "ASC") {
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
		return DataObject::get("Post", "TopicID = '$SQL_id'", "Created $order" , "", (int)$_GET['start'] . ", $numPerPage");
	}


	/**
	 * Return recent posts in this forum or topic
	 *
	 * @param int $topicID ID of the relevant topic (set to NULL for all topics)
	 * @param int $limit Max. number of posts to return
	 * @param int $lastVisit Optional: Unix timestamp of the last visit (GMT)
	 * @param int $lastPostID Optional: ID of the last read post
	 * @return DataObjectSet Returns the posts.
	 */
	function RecentPosts($topicID = null, $limit = null, $lastVisit = null, $lastPostID = null) {
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

		return DataObject::get("Post", "ForumID = '$this->ID' $filter", "Created DESC", "", $limit);
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
	public function NewPostsAvailable($lastVisit, $lastPostID,$topicID = null, array &$data = null) {
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
	function ReplyForm($addMode = false) {
		// Check forum posting permissions
	
		// Check if we're adding a new post instead of replying
		if($addMode == true) {
			if(!$this->CheckForumPermissions("starttopic")) {
				$messageSet = array(
				'default' => _t('Forum.LOGINTOPOST','You\'ll need to login before you can post to that forum. Please do so below.'),
				'alreadyLoggedIn' => _t('Forum.LOGINTOPOSTLOGGEDIN','I\'m sorry, but you can\'t post to this forum until you\'ve logged in.  If you want to log in as someone else, do so below. If you\'re logged in and you still can\'t post, you don\'t have the correct permissions to post.'),
				'logInAgain' => _t('Forum.LOGINTOPOSTAGAIN','You have been logged out of the forums.  If you would like to log in again to post, enter a username and password below.'),
				);
	
				Security::permissionFailure($this, $messageSet);
				return;
			}
		} else {
			if(!$this->CheckForumPermissions("post")) {
				$messageSet = array(
				'default' => _t('Forum.LOGINTOPOST','You\'ll need to login before you can post to that forum. Please do so below.'),
				'alreadyLoggedIn' => _t('Forum.LOGINTOPOSTLOGGEDIN','I\'m sorry, but you can\'t post to this forum until you\'ve logged in.  If you want to log in as someone else, do so below. If you\'re logged in and you still can\'t post, you don\'t have the correct permissions to post.'),
				'logInAgain' => _t('Forum.LOGINTOPOSTAGAIN','You have been logged out of the forums.  If you would like to log in again to post, enter a username and password below.'),
				);
	
				Security::permissionFailure($this, $messageSet);
				return;
			}
		}

		if(!$this->currentPost) $this->currentPost = $this->Post($this->urlParams['ID']);
		
		if($this->currentPost && ($this->currentPost->IsReadOnly == true && !$this->isAdmin())) {
			Session::set('ForumAdminMsg', 'Sorry this Thread is Read only');
			return Director::redirect($this->URLSegment.'/');
		}
		
		// See if this user has already subscribed
		if($this->currentPost) $subscribed = Post_Subscription::already_subscribed($this->currentPost->TopicID);
		else $subscribed = false;

		$fields = new FieldSet(
			new TextField("Title", "Title", $this->currentPost ? "Re: " . $this->currentPost->Title : "" ),
			new TextareaField("Content", "Content"),
			new LiteralField("BBCodeHelper", "<div class=\"BBCodeHint\">[ <a href=\"#BBTagsHolder\" id=\"BBCodeHint\">" . _t('Forum.BBCODEHINT','View Formatting Help') . "</a> ]</div>"),
			new CheckboxField("TopicSubscription", _t('Forum.SUBSCRIBETOPIC','Subscribe to this topic (Receive email notifications when a new reply is added)'), $subscribed),
			new HiddenField("Parent", "", $this->currentPost ? $this->currentPost->ID : "" )
		);

		// Check if we can attach files to this forum's posts

		if($this->canAttach()) {
			$fileUploadField = new FileField("Attachment", "Attach File");
			$fileUploadField->setAllowedMaxFileSize(1000000);
			$fields->push(
				$fileUploadField
			);
		}

		$actions = 	new FieldSet(
			new FormAction("postAMessage", "Post")
		);

		$required = new RequiredFields("Title", "Content");
		$replyform = new Form($this, "ReplyForm", $fields, $actions, $required);
	
		$currentID = $this->currentPost	? $this->currentPost->ID : "";

		if(Session::get("forumInfo.{$currentID}.postvar") != null) {
			$_REQUEST = Session::get("forumInfo.{$currentID}.postvar");
			Session::clear("forumInfo.{$currentID}.postvar");
		}

		$replyform->loadDataFrom($_REQUEST);

		// Optional spam protection
		if(class_exists('SpamProtectorManager') && ForumHolder::$use_spamprotection_on_posts) {
			$protecter = SpamProtectorManager::update_form($replyform);
			$protecter->setFieldMapping('Title', 'Content');
		}

		return $replyform;
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
	
	function getForbiddenWords() {
		$words = DataObject::get_one("ForumHolder")->ForbiddenWords;
		return $words;
	}
	
	/**
	* This function filters $content by forbidden words, entered in forum holder.
	*
	* @param String $content (it can be Post Content or Post Title)
	* @return String $content (filtered string)
	**/
	function filterLanguage( $content ) {
		$words = $this->getForbiddenWords();
		if($words != ""){
			$words = explode(",",$words);
			foreach($words as $word){
				$content = str_ireplace(trim($word),"*",$content);
			}
		}
		return $content;
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
		
		$data["Content"] = $this->filterLanguage($data["Content"]);
		$data["Title"] = $this->filterLanguage($data["Title"]);
		$member = Member::currentUser();
		$parent = null;
		if($data['Parent']) $parent = DataObject::get_by_id('Post',	Convert::raw2sql($data['Parent']));
		
		// check they have correct posting rights
		if($parent && ($parent->IsReadOnly == true && !$this->isAdmin())) {
			Session::set('ForumAdminMsg', 'Sorry this Thread is Read only');
			return Director::redirect($this->URLSegment.'/');
		} 
		// Use an existing post, otherwise create a new one
		if(!empty($data['PostID'])) {
			$post = DataObject::get_by_id('Post', Convert::raw2sql($data['PostID']));
		} else {
			$post = new Post();
		}

		if(isset($parent)) {
			$currentID = $parent->ID;
		} else {
			$currentID = 0;
		}

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

		$post->TopicID = isset($parent) ? $parent->TopicID : '';

		if($member) $post->AuthorID = $member->ID;
		$post->ForumID = $this->ID;
		$post->write();
		
		// Upload and Save all files attached to the field
		if(!empty($data['Attachment'])) {
			
			// Attachment will always be blank, If they had an image it will be at least in Attachment-0
			$id = 0;
			while(isset($data['Attachment-' . $id])) {
				$image = $data['Attachment-' . $id];
				
				if($image) {
					// check to see if a file of same exists
					$title = Convert::raw2sql($image['name']);
					$file = DataObject::get_one("Post_Attachment", "`File`.Title = '$title' AND `Post_Attachment`.PostID = '$post->ID'");
					if(!$file) {
						$file = new Post_Attachment();
						$file->PostID = $post->ID;
						$file->OwnerID = Member::currentUserID();
						
						$upload = new Upload();
						$upload->loadIntoFile($image, $file);
						
						$file->write();
					}
				}
				
				$id++;
			}
		}
		
		
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
		
		Director::redirect($this->Link() . 'show/' . $post->TopicID .'?showPost=' . $post->ID);
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
	 * Show will get the selected post
	 * @return Array array of options.
	 */
	function show() {
		RSSFeed::linkToFeed($this->Link("rss") . '/' . $this->urlParams['ID'],sprintf(_t('Forum.POSTTOTOPIC',"Posts to the '%s' topic"),$this->Post()->Title));

		$SQL_id = Convert::raw2sql($this->urlParams['ID']);
		$title = Convert::raw2xml($this->Title);
		if(is_numeric($SQL_id)) {
			$topic = DataObject::get_by_id("Post", $SQL_id);
			if($topic) {
				$topic->incNumViews();
				$title = Convert::raw2xml($topic->Title) . ' &raquo; ' . $title;// Set the Forum Thread Title.
			}
		}
		return array(	
			'Title' => $title
		);
	}
	
	/**
	 * Get the RSS feed
	 *
	 * This method outputs the RSS feed to the browser. If the URL parameter
	 * "ID" is set it will output only posts for that topic ID.
	 */
	function rss() {
		HTTP::set_cache_age(3600); // cache for one hour

		$data = array('last_created' => null, 'last_id' => null);

    	if(!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
			 !isset($_SERVER['HTTP_IF_NONE_MATCH'])) {

			// just to get the version data..
			$this->NewPostsAvailable(null, null, $this->urlParams['ID'], $data);

      		// No information provided by the client, just return the last posts
			$rss = new RSSFeed($this->RecentPosts($this->urlParams['ID'], 30),
												 $this->Link() . 'rss',
												 sprintf(_t('Forum.RSSFORUMPOSTSTO',"Forum posts to '%s'"),$this->Title), "", "Title",
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
				$rss = new RSSFeed($this->RecentPosts($this->urlParams['ID'], 50, null, $etag),
													 $this->Link() . 'rss',
													 sprintf(_t('Forum.RSSFORUMPOSTSTO'),$this->Title), "", "Title",
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
			'Subtitle' => _t('Forum.NEWTOPIC','Start a new topic'),
			'Abstract' => DataObject::get_one("ForumHolder")->ForumAbstract
		);
	}


	/**
	 * Get the forum title
	 *
	 * @return string Returns the forum title
	 */
	function getHolderSubtitle() {
		return $this->Title;
	}


	/**
	 * Get the forum holders' abstract. Must cast it to HTMLText to keep links and 
	 * formatting in tack
	 *
	 * @return string Returns the holders' abstract
	 * @see ForumHolder::getAbstract()
	 */
	function getHolderAbstract() {
		$abstract = DataObject::get_one("ForumHolder")->HolderAbstract;
		$output = new HTMLText('Abstract');
		$output->setValue($abstract);
		return $output;
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
		return $this->Parent()->Forums();
/*	 	$categories = DataObject::get("ForumCategory", "ForumHolderID='{$this->ParentID}'");
		if($this->ShowInCategories) {
			// If there are no categories, we just don't display any.
			if (!$categories) return new DataObjectSet();
			foreach($categories as $category) {
				$category->CategoryForums = DataObject::get("Forum", "CategoryID = '$category->ID'");
			}
			return $categories;
		}
		return DataObject::get("Forum", "ParentID='{$this->ID}'");*/
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
			if($forums) return $forums->First();
		}

	}
	/**
	 * Delete an Attachment 
	 * Called from the EditPost method. Its Done via Ajax
	 *
	 * @return boolean
	 */
	function deleteAttachment() {

		// check we were passed an id and member is logged in
		if(!$this->urlParams['ID'] || !Member::currentUser()) return false;
		
		// try and get the file
		$file = DataObject::get_by_id("Post_Attachment", (int) $this->urlParams['ID']);
	
		// woops no file with that ID
		if(!$file) return false;
		
		// check permissions
		if(!$this->isAdmin() && $file->OwnerID != Member::currentUserID()) return false;
	
		// Ok we are good
		$file->delete();
		
		if(!Director::is_ajax()) return Director::redirectBack(); // if Javascript is disabled 
		
		return true; 
	}

	/**
	 * Edit post action
	 *
	 * @return array Returns an array to render the edit post page
	 */
	function editpost() {
		return array(
			'Subtitle' => _t('Forum.EDITPOST','Edit a post')
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

		// @TODO - This is nasty. Sort of goes against the whole MVC thing doesnt it? 
		// Generate a List of all the attachments rather then use the multifile uploader which
		// doesn't like setting defaults
		
		$Attachments = "";
		if($this->currentPost && $attachmentList = $this->currentPost->Attachments()) {
			$Attachments = "<div id=\"CurrentAttachments\"><h4>Current Attachments</h4><ul>";
			foreach($attachmentList as $attachment) {
				$Attachments .= "<li class='attachment-$attachment->ID'>$attachment->Name [<a href='$this->URLSegment/deleteAttachment/$attachment->ID' rel='$attachment->ID' class='deleteAttachment'>". _t('Forum.REMOVE','remove') ."</a>]</li>";
			}
			$Attachments .= "<ul></div>";
		}
		
		$fields = new FieldSet(
			new TextField("Title", "Title", ($this->currentPost) ? $this->currentPost->Title : "" ),
			new TextareaField("Content", "Content", 5, 40, ($this->currentPost) ? $this->currentPost->Content : "" ),
			new LiteralField("BBCodeHelper", "<div class=\"BBCodeHint\">[ <a href=\"#BBTagsHolder\" id=\"BBCodeHint\">" . _t('Forum.BBCODEHINT') . "</a> ]</div>"),
			new CheckboxField("TopicSubscription", _t('Forum.SUBSCRIBETOPIC'), $subscribed),
			new LiteralField("CurrentAttachments", $Attachments),
			new HiddenField("ID", "ID", ($this->currentPost) ? $this->currentPost->ID: "" )
		);
		
		if($this->canAttach()) {
			$fileUploadField = new FileField("Attachment", "Attach File");
			$fileUploadField->setAllowedMaxFileSize(1000000);
			$fields->insertBefore($fileUploadField, 'CurrentAttachments');
		}
		
		
	  	return new Form($this, "EditPostForm",
			$fields,
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
			    	"Content" => "<p class=\"message bad\">" . _t('Forum.POSTNOTFOUND','The current post couldn\'t be found in the database. Please go back to the thread you were editing and try to edit the post again. If this error persists, please email the administrator.') . "</p>"
			  	);
			}
		}

		// User authentication
	  	if(Member::currentUser() && ($this->isAdmin() || Member::currentUser()->ID == $this->currentPost->AuthorID)) {
			return $this->EditPostForm();
	  	} else {
	    	return _t('Forum.WRONGPERMISSION','You don\'t have the correct permissions to edit this post.');
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
			    "Content" => "<p class=\"message bad\">" . _t('Forum.POSTNOTFOUND') . "</p>"
			  );
			}
		}

		// User authentication
		if(Member::currentUser() && ($this->isAdmin() || Member::currentUser()->ID == $this->currentPost->AuthorID)) {
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

			// Do upload of the new files
			// Upload and Save all files attached to the field
			if(isset($data['Attachment']) && $data['Attachment']) {
				
				// Attachment will always be blank, If they had an image it will be at least in Attachment-0
				$id = 0;
				while(isset($data['Attachment-'.$id])) {
					$image = $data['Attachment-'.$id];
					if($image) {
						// check to see if a file of same exists
						$title = Convert::raw2sql($image['name']);
						$file = DataObject::get_one("Post_Attachment", "`File`.Title = '$title' AND `Post_Attachment`.PostID = '$post->ID'");
						if(!$file) {
							$file = new Post_Attachment();
							$file->PostID = $post->ID;
							$file->OwnerID = Member::currentUserID();
							
							$upload = new Upload();
							$upload->loadIntoFile($image, $file);
						
							$file->write();
						}
					}
					$id++;
				}
			}
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
			$this->flushCache();
			Director::redirect($this->Link().'show/'.$this->currentPost->TopicID.'?showPost='. $this->currentPost->ID . '#post' . $this->currentPost->ID .'&flush=1');
	  } else {
	    $messageSet = array(
				'default' => _t('Forum.LOGINTOEDIT','Enter your email address and password to edit this post.'),
				'alreadyLoggedIn' => _t('Forum.LOGINTOEDITLOGGEDIN','I\'m sorry, but you can\'t edit this post until you\'ve logged in.  You need to be either an administrator or the author of the post in order to edit it.'),
				'logInAgain' => _t('Forum.LOGINAGAIN'),
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
		if($this->isAdmin()) {
			// Get the current post if we haven't found it yet
		  	if(!$this->currentPost) {
				$this->currentPost = $this->Post($this->urlParams['ID']);
		    	if(!$this->currentPost) {
					return false;
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
	    		$dependentPosts = DataObject::get("Post","`Post`.`TopicID` = '" .Convert::raw2sql($this->currentPost->OldID) . "'");
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
		    	if (Director::is_ajax()) return 'window.location="' . $this->currentPost->Forum()->Link() . '";';
				Director::redirect($this->urlParams['URLSegment'] . "/show/" .$this->currentPost->TopicID . "/");
		    	return true;
		  	} 
			return true;
	  	}
		return false;
	}


	/**
	 * Get the latest members
	 *
	 * @param int $limit Number of members to return
	 */
	function LatestMember($limit = 1) {
		return $this->Parent()->LatestMember($limit);
	}

	/**
	 * Get a list of currently online users (last 15 minutes)
	 */
	function CurrentlyOnline() {
		return $this->Parent()->CurrentlyOnline();
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

	function ForumHolderURLSegment() {
		trigger_error('Forum::ForumHolderURLSegment is deprecated. Please use Forum::ForumHolderLink() instead which works with nested URLs.', E_USER_WARNING);
		return $this->ForumHolderLink();
	}

	/**
	 * Get the forum holder's URL segment
	 */
	function ForumHolderLink() {
		return DataObject::get_by_id("ForumHolder", $this->ParentID)->Link();
	}
	
	/**
	 * Returns the Forum Message from Session. This
	 * is used for things like Moving thread messages
	 * @return String
	 */
	function ForumAdminMsg() {
		$message = Session::get('ForumAdminMsg');
		Session::clear('ForumAdminMsg');
		return $message;
	}
	
	
	/** 
	 * Forum Admin Features form. 
	 * Handles the dropdown to select the new forum category and the checkbox for stickyness
	 *
	 * @return Form
	 */
	function AdminFormFeatures() {
		$id = Convert::raw2sql($this->urlParams['ID']);
		
		// Check to see if sticky
		$checkedSticky = false;
		$checkedGlobalSticky = false;
		$checkedReadOnly = false;

		if($posts = $this->Posts()) {
			$checkedSticky = ($posts->First()->IsSticky) ? true : false;
			$checkedGlobalSticky = ($posts->First()->IsGlobalSticky) ? true : false;
			$checkedReadOnly = ($posts->First()->IsReadOnly) ? true : false;
		}
		
		// Default Fields
		$fields = new FieldSet(
			new CheckboxField('IsSticky', _t('Forum.ISSTICKYTHREAD','Is this a Sticky Thread?'), $checkedSticky),
			new CheckboxField('IsGlobalSticky', _t('Forum.ISGLOBALSTICKY','Is this a Global Sticky (shown on all forums)'), $checkedGlobalSticky),
			new CheckboxField('IsReadOnly', _t('Forum.ISREADONLYTHREAD','Is this a Read only Thread?'), $checkedReadOnly),
			new HiddenField("Topic", "Topic",$id)
		);
		
		// Move Thread Dropdown
		$forums = DataObject::get("Forum", "`Forum`.ID != '$this->ID' and ParentID='{$this->ParentID}'");
		if($forums) {
			$fields->push(new DropdownField("NewForum", "Change Thread Forum", $forums->toDropDownMap('ID', 'Title', 'Select New Category:')), '', null, 'Select New Location:');
		}

		// Save Actions
		$actions = new FieldSet(
			new FormAction('doAdminFormFeatures', 'Save')
		);
		return new Form($this, 'AdminFormFeatures', $fields, $actions);
	}
	
	/** 
	 * Process's the moving of a given topic. Has to check for admin privledges,
	 * passed an old topic id (post id in URL) and a new topic id
	 */
	function doAdminFormFeatures($data, $form) {
		// check we are admin before we process anything
		if(!$this->CheckForumPermissions('admin')) return false;
		
		// Get the Object from the Topic ID 
		$oldTopic = Convert::raw2sql($data['Topic']);

		// get all posts in that topic
		$newForum = isset($data['NewForum']) ? Convert::raw2sql($data['NewForum']) : null;
		$posts = DataObject::get("Post", "`Post`.TopicID = '$oldTopic'");
		
		if(!$posts) return user_error("No Posts Found", E_USER_ERROR);
		// update all the posts under that topic to the sticky status and / or the 
		// new thread location
		
		foreach($posts as $post) {
			if($newForum > 0) {
				$post->ForumID = $newForum;
			}
			$post->IsReadOnly = (isset($data['IsReadOnly']) && $data['IsReadOnly']) ? true : false;
			$post->IsSticky = (isset($data['IsSticky']) && $data['IsSticky']) ? true : false;
			$post->IsGlobalSticky = (isset($data['IsGlobalSticky']) && $data['IsGlobalSticky']) ? true : false;
			$post->write();
		}

		Session::set('ForumAdminMsg','Thread Settings Have Been Updated');
		
		return Director::redirect($this->URLSegment.'/');
	}
}

?>
