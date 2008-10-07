<% if CheckForumPermissions %>
	<tr class="$EvenOdd">
		<td class="forumCategory odd">
			<a class="topicTitle" href="$Link" title="<% sprintf(_t('GOTOTHISTOPIC',"Go to the %s topic"),$Title.EscapeXML) %>">$Title</a>
			<% if Content %><span class="summary">$Content.Summary</span><% end_if %>
		</td>
	
		<td class="count even">
			$NumTopics
		</td>
		<td class="count odd">
			$NumPosts
		</td>
		<td class="even lastPost">
			<% control LatestPost %>
				<a class="topicTitle" href="$Link#post{$ID}" title="<% sprintf(_t('GOTOLATEST',"Go to latest post in %s"),$Topic.Title.EscapeXML) %>"><% control Topic %>$Title.LimitCharacters(20)<% end_control %></a>
				<% control Author %>
					<p class="userInfo">by <% if Link %><a href="$Link" title="Click here to view <% if Nickname %>$Nickname.EscapeXML<% else %>Anon<% end_if %>&#39;s profile"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a><% else %><span>Anon</span><% end_if %></p>
				<% end_control %>
				<p class="userInfo">$Created.Ago</p>
			<% end_control %>
		</td>
	</tr>
<% end_if %>