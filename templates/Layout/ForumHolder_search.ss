<% include ForumHeader %>
		
	<% if SearchResults %>
		<div class="forumHolderFeatures">
		<table id="TopicList">
			<tr class="rowOne">	
				<td class="pageNumbers">
					<span><strong>Page:</strong></span>
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
			<tr><th>Topic</th><th>Post</th></tr>
			<% control SearchResults %>
			<tr class="$EvenOdd">
				<td class="forumCategory">
					<% control Topic %>
					<a class="topicTitle" href="$Link" title="Go to the $Title topic">$Title</a>
					<% end_control %>
				</td>
				<td>
					<a class="topicTitle" href="$Link">$Title</a>
					<% control Author %>
					<p class="userInfo">by <a href="$Link" title="Click here to view <% if Nickname %>$Nickname<% else %>Anon<% end_if %>&#39;s profile"><% if Nickname %>$Nickname<% else %>Anon<% end_if %></a></p>
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
					<tr><td>There are no results for those word(s)</td></tr>
				</table>
			</div>
		<% end_if %>

<% include ForumFooter %>
