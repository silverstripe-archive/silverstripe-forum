# Installation

##Composer (recommended)

Run the following [composer](http://doc.silverstripe.org/framework/en/installation/composer) command in a terminal

```
    composer require silverstripe/forum dev-master
```

Alternatively, you can edit the projects composer.json file to require the module dependency.

```
    "require": {
		"silverstripe/forum": "dev-master"
	}
```

Run a composer update in your terminal to get the files and automatically put them in the correct place.

```
    composer update
```

Rebuild your database (see below).

## Manual directory placement

Place this directory in the root of your SilverStripe installation. 

Make sure it is named forum and not forum-v2 or any other combination.

Rebuild your database (see below).


## Rebuild database

Visit http://www.yoursite.com/dev/build/ in your browser or via the SilverStripe command line tool, [sake](http://doc.silverstripe.org/framework/en/topics/commandline)

```
	sake dev/build flush=1
```

The CMS should now have "Forum Holder" and "Forum" page types in the page type dropdown. By default SilverStripe will create
a default forum and a forum holder.

You should make sure each ForumHolder page type only has Forum children and each Forum has it's parent as a forum holder. Eg not nested in another forum. 

The module supports multiple Forum Holders, each with their own permissions and multiple Forums.


For more information on setting up the forums see the [configuration documentation](Configuration.md)
	
