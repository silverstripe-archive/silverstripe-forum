<?php

class ForumTest extends FunctionalTest {
	
	static $fixture_file = "forum/tests/ForumMemberProfileTest.yml";
	static $use_draft_site = true;
	
	function testgetForbiddenWords(){
		$forum = $this->objFromFixture("ForumHolder", "fh");
		$f_controller = new Forum_Controller($forum);
		$this->assertEquals($f_controller->getForbiddenWords(), "shit,fuck");
	}
	
	function testfilterLanguage(){
		$forum = $this->objFromFixture("ForumHolder", "fh");
		$f_controller = new Forum_Controller($forum);
		$this->assertEquals($f_controller->filterLanguage('shit'), "*");
		
		$this->assertEquals($f_controller->filterLanguage('shit and fuck'), "* and *");
		
		$this->assertEquals($f_controller->filterLanguage('hello'), "hello");
	}
	
	
	
}

?>