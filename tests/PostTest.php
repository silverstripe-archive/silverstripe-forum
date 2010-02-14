<?php

class PostTest extends FunctionalTest {
	
	static $fixture_file = "forum/tests/ForumTest.yml";
	
	function testGetTitle() {
		$post = $this->objFromFixture('Post', 'Post1');
		$reply = $this->objFromFixture('Post', 'Post2');
		
		$this->assertEquals($post->Title, "Test Thread");
		$this->assertEquals($reply->Title, "Re: Test Thread");
		
		$first = $this->objFromFixture('Post', 'Post3');
		$this->assertEquals($first->Title, 'Another Test Thread');
	}
	
	function testIssFirstPost() {
		$first = $this->objFromFixture('Post', 'Post1');
		$this->assertTrue($first->isFirstPost());
		
		$notFirst = $this->objFromFixture('Post', 'Post2');
		$this->assertFalse($notFirst->isFirstPost());
	}
	
	function testReplyLink() {
		$post = $this->objFromFixture('Post', 'Post1');
		
		$this->assertContains($post->Thread()->URLSegment .'/reply/'.$post->ID , $post->ReplyLink());
	}
	
	function testShowLink() {
		$post = $this->objFromFixture('Post', 'Post1');
		
		// test for show link on first page
		$this->assertContains($post->Thread()->URLSegment .'/show/'.$post->ThreadID.'?start=0#post'.$post->ID , $post->ShowLink());
		
		// test for link that should be last post on the first page
		$eighthPost = $this->objFromFixture('Post', 'Post9');
		$this->assertContains($eighthPost->Thread()->URLSegment .'/show/'.$eighthPost->ThreadID.'?start=0#post'.$eighthPost->ID , $eighthPost->ShowLink());
		
		// test for a show link on a subpage
		$lastPost = $this->objFromFixture('Post', 'Post10');
		$this->assertContains($lastPost->Thread()->URLSegment .'/show/'. $lastPost->ThreadID . '?start=1#post'.$lastPost->ID, $lastPost->ShowLink());
	}
	
	function testEditLink() {
		$post = $this->objFromFixture('Post', 'Post1');

		// should be false since we're not logged in.
		if($member = Member::currentUser()) $member->logOut();
		
		$this->assertFalse($post->EditLink());		
		
		// logged in as the member. Should be able to edit it
		$member = $this->objFromFixture('Member', 'test1');
		$member->logIn();
		
		$this->assertContains($post->Thread()->URLSegment .'/editpost/'. $post->ID, $post->EditLink());
		
		// log in as another member who is not
		$member->logOut();
		
		$memberOther = $this->objFromFixture('Member', 'test2');
		$memberOther->logIn();
		
		$this->assertFalse($post->EditLink());
	}
	
	function testDeleteLink() {
		$post = $this->objFromFixture('Post', 'Post1');

		// should be false since we're not logged in.
		if($member = Member::currentUser()) $member->logOut();
		
		$this->assertFalse($post->EditLink());		
		
		// logged in as the member. Should be able to delete it
		$member = $this->objFromFixture('Member', 'test1');
		$member->logIn();
		
		$this->assertContains($post->Thread()->URLSegment .'/deletepost/'. $post->ID, $post->DeleteLink());
		
		// because this is the first post test for the ID which is used in javascript
		$this->assertContains("id=\"firstPost\"", $post->DeleteLink());
		$member->logOut();
		
		// log in as another member who is not in a position to delete this post
		$member = $this->objFromFixture('Member', 'test2');
		$member->logIn();
		
		$this->assertFalse($post->DeleteLink());
		
		// log in as someone who can moderator this post (and therefore delete it)
		$member = $this->objFromFixture('Member', 'moderator');
		$member->logIn();
		
		// should be able to edit post since they're moderators
		$this->assertContains($post->Thread()->URLSegment .'/deletepost/'. $post->ID, $post->DeleteLink());
		
		// test that a 2nd post doesn't have the first post ID hook
		$memberOthersPost = $this->objFromFixture('Post', 'Post2');
		
		$this->assertFalse(strstr($memberOthersPost->DeleteLink(), "id=\"firstPost\""));
	}
	
	function testRSSContent() {
		// @todo escaping tests. They are handled by bbcode parser tests?
	}
	
	function testRSSAuthor() {
		// @todo 
	}
}