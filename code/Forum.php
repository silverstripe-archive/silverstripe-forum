<?php

/**
 * Forum represents a collection of forum threads. Each thread is a different topic on
 * the site. You can customize permissions on a per forum basis in the CMS.
 *
 * @todo Implement PermissionProvider for editing, creating forums.
 * 
 * @package forum
 */

class Forum extends Page {

	static $allowed_children = 'none';

	static $icon = "forum/images/treeicons/user";

	static $db = array(
		"Abstract" => "Text",
		"ForumViewers" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers', 'Anyone')",
		"ForumPosters" => "Enum('Anyone, LoggedInUsers, OnlyTheseUsers, NoOne', 'LoggedInUsers')",
		"CanAttachFiles" => "Boolean",
	);

	static $has_one = array(
		"Moderator" => "Member",
		"Category" => "ForumCategory",
		"ForumViewersGroup" => "Group",
		"ForumPostersGroup" => "Group",
	);
	
	static $many_many = array(
		'Moderators' => 'Member'
	);

	static $defaults = array(
		"ForumViewers" => "Anyone",
		"ForumPosters" => "LoggedInUsers"
	);

	/**
	 * Number of posts to include in the thread view before pagination takes effect.
	 *
	 * @var int
	 */
	static $posts_per_page = 8;

