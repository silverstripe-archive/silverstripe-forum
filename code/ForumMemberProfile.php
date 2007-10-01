<?php

class ForumMemberProfile extends Page_Controller {
	function __construct() {
		return parent::__construct(null);
	}
	
	function init() {
		if(Director::fileExists(project()."/css/forum.css")) Requirements::css(project()."/css/forum.css");
		else Requirements::css("forum/css/Forum.css");
		parent::init();
 	}
 	
 	/**
 	 * Get the latest 10 posts by this member
 	 */
 	function LatestPosts() {
 		$memberID = $this->urlParams['ID'];
 		$SQL_memberID = Convert::raw2sql($memberID);
 		
 		if(!is_numeric($SQL_memberID)) return null;
 		
 		$posts = DataObject::get("Post", "`AuthorID` = '$SQL_memberID'", "`Created` DESC", "", "0,10");
 		
 		return $posts;
 	}
	
	// Register
	
	function register() {
		return array(
			"Title" => "SilverStripe Forum",
			"Subtitle" => "Register",
			"Abstract" => DataObject::get_one("ForumHolder")->ProfileAbstract,
			"Form" => $this->RegistrationForm(),
		);
	}

	function RegistrationForm() {
		$fields = singleton('Member')->getForumFields(true);
		$form = new Form($this, 'RegistrationForm', $fields,
			new FieldSet(new FormAction("doregister", "Register")
		), new RequiredFields(
			"Nickname",
			"Email",
			"Password",
			"ConfirmPassword"
		));
		
		$member = new Member();
		$form->loadDataFrom($member);
		
		return $form;
	}
	function doregister($data, $form) {
		if($member = DataObject::get_one("Member","`Email` = '{$data['Email']}'")) {
  			if($member) {
  				$form->addErrorMessage("Blurb","Sorry, that email address already exists. Please choose another.","bad");
  				
  				// Load errors into session and post back
				Session::set("FormInfo.Form_RegistrationForm.data", $data);
  				Director::redirectBack();
  				die;
  			}
  		} elseif($member = DataObject::get_one("Member","`Nickname` = '{$data['Nickname']}'")) {
  			if($member) {
  				$form->addErrorMessage("Blurb","Sorry, that nickname already exists. Please choose another.","bad");
  				
  				// Load errors into session and post back
  				Session::set("FormInfo.Form_RegistrationForm.data", $data);
  				Director::redirectBack();
  				die;
  			}
  		}
  		
  		// create the new member 
		$member = Object::create('Member');
		$form->saveInto($member);
		
		// check password fields are the same before saving
		if($data['Password']==$data['ConfirmPassword']) {
			$member->Password=$data['Password'];
		} else {
			$form->addErrorMessage("Password","Both passwords need to match. Please try again.","bad");
			
			// Load errors into session and post back
			Session::set("FormInfo.Form_RegistrationForm.data", $data);
			Director::redirectBack();
		}

		$member->write();
		$member->login();
		Group::addToGroupByName($member, 'forum-members');
		
		return array(
			"Form" => DataObject::get_one("ForumHolder")->ProfileAdd
		);
	}

	
	// Edit profile
	
	function edit() {
		$form = $this->EditProfileForm() ? $this->EditProfileForm() : "<p class=\"error message\">You don't have the permission to edit that member.</p>";
		return array(
			"Title" => "Forum",
			"Subtitle" => DataObject::get_one("ForumHolder")->ProfileSubtitle,
			"Abstract" => DataObject::get_one("ForumHolder")->ProfileAbstract,
			"Form" => $form,
		);
	}
	
