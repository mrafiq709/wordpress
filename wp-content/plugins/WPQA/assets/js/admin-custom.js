(function($) {
	"use strict";
	
	/* Delete post - question - comment - answer */
	jQuery(".delete-question-post,.delete-comment-answer").click(function() {
		var answer = confirm(option_js.confirm_delete);
		if (answer) {
			var this_event = jQuery(this);
			var data_id = this_event.attr("data-id");
			var data_action = this_event.attr("data-action");
			var data_location = this_event.attr("data-location");
			var data_div = this_event.attr("data-div-id");
			var data_nonce = this_event.attr("data-nonce");
			jQuery.post(option_js.ajax_a,"data_id="+data_id+"&data_div="+jQuery("#"+data_div).val()+"&action="+data_action+"&wpqa_delete_nonce="+data_nonce,function (data) {
				window.location = data_location;
			});
		}
		return false;
	});
	
	/* Delete reports */
	jQuery(".reports-delete").click(function () {
		var answer = confirm(option_js.confirm_reports);
		if (answer) {
			var reports_delete = jQuery(this);
			var reports_delete_id = reports_delete.attr("data-attr");
			var reports_nonce = reports_delete.attr("data-nonce");
			reports_delete.css({"visibility":"hidden"});
			jQuery.post(option_js.ajax_a,"action=wpqa_reports_delete&reports_delete_id="+reports_delete_id+"&wpqa_report_nonce="+reports_nonce+"&reports_type="+(reports_delete.hasClass("reports-answers")?"_answer":""),function (result) {
				reports_delete.parent().parent().parent().parent().addClass('removed').fadeOut(function() {
					jQuery(this).remove();
					if (jQuery(".report-table > tr").length == 0 && jQuery(".reports-table-items .tablenav").length == 0) {
						jQuery(".report-table").html('<tr class="no-items"><td class="colspanchange" colspan="4">'+option_js.no_reports+'</td></tr>');
					}
				});
			});
		}
		return false;
	});
	
	/* Delete attachment */
	jQuery(".delete-this-attachment").click(function () {
		var answer = confirm(option_js.confirm_delete_attachment);
		if (answer) {
	    	var delete_attachment = jQuery(this);
	    	var attachment_id = delete_attachment.attr("href");
	    	var post_id = jQuery("#post_ID").val();
	    	var single_attachment = "No";
	    	if (delete_attachment.hasClass("single-attachment")) {
	    		single_attachment = "Yes";
	    	}
	    	jQuery.post(option_js.ajax_a,"action=wpqa_confirm_delete_attachment&attachment_id="+attachment_id+"&post_id="+post_id+"&single_attachment="+single_attachment,function (result) {
	    		delete_attachment.parent().fadeOut(function() {
	    			jQuery(this).remove();
	    		});
	    	});
		}
		return false;
	});
	
})(jQuery);