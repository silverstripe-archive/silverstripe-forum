<?php

/**
 * ForumHolder represents the top forum overview page. Its children
 * should be Forums. On this page you can also edit your global settings
 * for the entire forum.
 * 
 * @package forum
 */

class ForumHolder extends Page {

	static $db = array (
		"HolderSubtitle" => "Varchar(200)",
		"ProfileSubtitle" => "Varchar(200)",
		"ForumSubtitle" => "Varchar(200)",
		"HolderAbstract" => "HTMLText", 
		"ProfileAbstract" => "HTMLText", 
		"ForumAbstract" => "HTMLText", 
		"ProfileModify" => "HTMLText", 
		"ProfileAdd" => "HTMLText",
		"DisplaySignatures" => "Boolean",
		"ShowInCategories" => "Boolean",
		"AllowGravatars" => "Boolean",
		"ForbiddenWords" => "Text"	
	);
	
	static $has_one = array(
	);

	static $has_many = array(
		"Categories" => "ForumCategory"
	);

	static $allowed_children = array('Forum');
	
	static $defaults = array(
		"HolderSubtitle" => "Welcome to our forum!",
		"ProfileSubtitle" => "Edit Your Profile",
		"ForumSubtitle" => "Start a new topic",
		"HolderAbstract" => "<p>If this is your first visit, you will need to <a class=\"broken\" title=\"Click here to register\" href=\"ForumMemberProfile/register\">register</a> before you can post. However, you can browse all messages below.</p>",
		"ProfileAbstract" => "<p>Please fill out the fields below. You can choose whether some are publically visible by using the checkbox for each one.</p>",
		"ForumAbstract" => "<p>From here you can start a new topic.</p>",
		"ProfileModify" => "<p>Thanks, your member profile has been modified.</p>",
		"ProfileAdd" => "<p>Thanks, you are now signed up to the forum.</p>",
	);
	
	/**
	 * If the user has spam protection enabled and setup then we can provide spam
	 * prevention for the forum. This stores whether we actually want the registration
	 * form to have such protection
	 * 
	 * @var bool
	 */
	public static $use_spamprotection_on_register = true;
	
