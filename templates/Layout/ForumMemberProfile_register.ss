<% include ForumHeader %>
$Content
<div id="UserProfile">
	<% if CurrentMember %>
		<p><% _t('PLEASELOGOUT', 'Please logout before you register') %> - <a href="Security/logout"><% _t('LOGOUT', 'Logout') %></a></p>
	<% else %>
		$RegistrationForm
	<% end_if %>
</div>

<% include ForumFooter %>