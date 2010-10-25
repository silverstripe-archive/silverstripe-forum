PosterGroupsHide = function() {
	$('PosterGroups').style.display = "none";
}

Behaviour.register({
	'#Form_EditForm_ForumPosters_OnlyTheseUsers': {
		onclick: function() {
			$('PosterGroups').style.display = "block";
		},
		initialize: function() {
			if($('Form_EditForm_ForumPosters_OnlyTheseUsers')) {
				if($('Form_EditForm_ForumPosters_OnlyTheseUsers').checked) $('PosterGroups').style.display = "block";
				else $('PosterGroups').style.display = "none";
			}
		}
	},
	'#Form_EditForm_ForumPosters_Anyone': {
		onclick: PosterGroupsHide
	},
	'#Form_EditForm_ForumPosters_LoggedInUsers': {
		onclick: PosterGroupsHide
	},
	'#Form_EditForm_ForumPosters_NoOne': {
		onclick: PosterGroupsHide
	}
});