	/**
	 * If the user has spam protection enabled and setup then we can provide spam
	 * prevention for the forum. This stores whether we actually want the posting 
	 * form (adding, replying) to have such protection
	 * 
	 * @var bool
	 */
	public static $use_spamprotection_on_posts = false;
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->addFieldsToTab("Root.Content.Messages", array(
			new TextField("HolderSubtitle","Forum Holder Subtitle"),
			new HTMLEditorField("HolderAbstract","Forum Holder Abstract"),
			new TextField("ProfileSubtitle","Member Profile Subtitle"),
			new HTMLEditorField("ProfileAbstract","Member Profile Abstract"),
			new TextField("ForumSubtitle","Create topic Subtitle"),
			new HTMLEditorField("ForumAbstract","Create topic Abstract"),
			new HTMLEditorField("ProfileModify","Create message after modifing forum member"),
			new HTMLEditorField("ProfileAdd","Create message after adding forum member")
		));
		$fields->addFieldsToTab("Root.Content.Settings", array(
			new CheckboxField("DisplaySignatures", "Display Member Signatures?"),
			new CheckboxField("ShowInCategories", "Show Forums In Categories?"),
			new CheckboxField("AllowGravatars", "Allow <a href='http://www.gravatar.com/' target='_blank'>Gravatars</a>?")
		));
		$fields->addFieldsToTab("Root.Content.LanguageFilter", array(
			new TextField("ForbiddenWords", "Forbidden words (comma separated)"),
			new LiteralField("FWLabel","These words will be replaced by an asterisk")
		));
		return $fields;
	}
	
	/**
	 * Ensure that any categories that exist with no forum holder are updated to be owned by the first forum holder
	 * if there is one. This is required now that multiple forum holds are allowed, and categories belong to holders.
	 *
	 * @see sapphire/core/model/DataObject#requireDefaultRecords()
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if (!$cats = DataObject::get("ForumCategory", '"ForumCategory"."ForumHolderID" = 0')) return;

		if (!$holder = DataObject::get_one("ForumHolder")) return;

		foreach ($cats as $c) {
			$c->ForumHolderID = $holder->ID;
			$c->write();
		}
	}
	
	/**
	 * If we're on the search action, we need to at least show
	 * a breadcrumb to get back to the ForumHolder page.
	 * @return string
	 */
	function Breadcrumbs() {
		$params = Controller::curr()->getURLParams();

		if(isset($params['Action'])) {
			switch($params['Action']) {
				case 'search':
					return "<a href=\"{$this->Link()}\">{$this->Title}</a> &raquo; " . _t('SEARCHBREADCRUMB', 'Search');
					break;
				case 'memberlist':
					return "<a href=\"{$this->Link()}\">{$this->Title}</a> &raquo; " . _t('MEMBERLIST', 'Member List');
					break;
				case 'popularthreads':
					return "<a href=\"{$this->Link()}\">{$this->Title}</a> &raquo; " . _t('MOSTPOPULARTHREADS', 'Most popular threads');
					break;
			}
		}
	}
	
	/**
	 * Get a list of currently online users (last 15 minutes)
	 * that belong to the "forum-members" code {@link Group}.
	 * 
	 * @return DataObjectSet of {@link Member} objects
	 */
	function CurrentlyOnline() {
		$forumGroupID = (int) DataObject::get_one('Group', "Code = 'forum-members'")->ID;
		$adminGroupID = (int) DataObject::get_one('Group', "(Code = 'administrators' OR Code = 'Administrators')")->ID;
		
		if(method_exists(DB::getConn(), 'datetimeIntervalClause')) {
			$timeconstrain = 'LastVisited > ' . DB::getConn()->datetimeIntervalClause('now', '-15 MINUTE');
		} else {
			$timeconstrain = "LastVisited > NOW() - INTERVAL 15 MINUTE";
		}
		return DataObject::get(
			'Member',
			$timeconstrain . " AND (GroupID = '$forumGroupID' OR GroupID = '$adminGroupID')",
			'FirstName, Surname',
			'LEFT JOIN Group_Members ON Member.ID = Group_Members.MemberID'
		);
	}
	
	/**
	 * Get the latest members
	 *
	 * @param int $limit Number of members to return
	 */
	function LatestMember($limit = 1) {
		$forumGroupID = (int) DataObject::get_one('Group', "Code = 'forum-members'")->ID;
		$adminGroupID = (int) DataObject::get_one('Group', "(Code = 'administrators' OR Code = 'Administrators')")->ID;
		
		return DataObject::get("Member", "(GroupID = '$forumGroupID' OR GroupID = '$adminGroupID')", "`Member`.`ID` DESC", "LEFT JOIN Group_Members ON Member.ID = Group_Members.MemberID", $limit);
	}

	/**
	 * Get the forums. Actually its a bit more complex than that
	 * we need to group by the Forum Categories.
	 */
	function Forums() {
	 	$categories = DataObject::get("ForumCategory", "ForumHolderID={$this->ID}");	
		if($this->ShowInCategories) {
			if (!$categories) return new DataObjectSet();
			foreach($categories as $category) {
				$category->CategoryForums = DataObject::get("Forum", "CategoryID = '$category->ID' AND ParentID = '$this->ID'");
			}
			return $categories;
		}
		return DataObject::get("Forum", "ParentID = '$this->ID'");
	}

	/**
	 * A function that returns the correct base table to use for custom forum queries. It uses the getVar stage to determine
	 * what stage we are looking at, and determines whether to use SiteTree or SiteTree_Live (the general case). If the stage is
	 * not specified, live is assumed (general case). It is a static function so it can be used for both ForumHolder and Forum.
	 */
	static function baseForumTable() {
		$stage = Controller::curr()->getRequest()->getVar('stage');
		if (!$stage) $stage = Versioned::get_live_stage();
		return $stage == "Stage" ? "SiteTree" : "SiteTree_Live";
	}
}


