<div class="userInformation">
	<% control Author %>
		<a class="authorTitle" href="$Link" title="Go to this user's profile">$Nickname</a><br />
	<% if Avatar %>
		<% control Avatar.SetWidth(80) %>
		<img class="userAvatar" src="$URL" alt="avatar" /><br />
		<% end_control %>
	<% else %>
		<img class="userAvatar" src="forum/images/forummember_holder.gif" alt="$Nickname's avatar" /><br />
	<% end_if %>
	<% if ForumRank %><span class="rankingTitle expert">$ForumRank</span><br /><% end_if %>
	<% if NumPosts %><span class="postCount">$NumPosts posts</span><% end_if %>
	<% end_control %>
</div>
<div class="posterContent">
	<h4>$Title</h4>
	<p class="postDate">$Created.Long at $Created.Time</p>
	
	<% if EditLink || DeleteLink %>
		<div class="postModifiers">
			<% if EditLink %>
				$EditLink
			<% end_if %>
			<% if DeleteLink %>
				$DeleteLink
			<% end_if %>
		</div>
	<% end_if %>
		
	
	<div class="postType">
		<p>$Content.Parse(BBCodeParser)</p>
	</div>
				
	<% if Updated %>
		<p class="lastEdited"><strong>Last edited:</strong> $Updated.Long at $Updated.Time</p>
	<% end_if %>

	<% if Attachments %>
		<div class="attachments">
			<strong>Attached Files</strong>
			<ul class="attachmentList">
			<% control Attachments %>
				<li class="attachment">
					<a href="$Link"><img src="$Icon"></a>
					<a href="$Link">$Name</a> (<a href="$DownloadLink">download</a>)<br />
					<% if ClassName = "Image" %>$Width x $Height - <% end_if %>$Size
				</li>
			<% end_control %>
			</ul>
		</div>
	<% end_if %>
</div>
