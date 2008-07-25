<?php

class ForumHolder extends Page {

	static $db = array (
		"HolderSubtitle" => "Varchar(200)",
		"ProfileSubtitle" => "Varchar(200)",
		"ForumSubtitle" => "Varchar(200)",
		"HolderAbstract" => "HTMLText", 
		"ProfileAbstract" => "HTMLText", 
		"ForumAbstract" => "HTMLText", 
		"ProfileModify" => "HTMLText", 
		"ProfileAdd" => "HTMLText"
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
	
	function getCMSFields($cms) {
		$fields = parent::getCMSFields($cms);
		$fields->addFieldToTab("Root.Content.Messages", new TextField("HolderSubtitle","Forum Holder Subtitle"));
		$fields->addFieldToTab("Root.Content.Messages", new HTMLEditorField("HolderAbstract","Forum Holder Abstract"));
		$fields->addFieldToTab("Root.Content.Messages", new TextField("ProfileSubtitle","Member Profile Subtitle"));
		$fields->addFieldToTab("Root.Content.Messages", new HTMLEditorField("ProfileAbstract","Member Profile Abstract"));
		$fields->addFieldToTab("Root.Content.Messages", new TextField("ForumSubtitle","Create topic Subtitle"));
		$fields->addFieldToTab("Root.Content.Messages", new HTMLEditorField("ForumAbstract","Create topic Abstract"));
		$fields->addFieldToTab("Root.Content.Messages", new HTMLEditorField("ProfileModify","Create message after modifing forum member"));
		$fields->addFieldToTab("Root.Content.Messages", new HTMLEditorField("ProfileAdd","Create message after adding forum member"));
		return $fields;
	}

}


/**
 * ForumHolder Controller
 *
 */
class ForumHolder_Controller extends Page_Controller {

	/**
	 * Initialise the controller
	 */
	function init() {
		Requirements::themedCSS('Forum');

		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/behaviour.js");
		if($this->OpenIDAvailable())
			Requirements::javascript("forum/javascript/Forum_openid_description.js");

		RSSFeed::linkToFeed($this->Link("rss"), "Posts to all forums");
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
		Session::set('Security.Message.message',_t('Forum.CREDENTIALS'));
		Session::set('Security.Message.type', 'status');
		Session::set("BackURL", $this->Link());
		Director::redirect('Security/login');
	}


	/**
	 * Get the forum holders' subtitle
	 *
	 * @return string Returns the holders' subtitle
	 */
	function getSubtitle() {
		return $this->HolderSubtitle;
	}


	/**
	 * Get the forum holders' abstract
	 *
	 * @return string Returns the holders' abstract
	 */
	function getAbstract() {
		return $this->HolderAbstract;
	}


	/**
	 * Get the number of total posts
	 *
	 * @return int Returns the number of posts
	 */
	function TotalPosts() {
		return DB::query("SELECT COUNT(*) FROM Post WHERE Content != 'NULL'")->value(); 
	}


	/**
	 * Get the number of total topics (threads)
	 *
	 * @return int Returns the number of topics (threads)
	 */
	function TotalTopics() {
		return DB::query("SELECT COUNT(*) FROM Post WHERE ParentID = 0 AND Content != 'NULL'")->value(); 
	}


	/**
	 * Get the number of distinct authors
	 *
	 * @return int Returns the number of distinct authors
	 */
	function TotalAuthors() {
		return DB::query("SELECT COUNT(DISTINCT AuthorID) FROM Post")->value();
	}


	/**
	 * Get the forums
	 */
	function Forums() {
		return DataObject::get("Forum");
	}


	/**
	 * Get a list of currently online users (last 15 minutes)
	 */
	function CurrentlyOnline() {
		return DataObject::get("Member",
			"LastVisited > NOW() - INTERVAL 15 MINUTE",
			"FirstName, Surname",
			"");
	}


	/**
	 * The search action
	 *
	 * @return array Returns an array to render the search results.
	 */
	function search() {
		$XML_keywords = Convert::raw2xml($_REQUEST['for']);
		$Abstract = !empty($_REQUEST['for'])
			? "<p>" . sprintf(_t('ForumHolder.SEARCHEDFOR',"You searched for '%s'."),$XML_keywords) . "</p>"
			: null;

		return array("Subtitle" => _t('ForumHolder.SEARCHRESULTS','Search results'),
								 "Abstract" => $Abstract
		);
	}


	/**
	 * Returns the search results
	 */
	function SearchResults() {
		$SQL_query = Convert::raw2sql($_REQUEST['for']);

		// Search for authors
		$SQL_queryParts = split(' +', trim($SQL_query));
		foreach($SQL_queryParts as $SQL_queryPart) {
			$SQL_clauses[] = "FirstName LIKE '%$SQL_queryPart%' OR Surname LIKE '%$SQL_queryPart'";
		}

		$potentialAuthors = DataObject::get("Member", implode(" OR ", $SQL_clauses));
		$SQL_authorClause = '';
		if($potentialAuthors) {
			foreach($potentialAuthors as $potentialAuthor)
				$SQL_potentialAuthorIDs[] = $potentialAuthor->ID;
			$SQL_authorList = implode(", ", $SQL_potentialAuthorIDs);
			$SQL_authorClause = "OR AuthorID IN ($SQL_authorList)";
		}

		// Perform the search
		if(!isset($_GET['start']))
			$_GET['start'] = 0;

		return DataObject::get("Post",
			"MATCH (Title, Content) AGAINST ('$SQL_query') $SQL_authorClause",
			"MATCH (Title, Content) AGAINST ('$SQL_query') DESC",
			"",
			(int)$_GET['start'] . ', 10');
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
			$rss = new RSSFeed($this->RecentPosts(10), $this->Link(),
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
													 $this->Link(),
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
	function RecentPosts($limit = null, $lastVisit = null,
											 $lastPostID = null) {
		$filter = "TopicID > 0";   
		
		if($lastVisit)
			$lastVisit = @date('Y-m-d H:i:s', $lastVisit);

		$lastPostID = (int)$lastPostID;
		if($lastPostID > 0)
			$filter .= "ID > $lastPostID";

		if($lastVisit) {
			if($lastPostID > 0)
				$filter .= " AND ";

			$filter .= "Created > '$lastVisit'";
		}

		return DataObject::get("Post", $filter, "Created DESC", "", $limit);
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
																		array &$data = null) {
		$version = DB::query("SELECT max(ID) as LastID, max(Created) " .
			"as LastCreated FROM Post")->first();

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
	 * Get the latest members
	 *
	 * @param int $limit Number of members to return
	 */
	function LatestMember($limit = null) {
		$forumgroup = DataObject::get_one('Group', 'Code="forum-members"');
		if($forumgroup) {
			return $forumgroup->getManyManyComponents('Members', '', 'Created DESC', '', 1);
		}	
	}


	/**
	 * Get the URL segment
	 */
	function URLSegment() {
		return $this->URLSegment;
	}
}

?>
