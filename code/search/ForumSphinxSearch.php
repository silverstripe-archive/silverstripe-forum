<?php

/**
 * An extension to the default Forum search to use the {@link Sphinx} class instead
 * of the standard database search.
 *
 * To use Sphinx instead of the built in Search is use:
 *
 * ``ForumHolder::set_search_engine('Sphinx');``
 *
 * @todo Currently this does not index or search forum Titles...
 *
 * @package forum
 */

class ForumSphinxSearch implements ForumSearchProvider {
	
	private $search_cache = array();
	
	// These are classes that *may* be indexed by Sphinx. If they are,
	// we can search for them, and we may need to add extra sphinx
	// properties to them.
	protected static $extra_search_classes = array('Forum', 'Member');

	/**
	 * Get the results
	 *
	 * @return DataObjectSet
	 */
	public function getResults($forumHolderID, $query, $order, $offset = 0, $limit = 10) {
		
		// Work out what sorting method
		switch($order) {
			case 'date':
				$mode = 'fields';
				
				$sortarg = array('Created' => 'DESC');
				break;
			case 'title':
				$mode = 'fields';
				
				$sortarg = array('Title' => 'ASC');
				break;
			default:
				// Sort by relevancy, but add the calculated age band,
				// which will push up more recent content.
				$mode = 'eval';
				$sortarg = "@relevance + _ageband";
				break;
		}
		
		$cachekey = $query.':'.$offset;
		if (!isset($this->search_cache[$cachekey])) {
			// Determine the classes to search. This always include
			// ForumThread and Post, since we decorated them. It also
			// includes Forum and Member if they are decorated, as
			// appropriate.
			$classes = array('ForumThread', 'Post');
			foreach (self::$extra_search_classes as $c) {
				if (Object::has_extension($c, 'SphinxSearchable')) $classes[] = $c;
			}

			$this->search_cache[$cachekey] = SphinxSearch::search($classes, $query, array(
				'start'			=> $offset,
				'pagesize'		=> $limit,
				'sortmode'		=> $mode,
				'sortarg'		=> $sortarg,
				'field_weights'	=> array("Title" => 5, "Content" => 1)
			));
		}
		
		return $this->search_cache[$cachekey]->Matches;
	}
	
	public function load() {
		// Add the SphinxSearchable extension to ForumThread and Post,
		// with an extra computed column that gives an age band. The
		// age bands are based on Created, as follows:
		// _ageband = 10		where object is <30 days old
		// _ageband = 9			where object is 30-90 days old
		// _ageband = 8			where object is 90-180 days old
		// _ageband = 7			where object is 180 days to 1 year old
		// _ageband = 6			older than one year.
		// The age band is calculated so that when added to @relevancy,
		// it can be sorted. This calculation is valid for data that
		// ages like Post and ForumThread, but not for other classes
		// we can search, like Member and Forum. In those cases,
		// we still have to add the extra field _ageband, but we set it
		// to 10 so it's sorted like it's recent.
		DataObject::add_extension('ForumThread', 'SphinxSearchable');
		Object::set_static("ForumThread", "sphinx", array(
			"extra_fields" => array("_ageband" => "if(datediff(now(),Created)<10,10,if(datediff(now(),Created)<90,9,if(datediff(now(),Created)<180,8,if(datediff(now(),Created)<365,7,6)))")
		));
		DataObject::add_extension('Post', 'SphinxSearchable');
		Object::set_static("Post", "sphinx", array(
			"extra_fields" => array("_ageband" => "if(datediff(now(),Created)<10,10,if(datediff(now(),Created)<90,9,if(datediff(now(),Created)<180,8,if(datediff(now(),Created)<365,7,6)))")
		));

		// For classes that might be indexed, add the extra field if they
		// are decorated with SphinxSearchable.
		foreach (self::$extra_search_classes as $c) {
			if (Object::has_extension($c, 'SphinxSearchable')) {
				$conf = Object::uninherited_static($c, "sphinx");
				if (!$conf) $conf = array();
				if (!isset($conf['extra_fields'])) $conf['extra_fields'] = array();
				$conf['extra_fields']['_ageband'] = "10";
				Object::set_static($c, "sphinx", $conf);
			}
		}
	}
}
