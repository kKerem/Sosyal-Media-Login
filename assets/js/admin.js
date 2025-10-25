jQuery(document).ready(function($) {
    // Provider toggle functionality
    $('.provider-toggle').on('change', function() {
        var provider = $(this).data('provider');
        var fieldsContainer = $('#' + provider + '_fields');
        
        if ($(this).is(':checked')) {
            fieldsContainer.slideDown(300);
        } else {
            fieldsContainer.slideUp(300);
        }
    });

    // Simple form validation
    $('form').on('submit', function(e) {
        var hasEnabledProvider = false;
        $('.provider-toggle:checked').each(function() {
            hasEnabledProvider = true;
        });

        if (!hasEnabledProvider) {
            e.preventDefault();
            alert('En az bir sağlayıcıyı aktif etmeniz gerekiyor.');
        }
    });
});