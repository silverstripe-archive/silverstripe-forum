<?php

class ForumAdmin extends GenericDataAdmin{
	public function Link($action=null) {
		if(!$action) $action = "index";
		return "admin/forum/$action/" . $this->currentPageID();
	}
	
	public function performSearch(){
	}
	
	public function getSearchFields(){
	}
	
	public function getLink(){
	}
	
	/**
	 * Return the entire site tree as a nested set of ULs
	*/
	public function Results() {
		$obj = singleton('Forum');
		$obj->setMarkingFilter("ClassName", "Forum");
		$obj->markPartialTree();
		
		if($p = $this->currentPage()) $obj->markToExpose($p);

		// getChildrenAsUL is a flexible and complex way of traversing the tree

		$siteTree = $obj->getChildrenAsUL("",

					' "<li id=\"record-$child->ID\" class=\"$child->class" . $child->markingClasses() .  ($extraArg->isCurrentPage($child) ? " current" : "") . "\">" . ' .

					' "<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" class=\"" . ($child->hasChildren() ? " contents" : "") . "\" >" . $child->Title . "</a>" ',

					$this, true);
					

		// Wrap the root if needs be.

		$rootLink = $this->Link() . 'show/root';

		if(!isset($rootID)) $siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-root\" class=\"Root\"><a href=\"$rootLink\">http://www.yoursite.com/assets</a>"

					. $siteTree . "</li></ul>";


		return $siteTree;

	}
}