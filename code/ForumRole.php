<?php
/*
 * Created on Jun 19, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 class ForumRole extends DataObjectDecorator {
 	
 	/**
	 * Edit the given query object to support queries for this extension
	 */
	function augmentSQL(SQLQuery &$query) {}
 	
 	/**
	 * Update the database schema as required by this extension
	 */
	function augmentDatabase() {
 		$exist =  DB::query( "SHOW TABLES LIKE 'ForumMember'" )->numRecords();
 		if( $exist > 0 ) {
 			DB::query( "UPDATE `Member`, `ForumMember` " .
 				"SET `Member`.`ClassName` = 'Member'," .
 				"`Member`.`ForumRank` = `ForumMember`.`ForumRank`," .
 				"`Member`.`Occupation` = `ForumMember`.`Occupation`," .
 				"`Member`.`Country` = `ForumMember`.`Country`," .
 				"`Member`.`Nickname` = `ForumMember`.`Nickname`," .
 				"`Member`.`FirstNamePublic` = `ForumMember`.`FirstNamePublic`," .
 				"`Member`.`SurnamePublic` = `ForumMember`.`SurnamePublic`," .
 				"`Member`.`OccupationPublic` = `ForumMember`.`OccupationPublic`," .
 				"`Member`.`CountryPublic` = `ForumMember`.`CountryPublic`," .
 				"`Member`.`EmailPublic` = `ForumMember`.`EmailPublic`," .
 				"`Member`.`AvatarID` = `ForumMember`.`AvatarID`," .
 				"`Member`.`LastViewed` = `ForumMember`.`LastViewed`" .
 				"WHERE `Member`.`ID` = `ForumMember`.`ID`"
 			);
 			echo( "<div style=\"padding:5px; color:white; background-color:blue;\">The data transfer has succeeded. However, to complete it, you must delete the ForumMember table. To do this, execute the query \"DROP TABLE 'ForumMember'\".</div>" );
 		}
	}
 	
 	/**
	 * Define extra database fields.
	 * Return an map where the keys are db, has_one, etc, and the values are additional fields / relations to be defined
	 */
	function extraDBFields() {
		return array(
			'db' => array(
				'ForumRank' => 'Varchar',
				'Occupation' => 'Varchar',
				'Country' => 'Varchar',
				'Nickname' => 'Varchar',
				'FirstNamePublic' => 'Boolean',
				'SurnamePublic' => 'Boolean',
				'OccupationPublic' => 'Boolean',
				'CountryPublic' => 'Boolean',
				'EmailPublic' => 'Boolean',
				'LastViewed' => 'SSDatetime'
			),
			'has_one' => array(
				'Avatar' => 'Image'
			),
			'defaults' => array(
				'ForumRank' => 'Community Member'
			)
		);
	}
	
	// Additional Methods
	
	function NumPosts() {
		if(is_numeric($this->owner->ID)) {
			return (int)DB::query("SELECT count(*) FROM Post WHERE AuthorID = '" . $this->owner->ID . "'")->value();
		} else {
			return 0;
		}
	}
	
	function Link() {
		return "ForumMemberProfile/show/" . $this->owner->ID;
	}
	
	function getForumFields($addMode = false) {
		return new FieldSet(
			new HeaderField("Personal Details"),
			
			new LiteralField("Blurb","<p id=\"helpful\">Tick the fields to show in public profile</p>"),
			
			new CheckableOption( "UnnecessaryNicknamePublic", new TextField("Nickname", "Nickname"), true, true ),
			new CheckableOption( "FirstNamePublic", new TextField("FirstName", "First name") ),
			new CheckableOption( "SurnamePublic", new TextField("Surname", "Surname") ),
			new CheckableOption( "OccupationPublic", new TextField("Occupation", "Occupation"), true ),
			new CheckableOption( "CountryPublic", new CountryDropdownField("Country", "Country"), true ),
			
			new HeaderField("User Details"),
			new CheckableOption( "EmailPublic", $addMode ? new EmailField("Email", "Email") : new ReadonlyField("Email", "Email") ),
			new PasswordField("Password", "Password") ,
			new PasswordField("ConfirmPassword", "Confirm Password") ,
			new SimpleImageField("Avatar", "Upload avatar") ,
			new ReadonlyField("ForumRank", "User rating")
		);
	}
	
	function updateCMSFields(FieldSet &$fields) {
		if( Permission::checkMember( $this->owner->ID, "ACCESS_FORUM" ) ) {
			$fields->insertBefore( new TextField( "Nickname", "Nickname" ), "FirstName" );
			$fields->insertAfter( new TextField( "Occupation", "Occupation" ), "Surname" );
			$fields->insertAfter( new CountryDropdownField( "Country", "Country" ), "Occupation" );

			$fields->push( new PasswordField( "ConfirmPassword", "Confirm Password" ) );
			$fields->push( new ImageField( "Avatar", "Upload avatar" ) );
			$fields->push( new DropdownField( "ForumRank", "User rating", array(
				"Community Member" => "Community Member",
				"Administrator" => "Administrator",
				"Moderator" => "Moderator",
				"SilverStripe User" => "SilverStripe User",
				"SilverStripe Developer" => "SilverStripe Developer",
				"Core Development Team" => "Core Development Team",
				"Google Summer of Code Hacker"	=> "Google Summer of Code Hacker",
				"Lead Developer" => "Lead Developer"
			)));
		}
	}
	
	function getValidator() {
		return new ForumMember_Validator();
	}
	
	/**
	 * Can the current user edit the given member?
	 * @return true if this member can be edited, false otherwise
	 */
	function canEdit() {
		if($this->owner->ID == Member::currentUserID()) return true;
		
		if($member = Member::currentUser()) return $member->can('AdminCMS');
		
		return false;
	}
	
	/**
	 * Used in preference to the Nickname field on templates
	 * Provides a default for the nickname field (first name, or "Anonymous User" if that's not set)
	 */
	function Nickname() {
		if($this->owner->Nickname) return $this->owner->Nickname;
		else if($this->owner->FirstName) return $this->owner->FirstName;
		else return "Anonymous user";
	}
	
	
 }
 
 class ForumMember_Validator extends Member_Validator {
	function javascript() {
		$js = parent::javascript();
		
		$formID = $this->form->FormName();
		$passwordFieldName = $this->form->dataFieldByName('Password')->id();
		$passwordConfirmFieldName = $this->form->dataFieldByName('ConfirmPassword')->id();
		
		
		$passwordcheck = <<<JS
Behaviour.register({
	"#$formID": {
		validatePasswordConfirmation: function() {
			var passEl = _CURRENT_FORM.elements['Password'];
			var confEl = _CURRENT_FORM.elements['ConfirmPassword'];
		
			if(passEl.value == confEl.value)
				return true;
			else {
				validationError(confEl, "Passwords don't match.", "error");
				return false;
			}
		},
		initialize: function() {
			var passEl = $('$passwordFieldName');
			var confEl = $('$passwordConfirmFieldName');

			confEl.value = passEl.value;
		}
	}
});
JS;

	Requirements::customScript($passwordcheck, 'func_validatePasswordConfirmation');
	
	$js .= "\$('$formID').validatePasswordConfirmation();";
	return $js;
	
	}
}

/**
 * Email template for topic notifications
 */
class ForumMember_TopicNotification extends Email_Template {
	// TODO Figure out why '$Nickname <$Email>' doesn't work for protected $to
	protected
		$to = '$Email',
		$subject = 'New reply to \'$Title\'',
		$ss_template = 'ForumMember_TopicNotification';
	
	/**
	 * This only exists because you can't do "protected $from = Email::getAdminEmail()" with PHP
	 */
	function __construct() {
		$this->setFrom(Email::getAdminEmail());
		
		parent::__construct();
	}
}

?>
