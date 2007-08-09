<% include ForumHeader %>
	
	<% if CurrentMember %>
		<% if ViewMode = Edit %>
			$EditForm
		<% else %>
			$EditForm_Preview
		<% end_if %>
	<% else %>
		<p class="error message">If you would like to post, please <a href="Security/login" title="log in">log in</a> or <a href="ForumMemberProfile/register" title="register">register</a> first.</p>
	<% end_if %>
	
	<% if BBTags %>
		<div id="BBTagsHolder" class="hide">
			<h2 class="bbcodeExamples">Available BB Code tags</h2>
			<ul class="bbcodeExamples">
				<% control BBTags %>
					<li class="$FirstLast">
						<strong>$Title</strong><% if Description %>: $Description<% end_if %><br />
						<strong>Example</strong>: <span class="example">$Example</span>
					</li>
				<% end_control %>
			</ul>
		</div>
	<% end_if %>
	
<% include ForumFooter %>