<div id="RegisterLogin">
	<% if CurrentMember %>
		<p>You're logged in as <% if CurrentMember.Nickname %>$CurrentMember.Nickname<% else %>Anonymous<% end_if %></p>
		<span><a href="Security/logout" title="Click here to log out">log out</a> |
			<a href="ForumMemberProfile/edit" title="Click here to edit your profile">my profile</a>
		</span>
	<% else %>
		<span><a href="$LoginURL" title="Click here to login">Login</a> |
			<a href="Security/lostpassword" title="Click here to retrieve your password">forgot password</a> |
			<a href="ForumMemberProfile/register" title="Click here to register">register</a><% if OpenIDAvailable %> |
			<a href="ForumMemberProfile/registerwithopenid" title="Click here to register with OpenID">register with OpenID <img src="sapphire/images/openid-small.gif" alt="Click here to register with OpenID" /></a>
	<div id="OpenIDDescription" class="hide">
	  <span><a href="#" id="HideOpenIDdesc">X</a></span>
		<h1>What is OpenID?</h1>
		<p>OpenID is an Internet-wide identity system that allows you to sign in to many websites with a single account.</p>
		<p>With OpenID, your ID becomes a URL (e.g. http://<strong>username</strong>.myopenid.com/). You can get a free OpenID for example from <a href="http://www.myopenid.com">myopenid.com</a>.</p>
		<p>For more information visit the <a href="http://openid.net">official OpenID site.</a></p>
	</div>
			(<a href="#" id="ShowOpenIDdesc">what is OpenID?</a>)<% end_if %>
		</span>
	<% end_if %>
</div>
