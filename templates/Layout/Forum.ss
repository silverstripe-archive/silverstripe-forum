<div id="Forum">
	<% include ForumHeader %>
	
	<% if Content %><div class="typography">$Content</div><% end_if %>
	<a href="{$Link}starttopic" title="Click here to start a new topic"><img src="forum/images/forum_startTopic.gif" alt="Start new topic" /></a>
	
	<div class="forumHolderFeatures">
		<table id="TopicList">
			<tr>
				<th class="odd">Topic</th>
				<th class="odd">Posts</th>
				<th class="even">Last Post</th>
			</tr>
			<% if Topics %>
				<% control Topics %>
				<tr class="$EvenOdd">
					<td class="topicName">
						<a class="topicTitle" href="$Link" title="Go to the $Title topic">$Title</a>
						<p class="summary">
							<% control Author %>
								<% if Link %>
									<a href="$Link" title="Click here to view <% if Nickname %>$Nickname<% else %>Anon<% end_if %>'s profile"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a>
								<% else %>
									<span>Anon</span>
								<% end_if %>
							<% end_control %>
						</p>
					</td>
					<td class="count">
						$NumPosts
					</td>
					<td class="lastPost">
						<% control LatestPost %>
							<p class="userInfo">$Created.Ago</p>
							<p class="userInfo">by <% control Author %><% if Link %><a href="$Link" title="Click here to view <% if Nickname %>$Nickname<% else %>Anon<% end_if %>'s profile"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a><% else %><span>Anon</span><% end_if %><% end_control %> <a href="$Link#post$ID" title="Go to the first unread post in the '$Title' topic."><img src="forum/images/right.png" alt=""></a></p>
						<% end_control %>
					</td>
				</tr>
				<% end_control %>
			<% else %>
				<tr>
					<td colspan="3" class="forumCategory">There are no topics in this forum, <a href="{$Link}starttopic" title="Click here to start a new topic">click here to start a new topic</a>.</td>
				</tr>
			<% end_if %>
		</table>
	</div>
	
	<% include ForumFooter %>
</div>