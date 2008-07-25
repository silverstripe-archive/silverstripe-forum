<div id="ForumContent" class="typography">
	<% include ForumLogin %>
	<% if Parent %><h1>$Parent.Title</h1><% end_if %>
	<div class="clear"><!-- --></div>
	<div class="forumFeatures">
		<div class="featureRight">
			<p class="forumStats">$TotalPosts <strong><% _t('POSTS','Posts') %></strong> <% _t('IN','in') %> $TotalTopics <strong><% _t('TOPICS','Topics') %></strong> <% _t('BY','by') %> $TotalAuthors <strong><% _t('MEMBERS','members') %></strong></p>
			<span class="jumpTo"><% _t('JUMPTO','Jump to:') %></span>
			<select onchange="if(this.value) location.href = this.value">
				<option value=""><% _t('SELECT','Select') %></option>
				<% control Forums %>
					<% if CheckForumPermissions %>
						<option value="$Link">$Title</option>
					<% end_if %>
				<% end_control %>
			</select>
			<div id="ForumSearch">
				<form action="<% if ForumHolderURLSegment %>{$ForumHolderURLSegment}<% else %>{$URLSegment}<% end_if %>/search/" method="get">
					<fieldset>
						<!-- span><% _t('SEARCH','Search:') %></span -->
						<input class="text" type="text" name="for" />
						<input class="submit" type="submit" value="<% _t('SEARCHBUTTON','Search') %>"/>
					</fieldset>
				</form>
			</div>
		</div>
		<div class="featureLeft">
			<h2>$Subtitle</h2>
			<% if Abstract %>$Abstract<% else %>$Content<% end_if %>
			<span class="breadcrumbs"><strong>$Breadcrumbs</strong></span>
		</div>
    <div class="clear"><!-- --></div>
	</div>
