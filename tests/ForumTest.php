<?php

/**
 * @todo Write Tests for doPostMessageForm()
 */
class ForumTest extends FunctionalTest {
	
	static $fixture_file = "forum/tests/ForumTest.yml";
	
	function testCanView() {
		// test viewing not logged in on each of the forums
		if($member = Member::currentUser()) $member->logOut();
		
		$public = $this->objFromFixture('Forum', 'general');
		$private = $this->objFromFixture('Forum', 'loggedInOnly');
		$limited = $this->objFromFixture('Forum', 'limitedToGroup');
		
		$this->assertTrue($public->canView());
		$this->assertFalse($private->canView());
		$this->assertFalse($limited->canView());
		
		// try logging in a member, then we should be able to view both
		$member = $this->objFromFixture('Member', 'test1');
		$member->logIn();
		
		$this->assertTrue($public->canView());
		$this->assertTrue($private->canView());
		$this->assertFalse($limited->canView());
		
		// login as someone who can view the limited forum
		$member = $this->objFromFixture('Member', 'test2');
		$member->logIn();
		
		$this->assertTrue($public->canView());
		$this->assertTrue($private->canView());
		$this->assertTrue($limited->canView());
	}
	
	function testCanEdit() {
		if($member = Member::currentUser()) $member->logOut();
		
		$forum = $this->objFromFixture('Forum', 'general');
		
		$this->assertFalse($forum->canEdit());
		
		$member = $this->objFromFixture('Member', 'test1');
		$member->logIn();
		
		$this->assertFalse($forum->canEdit());

		$member->logOut();

		$member = $this->objFromFixture('Member', 'moderator');
		$member->logIn();

		$this->assertTrue($forum->canEdit());
	}
	
	function testCanPost() {
		// test post not logged in on each of the forums
		if($member = Member::currentUser()) $member->logOut();
		
		$public = $this->objFromFixture('Forum', 'general');
		$private = $this->objFromFixture('Forum', 'loggedInOnly');
		$limited = $this->objFromFixture('Forum', 'limitedToGroup');
		$noPost = $this->objFromFixture('Forum', 'noPostingForum');
		
		$this->assertFalse($public->canPost());
		$this->assertFalse($private->canPost());
		$this->assertFalse($limited->canPost());
		$this->assertFalse($noPost->canPost());
		
		// try logging in a member, then we should be able to view both
		$member = $this->objFromFixture('Member', 'test1');
		$member->logIn();
		
		$this->assertTrue($public->canPost());
		$this->assertTrue($private->canPost());
		$this->assertFalse($limited->canPost());
		$this->assertFalse($noPost->canPost());
		
		// login as someone who can view the limited forum
		$member->logOut();
		$member = $this->objFromFixture('Member', 'test2');
		$member->logIn();
		
		$this->assertTrue($public->canPost());
		$this->assertTrue($private->canPost());
		$this->assertTrue($limited->canPost());
		$this->assertFalse($noPost->canPost());
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
		
		$this->assertEquals($forumWithPosts->getTopics()->Count(), '2');
		$this->assertEquals($forumWithPosts->getTopics()->First()->Title, 'Test Thread');
		
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
		
		$this->assertEquals($forumWithPosts->getNumTopics(), 5);
		
		$forumWithoutPosts = $this->objFromFixture("Forum", "forum1cat2");

		$this->assertEquals($forumWithoutPosts->getNumTopics(), 0);
	}
	
	function testGetTotalAuthors() {
		$forumWithPosts = $this->objFromFixture("Forum", "general");
		
		$this->assertEquals($forumWithPosts->getNumAuthors(), 2);
		
		$forumWithoutPosts = $this->objFromFixture("Forum", "forum1cat2");

		$this->assertEquals($forumWithoutPosts->getNumAuthors(), 0);
	}

}