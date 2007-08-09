	<div id="CurrentlyOnline">
		<h3>Currently Online:</h3>
		<p>
			<% if CurrentlyOnline %>
				<% control CurrentlyOnline %>
					<% if Link %><a href="$Link" title="<% if Nickname %>$Nickname<% else %>Anon<% end_if %> is online"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a><% else %><span>Anon</span><% end_if %><% if Last %><% else %>,<% end_if %>
				<% end_control %>
			<% else %>
				<span>There is nobody online.</span>
			<% end_if %>

		</p>
		<p>
			<span>Welcome to our latest member:</span>			
			<% control LatestMember %>
				<% if Link %><a href="$Link" title="<% if Nickname %>$Nickname<% else %>Anon<% end_if %> is online"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a><% else %><span>Anon</span><% end_if %><% if Last %><% else %>,<% end_if %> 
			<% end_control %>
		</p>
	</div>
</div>