<% include ForumHeader %>
	<div class="forumHolderFeatures">
		
		<table id="MembersList">
			<tr class="head">
				<th><a href="{$URLSegment}/memberlist/?order=name" title="Order by Name">Member Name:</a></th>
				<th><a href="{$URLSegment}/memberlist/?order=country" title="Order by Country">Country:</a></th>
				<th><a href="{$URLSegment}/memberlist/?order=posts" title="Order by Posts">Forum Posts:</a></th>
				<th><a href="{$URLSegment}/memberlist/?order=joined" title="Order by Joined">Joined:</a></th>
			</tr>
			
			<% control Members %>
				<tr class="$EvenOdd">
					<td><a href="ForumMemberProfile/show/{$ID}" title="View Profile">$Nickname</a></td>
					<td><% if CountryPublic %>$FullCountry<% else %>Private<% end_if %></td>
					<td class="numericField"><% if NumPosts = 0 %><% else %>$NumPosts(false)<% end_if %></td>
					<td><% control Created %>$DayOfMonth $ShortMonth $Year<% end_control %></td>
				</tr>
			<% end_control %>
		</table>
		
		<% if Members.MoreThanOnePage %>
			<div id="ForumMembersPagination">
				<p>
					<% if Members.NotFirstPage %>
						<a class="prev" href="$Members.PrevLink" title="View the previous page">Prev</a>
					<% end_if %>
				
					<span>
				    	<% control Members.PaginationSummary(4) %>
							<% if CurrentBool %>
								$PageNum
							<% else %>
								<% if PageNum %>
									<a href="$Link" title="View page number $PageNum">$PageNum</a>
								<% else %>
									...
								<% end_if %>
							<% end_if %>
						<% end_control %>
					</span>
				
					<% if Members.NotLastPage %>
						<a class="next" href="$Members.NextLink" title="View the next page">Next</a>
					<% end_if %>
				</p>
			</div>
		<% end_if %>
	</div>
	
<% include ForumFooter %>