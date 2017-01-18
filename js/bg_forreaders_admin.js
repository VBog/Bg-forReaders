jQuery(document).ready( function() {
	jQuery('#bg_forreaders_for_readers').click( function() {
		if(jQuery(this).attr("checked") != 'checked') { 
			jQuery('#bg_forreaders_generate').prop('disabled',true);
		} else {
			jQuery('#bg_forreaders_generate').prop('disabled',false);
		}		
	});
	jQuery('#bg_forreaders_generate').click( function() {
		id = parseInt(jQuery(this).attr("post_id"));
		jQuery.ajax({
			url:		ajaxurl,  
			type:		"POST",  
			dataType:	"html",  
			cache: false,
			async: true,				// асинхронный запрос
			data: {
				action: 'bg_forreaders',
				nonce : bg_forreaders.nonce,
				id: id
			},
			success: function(response) {
				if (response) alert(response);
			},
			error: function(response) {  
				if (response) alert("Error. "+response);
			}
		});
	});
});
