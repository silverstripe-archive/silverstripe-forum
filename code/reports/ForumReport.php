<?php

class ForumReport_MemberSignups extends SideReport {
	function title() {
		return _t('Forum.FORUMSIGNUPS',"Forum Signups by Month");
	}
	function records() {
		$members = DB::query("SELECT date_format( Created, '%M %Y' ) as Month , count( Created ) as NumberJoined FROM Member group by date_format( Created, '%M %Y' ) order by Month DESC");
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

class ForumReport_MonthlyPosts extends SideReport {
	function title() {
		return _t('Forum.FORUMMONTHLYPOSTS',"Forum Posts by Month");
	}
	function records() {
		$members = DB::query("SELECT date_format( Created, '%Y %M' ) as Month , count( Created ) as PostsTotal FROM Post group by date_format( Created, '%M %Y' ) order by Created DESC");
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
?>