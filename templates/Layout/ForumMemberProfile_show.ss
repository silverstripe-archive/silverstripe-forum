<% include ForumHeader %>
	<% control Member %>
		<div id="UserProfile">
			<h2><% if Nickname %>$Nickname<% else %>Anon<% end_if %>&#39;s <% _t('PROFILE','Profile') %></h2>
			<div><label class="left"><% _t('NICKNAME','Nickname') %>:</label> <p class="readonly"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></p></div>
			<% if FirstNamePublic %>
			<div><label class="left"><% _t('FIRSTNAME','First Name') %>:</label> <p class="readonly">$FirstName</p></div>
			<% end_if %>
			<% if SurnamePublic %>
			<div><label class="left"><% _t('SURNAME','Surname') %>:</label> <p class="readonly">$Surname</p></div>
			<% end_if %>
			<% if EmailPublic %>
			<div><label class="left"><% _t('EMAIL','Email') %>:</label> <p class="readonly"><a href="mailto:$Email">$Email</a></p></div>
			<% end_if %>
			<% if OccupationPublic %>
			<div><label class="left"><% _t('OCCUPATION','Occupation') %>:</label> <p class="readonly">$Occupation</p></div>
			<% end_if %>
			<% if CountryPublic %>
			<div><label class="left"><% _t('COUNTRY','Country') %>:</label> <p class="readonly">$Country</p></div>
			<% end_if %>
			<div><label class="left"><% _t('POSTNO','Number of posts') %>:</label> <p class="readonly">$NumPosts</p></div>
			<div><label class="left"><% _t('FORUMRANK','Forum ranking') %>:</label> <% if ForumRank %><p class="readonly">$ForumRank</p><% else %><p><% _t('NORANK','No ranking') %></p><% end_if %></div>
			<% if Avatar %>
				<div><label class="left"><% _t('AVATAR','Avatar') %>:</label> <p>
				<% control Avatar.SetWidth(80) %>
					<img class="userAvatar" src="$URL" alt="<% _t('AVATAR') %>" />
				<% end_control %> </p></div>
			<% else %>
				<div><label class="left"><% _t('AVATAR') %>:</label> <p><img class="userAvatar" src="forum/images/forummember_holder.gif" width="80" alt="<% if Nickname %>$Nickname<% else %>Anon<% end_if %><% _t('USERSAVATAR','&#39;s avatar') %>" /></p></div>
			<% end_if %>
		</div>
	<% end_control %>
	<% if LatestPosts %>
		<div id="MemberLatestPosts">
			<h2><% _t('LATESTPOSTS','Latest Posts') %></h2>
			<ul>
				<% control LatestPosts %>
					<li><a href="$Link#post$ID">$Title</a> (<% sprintf(_t('LASTPOST',"Last post: %s "),$Created.Ago) %>)</li>
				<% end_control %>
			</ul>
		</div>
	<% end_if %>
<% include ForumFooter %>