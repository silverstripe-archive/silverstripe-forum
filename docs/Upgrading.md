Upgrading
================================

To help with the transition between early versions of the forum module and the latest version we have included migration scripts in 
forum/code/migration. 

It is recommended that you __backup your database__ before upgrading the module and running these scripts.

To run these migration tasks you can do it either via the web browser 

	http://www.yoursite.com/dev/tasks/ForumMigrationTask
	
Or via the CLI / Sake interface
	
	sake /dev/tasks/ForumMigrationTask
	
Upgrading from 0.* to 0.5
-------------------------------------

In 0.5 (trunk) we have totally refactored alot of methods which are used extensively in the templates. So if you have your own custom
forum theme it is highly likely that these will not work with the 0.5 release. An overview of whats changed

* Methods have been removed from individual PageTypes and left on ForumHolder. Therefore for things like ForumHeader.ss need to now loop over ForumHolder

	// old ForumHeader.ss
	<% if ShowInCategories %>
		<% control Forums %>
	
	// new method
	<% control ForumHolder %>
		<% if ShowInCategories %>
			<% control Forums %>
			
* ReplyForm has been renamed from ReplyForm to PostMessageForm() which is used over all 3 forms now. So you'll have to update your 
themes to use $PostMessageForm.
