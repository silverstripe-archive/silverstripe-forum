<?php

class ForumMemberProfileTest extends FunctionalTest {
	static $fixture_file = "forum/tests/ForumMemberProfileTest.yml";
	
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
		$this->assertExactHTMLMatchBySelector("div#UserProfile p", array(
			'<p class="readonly">test1</p>',
			'<p class="readonly">0</p>',
			'<p class="readonly">n00b</p>',
			'<p><img class="userAvatar" src="forum/images/forummember_holder.gif" width="80" alt="test1\'s avatar"/></p>',
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
		$this->assertExactHTMLMatchBySelector("div#UserProfile p", array(
			'<p class="readonly">test2</p>',
			'<p class="readonly">Test</p>',
			'<p class="readonly">Two</p>',
			'<p class="readonly"><a href="mailto:test2@example.com">test2@example.com</a></p>',
			'<p class="readonly">OtherUser</p>',
			'<p class="readonly">Australia</p>',
			'<p class="readonly">0</p>',
			'<p class="readonly">l33t</p>',
			'<p><img class="userAvatar" src="forum/images/forummember_holder.gif" width="80" alt="test2\'s avatar"/></p>',
		));
	}
}

?>