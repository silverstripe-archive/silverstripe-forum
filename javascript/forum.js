/**
 * Javascript features for the SilverStripe forum module
 * 
 * @package Forum
 */

(function($) {
$(document).ready(function() {

	/**
	 * Handle Open ID information Box
	 */
	
	$("#ShowOpenIDdesc").click(function() {
		if($("#OpenIDDescription").hasClass("showing")) {
			$("#OpenIDDescription").hide().removeClass("showing");
		} else {
			$("#OpenIDDescription").show().addClass("showing");
		}
		return false;
	});
	
	$("#HideOpenIDdesc").click(function() {
		$("#OpenIDDescription").hide();
		return false;
	});
	
	
	/**
	 * BBCode Show/Hide
	 */
	
	$("#BBCodeHint").click(function() {
		if($("#BBTagsHolder").hasClass("showing")) {
			$(this).text("View Formatting Help");
			$("#BBTagsHolder").hide().removeClass("showing");
		} else {
			$(this).text("Hide Formatting Help");
			$("#BBTagsHolder").show().addClass("showing");
		}
		return false;
	});
	
	/** 
	 * MultiFile Uploader called on Reply and Edit Forms
	 */
	$('#Form_ReplyForm_Attachment').MultiFile(); 
	
})
})(jQuery);