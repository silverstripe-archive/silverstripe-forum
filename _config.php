<?php

/* Put here all the function calls to update the link with the Member table */

DataObject::add_extension('Member', 'ForumRole');

MemberTableField::addPermissions( array( "show", "export" ) );

MemberTableField::addMembershipFields( array( 
	"Nickname" => "Nickname",
	"Occupation" => "Occupation",
	"Country" => "Country",
	"ForumRank" => "ForumRank"
	));

?>