<?php

/**
 * Forum Post Object. Contains a single post by the user. A thread is generated with multiple posts
 * 
 * @todo Implement the Moderation.
 *
 * @package forum
 */

class Post extends DataObject {
	
	static $db = array(
		"Content" => "Text",
		"Status" => "Enum('Awaiting, Moderated, Rejected, Archived', 'Moderated')",
	);
	
	static $indexes = array(
		"SearchFields" => "fulltext (Content)"
	);

	static $casting = array(
		"Updated" => "SSDatetime",
		"RSSContent" => "HTMLText",
		"RSSAuthor" => "Varchar",
		"Content" => "Text"
	);

	static $has_one = array(
		"Author" => "Member",
		"Thread" => "ForumThread"
	);

	static $has_many = array(
		"Attachments" => "Post_Attachment"
	);
	
	/**
	 * Return whether we can edit this post. Only the user, moderator
	 * or admin can edit post
	 *
	 * @return bool
	 */
	function canEdit() {
		if($member = Member::currentUser()) {
			if($member->ID == $this->AuthorID) return true;
		}

		return $this->Thread()->canEdit();
	}
	
	/**
	 * Return whether we can view this post. Needs to check the forums can
	 * view since an individual posts won't be 'hidden' but whole forums will
	 * be. Readonly posts aren't supported but read only threads are
	 *
	 * @return bool
	 */
	function canView() {
		return $this->Thread()->canView();
	}
	
	/**
	 * Returns the absolute url rather then relative. Used in Post RSS Feed
	 *
	 * @return String
	 */
	function AbsoluteLink() {
		return Director::absoluteURL($this->Link());
	}

	/**
	 * Return the title of the post. Because we don't have to have the title
	 * on individual posts check with the topic
	 * 
	 * @return String
	 */
	function getTitle() {
		$value = DB::query("SELECT count(ID) FROM Post WHERE ThreadID = '$this->ThreadID' AND ID < '$this->ID'")->value();
		
		if($value > 0) {
			return sprintf(_t('Post.RESPONSE',"Re: %s",PR_HIGH,'Post Subject Prefix'),$this->Thread()->Title);
		}
		return $this->Thread()->Title;
	}

	/**
	 * Return the last edited date, if it's different from created
	 */
	function Updated() {
		if($this->LastEdited != $this->Created) return $this->LastEdited;
	}
	
	/**
	 * Is this post the first post in the thread. Check if their is a post with an ID less
	 * than the one of this post in the same thread
	 *
	 * @return bool
	 */
	function isFirstPost() {
		return (DataObject::get_one('Post', "ThreadID = '$this->ThreadID' AND ID < '$this->ID'")) ? false : true;
	}
	
	/**
	 * Return a link to edit this post.
	 * 
	 * @return String
	 */
	function EditLink() {	
		if($this->canEdit()) {
			$url = $this->Link('editpost');
			
			return "<a href=\"{$url}/{$this->ID}\">" . _t('Post.EDIT','Edit') . "</a>";
		}
		
		return false;
	}

	/**
	 * Return a link delete this post.
	 * 
	 * If the member is an admin of this forum, (ADMIN permissions
	 * or a moderator) then they can delete the post.
	 *
	 * @return String
	 */
	function DeleteLink() {
 		$id = ($this->isFirstPost()) ? " id=\"firstPost\" " : " ";

		if($this->canEdit()) {
			$url = $this->Link('deletepost');
			
			return "<a".$id."class=\"deletelink\" rel=\"$this->ID\" href=\"{$url}/{$this->ID}\">" . _t('Post.DELETE','Delete') ."</a>";
		}
		
		return false;
	}
	
	/**
	 * Return a link to the reply form. Permission checking is handled on the actual URL
	 * and not on this function
	 *
	 * @return String
	 */
	function ReplyLink() {
		$url = $this->Link('reply');

		return "<a href=\"$url\">" . _t('Post.REPLYLINK','Post Reply') . "</a>";
	}
		
	/**
	 * Return a link to the post view.
	 *
	 * @return String
	 */
	function ShowLink() {
		$url = $this->Link('show');
		return "<a href=\"$url\">" . _t('Post.SHOWLINK','Show Thread') . "</a>";
	}
	
	
	/**
	 * Return a link to mark this post as spam.
	 * used for the spamprotection module
	 *
	 * @return String
	 */
	function MarkAsSpamLink() {
		if(class_exists('SpamProtectorManager') && $member = Member::currentUser()) {
		 	if($member->ID != $this->AuthorID)
				return "<a href=\"{$this->Forum()->Link('markasspam')}{$this->ID}\" class='markAsSpamLink' rel=\"$this->ID\">". _t('Post.MARKASSPAM', 'Mark as Spam') ."</a>";
		}
	}

	function RSSContent() {
		$parser = new BBCodeParser($this->Content);
		$html = $parser->parse();
		if($this->Thread()) $html .= '<br><br>' . sprintf(_t('Post.POSTEDTO',"Posted to: %s"),$this->Thread()->Title);
		$html .= " ". $this->ShowLink() . " | " .$this->ReplyLink();
		return $html;
	}

	
	function RSSAuthor() {
		$author = $this->Author();
		return $author->Nickname;
	}
	
	/**
	 * Return a link to show this post
	 *
	 * @return String
	 */
	function Link($action = "show") {
		// only include the forum thread ID in the URL if we're showing the thread either 
		// by showing the posts or replying therwise we only need to pass a single ID.
		$includeThreadID = ($action == "show" || $action == "reply") ? true : false;
		$link = $this->Thread()->Link($action, $includeThreadID);

		// calculate what page results the post is on count is the position of the post in the thread
		$count = DB::query("
			SELECT COUNT(ID) 
			FROM Post 
			WHERE ThreadID = '$this->ThreadID' AND Status = 'Moderated' AND ID <= $this->ID")->value();
		
		// round it to the correct page
		$count = ($count < (Forum::$posts_per_page + 1)) ? 0 : floor($count/Forum::$posts_per_page);
		
		return ($action == "show") ? $link . '?start='.$count.'#post' . $this->ID : $link;
	}
	
}

/**
 * Attachments for posts (one post can have many attachments)
 *
 * @package forum
 */
class Post_Attachment extends File {
	static $has_one = array(
		"Post" => "Post"
	);

	/**
	 * Allows the user to download a file without right-clicking
	 */
	function download() {
		$SQL_ID = Convert::raw2sql($this->urlParams['ID']);
		if(is_numeric($SQL_ID)) {
			$file = DataObject::get_by_id("Post_Attachment", $SQL_ID);
			$response = SS_HTTPRequest::send_file(file_get_contents($file->getFullPath()), $file->Name);
			$response->output();
		}

		// Missing something or hack attempt
		return Director::redirectBack();
	}
}