jQuery(document).ready(function($) {
    $('.jobready-submit').on('click', function() {
        let widget = $(this).closest('.jobready-widget');
        let resume = widget.find('.jobready-resume').val();
        let job = widget.find('.jobready-job').val();
        let apiUrl = widget.data('api-url') || jobreadyData.apiUrl;

        widget.find('.jobready-results').html('Loading...');

        $.ajax({
            url: apiUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                resume: resume,
                job_description: job
            }),
            success: function(res) {
                widget.find('.jobready-results').html(res.recommendations || 'No recommendations found.');
            },
            error: function() {
                widget.find('.jobready-results').html('Error fetching recommendations.');
            }
        });
    });
});
