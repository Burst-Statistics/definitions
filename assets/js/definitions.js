'use strict';
jQuery(document).ready(function ($) {
    var distance = 10;
    var preloaded = [];
    var previews = [];

    wpdef_load_previews();

    //nearness
    $( 'body' ).mousemove( function( event ) {
        if (!$('.wpdef-preview').length) return;

        $('.wpdef-preview').each(function(){

            var element = $(this);
            var left = element.offset().left - distance,
                top = element.offset().top - distance,
                right = left + element.width() + ( 2 * distance ),
                bottom = top + element.height() + ( 2 * distance ),
                x = event.pageX,
                y = event.pageY;

            if ( x > left && x < right && y > top && y < bottom ) {
                var definitions_id = $(this).data('definitions_id');
                var html = wpdef_get_preview(definitions_id);
                if (!$(this).find('.wpdef-preview-content').length ){
                    $(this).append(html);
                }

            }
        });
    } );

    /**
     *
     */

    function wpdef_load_previews(){
        if (!$('.wpdef-preview').length) return;

        $('.wpdef-preview').each(function(){
            var definitions_id = $(this).data('definitions_id');
            if (!preloaded.includes(definitions_id)) preloaded.push(definitions_id);
        });

        $.ajax({
            type: "GET",
            url: wpdef.url,
            dataType: 'json',
            data: ({
                action: 'wpdef_load_preview',
                ids: preloaded,
            }),
            success: function (response) {
                if (response.success) {
                    previews = response.previews;
                    console.log(previews);
                }
            }
        });

    }


    function wpdef_get_preview(id) {
        var html = '';

        previews.forEach(function(preview) {
                if (parseInt(preview.id) === parseInt(id) ) {
                    html = preview.html;
                }
            }
        );
        return html;

    }


});