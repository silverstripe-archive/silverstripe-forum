<div class="userInformation">
	<% control Author %>
		<a class="authorTitle" href="$Link" title="<% _t('GOTOPROFILE','Go to this User\'s Profile') %>">$Nickname</a><br />
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
	<h4><a href="#post$ID">$Title <img src="forum/images/right.png" alt="Link to this post" title="Link to this post" /></a></h4>
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
		<p class="lastEdited"><strong><% _t('LASTEDITED','Last edited:') %></strong> $Updated.Long <% _t('AT') %> $Updated.Time</p>
	<% end_if %>
	<% if Attachments %>
		<div class="attachments">
			<strong><% _t('ATTACHED','Attached Files') %></strong> 
			<ul class="attachmentList">
			<% control Attachments %>
				<li class="attachment">
					<a href="$Link"><img src="$Icon"></a>
					<a href="$Link">$Name</a><br />
					<% if ClassName = "Image" %>$Width x $Height - <% end_if %>$Size
				</li>
			<% end_control %>
			</ul>
		</div>
	<% end_if %>
</div>
