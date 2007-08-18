<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
	<head>
		<% base_tag %>
		<title>File Attachment Iframe</title>
	</head>

	<body>
		$UploadForm
		
		<% if Attachments %>
		<div class="attachments">
			<strong>Attached Files:</strong>
			<% control Attachments %>
				<div>
					<a href="$Link" target="_blank">$Name</a> - $Size
				</div>
			<% end_control %>
		</div>
	<% end_if %>
	</body>
</html>