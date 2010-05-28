<div id="ForumContent" class="typography">
	<% include ForumLogin %>
	<div class="clear"><!-- --></div>
	<div class="forumFeatures">
		<div class="featureRight">
			<p class="forumStats">$TotalPosts <strong><% _t('POSTS','Posts') %></strong> <% _t('IN','in') %> $TotalTopics <strong><% _t('TOPICS','Topics') %></strong> <% _t('BY','by') %> $TotalAuthors <strong><% _t('MEMBERS','members') %></strong></p>
			<span class="jumpTo"><% _t('JUMPTO','Jump to:') %></span>
			<select onchange="if(this.value) location.href = this.value">
				<option value=""><% _t('SELECT','Select') %></option>
				<% if ShowInCategories %>
					<% control Forums %>
						<optgroup label="$Title">
							<% control Forums %>
								<% if CheckForumPermissions %>
									<option value="$Link">$Title</option>
								<% end_if %>
							<% end_control %>
						</optgroup>
					<% end_control %>
					
				<% else %>
					<% control Forums %>
						<% if CheckForumPermissions %>
							<option value="$Link">$Title</option>
						<% end_if %>
					<% end_control %>
				<% end_if %>
			</select>
			<div id="ForumSearch">
				<form action="<% if ForumHolderLink %>{$ForumHolderLink}<% else %>{$Link}<% end_if %>search/" method="get">
					<fieldset>
						<!-- span><% _t('SEARCH','Search:') %></span -->
						<input class="text" type="text" name="Search" />
						<input class="submit" type="submit" value="<% _t('SEARCHBUTTON','Search') %>"/>
					</fieldset>
				</form>
			</div>
		</div>
		<div class="featureLeft">
			<h2>$HolderSubtitle</h2>
			<% if HolderAbstract %>$HolderAbstract<% else %>$Content<% end_if %>
			<p>$Breadcrumbs</p>
			<% if Moderators %><p>Moderators: <% control Moderators %><a href="$Link">$Nickname</a><% if Last %><% else %>, <% end_if %><% end_control %></p><% end_if %>
		</div>	
    <div class="clear"><!-- --></div>
</div>