	function EditProfileForm() {
		$fields = singleton('Member')->getForumFields();
		$fields->push(new HiddenField("ID"));
		
		$form = new Form($this, 'EditProfileForm', $fields,
			new FieldSet(new FormAction("dosave", "Save changes")),
			new RequiredFields(
			"Nickname"
		));
		
		$member = $this->Member();
		
		/* 14/06/07 Modification */
		
		if($member && $member->hasMethod('canEdit') && $member->canEdit()) {
			$member->Password="";
			$form->loadDataFrom($member);
			return $form;
		} else {
			return null;
		}
		
		/* 14/06/07 Modification */
	}
	function dosave($data, $form) {
		$member = DataObject::get_by_id("Member", $data['ID']);
		if($member->canEdit()) {
			if(!empty($data['Password']) && !empty($data['ConfirmPassword'])) {
				if($data['Password']==$data['ConfirmPassword']) {
					$member->Password=$data['Password'];
				} else {
					$form->addErrorMessage("Blurb","Both passwords need to match. Please try again.","bad");
					Director::redirectBack();
				}
			} else {
				$form->dataFieldByName("Password")->setValue($member->Password);
			}
		}
		if($nicknameCheck = DataObject::get_one("Member","`Nickname` = '{$data['Nickname']}' AND `Member`.`ID` != '{$member->ID}'")){
  			if($nicknameCheck) {
  				$form->addErrorMessage("Blurb","Sorry, that nickname already exists. Please choose another.","bad");
  				Director::redirectBack();
  				die;
  			}
  		}
		$form->saveInto($member);
		$member->write();
		Director::redirect('thanks');
	}
	
	function thanks() {
		return array(
			"Form" => DataObject::get_one("ForumHolder")->ProfileModify
		);
	}
 	
 	function Link($action = null) {
 		return "$this->class/$action";
 	}

 	function Member() {
		if(is_numeric($this->urlParams['ID'])) {
			$member = DataObject::get_by_id("Member", $this->urlParams['ID']);
			if($this->urlParams['Action'] == "show") $member->Country = Geoip::countryCode2name($member->Country ? $member->Country : "NZ");
			return $member;
		} else {
			$member = Member::currentUser();
			if($this->urlParams['Action'] == "show") $member->Country = Geoip::countryCode2name($member->Country ? $member->Country : "NZ");
			return $member;
		}
	}
	
	
	/*
	 * Get the latest member in the system
	 * 
	 */
	function LatestMember($limit = null) {
		return DataObject::get("Member", "", "`Member`.`ID` DESC", "", 1);
	}
	
	/**
	 * This will trick SilverStripe into placing this page within the site tree
	 */
	function getParent() {
		$siblingForum = Forum_Controller::getLastForumAccessed();
		return $siblingForum->Parent;
	}
	function getParentID() {
		$siblingForum = Forum_Controller::getLastForumAccessed();
		return $siblingForum->ParentID;
	}
	function data() {
		return $this;
	}
	
	function CurrentlyOnline() {
		return DataObject::get("Member", "LastVisited > NOW() - INTERVAL 15 MINUTE", "FirstName, Surname",
			"INNER JOIN Group_Members ON Group_Members.GroupID IN (1,2,3) AND Group_Members.MemberID = Member.ID");
	}
	
	/**
	 * Stuff that's only on forum holders...
	 */
	function _ForumHolder() {
		return new ForumHolder_Controller(DataObject::get_one("ForumHolder"));
	}
	
	function TotalPosts() {
		return $this->ForumHolder()->TotalPosts();
	}
	function TotalTopics() {
		return $this->ForumHolder()->TotalTopics();
	}
	function TotalAuthors() {
		return $this->ForumHolder()->TotalAuthors();
	}
	function Forums() {
		return $this->ForumHolder()->Forums();
	}
	function SearchResults() {
		return $this->ForumHolder()->SearchResults();
	}
	function getSubtitle() {
		return "User profile";
	}
	function getAbstract() {
		return $this->ForumHolder()->getAbstract();
	}
	function URLSegment() {
		return $this->ForumHolder()->URLSegment();
	}

	/*
	 * This needs MetaTags because it doesn't extend SiteTree at any point
	 */
	function MetaTags($includeTitle = true) {
		$tags = "";
		$Title = "Forum user profile";
		if($includeTitle == true) {
			$tags .= "<title>" . $Title . "</title>\n";
		}

		return $tags;
	}
	
}

?>