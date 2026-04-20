$(document).ready(function(){
    $('.contacts__filter-select .dropdown-select .dropdown-select__list-item a').on('click', function (e) {
        e.preventDefault();

        var $select = $(this).closest('.dropdown-select');

        // set current item
        $select.find('.dropdown-select__list-link--current').removeClass('dropdown-select__list-link--current');
        $(this).addClass('dropdown-select__list-link--current');

        // change title
        var $title = $select.find('.dropdown-select__title');
        $title.find('span').text($(this).text());

        // close
        $title.trigger('click');

        // section id
        var id = $(this).data('id');

        if ($select.hasClass('city')) { 
            // region select
            var $region = $(this).closest('.contacts__panel-wrapper').find('.dropdown-select.region');
            var href = $(this).attr('href');
            
            if (
                !id &&
                $region.length
            ) { 
                $currentRegion = $region.find('.dropdown-select__list-link--current');
                if ($currentRegion.length) { 
                    id = $region.find('.dropdown-select__list-link--current').data('id');
                    var href = $region.find('.dropdown-select__list-link--current').attr('href');
                }
            }

            var last_id = $select.data('id');
            last_id = last_id > 0 ? last_id : 0;
            if (id != last_id) { 
                $select.data('id', id);

                $.ajax({
                    url: href,
                    type: 'POST',
                    data: {SECTION_ID: id}
                }).done(function (html) {
                    var ob = BX.processHTML(html);
                    $('.contacts__ajax_items')[0].innerHTML = ob.HTML;
                    BX.ajax.processScripts(ob.SCRIPT);
                });
            }
        }
    });
        
    $('.contacts__filter-select .dropdown-select.region .dropdown-select__list-item a').on('click', function (e) {
        e.preventDefault();
        
        // region section id
        var id = $(this).data('id');

        // city select
        var $city = $(this).closest('.contacts__panel-wrapper').find('.dropdown-select.city');
        if($city.length){
            var $emptyCity = $city.find('.dropdown-select__list-item a.empty');
            var $currentCity = $city.find('.dropdown-select__list-link--current');
            var $visibleCities = $city.find('.dropdown-select__list-item a[data-parent_id=' + id + ']');

            $city.find('.dropdown-select__list-item a').hide();
            $emptyCity.show();

            if (id) {
               $visibleCities.show();
            }

            var bOpenCitySelect = id && $visibleCities.length > 1;

            if (!bOpenCitySelect) {
                $city.find('.dropdown-select__title').addClass('clicked');
            }
            
            // last region section id
            var last_id = $currentCity.data('parent_id');

            if (
                last_id != id
            ) {
                if ($visibleCities.length == 1) {
                    $visibleCities.first().trigger('click');
                }
                else { 
                    $emptyCity.trigger('click');
                }
            }
            else { 
                $currentCity.trigger('click');
            }

            if (!bOpenCitySelect) {
                $city.find('.dropdown-select__title').removeClass('clicked');
            }
        }
    });

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