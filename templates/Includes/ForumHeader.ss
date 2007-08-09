<div id="ForumContent">

	<% include ForumLogin %>
	
	<h1 class="genericTitle">$Parent.Title</h1>
	
	<% include Menu2 %>
	
	<div class="forumFeatures">
		
		<div class="featureRight">
			<p class="forumStats">$TotalPosts <strong>Posts</strong> in $TotalTopics <strong>Topics</strong> by $TotalAuthors <strong>members</strong></p>
			
			<span class="jumpTo">Jump to:</span>
			<select onchange="if(this.value) location.href = this.value">
				<option value="">-- Select --</option>
				<% control Forums %>
					<% if CheckForumPermissions %>
						<option value="$Link">$Title</option>
					<% end_if %>
				<% end_control %>
			</select>
			
			<div id="ForumSearch">
				<form action="<% if ForumHolderURLSegment %>{$ForumHolderURLSegment}<% else %>{$URLSegment}<% end_if %>/search/" method="get">
					<fieldset>
						
						<!-- 12/06/07 Modification -->
					
						<!-- span>Search:</span -->
						<input class="text" type="text" name="for" />
						<input class="submit" type="submit" value="Search"/>
						
						<!-- 12/06/07 Modification End -->
						
					</fieldset>
				</form>
			</div>
		</div>
		
		<div class="featureLeft">
			<h2>$Subtitle</h2>
			<% if Abstract %>$Abstract<% else %>$Content<% end_if %>
			<span class="breadcrumbs"><strong>$Breadcrumbs</strong></span>
		</div>	
	
	</div>
	
	<div class="clear"><!-- --></div>