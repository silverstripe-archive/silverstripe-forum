<?php

DataObject::add_extension('Member', 'ForumRole');

Object::add_extension('Member_Validator', 'ForumRole_Validator');

BBCodeParser::enable_smilies();
