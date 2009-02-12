<?php

DataObject::add_extension('Member', 'ForumRole');

Object::add_extension('Member_Validator', 'ForumRole_Validator');

MemberTableField::addMembershipFields(array(
	"Nickname" => "Nickname",
	"Occupation" => "Occupation",
	"Country" => "Country",
	"ForumRank" => "ForumRank"
));

BBCodeParser::enable_smilies();

?>
