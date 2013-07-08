<% include ForumHeader %>

	<% if SearchResults %>
		<div id="forum_search" class="forumHolderFeatures">
			<table class="topicList">
				<tr class="rowOne category">
					<td class="pageNumbers" colspan="3">
						<p>						
							<span class="paginationPageNumber">
								<% _t('ForumHolder_search_ss.PAGE','Page') %>
								<% loop SearchResults.PaginationSummary(10) %>
									<% if CurrentBool %>
										<strong>$PageNum</strong>
									<% else %>
										<% if Link %>
											<a href="$Link">$PageNum</a>
										<% else %>
											&hellip;
										<% end_if %>
									<% end_if %>
								<% end_loop %>
							</span>
							<% if SearchResults.NextLink %><a class="paginationNextLink" style="float: right" href="$SearchResults.NextLink"><% _t('ForumHolder_search_ss.Next', 'Next') %> &gt;</a><% end_if %>
							<% if SearchResults.PrevLink %><a class="paginationPrevLink" style="float: right" href="$SearchResults.PrevLink">&lt; <% _t('ForumHolder_search_ss.PREV','Prev') %></a><% end_if %>
						</p>
					</td>
				</tr>
				<tr>
					<th><% _t('ForumHolder_search_ss.THREAD', 'Thread') %></th>
					<th><% _t('ForumHolder_search_ss.ORDER', 'Order:') %>
						<a href="{$URLSegment}/search/?Search={$Query.ATT}" <% if Order = relevance %>class="current"<% end_if %> title="<% _t('ForumHolder_search_ss.ORDERBYRELEVANCE', 'Order by Relevance. Most relevant first') %>"><% _t('ForumHolder_search_ss.RELEVANCE', 'Relevance') %></a> |
						<a href="{$URLSegment}/search/?Search={$Query.ATT}&amp;order=date" <% if Order = date %>class="current"<% end_if %> title="<% _t('ForumHolder_search_ss.ORDERBYDATE', 'Order by Date. Newest First') %>"><% _t('ForumHolder_search_ss.DATE', 'Date') %></a> |
						<a href="{$URLSegment}/search/?Search={$Query.ATT}&amp;order=title" <% if Order = title %>class="current"<% end_if %>title="<% _t('ForumHolder_search_ss.ORDERBYTITLE', 'Order by Title') %>"><% _t('ForumHolder_search_ss.TITLE', 'Title') %></a>
					</th>
					<th>
						<a href="$RSSLink"><% _t('ForumHolder_search_ss.RSSFEED', 'RSS Feed') %></a>
					</th>
				</tr>
				<% loop SearchResults %>
				<tr class="$EvenOdd">
					<td class="forumCategory" colspan="3">
						<% loop Thread %>
							<a class="topicTitle" href="$Link" title="<% sprintf(_t('Forum.ss.GOTOTHISTOPIC',"Go to the %s topic"),$Title) %>">$Title</a>
						<% end_loop %>
					
						<p>$Content.ContextSummary <span class="dateInfo">$Created.Ago</span></p>
					</td>
				</tr>
				<% end_loop %>
				<tr class="rowOne category">
					<td class="pageNumbers" colspan="3">
						<p>
							<span class="paginationPageNumber">
							<% _t('ForumHolder_search_ss.PAGE','Page') %>
							<% loop SearchResults.PaginationSummary(10) %>
								<% if CurrentBool %>
									<strong>$PageNum</strong>
								<% else %>
									<% if Link %>
										<a href="$Link">$PageNum</a>
									<% else %>
										&hellip;
									<% end_if %>
								<% end_if %>
							<% end_loop %>
							</span>
							<% if SearchResults.NextLink %><a class="paginationNextLink" style="float: right" href="$SearchResults.NextLink"><% _t('ForumHolder_search_ss.Next', 'Next') %> &gt;</a><% end_if %>
							<% if SearchResults.PrevLink %><a class="paginationPrevLink" style="float: right" href="$SearchResults.PrevLink">&lt; <% _t('ForumHolder_search_ss.PREV','Prev' ) %></a><% end_if %>
						</p>
					</td>
				</tr>
			</table>
		</div>
	<% else %>
		<div class="forumHolderFeatures">
			<table id="TopicList">
				<tr><td><% _t('ForumHolder_search_ss.NORESULTS','There are no results for those word(s)') %></td></tr>
			</table>
		</div>
	<% end_if %>

<% include ForumFooter %>