<% include ForumHeader %>
	
	<div id="TopicTree">
		<div id="Root">
	
			<% if ViewMode = Edit %>
				$ReplyForm
			<% else %>
				$ReplyForm_Preview
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

		</div>
	</div>

<% include ForumFooter %>