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
		  			<% if Members.PrevLink %>
		    			<a href="$Members.PrevLink">&lt;&lt; Prev</a> | 
		  			<% end_if %>

		  			<% control Members.Pages %>
		    			<% if CurrentBool %>
		      				<strong>$PageNum</strong> 
		    			<% else %>
		      				<a href="$Link" title="Go to page $PageNum">$PageNum</a> 
		    			<% end_if %>
		  			<% end_control %>

		  			<% if Members.NextLink %>
		    			| <a href="$Members.NextLink">Next &gt;&gt;</a>
		  			<% end_if %>
		  		</p>
			</div>
		<% end_if %>
	</div>
	
<% include ForumFooter %>