	/**
	 * Can this user view the thread.
	 *
	 * @return bool
	 */
	function canView() {
		if($this->ForumViewers == "Anyone" || $this->isAdmin()) return true;
		
		$member = Member::currentUser();
		
		if($member) {
			if($this->ForumViewers == "LoggedInUsers" || ($this->ForumViewers == "OnlyTheseUsers" && $member->inGroup($this->ForumViewersGroupID))) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Can the user edit this thread - The settings and configuration in the thread.
	 * Not the individual posts. Individual posts is controlled by canCreate
	 *
	 * @return bool
	 */
	function canEdit() {
		return $this->isAdmin();
	}
	
	/**
	 * Can the user post threads to this forum
	 *
	 * @return bool
	 */
	function canPost() {	
		if($this->ForumPosters == "Anyone" || $this->isAdmin()) return true;
		
		$member = Member::currentUser();

		if($member) {
			if($this->ForumPosters == "LoggedInUsers" || ($this->ForumPosters == "OnlyTheseUsers" && $member->inGroup($this->ForumPostersGroupID))) {
				return true;
			}
		}
		
		return false;
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
			DB::alteration_message(_t('Forum.GROUPCREATED','Forum Members group created'),"created"); 
		}
		else if(DB::query("SELECT * FROM \"Permission\" WHERE \"GroupID\" = '$forumGroup->ID' AND \"Code\" LIKE '$code'")->numRecords() == 0 ) {
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
			DB::alteration_message(_t('Forum.FORUMHOLDERCREATED','ForumHolder page created'),"created");
			$forum = new Forum();
			$forum->Title = _t('Forum.TITLE','General Discussion');
			$forum->URLSegment = "general-discussion";
			$forum->ParentID = $forumholder->ID;
			$forum->Content = "<p>"._t('Forum.WELCOMEFORUM','Welcome to SilverStripe Forum Module! This is the default Forum page. You can now add topics.')."</p>";
			$forum->Status = "Published";
			$forum->CategoryID = $category->ID;
			$forum->write();
			$forum->publish("Stage", "Live");

			DB::alteration_message(_t('Forum.FORUMCREATED','Forum page created'),"created");
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
		$fields->addFieldToTab("Root.Access", new TreeDropdownField("ForumViewersGroupID", "Group"));
		$fields->addFieldToTab("Root.Access", new HeaderField(_t('Forum.ACCESSPOST','Who can post to the forum?'), 2));
		$fields->addFieldToTab("Root.Access", new OptionsetField("ForumPosters", "", array(
		  	"Anyone" => _t('Forum.READANYONE', 'Anyone'),
		  	"LoggedInUsers" => _t('Forum.READLOGGEDIN', 'Logged-in users'),
		  	"OnlyTheseUsers" => _t('Forum.READLIST', 'Only these people (choose from list)'),
			"NoOne" => _t('Forum.READNOONE', 'Nobody. Make Forum Read Only')
		)));
		$fields->addFieldToTab("Root.Access", new TreeDropdownField("ForumPostersGroupID", "Group"));

		$fields->addFieldToTab("Root.Access", new OptionsetField("CanAttachFiles", _t('Forum.ACCESSATTACH','Can users attach files?'), array(
			"1" => _t('Forum.YES','Yes'),
			"0" => _t('Forum.NO','No')
		)));

		$fields->addFieldToTab("Root.Category",
			new HasOneCTFWithDefaults(
				$this,
				'Category',
				'ForumCategory',
				array(
					'Title' => 'Title'
				),
				'getCMSFields_forPopup',
				"\"ForumHolderID\"={$this->ParentID}",
				null,
				null,
				array("ForumHolderID" => $this->ParentID)
			)
		);

		// TagField comes in it's own module.
		// If it's installed, use it to select moderators for this forum
		if(class_exists('TagField')) {
			$fields->addFieldToTab('Root.Content.Moderators',
				new TagField(
					'Moderators',
					_t('MODERATORS', 'Moderators for this forum'),
					null,
					'Forum',
					'Nickname'
				),
				'Content'
			);
		} else {
			$fields->addFieldToTab('Root.Content.Moderators', new LiteralField('ModeratorWarning', '<p>Please install the <a href="http://silverstripe.org/tag-field-module/" target="_blank">TagField module</a> to manage moderators for this forum.</p>'));
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

		return (Permission::check('ADMIN') || $isModerator) ? true : false;
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
			$topic = DataObject::get_by_id("ForumThread", $SQL_id);

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

			$page = $page->Parent;
		}

		return implode(" &raquo; ", array_reverse(array_merge($nonPageParts,$parts)));
	}
	
	/**
	 * Helper Method from the template includes. Uses $ForumHolder so in order for it work 
	 * it needs to be included on this page
	 *
	 * @return ForumHolder
	 */
	function getForumHolder() {
		return $this->Parent();
	}

	/**
	 * Get the latest posting of the forum. For performance the forum ID is stored on the
	 * {@link Post} object as well as the {@link Forum} object
	 * 
	 * @return Post
	 */
	function getLatestPost() {
		return DataObject::get_one('Post', "\"Post\".\"ForumID\" = '$this->ID'", true, "\"Post\".\"ID\" DESC");
	}

	/**
	 * Get the number of total topics (threads) in this Forum
	 *
	 * @return int Returns the number of topics (threads)
	 */
	function getNumTopics() {
		return (int)DB::query("
			SELECT count(\"ID\") 
			FROM \"ForumThread\" 
			WHERE \"ForumID\" = $this->ID")->value();
	}

	/**
	 * Get the number of total posts
	 *
	 * @return int Returns the number of posts
	 */
	function getNumPosts() {
		return (int)DB::query("
			SELECT COUNT(*) 
			FROM \"Post\" 
			WHERE \"Post\".\"ForumID\" = $this->ID")->value();
	}

	/**
	 * Get the number of distinct authors
	 *
	 * @return int Returns the number of distinct authors
	 */
	function getNumAuthors() {
		return DB::query("
			SELECT COUNT(DISTINCT \"AuthorID\") 
			FROM \"Post\" 
			WHERE \"Post\".\"ForumID\" = $this->ID")->value();
	}

	/**
	 * Returns the topics (first posting of each thread) for this forum
	 * @return DataObjectSet
	 */
	function getTopics() {
		if(Member::currentUser()==$this->Moderator() && is_numeric($this->ID)) {
			$statusFilter = "(\"PostList\".\"Status\" IN ('Moderated', 'Awaiting')";
		} else {
			$statusFilter = "\"PostList\".\"Status\" = 'Moderated'";
		}
		
		if(isset($_GET['start']) && is_numeric($_GET['start'])) $limit = Convert::raw2sql($_GET['start']) . ", 30";
		else $limit = 30;

		return DataObject::get(
			"ForumThread", 
			"\"ForumThread\".\"ForumID\" = $this->ID AND \"ForumThread\".\"IsGlobalSticky\" = 0 AND \"ForumThread\".\"IsSticky\" = 0 AND $statusFilter", 
			"max(\"PostList\".\"Created\") DESC, max(\"PostList\".\"ID\") DESC",
			"INNER JOIN \"Post\" AS \"PostList\" ON \"PostList\".\"ThreadID\" = \"ForumThread\".\"ID\"", 
			$limit
		);
	}
	
	/**
	 * Return the Sticky Threads
	 * @return DataObjectSet
	 */
	function getStickyTopics() {
		$standard = DataObject::get(
			"ForumThread", 
			"\"ForumThread\".\"ForumID\" = $this->ID AND \"ForumThread\".\"IsSticky\" = 1", 
			"MAX(\"PostList\".\"Created\") DESC",
			"INNER JOIN \"Post\" AS \"PostList\" ON \"PostList\".\"ThreadID\" = \"ForumThread\".\"ID\""
		);

		// We have to join posts through their forums to their holders, and then restrict the holders to just the parent of this forum.
		$global = DataObject::get(
			"ForumThread", 
			"\"ForumThread\".\"IsGlobalSticky\" = 1", 
			"MAX(\"PostList\".\"Created\") DESC",
			"INNER JOIN \"Post\" AS \"PostList\" ON \"PostList\".\"ThreadID\" = \"ForumThread\".\"ID\""
		);

		if($global) {
			$global->merge($standard);
			$global->sort('PostList.Created');
			
			return $global;
		}
		return $standard;
	}
}

/**
 * The forum controller class
 *
 * @package forum
 */
class Forum_Controller extends Page_Controller {

	static $allowed_actions = array(
		'AdminFormFeatures',
		'deleteattachment',
		'deletepost',
		'doAdminFormFeatures',
		'doPostMessageForm',
		'editpost',
		'markasspam',
		'PostMessageForm',
		'reply',
		'show',
		'starttopic',
		'subscribe',
		'unsubscribe'
	);
	
	
	function init() {
		parent::init();
		if(Director::redirected_to()) return;
		
 	  	if(!$this->canView()) {
 		  	$messageSet = array(
				'default' => _t('Forum.LOGINDEFAULT','Enter your email address and password to view this forum.'),
				'alreadyLoggedIn' => _t('Forum.LOGINALREADY','I\'m sorry, but you can\'t access this forum until you\'ve logged in.  If you want to log in as someone else, do so below'),
				'logInAgain' => _t('Forum.LOGINAGAIN','You have been logged out of the forums.  If you would like to log in again, enter a username and password below.')
			);

			Security::permissionFailure($this, $messageSet);
			return;
 		}
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

		RSSFeed::linkToFeed($this->Parent->Link("rss/$this->ID"), sprintf(_t('Forum.RSSFORUM',"Posts to the '%s' forum"),$this->Title)); 
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
		return $this->Parent()->OpenIDAvailable();
	}

	/**
	 * Subscribe a user to a thread given by an ID.
	 * 
	 * Designed to be called via AJAX so return true / false
	 *
	 * @return bool
	 */
	function subscribe() {
		if(Member::currentUser() && !ForumThread_Subscription::already_subscribed($this->urlParams['ID'])) {
			$obj = new ForumThread_Subscription();
			$obj->ThreadID = (int) $this->urlParams['ID'];
			$obj->MemberID = Member::currentUserID();
			$obj->LastSent = date("Y-m-d H:i:s"); 
			$obj->write();
			
			die('1');
		}
		
		return false;
	}
	
	/**
	 * Unsubscribe a user from a thread by an ID
	 *
	 * Designed to be called via AJAX so return true / false
	 *
	 * @return bool
	 */
	function unsubscribe() {
		$member = Member::currentUser();
		
		if(!$member) Security::permissionFailure($this, _t('LOGINTOUNSUBSCRIBE', 'To unsubscribe from that thread, please log in first.'));
		
		if(ForumThread_Subscription::already_subscribed($this->urlParams['ID'], $member->ID)) {

			DB::query("
				DELETE FROM \"ForumThread_Subscription\" 
				WHERE \"ThreadID\" = '". Convert::raw2sql($this->urlParams['ID']) ."' 
				AND \"MemberID\" = '$member->ID'");
			
			die('1');
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
				$post = DataObject::get_by_id('Post', $this->urlParams['Action']);
				
		    	// Delete the post in question
	      		if($post) {
					SpamProtectorManager::send_feedback($post, 'spam');
					
					$post->delete();
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Get posts to display. This method assumes an URL parameter "ID" which contains the thread ID.
	 *
	 * @return DataObjectSet Posts
	 */
	function Posts($order = "ASC") {
		$SQL_id = Convert::raw2sql($this->urlParams['ID']);
		
		$numPerPage = Forum::$posts_per_page;

		if(isset($_GET['showPost']) && !isset($_GET['start'])) {
			$allIDs = DB::query("SELECT \"ID\" FROM \"Post\" WHERE \"ThreadID\" = '$SQL_id' ORDER BY \"Created\"")->column();
			if($allIDs) {
				$foundPos = array_search($_GET['showPost'], $allIDs);
				$_GET['start'] = floor($foundPos / $numPerPage) * $numPerPage;
			}
		}

		if(!isset($_GET['start'])) $_GET['start'] = 0;

		return DataObject::get("Post", "\"ThreadID\" = '$SQL_id'", "\"Created\" $order" , "", (int)$_GET['start'] . ", $numPerPage");
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
	 * Section for dealing with reply / edit / create threads form
	 *
	 * @return Form Returns the post message form
	 */
	function PostMessageForm($addMode = false, $post = false) {
		
		$thread = false;
		
		if($post) $thread = $post->Thread();
		else if(isset($this->urlParams['ID'])) $thread = DataObject::get_by_id('ForumThread', $this->urlParams['ID']);	
		
		// Check to see that the user has create forum thread rights
		if(!$this->canPost()) {
			$messageSet = array(
				'default' => _t('Forum.LOGINTOPOST','You\'ll need to login before you can post to that forum. Please do so below.'),
				'alreadyLoggedIn' => _t('Forum.LOGINTOPOSTLOGGEDIN','I\'m sorry, but you can\'t post to this forum until you\'ve logged in.  If you want to log in as someone else, do so below. If you\'re logged in and you still can\'t post, you don\'t have the correct permissions to post.'),
				'logInAgain' => _t('Forum.LOGINTOPOSTAGAIN','You have been logged out of the forums.  If you would like to log in again to post, enter a username and password below.'),
			);
			
 			Security::permissionFailure($this, $messageSet);

			return false;
		}

		$fields = new FieldSet(
			($post && $post->isFirstPost() || !$thread) ? new TextField("Title", _t('Forum.FORUMTHREADTITLE', 'Title')) : new ReadonlyField('Title',  _t('Forum.FORUMTHREADTITLE', 'Title'), 'Re:'. $thread->Title),
			new TextareaField("Content", _t('Forum.FORUMREPLYCONTENT', 'Content')),
			new LiteralField("BBCodeHelper", "<div class=\"BBCodeHint\">[ <a href=\"#BBTagsHolder\" id=\"BBCodeHint\">" . _t('Forum.BBCODEHINT','View Formatting Help') . "</a> ]</div>"),
			new CheckboxField("TopicSubscription", 
				_t('Forum.SUBSCRIBETOPIC','Subscribe to this topic (Receive email notifications when a new reply is added)'), 
				($thread) ? $thread->getHasSubscribed() : false)
		);
		
		if($thread) $fields->push(new HiddenField('ThreadID', 'ThreadID', $thread->ID));
		if($post) $fields->push(new HiddenField('ID', 'ID', $post->ID));
		
		// Check if we can attach files to this forum's posts
		if($this->canAttach()) {
			$fields->push(new FileField("Attachment", _t('Forum.ATTACH', 'Attach file')));
		}
		
		// If this is an existing post check for current attachments and generate
		// a list of the uploaded attachments
		if($post && $attachmentList = $post->Attachments()) {
			if($attachmentList->exists()) {
				$attachments = "<div id=\"CurrentAttachments\"><h4>". _t('Forum.CURRENTATTACHMENTS', 'Current Attachments') ."</h4><ul>";
				$link = $this->Link();
				
				foreach($attachmentList as $attachment) {
					$attachments .= "<li class='attachment-$attachment->ID'>$attachment->Name [<a href='{$link}deleteattachment/$attachment->ID' rel='$attachment->ID' class='deleteAttachment'>". _t('Forum.REMOVE','remove') ."</a>]</li>";
				}
				$attachments .= "<ul></div>";
			
				$fields->push(new LiteralField('CurrentAttachments', $attachments));
			}
		}
		
		$actions = 	new FieldSet(
			new FormAction("doPostMessageForm", _t('Forum.REPLYFORMPOST', 'Post'))
		);

		$required = ($addMode) ? new RequiredFields("Title", "Content") : new RequiredFields("Content");
		
		$form = new Form($this, "PostMessageForm", $fields, $actions, $required);

		if($post) $form->loadDataFrom($post);
		
		return $form;
	}
	
	/**
	 * Wrapper for older templates. Previously the new, reply and edit forms were 3 separate
	 * forms, they have now been refactored into 1 form. But in order to not break existing 
	 * themes too much just include this.
	 *
	 * @deprecated 0.5 
	 * @return Form
	 */
	function ReplyForm() {
		user_error('Please Use $PostMessageForm in your template rather that $ReplyForm', E_USER_WARNING);
		
		return $this->PostMessageForm();
	}
	
	/**
	 * Post a message to the forum. This method is called whenever you want to make a
	 * new post or edit an existing post on the forum
	 *
	 * @param Array - Data
	 * @param Form - Submitted Form
	 */
	function doPostMessageForm($data, $form) {
		$member = Member::currentUser();
		$content = (isset($data['Content'])) ? $this->filterLanguage($data["Content"]) : "";
		$title = (isset($data['Title'])) ? $this->filterLanguage($data["Title"]) : false;

		// If a thread id is passed append the post to the thread. Otherwise create
		// a new thread
		$thread = false;
		
		if(isset($data['ThreadID'])) {
			$thread = DataObject::get_by_id('ForumThread', $data['ThreadID']);
		}
		if(!$thread) {
			$thread = new ForumThread();
			$thread->ForumID = $this->ID;
			if($title) $thread->Title = $title;
		}
		
		// check permissions. even if this is a reply we can check canCreate since if a thread
		// is readonly then they cannot reply to it.
		if(!$thread->canCreate()) {
			$messageSet = array(
				'default' => _t('Forum.LOGINTOPOST','You\'ll need to login before you can post to that forum. Please do so below.'),
				'alreadyLoggedIn' => _t('Forum.NOPOSTPERMISSION','I\'m sorry, but you do not have permission post to this forum.'),
				'logInAgain' => _t('Forum.LOGINTOPOSTAGAIN','You have been logged out of the forums.  If you would like to log in again to post, enter a username and password below.'),
			);

			Security::permissionFailure($this, $messageSet);
			
			return false;
		}

		// If this is a simple edit the post then handle it here. Look up the correct post,
		// make sure we have edit rights to it then update the post
		$post = false;
		
		if(isset($data['ID'])) {
			$post = DataObject::get_by_id('Post', $data['ID']);
			
			if($post && $post->isFirstPost()) {
				if($title) {
					$thread->Title = $title;
				}
			}
		}
		
		// from now on the user has the correct permissions. save the current thread settings
		$thread->write();
		
		if(!$post || !$post->canEdit()) {
			$post = new Post();
			$post->AuthorID = $member->ID;
			$post->ThreadID = $thread->ID;
		}
		
		$post->ForumID = $thread->ForumID;
		$post->Content = $content;
		$post->write();

		// Upload and Save all files attached to the field
		// Attachment will always be blank, If they had an image it will be at least in Attachment-0
		if(!empty($data['Attachment'])) {

			$id = 0;
			// 
			// @todo this only supports ajax uploads. Needs to change the key (to simply Attachment).
			//
			while(isset($data['Attachment-' . $id])) {
				$image = $data['Attachment-' . $id];
				
				if($image) {
					// check to see if a file of same exists
					$title = Convert::raw2sql($image['name']);
					$file = DataObject::get_one("Post_Attachment", "\"Title\" = '$title' AND \"PostID\" = '$post->ID'");
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
			if(!ForumThread_Subscription::already_subscribed($thread->ID)) {
				// Create a new topic subscription for this member
				$obj = new ForumThread_Subscription();
				$obj->ThreadID = $thread->ID;
				$obj->MemberID = Member::currentUserID();
				$obj->write();
			}
		} else {
			// See if the member wanted to remove themselves
			if(ForumThread_Subscription::already_subscribed($post->TopicID)) {
				DB::query("DELETE FROM \"ForumThread_Subscription\" WHERE \"ThreadID\" = '$post->ThreadID' AND \"MemberID\" = '$member->ID'");
			}
		}
		
		
		// Send any notifications that need to be sent
		ForumThread_Subscription::notify($post);
		
		return $this->redirect($post->Link());
	}
	
	/** 
	 * Return the Forbidden Words in this Forum
	 *
	 * @return Text
	 */
	function getForbiddenWords() {
		return $this->Parent()->ForbiddenWords;
	}
	
	/**
	* This function filters $content by forbidden words, entered in forum holder.
	*
	* @param String $content (it can be Post Content or Post Title)
	* @return String $content (filtered string)
	*/
	function filterLanguage($content) {
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
	 * Get the link for the reply action
	 *
	 * @return string URL for the reply action
	 */
	function ReplyLink() {
		return $this->Link() . "reply/" . $this->urlParams['ID'];
	}

	/**
	 * Show will get the selected thread to the user. Also increments
	 * the forums view count.
	 *
	 * @return Array
	 */
 	function show() {
		$title = Convert::raw2xml($this->Title);
		
		if($thread = $this->getForumThread()) {
			$thread->incNumViews();
			
			$posts = sprintf(_t('Forum.POSTTOTOPIC',"Posts to the '%s' topic"),$title);
			RSSFeed::linkToFeed($this->Link("rss") . '/' . $this->urlParams['ID'], $posts);
				
			$title = Convert::raw2xml($thread->Title) . ' &raquo; ' . $title;
		}
	
		return array(
			'Title' => DBField::create('HTMLText',$title)
		);
	}

	/**
	 * Start topic action
	 *
	 * @return array Returns an array to render the start topic page
	 */
	function starttopic() {
		return array(
			'Subtitle' => DBField::create('HTMLText', _t('Forum.NEWTOPIC','Start a new topic')),
			'Abstract' => DBField::create('HTMLText', DataObject::get_one("ForumHolder")->ForumAbstract)
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
	 * Get the currently viewed forum. Ensure that the user can access it
	 *
	 * @return ForumThread
	 */
	function getForumThread() {
		if(isset($this->urlParams['ID'])) {
			$SQL_id = Convert::raw2sql($this->urlParams['ID']);

			if(is_numeric($SQL_id)) {
				if($thread = DataObject::get_by_id('ForumThread', $SQL_id)) {
					if(!$thread->canView()) {
						Security::permissionFailure($this);
						
						return false;
					}
					
					return $thread;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Delete an Attachment 
	 * Called from the EditPost method. Its Done via Ajax
	 *
	 * @return boolean
	 */
	function deleteattachment() {
		
		// check we were passed an id and member is logged in
		if(!isset($this->urlParams['ID'])) return false;
		
		$file = DataObject::get_by_id("Post_Attachment", (int) $this->urlParams['ID']);
	
		if($file && $file->canDelete()) {
			$file->delete();
			
			return (!Director::is_ajax()) ? Director::redirectBack() : true;
		}
		
		return false;
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
	 * Get the post edit form if the user has the necessary permissions
	 *
	 * @return Form
	 */
	function EditForm() {
		$id = (isset($this->urlParams['ID'])) ? $this->urlParams['ID'] : null;
		$post = DataObject::get_by_id('Post', $id);

		return $this->PostMessageForm(false, $post);
	}
	

	/**
	 * Delete a post via the url.
	 *
	 * @return bool
	 */
	function deletepost() {
		if(isset($this->urlParams['ID'])) {
			if($post = DataObject::get_by_id('Post', (int) $this->urlParams['ID'])) {
				if($post->canEdit()) {
					// delete the whole thread if this is the first one
					if($post->isFirstPost()) {
						$thread = DataObject::get_by_id("ForumThread", $post->ThreadID);
						$thread->delete();
					}
					else {
						// delete the post
						$post->delete();
					}
				
					return true;
				}
			}
	  	}
	
		return false;
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
		$id = (isset($this->urlParams['ID'])) ? $this->urlParams['ID'] : false;
		
		$fields = new FieldSet(
			new CheckboxField('IsSticky', _t('Forum.ISSTICKYTHREAD','Is this a Sticky Thread?')),
			new CheckboxField('IsGlobalSticky', _t('Forum.ISGLOBALSTICKY','Is this a Global Sticky (shown on all forums)')),
			new CheckboxField('IsReadOnly', _t('Forum.ISREADONLYTHREAD','Is this a Read only Thread?')),
			new HiddenField("ID", "Thread")
		);
		
		$forums = DataObject::get("Forum");
	
		if($forums) {
			$fields->push(new DropdownField("ForumID", "Change Thread Forum", $forums->toDropDownMap('ID', 'Title', 'Select New Category:')), '', null, 'Select New Location:');
		}
	
		$actions = new FieldSet(
			new FormAction('doAdminFormFeatures', _t('Forum.SAVE', 'Save'))
		);
		
		$form = new Form($this, 'AdminFormFeatures', $fields, $actions);
		
		// need this id wrapper since the form method is called on save as 
		// well and needs to return a valid form object
		if($id) {
			$thread = DataObject::get_by_id('ForumThread', $id);
			
			if($thread && $thread->canEdit()) $form->loadDataFrom($thread);
		}

		return $form;
	}
	
	/** 
	 * Process's the moving of a given topic. Has to check for admin privledges,
	 * passed an old topic id (post id in URL) and a new topic id
	 */
	function doAdminFormFeatures($data, $form) {
		
		if(isset($data['ID'])) {
			$thread = DataObject::get_by_id('ForumThread', $data['ID']);

			if($thread && $thread->canEdit()) {
				$form->saveInto($thread);
				$thread->write();
			}
		}
		return Director::redirect($this->Link());
	}
}