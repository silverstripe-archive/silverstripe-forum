<div id="ForumContent" class="typography">
	<% include ForumLogin %>
	<div class="clear"><!-- --></div>
	<div class="forumFeatures">
		<div class="featureRight">
			<% if NumPosts %>
				<p class="forumStats">
					$NumPosts 
					<strong><% _t('POSTS','Posts') %></strong> 
					<% _t('IN','in') %> $NumTopics <strong><% _t('TOPICS','Topics') %></strong> 
					<% _t('BY','by') %> $NumAuthors <strong><% _t('MEMBERS','members') %></strong>
				</p>
			<% end_if %>
			<% control ForumHolder %>
				<span class="jumpTo"><% _t('JUMPTO','Jump to:') %></span>
				<select onchange="if(this.value) location.href = this.value">
					<option value=""><% _t('SELECT','Select') %></option>
					<% if ShowInCategories %>
						<% control Forums %>
							<optgroup label="$Title">
								<% control CategoryForums %>
									<% if can(view) %>
										<option value="$Link">$Title</option>
									<% end_if %>
								<% end_control %>
							</optgroup>
						<% end_control %>
					<% else %>
						<% control Forums %>
							<% if can(view) %>
								<option value="$Link">$Title</option>
							<% end_if %>
						<% end_control %>
					<% end_if %>
				</select>
				

				<div id="ForumSearch">
					<form action="$Link(search)" method="get">
						<fieldset>
							<legend><% _t('SEARCHBUTTON','Search') %></legend>
					
							<input class="text" type="text" name="Search" />
							<input class="submit" type="submit" value="<% _t('SEARCHBUTTON','Search') %>"/>
						</fieldset>
					</form>
				</div>

			<% end_control %>
		</div>
		<div class="featureLeft">
			<h2>$HolderSubtitle</h2>
			
			$ForumHolder.HolderAbstract
			
			<p id="ForumBreadCrumbs">$Breadcrumbs</p>
			
			<% if Moderators %><p>Moderators: <% control Moderators %><a href="$Link">$Nickname</a><% if Last %><% else %>, <% end_if %><% end_control %></p><% end_if %>
		</div>	
    <div class="clear"><!-- --></div>
</div>
