jQuery(document).ready(function($) {
    $('.rating-star').on('click', function(e) {
        e.preventDefault();

        var postId = $(this).data('post-id');
        var rating = $(this).data('rating');

        $.ajax({
            url: ajax_vars.ajaxurl,
            method: 'POST',
            data: {
                action: 'handle_rating',
                post_id: postId,
                rating: rating
            },
            success: function(response) {
                if (response.success) {
                    // alert('Rating updated successfully.');
                } else {
                    // alert('Failed to update rating.');
                }
            }
        });
    });
});
