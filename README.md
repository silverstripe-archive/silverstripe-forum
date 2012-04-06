# Forum Module

## Maintainer Contact

Sean Harvey (Nickname: sharvey, halkyon) <sean (at) silverstripe (dot) com>
Will Rossiter (Nickname: wrossiter, willr) <will (at) silverstripe (dot) com>

## Requirements

 * SilverStripe 2.4
 * tagfield for adding moderators (https://github.com/chillu/silverstripe-tagfield)

## Features

The forum module contains many features which aren't turned on by default or are configurable in the CMS. For a full list of configuration
options see /forum/docs/Configuration.md

* Allows multiple forum holders which can have multiple forums, each with separate security permissions that can be adjusted inside the CMS
* Forums can be grouped in categories
* RSS feeds for each forum, overall and search results
* Member profiles showing recent posts
* Write Posts in BBCode and attach multiple files to a post
* Email Topic subscription (subscribe to a topic and get an email when someone posts something new)
* Sticky Posts (both localized to a forum or an entire forum holder)
* Forum Moderators. Give specific people access to editing / deleting posts. Customizable on a forum by forum level
* Support for smilies in posts.
* Gravatar support. 
* Supports custom signatures.
* View Counter for posts.
* Forbidden Word Check
* Reports. 
  - Includes recent members, highest ranking members, popular posts, threads and forums
	- CMS Reports include: total people joined this month, total posts this month


## Installation Instructions

1. Place this directory in the root of your SilverStripe installation. Make sure it is named forum and not forum-v2 or any other 
  combination

2. Rebuild your database. Visit http://www.yoursite.com/dev/build/ in your browser or via SAKE - sake dev/build flush=1

3. The CMS should now have "Forum Holder" and "Forum" page types in the page type dropdown. By default SilverStripe will create
a couple default forums and a forum holder.

You should make sure each ForumHolder page type only has Forum children and each forum has its parent as a forum holder. Eg not nested in 
another forum. The module supports multiple forum holders each with their own permissions. For more configuration information see 

## Upgrading

To help with the transition between early versions of the forum module and the latest version we have included migration scripts in 
forum/code/migration. 

It is recommended that you __backup your database__ before upgrading the module and running these scripts.

To run these migration tasks you can do it either via the web browser 

  http://www.yoursite.com/dev/tasks/ForumMigrationTask
	
Or via the CLI / Sake interface
	
	sake /dev/tasks/ForumMigrationTask
	
### Upgrading from 0.* to 0.5

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


## Configuration

A number of options are configurable in the forum module. Most are in the CMS and can be customized on a per forum holder basis
which are configurable from the forum holder page.

An example of options which are configurable in the CMS include 

* Show In Categories - Group multiple forums into a 'category'. Eg Modules might be a category and Ecommerce, Forum would be forums in that category
* Display Signatures - Allow people to attach signatures to their posts
* Forbidden Words - List of words which will be removed from posts
* Viewers - set who can view this forum 
* Posters - set who can post to this forum
  
	