<% include ForumHeader %>
	
	<table class="postHeader">
		<tr class="rowOne">	
			<td class="pageNumbers">
				<span><strong>Page:</strong></span>
				<% control Posts.Pages %>
					<% if CurrentBool %>
						<span><strong>$PageNum</strong></span>
					<% else %>
						<a href="$Link">$PageNum</a>
					<% end_if %>
					<% if Last %><% else %>,<% end_if %>
				<% end_control %>
			</td>
			<td class="gotoButtonEnd" >
				<a href="#Footer" title="Click here to go the end of this post">go to end</a>	
			</td>
			<td class="replyButton">
				<a href="$ReplyLink" title="Click here to reply to this topic">Reply</a>
			</td>
			<td class="viewOptions">
				$FlatThreadedDropdown
			</td>
		</tr>
		
		<tr class="rowTwo">
			<td class="author">
				<span>Author</span>				
			</td>
			<td class="topicTitle">
				<span><strong>Topic:</strong> $Post.Title</span>
			</td>
			<td class="noOfReads">
				<span><strong>$Post.NumViews views</strong></span>
			</td>
		</tr>
	</table>
	
	<ul id="Posts">
		<% control Posts %>
			<li id="post{$ID}" class="$EvenOdd">
				<% include SinglePost %>
			</li>
		<% end_control %>
	</ul>

	<table class="postHeader">
		
		<tr class="rowTwo">
			<td class="author">&nbsp;</td>
			<td class="topicTitle">&nbsp;</td>
			<td class="noOfReads">
				<span><strong>$Post.NumViews views</strong></span>
			</td>
		</tr>
		
		<tr class="rowOne">	
			<td class="pageNumbers">
				<% if Posts.MoreThanOnePage %>
					<% if Posts.NotFirstPage %>
						<a class="prev" href="$Posts.PrevLink" title="View the previous page">Prev</a>
					<% end_if %>
				<% end_if %>
			</td>
			<td class="gotoButtonTop" >
				<a href="#Header" title="Click here to go the top of this post">go to top</a>	
			</td>
			<td class="replyButton">
				<a href="$ReplyLink" title="Click here to reply to this topic">Reply</a>
				<% if Posts.MoreThanOnePage %>
					<% if Posts.NotLastPage %>
						<a class="next" href="$Posts.NextLink" title="View the next page">Next &gt</a>
					<% end_if %>
				<% end_if %>
			</td>
			<td class="viewOptions">
				$FlatThreadedDropdown
			</td>
		</tr>
	</table>

<% include ForumFooter %>