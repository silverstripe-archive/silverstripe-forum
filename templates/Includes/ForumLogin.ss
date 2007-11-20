<div id="RegisterLogin">
	<% if CurrentMember %>
		<p>You're logged in as <% if CurrentMember.Nickname %>$CurrentMember.Nickname<% else %>Anonymous<% end_if %></p>
		<span><a href="Security/logout" title="Click here to log out">log out</a> |
			<a href="ForumMemberProfile/edit" title="Click here to edit your profile">my profile</a>
		</span>
	<% else %>
		<span><a href="$LoginURL" title="Click here to login">Login</a> |
			<a href="Security/lostpassword" title="Click here to retrieve your password">forgot password</a> |
			<a href="ForumMemberProfile/register" title="Click here to register">register</a> |
			<a href="ForumMemberProfile/registerwithopenid" title="Click here to register with OpenID">register with OpenID <img src="sapphire/images/openid-small.gif" alt="OpenID" /></a>
		</span>
	<% end_if %>
</div>
