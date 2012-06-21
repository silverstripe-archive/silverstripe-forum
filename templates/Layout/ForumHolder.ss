<% include ForumHeader %>

<table class="forum-topics">

	<% if GlobalAnnouncements %>
		<tr class="category">
			<td colspan="4"><% _t('ANNOUNCEMENTS', 'Announcements') %></td>
		</tr>
		<% loop GlobalAnnouncements %>
			<% include ForumHolder_List %>
		<% end_loop %>
	<% end_if %>

	<% if ShowInCategories %>
		<% loop Forums %>
			<tr class="category"><td colspan="4">$Title</td></tr>
			<tr class="category">
				<th><% if Count = 1 %><% _t('FORUM','Forum') %><% else %><% _t('FORUMS', 'Forums') %><% end_if %></th>
				<th><% _t('THREADS','Threads') %></th>
				<th><% _t('POSTS','Posts') %></th>
				<th><% _t('LASTPOST','Last Post') %></th>
			</tr>
			<% loop CategoryForums %>
				<% include ForumHolder_List %>
			<% end_loop %>
		<% end_loop %>
	<% else %>
		<tr class="category">
			<td><% _t('FORUM','Forum') %></td>
			<td><% _t('THREADS','Threads') %></td>
			<td><% _t('POSTS','Posts') %></td>
			<td><% _t('LASTPOST','Last Post') %></td>
		</tr>
		<% loop Forums %>
			<% include ForumHolder_List %>
		<% end_loop %>
	<% end_if %>
</table>

<% include ForumFooter %>