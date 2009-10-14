<?php

class ForumMemberProfileTest extends FunctionalTest {
	static $fixture_file = "forum/tests/ForumMemberProfileTest.yml";
	static $use_draft_site = true;
	
	function testMemberProfileDisplays() {
		/* Get the profile of a secretive member */
		$this->get('ForumMemberProfile/show/' . $this->idFromFixture('Member', 'test1'));
		
		/* Check that it just contains the bare minimum */
		$this->assertExactMatchBySelector("div#UserProfile label", array(
			"Nickname:",
			"Number of posts:",
			"Forum ranking:",
			"Avatar:",
		));
		$this->assertExactMatchBySelector("div#UserProfile p", array(
			'test1',
			'0',
			'n00b',
			'',
		));

		/* Get the profile of a public member */
		$this->get('ForumMemberProfile/show/' . $this->idFromFixture('Member', 'test2'));

		/* Check that it just contains everything */
		$this->assertExactMatchBySelector("div#UserProfile label", array(
			"Nickname:",
			'First Name:',
			'Surname:',
			'Email:',
			'Occupation:',
			'Country:',
			'Number of posts:',
			'Forum ranking:',
			'Avatar:'
		));
		$this->assertExactMatchBySelector("div#UserProfile p", array(
			'test2',
			'Test',
			'Two',
			'',
			'OtherUser',
			'Australia',
			'0',
			'l33t',
			'',
		));
	}

	/**
	 * Tests around multiple forum holders, to ensure that given a forum holder, methods only retrieve
	 * categories and forums relevant to that holder.
	 * @return unknown_type
	 */
	function testMultiForumHolders() {
		// Test ForumHolder::Forums() on 'fh', from which we expect 2 forums
		$fh = $this->objFromFixture("ForumHolder", "fh");
		$fh_controller = new ForumHolder_Controller($fh);
		$this->assertTrue($fh_controller->Forums()->Count() == 2, "fh has 2 forums");

		// Test ForumHolder::Categories() on 'fh', from which we expect 2 categories
		$this->assertTrue($fh_controller->Categories()->Count() == 2, "fh first forum has two categories");

		// Test what we got back from the two categories. The first expects 2 forums, the second
		// expects none.
		$this->assertTrue($fh_controller->Categories()->First()->Forums()->Count() == 2, "fh first category has 2 forums");
		$this->assertTrue($fh_controller->Categories()->Last()->Forums()->Count() == 0, "fh second category has 0 forums");
		
		// Test ForumHolder::Categories() on 'fh2', from which we expect 2 categories
		$fh2 = $this->objFromFixture("ForumHolder", "fh2");
		$fh2_controller = new ForumHolder_Controller($fh2);
		$this->assertTrue($fh2_controller->Categories()->Count() == 2, "fh first forum has two categories");
		
		// Test what we got back from the two categories. Each expects 1.
		$this->assertTrue($fh2_controller->Categories()->First()->Forums()->Count() == 1, "fh first category has 1 forums");
		$this->assertTrue($fh2_controller->Categories()->Last()->Forums()->Count() == 1, "fh second category has 1 forums");
	}
}

?>