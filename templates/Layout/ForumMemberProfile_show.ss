<% include ForumHeader %>
	<% control Member %>
		<div id="UserProfile">
			<h2><% if Nickname %>$Nickname<% else %>Anon<% end_if %>&#39;s Profile</h2>
			
			<div><label class="left">Nickname:</label> <p class="readonly"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></p></div>
			
			<% if FirstNamePublic %>
			<div><label class="left">First Name:</label> <p class="readonly">$FirstName</p></div>
			<% end_if %>

			<% if SurnamePublic %>
			<div><label class="left">Surname:</label> <p class="readonly">$Surname</p></div>
			<% end_if %>

			<% if EmailPublic %>
			<div><label class="left">Email:</label> <p class="readonly"><a href="mailto:$Email">$Email</a></p></div>
			<% end_if %>

			<% if OccupationPublic %>
			<div><label class="left">Occupation:</label> <p class="readonly">$Occupation</p></div>
			<% end_if %>

			<% if CountryPublic %>
			<div><label class="left">Country:</label> <p class="readonly">$Country</p></div>
			<% end_if %>
			
			<div><label class="left">Number of posts:</label> <p class="readonly">$NumPosts</p></div>
			<div><label class="left">Forum ranking:</label> <% if ForumRank %><p class="readonly">$ForumRank</p><% else %><p>No ranking</p><% end_if %></div>
			
			<% if Avatar %>
				<div><label class="left">Avatar:</label> <p>
				<% control Avatar.SetWidth(80) %>
					<img class="userAvatar" src="$URL" alt="avatar" />
				<% end_control %> </p></div>
			<% else %>
				<div><label class="left">Avatar:</label> <p><img class="userAvatar" src="forum/images/forummember_holder.gif" width="80" alt="<% if Nickname %>$Nickname<% else %>Anon<% end_if %>&#39;s avatar" /></p></div>
			<% end_if %>
		
		</div>
	<% end_control %>
	
	<% if LatestPosts %>
		<div id="MemberLatestPosts">
			<h2>Latest Posts</h2>
			
			<ul>
				<% control LatestPosts %>
					<li><a href="$Link#post$ID">$Title</a> (Last post: $Created.Ago)</li>
				<% end_control %>
			</ul>
		</div>
	<% end_if %>

<% include ForumFooter %>