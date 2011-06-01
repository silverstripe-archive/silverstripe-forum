<?php

/**
 * @todo Write Tests for doPostMessageForm()
 */
class ForumTest extends FunctionalTest {
	
	static $fixture_file = "forum/tests/ForumTest.yml";
	
	function testCanView() {
		// test viewing not logged in
		if($member = Member::currentUser()) $member->logOut();
		
		$public = $this->objFromFixture('Forum', 'general');
		$private = $this->objFromFixture('Forum', 'loggedInOnly');
		$limited = $this->objFromFixture('Forum', 'limitedToGroup');
		$noposting = $this->objFromFixture('Forum', 'noPostingForum');
		$inherited = $this->objFromFixture('Forum', 'inheritedForum');
		
		$this->assertTrue($public->canView());
		$this->assertFalse($private->canView());
		$this->assertFalse($limited->canView());
		$this->assertTrue($noposting->canView());
		$this->assertFalse($inherited->canView());
		
		// try logging in a member
		$member = $this->objFromFixture('Member', 'test1');
		$member->logIn();
		
		$this->assertTrue($public->canView());
		$this->assertTrue($private->canView());
		$this->assertFalse($limited->canView());
		$this->assertTrue($noposting->canView());
		$this->assertFalse($inherited->canView());
		
		// login as a person with access to restricted forum
		$member = $this->objFromFixture('Member', 'test2');
		$member->logIn();
		
		$this->assertTrue($public->canView());
		$this->assertTrue($private->canView());
		$this->assertTrue($limited->canView());
		$this->assertTrue($noposting->canView());
		$this->assertFalse($inherited->canView());

		// Moderator should be able to view his own forums
		$member = $this->objFromFixture('Member', 'moderator');
		$member->logIn();

		$this->assertTrue($public->canView());
		$this->assertTrue($private->canView());
		$this->assertTrue($limited->canView());
		$this->assertTrue($noposting->canView());
		$this->assertTrue($inherited->canView());
	}

	function testCanPost() {
		// test viewing not logged in
		if($member = Member::currentUser()) $member->logOut();
		
		$public = $this->objFromFixture('Forum', 'general');
		$private = $this->objFromFixture('Forum', 'loggedInOnly');
		$limited = $this->objFromFixture('Forum', 'limitedToGroup');
		$noposting = $this->objFromFixture('Forum', 'noPostingForum');
		$inherited = $this->objFromFixture('Forum', 'inheritedForum');
		
		$this->assertTrue($public->canPost());
		$this->assertFalse($private->canPost());
		$this->assertFalse($limited->canPost());
		$this->assertFalse($noposting->canPost());
		$this->assertFalse($inherited->canPost());
		
		// try logging in a member
		$member = $this->objFromFixture('Member', 'test1');
		$member->logIn();
		
		$this->assertTrue($public->canPost());
		$this->assertTrue($private->canPost());
		$this->assertFalse($limited->canPost());
		$this->assertFalse($noposting->canPost());
		$this->assertFalse($inherited->canPost());
		
		// login as a person with access to restricted forum
		$member = $this->objFromFixture('Member', 'test2');
		$member->logIn();
		
		$this->assertTrue($public->canPost());
		$this->assertTrue($private->canPost());
		$this->assertTrue($limited->canPost());
		$this->assertFalse($noposting->canPost());
		$this->assertFalse($inherited->canPost());

		// Moderator should be able to view his own forums
		$member = $this->objFromFixture('Member', 'moderator');
		$member->logIn();

		$this->assertTrue($public->canPost());
		$this->assertTrue($private->canPost());
		$this->assertFalse($limited->canPost());
		$this->assertFalse($noposting->canPost());
		$this->assertFalse($inherited->canPost());
	}

	function testCanModerate() {
		// test viewing not logged in
		if($member = Member::currentUser()) $member->logOut();
		
		$public = $this->objFromFixture('Forum', 'general');
		$private = $this->objFromFixture('Forum', 'loggedInOnly');
		$limited = $this->objFromFixture('Forum', 'limitedToGroup');
		$noposting = $this->objFromFixture('Forum', 'noPostingForum');
		$inherited = $this->objFromFixture('Forum', 'inheritedForum');
		
		$this->assertFalse($public->canModerate());
		$this->assertFalse($private->canModerate());
		$this->assertFalse($limited->canModerate());
		$this->assertFalse($noposting->canModerate());
		$this->assertFalse($inherited->canModerate());
		
		// try logging in a member
		$member = $this->objFromFixture('Member', 'test1');
		$member->logIn();
		
		$this->assertFalse($public->canModerate());
		$this->assertFalse($private->canModerate());
		$this->assertFalse($limited->canModerate());
		$this->assertFalse($noposting->canModerate());
		$this->assertFalse($inherited->canModerate());
		
		// login as a person with access to restricted forum
		$member = $this->objFromFixture('Member', 'test2');
		$member->logIn();
		
		$this->assertFalse($public->canModerate());
		$this->assertFalse($private->canModerate());
		$this->assertFalse($limited->canModerate());
		$this->assertFalse($noposting->canModerate());
		$this->assertFalse($inherited->canModerate());

		// Moderator should be able to view his own forums
		$member = $this->objFromFixture('Member', 'moderator');
		$member->logIn();

		$this->assertTrue($public->canModerate());
		$this->assertTrue($private->canModerate());
		$this->assertTrue($limited->canModerate());
		$this->assertTrue($noposting->canModerate());
		$this->assertTrue($inherited->canModerate());
	}

