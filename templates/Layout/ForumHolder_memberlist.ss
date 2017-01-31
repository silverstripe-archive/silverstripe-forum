<% include ForumHeader %>
	<div class="forumHolderFeatures">
		
		<table id="MembersList">
			<tr class="head">
				<th><a href="{$Link}memberlist/?order=name" title="<% _t('ORDERBYNAME', 'Order by Name') %>"><% _t('MEMBERNAME', 'Member Name') %>:</a></th>
				<th><a href="{$Link}memberlist/?order=country" title="<% _t('ORDERBYCOUNTRY', 'Order by Country') %>"><% _t('COUNTRY', 'Country') %>:</a></th>
				<th><a href="{$Link}memberlist/?order=posts" title="<% _t('ORDERBYPOSTS', 'Order by Posts') %>"><% _t('FORUMPOSTS', 'Forum Posts') %>:</a></th>
				<th><a href="{$Link}memberlist/?order=joined" title="<% _t('ORDERBYJOINED', 'Order by Joined') %>"><% _t('JOINED', 'Joined') %>:</a></th>
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
						<a class="prev" href="$Members.PrevLink" title="View the previous page"><% _t('PREV', 'Prev') %></a>
					<% end_if %>
				
					<span>
				    	<% control Members.PaginationSummary(4) %>
							<% if CurrentBool %>
								$PageNum
							<% else %>
								<% if PageNum %>
									<a href="$Link">$PageNum</a>
								<% else %>
									...
								<% end_if %>
							<% end_if %>
						<% end_control %>
					</span>
				
					<% if Members.NotLastPage %>
						<a class="next" href="$Members.NextLink" title="View the next page"><% _t('NEXT', 'Next') %></a>
					<% end_if %>
				</p>
			</div>
		<% end_if %>
	</div>
	
<% include ForumFooter %>