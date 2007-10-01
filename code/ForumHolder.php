<?php

class ForumHolder extends Page {
	
	static $db = array (
		"HolderSubtitle" => "Varchar(200)",
		"ProfileSubtitle" => "Varchar(200)",
		"ForumSubtitle" => "Varchar(200)",
		"HolderAbstract" => "Text",
		"ProfileAbstract" => "Text",
		"ForumAbstract" => "Text",
		"ProfileModify" => "Text",
		"ProfileAdd" => "Text"
	);
	
	static $allowed_children = array('Forum');
	
	static $defaults = array(
		"HolderSubtitle" => "Welcome to our forum!",
		"ProfileSubtitle" => "Edit Your Profile",
		"ForumSubtitle" => "Start a new topic",
		"HolderAbstract" => "<p>If this is your first visit, you will need to <a class=\"broken\" title=\"Click here to register\" href=\"/ForumMemberProfile/register\">register</a> before you can post. However, you can browse all messages below.</p>",
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

class ForumHolder_Controller extends Page_Controller {
	function init() {
		Requirements::themedCSS('Forum');
		RSSFeed::linkToFeed($this->Link("rss"), "Posts to all forums");
		parent::init();	
	}

	function getSubtitle() {
		return $this->HolderSubtitle;
	}
	function getAbstract() {
		return $this->HolderAbstract;
	}
	
	function TotalPosts() {
		return DB::query("SELECT COUNT(*) FROM Post")->value();
	}
	function TotalTopics() {
		return DB::query("SELECT COUNT(*) FROM Post WHERE ParentID = 0")->value();
			}
	function TotalAuthors() {
		return DB::query("SELECT COUNT(DISTINCT AuthorID) FROM Post")->value();
	}

	function Forums() {
		return DataObject::get("Forum");
	}
	
	function CurrentlyOnline() {
		return DataObject::get("Member", "LastVisited > NOW() - INTERVAL 15 MINUTE", "FirstName, Surname",
			"INNER JOIN Group_Members ON Group_Members.GroupID IN (1,2,3) AND Group_Members.MemberID = Member.ID");
	}
	
	function search() {
		$XML_keywords = Convert::raw2xml($_REQUEST['for']);
		$Abstract = !empty($_REQUEST['for']) ? "<p>You searched for '".$XML_keywords."'.</p>" : null;
		return array(
			"Subtitle" => "Search results",
			"Abstract" => $Abstract
		);
	}
	
	
	function SearchResults() {
		$SQL_query = Convert::raw2sql($_REQUEST['for']);
		
		// Search for authors
		$SQL_queryParts = split(' +', trim($SQL_query));
		foreach($SQL_queryParts as $SQL_queryPart) {
			$SQL_clauses[] = "FirstName LIKE '%$SQL_queryPart%' OR Surname LIKE '%$SQL_queryPart'";
		}

		$potentialAuthors = DataObject::get("Member", implode(" OR ", $SQL_clauses));
		if($potentialAuthors) {
			foreach($potentialAuthors as $potentialAuthor) $SQL_potentialAuthorIDs[] = $potentialAuthor->ID;
			$SQL_authorList = implode(", ", $SQL_potentialAuthorIDs);
			$SQL_authorClause = "OR AuthorID IN ($SQL_authorList)";
		}

		// Perform the search
		return DataObject::get("Post", "MATCH (Title, Content) AGAINST ('$SQL_query') $SQL_authorClause", 
			"MATCH (Title, Content) AGAINST ('$SQL_query') DESC", "", (int)$_GET['start'] . ', 10');
	}

	function rss() {
		$rss = new RSSFeed($this->RecentPosts(10), $this->Link(), "Forum posts to '$this->Title'", "", "Title", "RSSContent", "RSSAuthor");
		$rss->outputToBrowser();
	}
	
	function RecentPosts($limit = null) {
		return DataObject::get("Post", "", "Created DESC", "", $limit);
	}
	
	function LatestMember($limit = null) {
		return DataObject::get("Member", "", "`Member`.`ID` DESC", "", 1);
	}
	
	function URLSegment() {
		return $this->URLSegment;
	}	
}

?>