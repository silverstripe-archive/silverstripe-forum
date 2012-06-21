<?php
/**
 * Forum Reports.
 * These are some basic reporting tools which sit in the CMS for the user to view.
 * No fancy graphing tools or anything just some simple querys and numbers
 * 
 * @package forum
 */

/**
 * Member Signups Report.
 * Lists the Number of people who have signed up in the past months categorized 
 * by month.
 */
class ForumReport_MemberSignups extends SS_Report {
	function title() {
		return _t('Forum.FORUMSIGNUPS',"Forum Signups by Month");
	}
	function records($params = array()) {
		$members = DB::query("
			SELECT DATE_FORMAT(\"Created\", '%Y %M') AS \"Month\", COUNT(\"Created\") AS \"NumberJoined\"
			FROM \"Member\"
			GROUP BY DATE_FORMAT(\"Created\", '%M %Y')
			ORDER BY \"Created\" DESC
		");
		$output = array();
		foreach($members->map() as $record => $value) {
			$output[$record] = $value;
		}
		return $output;
	
	}
	function fieldsToShow() {
	}
	function getHTML() {
		$result = "<ul class=\"$this->class\">\n";
		foreach($this->records() as $record => $value) {
			$signups = ($value == 1) ? "Signup" : "Signups";
			$result .= "<li>". $record . " - ". $value . ' '. $signups ."</li>";
		}
		$result .= "</ul>";
		return $result;
	}
}

/**
 * Member Posts Report.
 * Lists the Number of Posts made in the forums in the past months categorized 
 * by month.
 */
class ForumReport_MonthlyPosts extends SS_Report {
	function title() {
		return _t('Forum.FORUMMONTHLYPOSTS',"Forum Posts by Month");
	}
	
	function records($params = array()) {
		$members = DB::query("
			SELECT DATE_FORMAT(\"Created\", '%Y %M') AS \"Month\", COUNT(\"Created\") AS \"PostsTotal\"
			FROM \"Post\"
			GROUP BY DATE_FORMAT(\"Created\", '%M %Y')
			ORDER BY \"Created\" DESC
		");
		$output = array();
		foreach($members->map() as $record => $value) {
			$output[$record] = $value;
		}
		return $output;

	}
	
	function fieldsToShow() {
	}
	
	function getHTML() {
		$result = "<ul class=\"$this->class\">\n";
		foreach($this->records() as $record => $value) {
			$signups = ($value == 1) ? "Post" : "Posts";
			$result .= "<li>". $record . " - ". $value . ' '. $signups ."</li>";
		}
		$result .= "</ul>";
		return $result;
	}
}