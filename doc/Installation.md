Installation
================================

1. Place this directory in the root of your SilverStripe installation. Make sure it is named forum and not forum-v2 or any other 
	combination

2. Rebuild your database. Visit http://www.yoursite.com/dev/build/ in your browser or via SAKE - sake dev/build flush=1

3. The CMS should now have "Forum Holder" and "Forum" page types in the page type dropdown. By default SilverStripe will create
a couple default forums and a forum holder.

You should make sure each ForumHolder page type only has Forum children and each forum has its parent as a forum holder. Eg not nested in 
another forum. The module supports multiple forum holders each with their own permissions. For more configuration information see 

	/forum/docs/Configuration.md
	