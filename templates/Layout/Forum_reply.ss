<% include ForumHeader %>
	$PostMessageForm
	
	<div id="PreviousPosts">
		<ul id="Posts">
			<% control Posts(DESC) %>
				<li class="$EvenOdd">
					<% include SinglePost %>
				</li>
			<% end_control %>
		</ul>
		<div class="clear"><!-- --></div>
	</div>
	
<% include ForumFooter %>
