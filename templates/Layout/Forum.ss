<div id="Forum">
	<% include ForumHeader %>

	$Content
	<% if ForumPosters = NoOne %>
		<p class="message error"><% _t('READONLYFORUM', 'This Forum is read only. You cannot post replies or start new threads') %></p>
	<% end_if %>
	<% if CheckForumPermissions(post) %>
		<p><a href="{$Link}starttopic" title="<% _t('NEWTOPIC','Click here to start a new topic') %>"><img src="forum/images/forum_startTopic.gif" alt="<% _t('NEWTOPICIMAGE','Start new topic') %>" /></a></p>
	<% end_if %>
	<div class="forumHolderFeatures">
		<table id="TopicList">
			<tr>
				<th class="odd"><% _t('TOPIC','Topic') %></th>
				<th class="odd"><% _t('POSTS','Posts') %></th>
				<th class="even"><% _t('LASTPOST','Last Post') %></th>
			</tr>
			<% if Topics %>
				<% control Topics %>
					<tr class="$EvenOdd">
						<td class="topicName">
							<a class="topicTitle" href="$Link" title="<% sprintf(_t('',"Go to the %s topic"),$Title.EscapeXML) %>">$Title</a>
							<p class="summary">
								<% control Author %>
									<% if Link %>
										<a href="$Link" title="<% _t('CLICKTOUSER','Click here to view') %>"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a>
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
								<p class="userInfo">
									<% _t('BY','by') %> <% control Author %><% if Link %><a href="$Link" title="<% _t('CLICKTOUSER') %> <% if Nickname %>$Nickname.EscapeXML<% else %>Anon<% end_if %><% _t('CLICKTOUSER2') %>"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a><% else %><span>Anon</span><% end_if %><% end_control %> <a href="$Link#post$ID" title="<% sprintf(_t('GOTOFIRSTUNREAD',"Go to the first unread post in the %s topic."),$Title.EscapeXML) %>"><img src="forum/images/right.png" alt=""></a>
								</p> 
							<% end_control %>
						</td>
					</tr>
				<% end_control %>
			<% else %>
				<tr>
					<td colspan="3" class="forumCategory"><% _t('NOTOPICS','There are no topics in this forum, ') %><a href="{$Link}starttopic" title="<% _t('NEWTOPIC') %>"><% _t('NEWTOPICTEXT','click here to start a new topic') %>.</a></td>
				</tr>
			<% end_if %>
		</table>

		<% if Topics.MoreThanOnePage %>
			<div class="typography">
				<p>
					<% if Topics.PrevLink %><a style="float: left" href="$Topics.PrevLink">	&lt; <% _t('PREVLNK','Previous Page') %></a><% end_if %>
					<% if Topics.NextLink %><a style="float: right" href="$Topics.NextLink"><% _t('PREVLNK','Previous Page') %> &gt;</a><% end_if %>
					
					<% control Topics.Pages %>
						<% if CurrentBool %>
							<strong>$PageNum</strong>
						<% else %>
							<a href="$Link">$PageNum</a>
						<% end_if %>
					<% end_control %>
				</p>
			</div>
		<% end_if %>
		
	</div>
	<% include ForumFooter %>
</div>