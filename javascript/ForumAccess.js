ForumPostersGroupHide = function() {
	$('ForumPostersGroupID').style.display = "none";
}

Behaviour.register({
	'#Form_EditForm_ForumPosters_OnlyTheseUsers': {
		onclick: function() {
			$('ForumPostersGroupID').style.display = "block";
		},
		initialize: function() {
			if($('Form_EditForm_ForumPosters_OnlyTheseUsers')) {
				if($('Form_EditForm_ForumPosters_OnlyTheseUsers').checked) $('ForumPostersGroupID').style.display = "block";
				else $('ForumPostersGroupID').style.display = "none";
			}
		}
	},
	'#Form_EditForm_ForumPosters_Anyone': {
		onclick: ForumPostersGroupHide
	},
	'#Form_EditForm_ForumPosters_LoggedInUsers': {
		onclick: ForumPostersGroupHide
	}
});