jQuery(document).ready(function($) {
	
	// Media upload button
    $('#upload_image_button').click(function(e) {
        e.preventDefault();
        
        var imageFrame;
        if (imageFrame) {
            imageFrame.open();
            return;
        }

        imageFrame = wp.media({
            title: 'Select or Upload an Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        imageFrame.on('select', function() {
            var attachment = imageFrame.state().get('selection').first().toJSON();
            $('#marker_image').val(attachment.url); 
        });

        imageFrame.open();
    });
});