class ForumHolder_Controller extends Page_Controller {

	/**
	 * Initialise the controller
	 */
	function init() {
		Requirements::themedCSS('Forum');
		Requirements::javascript("forum/javascript/jquery.js");
		Requirements::javascript("forum/javascript/jquery.MultiFile.js");
		Requirements::javascript("forum/javascript/forum.js");
		RSSFeed::linkToFeed($this->Link("rss"), "Posts to all forums");
		parent::init();
	}
	
	/** 
	 * Generate a complete list of all the members data. Return a 
	 * set of all these members sorted by a GET variable
	 *  
	 * @todo Sort via AJAX
	 * @return DataObjectSet A DataObjectSet of all the members which are signed up
	 */
	function memberlist() {
		$forumGroupID = (int) DataObject::get_one('Group', "Code = 'forum-members'")->ID;
		
		// If sort has been defined then save it as in the session
		$order = (isset($_GET['order'])) ? $_GET['order']: "";
		
		if(!isset($_GET['start']) || !is_numeric($_GET['start']) || (int) $_GET['start'] < 1) {
			$_GET['start'] = 0;
		}
		
		$SQL_start = (int) $_GET['start'];

		switch($order) {
			case "joined":
				$members = DataObject::get("Member", "GroupID = '$forumGroupID'", "`Member`.Created ASC", "LEFT JOIN Group_Members ON Member.ID = Group_Members.MemberID", "{$SQL_start},100");
			break;
			case "name":
				$members = DataObject::get("Member", "GroupID = '$forumGroupID'", "`Member`.Nickname ASC", "LEFT JOIN Group_Members ON Member.ID = Group_Members.MemberID", "{$SQL_start},100");
			break;
			case "country":
				$members = DataObject::get("Member", "GroupID = '$forumGroupID' AND `Member`.CountryPublic = TRUE", "`Member`.Country ASC", "LEFT JOIN Group_Members ON Member.ID = Group_Members.MemberID", "{$SQL_start},100");
			break;
			case "posts": 
				$query = singleton('Member')->extendedSQL('', "NumPosts DESC", "{$SQL_start},100");
				$query->select[] = "(SELECT COUNT(*) FROM `Post` WHERE `Post`.AuthorID = `Member`.ID) AS NumPosts";
				$records = $query->execute();
				$members = singleton('Member')->buildDataObjectSet($records, 'DataObjectSet', $query, 'Member');
				$members->parseQueryLimit($query);
			break;
			default:
				$members = DataObject::get("Member", "GroupID = '$forumGroupID'", "`Member`.Created DESC", "LEFT JOIN Group_Members ON Member.ID = Group_Members.MemberID", "{$SQL_start},100");
			break;
		}
		
		return array(
			'Subtitle' => _t('ForumHolder.MEMBERLIST', 'Forum member List'),
			'Abstract' => $this->MemberListAbstract,
			'Members' => $members,
			'Title' => _t('ForumHolder.MEMBERLIST', 'Forum member List')
		);
	}
	
