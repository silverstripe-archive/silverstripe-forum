Behaviour.register({
	'a.deletelink': {
		onclick: function() {
			if(this.id == "firstPost") {
			  if(!confirm("Are you sure you wish to delete this thread?\nNote: This will delete ALL posts in this thread.")) return false;
			} else {
			  if(!confirm("Are you sure you wish to delete this post?")) return false;
			}
		}
	}
});