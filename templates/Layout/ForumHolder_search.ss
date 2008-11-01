<% include ForumHeader %>
	<% if SearchResults %>
		<div class="forumHolderFeatures">
		<table id="TopicList">
			<tr class="rowOne">
				<td class="pageNumbers">
					<span><strong><% _t('PAGE','Page:') %></strong></span>
					<% control SearchResults.Pages %>
						<% if CurrentBool %>
							<span><strong>$PageNum</strong></span>
						<% else %>
							<a href="$Link">$PageNum</a>
						<% end_if %>
						<% if Last %><% else %>,<% end_if %>
					<% end_control %>
				</td>
			</tr>
			<tr>
				<th><% _t('TOPIC','Topic') %></th>
				<th><% _t('POST','Post') %></th>
			</tr>
			<% control SearchResults %>
			<tr class="$EvenOdd">
				<td class="forumCategory">
					<% control Topic %>
					<a class="topicTitle" href="$Link" title="<% sprintf(_t('Forum.ss.GOTOTHISTOPIC',"Go to the %s topic"),$Title.EscapeXML) %>">$Title</a>
					<% end_control %>
				</td>
				<td>
					<a class="topicTitle" href="$Link">$Title</a>
					<% control Author %>
					<p class="userInfo"><% _t('BY','by') %> <a href="$Link" title="<% _t('CLICKTOUSER','Click here to view') %> <% if Nickname %>$Nickname.EscapeXML<% else %>Anon<% end_if %><% _t('CLICKTOUSER2','&#39;s profile') %>"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a></p>
					<% end_control %>
					<p class="userInfo">$Created.Nice</p>
				</td>
			</tr>
			<% end_control %>
		</table>
		</div>
		<% else %>
			<div class="forumHolderFeatures">
				<table id="TopicList">
					<tr><td><% _t('NORESULTS','There are no results for those word(s)') %></td></tr>
				</table>
			</div>
		<% end_if %>
<% include ForumFooter %>
