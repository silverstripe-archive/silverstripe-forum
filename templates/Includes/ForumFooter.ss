	<% control ForumHolder %>
		<div id="CurrentlyOnline">
			<p>
				<strong><% _t('CURRENTLYON','Currently Online:') %></strong>
		
				<% if CurrentlyOnline %>
					<% control CurrentlyOnline %>
						<% if Link %><a href="$Link" title="<% if Nickname %>$Nickname<% else %>Anon<% end_if %><% _t('ISONLINE',' is online') %>"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a><% else %><span>Anon</span><% end_if %><% if Last %><% else %>,<% end_if %>
					<% end_control %>
				<% else %>
					<span><% _t('NOONLINE','There is nobody online.') %></span>
				<% end_if %>
			</p>
			<p>
				<strong><% _t('LATESTMEMBER','Welcome to our latest member:') %></strong>			
				<% if LatestMembers(1) %>
					<% control LatestMembers(1) %>
						<% if Link %>
							<a href="$Link" <% if Nickname %>title="$Nickname<% _t('ISONLINE') %>"<% end_if %>><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a><% if Last %><% else %>,<% end_if %> 
						<% else %>
							<span>Anon</span><% if Last %><% else %>,<% end_if %> 
						<% end_if %>
					<% end_control %>
				<% end_if %>
			</p>
		</div>
	<% end_control %>
</div>