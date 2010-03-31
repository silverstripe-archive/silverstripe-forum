ForumViewersGroupHide = function() {
	$('ForumViewersGroupID').style.display = "none";
}

ForumPostersGroupHide = function() {
	$('ForumPostersGroup').style.display = "none";
}

Behaviour.register({
	'#Form_EditForm_ForumViewers_OnlyTheseUsers': {
		onclick: function() {
			$('ForumViewersGroupID').style.display = "block";
		},
		initialize: function() {
			if($('Form_EditForm_ForumViewers_OnlyTheseUsers')) {
				if($('Form_EditForm_ForumViewers_OnlyTheseUsers').checked) $('ForumViewersGroupID').style.display = "block";
				else $('ForumViewersGroupID').style.display = "none";
			}
		}
	},
	'#Form_EditForm_ForumViewers_Anyone': {
		onclick: ForumViewersGroupHide
	},
	'#Form_EditForm_ForumViewers_LoggedInUsers': {
		onclick: ForumViewersGroupHide
	},
	
	'#Form_EditForm_ForumPosters_OnlyTheseUsers': {
		onclick: function() {
			$('ForumPostersGroup').style.display = "block";
		},
		initialize: function() {
			if($('Form_EditForm_ForumPosters_OnlyTheseUsers')) {
				if($('Form_EditForm_ForumPosters_OnlyTheseUsers').checked) $('ForumPostersGroup').style.display = "block";
				else $('ForumPostersGroup').style.display = "none";
			}
		}
	},
	'#Form_EditForm_ForumPosters_Anyone': {
		onclick: ForumPostersGroupHide
	},
	'#Form_EditForm_ForumPosters_LoggedInUsers': {
		onclick: ForumPostersGroupHide
	},
	
	'#Form_EditForm_ForumRefreshOn': {
		onclick: function() {
			if(this.checked) $('ForumRefreshTime').style.display = 'inline';
			else $('ForumRefreshTime').style.display = 'none';
		},
		initialize: function() {
			if(!this.checked) $('ForumRefreshTime').style.display = 'none';
		}
	}
});