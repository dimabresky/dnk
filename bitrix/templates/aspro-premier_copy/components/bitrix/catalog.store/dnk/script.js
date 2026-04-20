$(document).ready(function(){
    $('.contacts__tabs .tabs .nav-tabs li a').on('click', function () {
        $content = $(this).closest('.contacts__row').find('.contacts__tab-content');

        if ($(this).attr('href') === '#map') {
            $content.addClass('contacts__tab-content--map');
        }
        else {
            $content.removeClass('contacts__tab-content--map');
        }
    });
});