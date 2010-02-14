<?php

/**
 * @todo Write some more complex tests for testing the can*() functionality
 */
class ForumThreadTest extends FunctionalTest {
	
	static $fixture_file = "forum/tests/ForumTest.yml";
	
	function testGetNumPosts() {
		$thread = $this->objFromFixture("ForumThread", "Thread1");
		
		$this->assertEquals($thread->getNumPosts(), '9');
	}
	
	function testIncViews() {
		$thread = $this->objFromFixture("ForumThread", "Thread1");
		
		// clear session
		Session::clear('ForumViewed-'.$thread->ID);
		
		$this->assertEquals($thread->NumViews, '10');
		
		$thread->incNumViews();
		
		$this->assertEquals($thread->NumViews, '11');
	}
	
	function testGetLatestPost() {
		$thread = $this->objFromFixture("ForumThread", "Thread1");
		
		$this->assertEquals($thread->getLatestPost()->Content, "This is the last post to a long thread");
	}
	
	function testGetFirstPost() {
		$thread = $this->objFromFixture("ForumThread", "Thread1");
		
		$this->assertEquals($thread->getFirstPost()->Content, "This is my first post");
	}
	
	function testSubscription() {
		$thread = $this->objFromFixture("ForumThread", "Thread1");
		$thread2 = $this->objFromFixture("ForumThread", "Thread2");
		
		$member = $this->objFromFixture("Member", "test1");
		$member2 = $this->objFromFixture("Member", "test2");
		
		$this->assertTrue(ForumThread_Subscription::already_subscribed($thread->ID, $member->ID));
		$this->assertTrue(ForumThread_Subscription::already_subscribed($thread->ID, $member2->ID));
		
		$this->assertFalse(ForumThread_Subscription::already_subscribed($thread2->ID, $member->ID));
		$this->assertFalse(ForumThread_Subscription::already_subscribed($thread2->ID, $member2->ID));
		
	}
}
