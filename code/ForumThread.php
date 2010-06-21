<?php

/**
 * A representation of a forum thread. A forum thread is 1 topic on the forum which
 * has multiple posts underneath it.
 *
 * @package forum
 */

class ForumThread extends DataObject {
	
	static $db = array(
		"Title" => "Varchar(255)",
		"NumViews" => "Int",
		"IsSticky" => "Boolean",
		"IsReadOnly" => "Boolean",
		"IsGlobalSticky" => "Boolean"	
	);
	
	static $has_one = array(
		'Forum' => 'Forum'
	);
	
	static $has_many = array(
		'Posts' => 'Post'
	);
	
	static $defaults = array(
		'NumViews' => 0,
		'IsSticky' => false,
		'IsReadOnly' => false,
		'IsGlobalSticky' => false
	);
	
	static $indexes = array(
		"SearchFields" => array('type'=>'fulltext', 'name'=>'SearchFields', 'value'=>'Title'),
	);
	
	/**
	 * Check to see if the user can perform editing tasks on this thread. This should
	 * be moderators and admins. For editing posts canCreate() is what referred to since
	 * currently we don't have that fine grain permissions.
	 *
	 * @return bool
	 */
	function canEdit() {
		return (!$this->IsReadOnly && $this->Forum()->canEdit()) ? true : false;
	}
	
	/**
	 * Check if the user can view this thread. Check if they can view the forum since
	 * the permissions on the forum are forum based, not thread based.
	 *
	 * @return bool
	 */
	function canView() {
		return $this->Forum()->canView();
	}
	
	/**
	 * Check to see if the user can create new posts in this thread rather than new threads
	 *
	 * @return bool
	 */
	function canCreate() {
		return (!$this->IsReadOnly && $this->Forum()->canPost()) ? true : false;
	}
	
	/** 
	 * Are Forum Signatures on Member profiles allowed
	 * 
	 * @return bool
	 */
	 function getDisplaySignatures() {
	 	return $this->Forum()->Parent()->DisplaySignatures;
	}
	
	/**
	 * Get the latest post from this thread. Nicer way then using an control
	 * from the template
	 *
	 * @return Post
	 */
	function getLatestPost() {
		return DataObject::get_one('Post', "\"ThreadID\" = '$this->ID'", true, "\"ID\" DESC");
	}
	
	/**
	 * Return the first post from the thread. Useful to working out the original author
	 *
	 * @return Post
	 */
	function getFirstPost() {
		return DataObject::get_one('Post', "\"ThreadID\" = '$this->ID'", true, "\"ID\" ASC");
	}

	/**
	 * Return the number of posts in this thread. We could use count on 
	 * the dataobject set but that is slower and causes a performance overhead
	 *
	 * @return int
	 */
	function getNumPosts() {
		return (int)DB::query("SELECT count(*) FROM \"Post\" WHERE \"ThreadID\" = $this->ID")->value();
	}
	
	/**
	 * Check if they have visited this thread before. If they haven't increment 
	 * the NumViews value by 1 and set visited to true.
	 */
	function incNumViews() {
		if(Session::get('ForumViewed-' . $this->ID)) return false;

		Session::set('ForumViewed-' . $this->ID, 'true');
		$this->NumViews++;
		$SQL_numViews = Convert::raw2sql($this->NumViews);
		DB::query("UPDATE \"ForumThread\" SET \"NumViews\" = '$SQL_numViews' WHERE \"ID\" = $this->ID");
	}
	
	/**
	 * Link to this forum thread
	 *
	 * @todo Use Title as the URL rather then ID
	 * @return String
	 */
	function Link($action = "show", $showID = true) {
		$baseLink = $this->Forum()->Link();
		$extra = ($showID) ? '/'.$this->ID : '';
		
		return ($action) ? $baseLink . $action . $extra : $baseLink;
	}
	
	/**
	 * Check to see if the user has subscribed to this thread
	 *
	 * @return bool
	 */
	function getHasSubscribed() {
		$member = Member::currentUser();

		return ($member) ? ForumThread_Subscription::already_subscribed($this->ID, $member->ID) : false;
	}
	
	/**
	 * Before deleting the thread remove all the posts
	 */
	function onBeforeDelete() {
		parent::onBeforeDelete(); 

		if($posts = $this->Posts()) {
			foreach($posts as $post) {
				// attachment deletion is handled by the {@link Post::onBeforeDelete}
				$post->delete();
			}
		}
	}
}


/**
 * Forum Thread Subscription: Allows members to subscribe to this thread
 * and receive email notifications when these topics are replied to.
 *
 * @package forum
 */
class ForumThread_Subscription extends DataObject {
	
	static $db = array(
		"LastSent" => "SS_Datetime"
	);

	static $has_one = array(
		"Thread" => "ForumThread",
		"Member" => "Member"
	);

	/**
	 * Checks to see if a Member is already subscribed to this thread
	 *
	 * @param int $threadID The ID of the thread to check
	 * @param int $memberID The ID of the currently logged in member (Defaults to Member::currentUserID())
	 * @return bool true if they are subscribed, false if they're not
	 */
	static function already_subscribed($threadID, $memberID = null) {
		if(!$memberID) $memberID = Member::currentUserID();
		$SQL_threadID = Convert::raw2sql($threadID);
		$SQL_memberID = Convert::raw2sql($memberID);

		if($SQL_threadID=='' || $SQL_memberID=='')
			return false;
			
		return (DB::query("
			SELECT COUNT(\"ID\") 
			FROM \"ForumThread_Subscription\" 
			WHERE \"ThreadID\" = '$SQL_threadID' AND \"MemberID\" = $SQL_memberID"
		)->value() > 0) ? true : false;
	}

	/**
	 * Notifies everybody that has subscribed to this topic that a new post has been added.
	 * To get emailed, people subscribed to this topic must have visited the forum 
	 * since the last time they received an email
	 *
	 * @param Post $post The post that has just been added
	 */
	static function notify(Post $post) {
		$list = DataObject::get(
			"ForumThread_Subscription",
			"\"ThreadID\" = '". $post->ThreadID ."' AND \"MemberID\" != '$post->AuthorID'"
		);
		
		if($list) {
			foreach($list as $obj) {
				$SQL_id = Convert::raw2sql((int)$obj->MemberID);

				// Get the members details
				$member = DataObject::get_one("Member", "\"Member\".\"ID\" = '$SQL_id'");
				
				if($member) {
					$email = new Email();
					$email->setFrom(Email::getAdminEmail());
					$email->setTo($member->Email);
					$email->setSubject('New reply for ' . $post->Title);
					$email->setTemplate('ForumMember_TopicNotification');
					$email->populateTemplate($member);
					$email->populateTemplate($post);
					$email->populateTemplate(array(
						'UnsubscribeLink' => Director::absoluteBaseURL() . $post->Thread()->Forum()->Link() . '/unsubscribe/' . $post->ID
					));
					$email->send();
				}
			}
		}
	}
}