	/**
	 * Show the 20 most popular threads.
	 * 
	 * Two configuration options are available:
	 * 1. "posts" - most popular threads by posts
	 * 2. "views" - most popular threads by views
	 * 
	 * e.g. mysite.com/forums/popularthreads?by=posts
	 *
	 * @return array
	 */
	function popularthreads() {
		$start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
		$method = isset($_GET['by']) ? $_GET['by'] : null;
		if(!$method) $method = 'posts';
		
		if($method == 'posts') {
			$threadRecords = DB::query("
				SELECT Post.*, (SELECT COUNT(*) FROM Post AS P WHERE Post.ID = P.TopicID) AS PostCount
				FROM Post JOIN SiteTree_Live ForumPage on Post.ForumID = ForumPage.ID
				WHERE TopicID = Post.ID AND ForumPage.ParentID='{$this->ID}'
				ORDER BY PostCount DESC
				LIMIT $start,20
			");
//			$threadRecords = DB::query("
//				SELECT *, (SELECT COUNT(*) FROM Post AS P WHERE Post.ID = P.TopicID) AS PostCount
//				FROM Post
//				WHERE TopicID = Post.ID
//				ORDER BY PostCount DESC
//				LIMIT $start,20
//			");
			
			$allThreadsCount = DB::query("
				SELECT count(*) as theCount
				FROM Post JOIN " . ForumHolder::baseForumTable() . " ForumPage on Post.ForumID=ForumPage.ID
				WHERE TopicID = Post.ID AND ForumPage.ParentID='{$this->ID}'")->value();
//			$allThreadsCount = DB::query('SELECT * FROM Post WHERE TopicID = Post.ID')->numRecords();
			$threads = singleton('Post')->buildDataObjectSet($threadRecords);
			if($threads) $threads->setPageLimits($start, '20', $allThreadsCount);
			
		} elseif($method == 'views') {
			$threads = DataObject::get('Post', '', 'NumViews DESC', '', "$start,20");
		}
		
		return array(
			'Title' => _t('ForumHolder.POPULARTHREADS', 'Most popular forum threads'),
			'Subtitle' => _t('ForumHolder.POPULARTHREADS', 'Most popular forum threads'),
			'Method' => $method,
			'Threads' => $threads
		);
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
		Session::set('Security.Message.message',_t('Forum.CREDENTIALS'));
		Session::set('Security.Message.type', 'status');
		Session::set("BackURL", $this->Link());
		Director::redirect('Security/login');
	}
	
	/**
	 * Get the number of total posts
	 *
	 * @return int Returns the number of posts
	 */
	function TotalPosts() {
		return DB::query("SELECT COUNT(*) FROM Post JOIN " . ForumHolder::baseForumTable() . " ForumPage ON Post.ForumID=ForumPage.ID WHERE Post.Content != 'NULL' and ForumPage.ParentID='{$this->ID}'")->value(); 
//		return DB::query("SELECT COUNT(*) FROM Post WHERE Content != 'NULL'")->value(); 
	}


	/**
	 * Get the number of total topics (threads)
	 *
	 * @return int Returns the number of topics (threads)
	 */
	function TotalTopics() {
		return DB::query("SELECT COUNT(*) FROM Post JOIN " . ForumHolder::baseForumTable() . " ForumPage ON Post.ForumID=ForumPage.ID WHERE Post.ParentID = 0 AND Post.Content != 'NULL' and ForumPage.ParentID='{$this->ID}'")->value(); 
//		return DB::query("SELECT COUNT(*) FROM Post WHERE ParentID = 0 AND Content != 'NULL'")->value(); 
	}


	/**
	 * Get the number of distinct authors
	 *
	 * @return int Returns the number of distinct authors
	 */
	function TotalAuthors() {
		return DB::query("SELECT COUNT(DISTINCT Post.AuthorID) FROM Post JOIN " . ForumHolder::baseForumTable() . " ForumPage ON Post.ForumID=ForumPage.ID and ForumPage.ParentID='{$this->ID}'")->value();
//		return DB::query("SELECT COUNT(DISTINCT AuthorID) FROM Post")->value();
	}
	
	/**
	 * The search action
	 *
	 * @return array Returns an array to render the search results.
	 */
	function search() {
		$keywords = (isset($_REQUEST['Search'])) ? $_REQUEST['Search'] : null;
		$order = Convert::raw2xml((isset($_REQUEST['order'])) ? $_REQUEST['order'] : null);
		
		$Abstract = !empty($_REQUEST['Search'])
			? "<p>" . sprintf(_t('ForumHolder.SEARCHEDFOR',"You searched for '%s'."),Convert::raw2xml($keywords)) . "</p>"
			: null;
		if(isset($_REQUEST['rss'])) {
			$rss = new RSSFeed($this->SearchResults(), $this->Link(), _t('ForumHolder.SEARCHRESULTS','Search results'), "", "Title", "RSSContent", "RSSAuthor");
			return $rss->outputToBrowser();	
		}
		$rssLink = $this->Link() ."search/?Search=".urlencode($keywords). "&order=".urlencode($order)."&rss";
		RSSFeed::linkToFeed($rssLink, _t('ForumHolder.SEARCHRESULTS','Search results'));
		return array(
			"Subtitle" => DBField::create('Text', _t('ForumHolder.SEARCHRESULTS','Search results')),
			"Abstract" => DBField::create('HTMLText', $Abstract),
			"Query" => DBField::create('Text', $keywords),
			"Order" => DBField::create('Text', ($order) ? $order : "relevance"),
			"RSSLink" => DBField::create('HTMLText', $rssLink)
		);
	}
	
	/**
	 * Returns the search results.
	 * 
	 * Perform a specific DB query in order to get relevant search
	 * results, this means we can sort by relevancy score, thanks
	 * to MySQL FULLTEXT searches.
	 * 
	 * @todo Specific to MySQL at this point.
	 * 
	 * @return DataObjectSet
	 */
	function SearchResults() {
		$searchQuery = Convert::raw2sql($_REQUEST['Search']);

		// Search for authors
		$SQL_queryParts = split(' +', trim($searchQuery));
		foreach($SQL_queryParts as $SQL_queryPart ) { 
			$SQL_clauses[] = "FirstName LIKE '%$SQL_queryPart%' OR Surname LIKE '%$SQL_queryPart' OR Nickname LIKE '%$SQL_queryPart'";
		}

		$potentialAuthors = DataObject::get('Member', implode(" OR ", $SQL_clauses), 'ID ASC');
		$SQL_authorClause = '';
		$SQL_potentialAuthorIDs = array();
		
		if($potentialAuthors) {
			foreach($potentialAuthors as $potentialAuthor) {
				$SQL_potentialAuthorIDs[] = $potentialAuthor->ID;
			}
			$SQL_authorList = implode(", ", $SQL_potentialAuthorIDs);
			$SQL_authorClause = "OR Post.AuthorID IN ($SQL_authorList)";
		}
		// Work out what sorting method
		$sort = "RelevancyScore DESC";
		if(isset($_GET['order'])) {
			switch($_GET['order']) {
				case 'date':
					$sort = "Post.Created DESC";
					break;
				case 'title':
					$sort = "Post.Title ASC";
					break;
			}
		}

		// Perform the search
		if(!empty($_GET['start'])) $limit = (int) $_GET['start'];
		else $limit = $_GET['start'] = 0;

		$queryString = "SELECT Post.ID, Post.Created, Post.LastEdited, Post.ClassName, Post.Title, Post.Content, Post.TopicID, Post.AuthorID, Post.ForumID,
				MATCH (Post.Title, Post.Content) AGAINST ('$searchQuery') AS RelevancyScore
			FROM Post JOIN " . ForumHolder::baseForumTable() . " ForumPage on Post.ForumID=ForumPage.ID
			WHERE
				MATCH (Post.Title, Post.Content) AGAINST ('$searchQuery' IN BOOLEAN MODE)
				$SQL_authorClause
				AND ForumPage.ParentID='{$this->ID}' 
			GROUP BY Post.TopicID
			ORDER BY $sort";
//		$queryString = "SELECT ID, Created, LastEdited, ClassName, Title, Content, TopicID, AuthorID, ForumID,
//				MATCH (Title, Content) AGAINST ('$searchQuery') AS RelevancyScore
//			FROM Post
//			WHERE MATCH (Title, Content) AGAINST ('$searchQuery' IN BOOLEAN MODE) $SQL_authorClause 
//			GROUP BY TopicID
//			ORDER BY $sort";
		
		// Get the 10 posts from the starting record
		$query = DB::query("
			$queryString
			LIMIT $limit, 10
		");
		
		// Find out how many posts that match with no limit
		$allPosts = DB::query($queryString);
		$allPostsCount = $allPosts ? $allPosts->numRecords() : 0;
		
		$baseClass = new Post();
		$postsSet = $baseClass->buildDataObjectSet($query);
		if($postsSet) {
			$postsSet->setPageLimits($limit, 10, $allPostsCount);
		}
		return $postsSet ? $postsSet: new DataObjectSet();
	}

	/**
	 * Get the RSS feed
	 *
	 * This method will output the RSS feed with the last 10 posts to the
	 * browser.
	 */
	function rss() {
		HTTP::set_cache_age(3600); // cache for one hour

		$data = array('last_created' => null, 'last_id' => null);

    	if(!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
			!isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			// just to get the version data..
			$this->NewPostsAvailable(null, null, $data);
      		// No information provided by the client, just return the last posts
			$rss = new RSSFeed($this->RecentPosts(50), $this->Link() . 'rss',
												 sprintf(_t('Forum.RSSFORUMPOSTSTO'),$this->Title), "", "Title",
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
			if($this->NewPostsAvailable($since, $etag, $data)) {
				HTTP::register_modification_timestamp($data['last_created']);
				$rss = new RSSFeed($this->RecentPosts(50, null, $etag),
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
		exit;
	}


	/**
	 * Get the last posts
	 *
	 * @param int $limit Number of posts to return
	 * @param int $lastVisit Optional: Unix timestamp of the last visit (GMT)
	 * @param int $lastPostID Optional: ID of the last read post
	 */
	function RecentPosts($limit = null, $lastVisit = null, $lastPostID = null) {
		$filter = "TopicID > 0";   
		
		if($lastVisit)
			$lastVisit = @date('Y-m-d H:i:s', $lastVisit);

		$lastPostID = (int)$lastPostID;
		if($lastPostID > 0)
			$filter .= " AND ID > $lastPostID";

		if($lastVisit) {
			$filter .= " AND Created > '$lastVisit'";
		}

		$filter .= " AND ForumPage.ParentID='{$this->ID}'";
		return DataObject::get("Post", $filter, "Created DESC", "JOIN " . ForumHolder::baseForumTable() . " ForumPage on Post.ForumID=ForumPage.ID", $limit);
//		return DataObject::get("Post", $filter, "Created DESC", "", $limit);
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
	public function NewPostsAvailable($lastVisit, $lastPostID,array &$data = null) {
		$version = DB::query("
			SELECT max(Post.ID) as LastID, max(Post.Created) as LastCreated 
			FROM Post 
			JOIN " . ForumHolder::baseForumTable() . " as ForumPage on Post.ForumID=ForumPage.ID 
			WHERE ForumPage.ParentID={$this->ID}")->first();
		
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
	 * Get the URL segment
	 * 
	 * @TODO Why this is explicitly defined?
	 */
	function URLSegment() {
		return $this->URLSegment;
	}
	
	function GlobalAnnouncements() {
		$announcement = DataObject::get("Post", "`Post`.ParentID = 0 AND `Post`.IsGlobalSticky = 1 AND ForumPage.ParentID={$this->ID}", "max(PostList.Created) DESC",
			"INNER JOIN `Post` AS PostList ON PostList.TopicID = `Post`.TopicID INNER JOIN " . ForumHolder::baseForumTable() . " ForumPage on `Post`.ForumID=ForumPage.ID");
		return $announcement;
	}
}

?>
