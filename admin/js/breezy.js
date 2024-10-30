jQuery(document).ready(function ($) {
	
	// Notification box to show success/error messages
	function pluginNotification(type, message) {
		$('.notification-wrapper').addClass("active")
		if (type == "success") {
			$('.notification-wrapper .message').text(message)
		} else if (type == "error") {
			$('.notification-wrapper .message').text(message)
		}
	
		$('.notification-wrapper .close-btn').unbind().bind('click', function(){
			$('.notification-wrapper').removeClass("active")
		})	
	}
	
	$('.toggle-sub-plugin').on('click', function (e) {
        e.preventDefault(); 

        var button = $(this);
        var sub_plugin = button.data('plugin');
        var status = button.data('status');
        var nonce = $('#breezy_nonce').val().trim();

        $.ajax({
            url: breezyAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'breezy_plugin_toggle',
                sub_plugin: sub_plugin,
                status: status,
                nonce: nonce 
            },
            success: function (response) {
				
				if (response.data.status === 'activated') {
                    button.text('Deactivate');
                    button.data('status', 'Deactivate');
					button.parents('tr').find('td:nth-child(2)').html('Activate');
                } else {
                    button.text('Activate');
                    button.data('status', 'Activate');
					button.parents('tr').find('td:nth-child(2)').html('Deactivate');
                }
                pluginNotification("success", response.data.message)
     
                location.reload();
            },
            error: function (jqXHR, textStatus, errorThrown) {
				pluginNotification("error", textStatus)
            }
        });
    });
	
	
   
	
});