<?php

/**
 * Migration Helper for Forum 0.2 to Forum 0.3
 *
 * @package forum
 */

class ForumMigrationTask extends BuildTask {

	protected $title = "Forum Database Migration";

	protected $description = "Upgrades your 0.2 forum version to the 0.3 structure";
	
	function run($request) {
		set_time_limit(0);

		// check to see if this has been run before. If it has then we will have already
		// have removed the parentID field
		$checkForMigration = DB::query("SHOW COLUMNS FROM \"Post\"")->column();
		
		if(!in_array('ParentID', $checkForMigration)) {
			echo "Script has already ran. You can only run the migration script once.\n";
			
			return false;
		}

		// go through all the posts with a parent ID = 0 and create the new thread objects
		$oldThreads = DB::query("SELECT * FROM \"Post\" WHERE \"ParentID\" = '0'");
		
		if($oldThreads) {
			$holder = DataObject::get_one("ForumHolder");
			if(!$holder) return user_error('No Forum Holder Found', E_USER_ERROR);
			
			$failbackForum = new Forum();
			$failbackForum->Title = "Unimported Threads";
			$failbackForum->ParentID = $holder->ID;
			$failbackForum->write();
			
			$needsFailback = false;
			
			$totalThreadsSuccessfulCount = 0;
			$totalThreadsErroredCount = 0;
			
			while($oldThread = $oldThreads->nextRecord()) {
				$hasError = false;
				
				$thread = new ForumThread();

				if(isset($oldThread['Title'])) {
					$thread->Title = $oldThread['Title'];
				}
				else {
					$hasError = true;
					$thread->Title = "Question";
				}
				$thread->NumViews = (isset($oldThread['NumViews'])) ? $oldThread['NumViews'] : 0;
				$thread->IsSticky = (isset($oldThread['IsSticky'])) ? $oldThread['IsSticky'] : false;
				$thread->IsReadOnly = (isset($oldThread['IsReadOnly'])) ? $oldThread['IsReadOnly'] : false;
				$thread->IsGlobalSticky = (isset($oldThread['IsGlobalSticky'])) ? $oldThread['IsGlobalSticky'] : false;
				
				if(isset($oldThread['ForumID'])) {
					$thread->ForumID = $oldThread['ForumID'];
				} 
				else {
					$hasError = true;
					$needsFailback = true;
					$thread->ForumID = $failbackForum->ID;
				}

				$thread->write();
				echo "Converted Thread: $thread->ID - $thread->Title. \n";	
				
				// find all children of the old post and redirect them to here
				$children = DataObject::get('Post', "\"TopicID\" = '". $oldThread['ID'] ."'");
				
				if($children) {
					foreach($children as $child) {
						$child->ThreadID = $thread->ID;
						$child->ForumID = $thread->ForumID;
						$child->write();
					}
				}
				
				if(!$hasError) {
					$totalThreadsSuccessfulCount++;
				}
				else {
					$totalThreadsErroredCount++;
				}
			}
		}
		echo "Converted $totalThreadsSuccessfulCount threads. Could not import $totalThreadsErroredCount threads.<br />";
		
		if(!$needsFailback) $failbackForum->delete();
		
		else {
			echo "Incorrectly imported threads are available to self moderate at <a href='". $failbackForum->Link() ."'>here</a><br />";
		}

		// transfer subscriptions
		// was a rename table but mysql had locking issues.
		$subscriptions = DB::query("SELECT * FROM \"Post_Subscription\"");
		$subCount = 0;
		if($subscriptions) {
			while($sub = $subscriptions->nextRecord()) {
				// don't import really odd data
				if(isset($sub['TopicID']) && isset($sub['MemberID'])) {
					$subCount++;
					
					$threadSub = new ForumThread_Subscription();
					$threadSub->ThreadID = $sub['TopicID'];
					$threadSub->MemberID = $sub['MemberID'];
					$threadSub->write();
				}
			}
		}
		
		echo "Transferred $subCount Thread Subscriptions<br />";
		
		// Update the permissions on the forums. The Posters, Viewers have changed from a int field
		// to an actual modelled relationship
		$forums = DataObject::get('Forum');
		if($forums) {
			foreach($forums as $forum) {
				$forum->ForumPostersGroupID = DB::query("SELECT \"ForumPostersGroup\" FROM \"Forum\" WHERE \"ID\" = '$forum->ID'")->value();
				$forum->ForumViewersGroupID = DB::query("SELECT \"ForumViewersGroup\" FROM \"Forum\" WHERE \"ID\" = '$forum->ID'")->value();
				
				$forum->write();
			}
		}
		
		// cleanup task. Delete old columns which are hanging round
		DB::dontRequireField('Post', 'ParentID');
		DB::dontRequireField('Post', 'TopicID');
		
		DB::dontRequireField('Post', 'Title');
		DB::dontRequireField('Post', 'NumViews');
		DB::dontRequireField('Post', 'IsSticky');
		DB::dontRequireField('Post', 'IsReadOnly');
		DB::dontRequireField('Post', 'IsGlobalSticky');
		
		DB::dontRequireTable('Post_Subscription');
		
		DB::dontRequireField('Forum', 'ForumViewersGroup');
		DB::dontRequireField("Forum", 'ForumPostersGroup');
		
		echo "Renamed old data columns in Post and removed Post_Subscription table <br />";
		
		echo "Finished<br />";
	}

}
