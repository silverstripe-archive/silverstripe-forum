<div id="RegisterLogin">
	<% if CurrentMember %>
		<p>You're logged in as <% if CurrentMember.Nickname %>$CurrentMember.Nickname<% else %>Anon<% end_if %></p>
		<span><a href="Security/logout" title="Click here to log out">log out</a> | <a href="ForumMemberProfile/edit" title="Click here to edit your profile">my profile</a></span>
	<% else %>
		<form $LoginForm.FormAttributes>
			<fieldset>
				<% if LoginForm.Message %>
					<p>$LoginForm.Message</p>
				<% end_if %>
			
				<input id="email" class="text" name="Email" value="Email" type="text" onfocus="if(this.value == 'Email') this.value = ''" />
				<input id="password" class="text" name="Password" type="password" value="Password" onfocus="if(this.type == 'text') { this.type = 'password'; this.value = ''; }"/>
				<input class="submit" type="submit" name="action_dologin" value="Login" />
				<input class="rememberCheckbox" name="Remember" type="checkbox" /><span>Remember me?</span>
				<span><a href="Security/lostpassword" title="Click here to retrieve your password">forgot password</a> | <a href="ForumMemberProfile/register">register</a></span>
			</fieldset>
		</form>

	<% end_if %>
</div>