	function testCanAttach() {
		$canAttach = $this->objFromFixture('Forum', 'general');
		$this->assertTrue($canAttach->canAttach());
		
		$noAttach = $this->objFromFixture('Forum', 'forum1cat2');
		$this->assertFalse($noAttach->canAttach());
	}
	
	function testgetForbiddenWords(){
		$forum = $this->objFromFixture("Forum", "general");
		$f_controller = new Forum_Controller($forum);
		$this->assertEquals($f_controller->getForbiddenWords(), "shit,fuck");
	}
	
	function testfilterLanguage(){
		$forum =  $this->objFromFixture("Forum", "general");
		$f_controller = new Forum_Controller($forum);
		$this->assertEquals($f_controller->filterLanguage('shit'), "*");
		
		$this->assertEquals($f_controller->filterLanguage('shit and fuck'), "* and *");
		
		$this->assertEquals($f_controller->filterLanguage('hello'), "hello");
	}
	
	function testGetStickyTopics() {
		$forumWithSticky = $this->objFromFixture("Forum", "general");
		
		$this->assertEquals($forumWithSticky->getStickyTopics()->Count(), '2');
		$this->assertEquals($forumWithSticky->getStickyTopics()->First()->Title, 'Sticky Thread');
		
		$forumWithGlobalOnly = $this->objFromFixture("Forum", "forum1cat2");
		
		$this->assertEquals($forumWithGlobalOnly->getStickyTopics()->Count(), '1');
		$this->assertEquals($forumWithGlobalOnly->getStickyTopics()->First()->Title, 'Global Sticky Thread');
	}
	
	function testTopics() {
		$forumWithPosts = $this->objFromFixture("Forum", "general");
		
		$this->assertEquals($forumWithPosts->getTopics()->Count(), '4');
		
		$forumWithoutPosts = $this->objFromFixture("Forum", "forum1cat2");
		
		$this->assertNull($forumWithoutPosts->getTopics());
	}
	
	function testGetLatestPost() {
		$forumWithPosts = $this->objFromFixture("Forum", "general");

		$this->assertEquals($forumWithPosts->getLatestPost()->Content, 'This is the last post to a long thread');
	
		$forumWithoutPosts = $this->objFromFixture("Forum", "forum1cat2");

		$this->assertFalse($forumWithoutPosts->getLatestPost());
	}
	
	function testGetNumTopics() {
		$forumWithPosts = $this->objFromFixture("Forum", "general");
		
		$this->assertEquals($forumWithPosts->getNumTopics(), 6);
		
		$forumWithoutPosts = $this->objFromFixture("Forum", "forum1cat2");

		$this->assertEquals($forumWithoutPosts->getNumTopics(), 0);
	}
	
	function testGetTotalAuthors() {
		$forumWithPosts = $this->objFromFixture("Forum", "general");
		
		$this->assertEquals($forumWithPosts->getNumAuthors(), 4);
		
		$forumWithoutPosts = $this->objFromFixture("Forum", "forum1cat2");

		$this->assertEquals($forumWithoutPosts->getNumAuthors(), 0);
	}

	function testMarkAsSpamLink() {
		$this->logInWithPermission("ADMIN");
	
		$spampost = $this->objFromFixture('Post', 'SpamSecondPost');
		
		$forum = $spampost->Forum();
		$author = $spampost->Author();

		$link = $forum->AbsoluteLink("markasspam") .'/'. $spampost->ID;
		
		$c = new Forum_Controller($forum);
		$response = $c->handleRequest(new SS_HTTPRequest('GET', 'markasspam/'. $spampost->ID));
		
		// removes the post
		$this->assertFalse(DataObject::get_by_id('Post', $spampost->ID));
		
		// removes the member
		$this->assertFalse(DataObject::get_by_id('Member', $author->ID));
		
		// does not effect the thread
		$thread = DataObject::get_by_id('ForumThread', $spampost->Thread()->ID);
		$this->assertEquals('1', $thread->getNumPosts());
		
		// mark the first post in that now as spam
		$spamfirst = $this->objFromFixture('Post', 'SpamFirstPost');

		$response = $c->handleRequest(new SS_HTTPRequest('GET', 'markasspam/'. $spamfirst->ID));

		// removes the thread
		$this->assertFalse(DataObject::get_by_id('ForumThread', $spamfirst->Thread()->ID));
	}
}
