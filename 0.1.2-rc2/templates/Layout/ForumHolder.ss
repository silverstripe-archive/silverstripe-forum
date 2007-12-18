<% include ForumHeader %>
	<div class="forumHolderFeatures">
		<table id="TopicList">
			<tr>
				<th class="odd">Forum</th>
				<th class="even">Threads</th>
				<th class="odd">Posts</th>
				<th class="even">Last Post</th>
			</tr>
			<% control AllChildren %>
				<% if CheckForumPermissions %>
					<tr class="$EvenOdd">
						<td class="forumCategory odd">
							<a class="topicTitle" href="$Link" title="Go to the $Title.EscapeXML topic">$Title</a>
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
								<a class="topicTitle" href="$Link#post{$ID}" title="Go to latest post in {$Topic.Title.EscapeXML}"><% control Topic %>$Title.LimitCharacters(20)<% end_control %></a>
							<% control Author %>
								<p class="userInfo">by <% if Link %><a href="$Link" title="Click here to view <% if Nickname %>$Nickname.EscapeXML<% else %>Anon<% end_if %>&#39;s profile"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a><% else %><span>Anon</span><% end_if %></p>
							<% end_control %>
								<p class="userInfo">$Created.Ago</p>
							<% end_control %>
						</td>
					</tr>
				<% end_if %>
			<% end_control %>
		</table>
	</div>
<% include ForumFooter %>