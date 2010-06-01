<?php

class ForumMemberProfileTest extends FunctionalTest {
	
	static $fixture_file = "forum/tests/ForumTest.yml";
	static $use_draft_site = true;
	
	function testMemberProfileDisplays() {
		/* Get the profile of a secretive member */
		$this->get('ForumMemberProfile/show/' . $this->idFromFixture('Member', 'test1'));
		
		/* Check that it just contains the bare minimum 
		 
		Commented out by wrossiter since this was breaking with custom themes. A test like this should not fail
		because of a custom theme. Will reenable these tests when we tackle the new Member functionality
		
		$this->assertExactMatchBySelector("div#UserProfile label", array(
			"Nickname:",
			"Number of posts:",
			"Forum ranking:",
			"Avatar:",
		));
		$this->assertExactMatchBySelector("div#UserProfile p", array(
			'test1',
			'5',
			'n00b',
			'',
		));

		/* Get the profile of a public member */
		$this->get('ForumMemberProfile/show/' . $this->idFromFixture('Member', 'test2'));

		/* Check that it just contains everything 
		
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
			'8',
			'l33t',
			'',
		));
		 */
	}
}