<?php

DataObject::add_extension('Member', 'ForumRole');

Object::add_extension('Member_Validator', 'ForumRole_Validator');

BBCodeParser::enable_smilies();

Object::add_static_var('Post', 'create_table_options', array('MySQLDatabase' => 'ENGINE=MyISAM'), true);
Object::add_static_var('ForumThread', 'create_table_options', array('MySQLDatabase' => 'ENGINE=MyISAM'), true);
