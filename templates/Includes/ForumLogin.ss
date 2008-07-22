<div id="RegisterLogin">
	<% if CurrentMember %>
		<p>Logged in as <% if CurrentMember.Nickname %>$CurrentMember.Nickname<% else %>Anonymous<% end_if %> | <a href="Security/logout" title="Click here to log out">Log Out</a> | <a href="ForumMemberProfile/edit" title="Click here to edit your profile">My Profile</a></p>
	<% else %>
		<p>
			<a href="$LoginURL" title="Click here to login">Login</a> |
			<a href="Security/lostpassword" title="Click here to retrieve your password">Forgot Password</a> |
			<a href="ForumMemberProfile/register" title="Click here to register">Register</a>
			<% if OpenIDAvailable %> |
				<a href="ForumMemberProfile/registerwithopenid" title="Click here to register with OpenID">Register with OpenID <img src="sapphire/images/openid-small.gif" alt="Click here to register with OpenID" /></a>
				(<a href="#" id="ShowOpenIDdesc">what is OpenID?</a>)
			<% end_if %>
		</p>
		<div id="OpenIDDescription" class="hide">
	  		<span><a href="#" id="HideOpenIDdesc">X</a></span>
			<h1>What is OpenID?</h1>
			<p>OpenID is an Internet-wide identity system that allows you to sign in to many websites with a single account.</p>
			<p>With OpenID, your ID becomes a URL (e.g. http://<strong>username</strong>.myopenid.com/). <a href="http://openid.net/get/">Find out how to get your own OpenID</a> for free (it's probable that you have already one).</p>
			<p>For more information visit the <a href="http://openid.net">official OpenID site.</a></p>
		</div>
	<% end_if %>
</div>
