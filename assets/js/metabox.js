'use strict';
jQuery(document).ready(function ($) {

    var post_count                  = $('.dfn-post-count').val();
    var animation_time              = -20;
    var definition_count_current    = 0;
    var definition_count_new        = 0;
    var step                        = 0;
    var performance_level           = 1;

    var existing_definitions = $('.dfn-existing-definitions').val().split(',');

    $(document).on('keyup', 'input[name="newtag[definitions_title]"]', update_add_definition_notice_state);

    function update_add_definition_notice_state() {
        var new_definition = $('input[name="newtag[definitions_title]"]').val();
        if ( new_definition.length == 0 ) {
            var notice_html = '<div class="dfn-icon-bullet-invisible"></div><span class="dfn-comment">Add a term to see results</span>';
            $('.dfn-definition-add-notice').html(notice_html);
            return;
        }

        if ( new_definition.indexOf(",") >= 0 ) {
            var new_definitions = new_definition.split(',');
        } else {
            var new_definitions = [new_definition];
        }

        var exists = [];

        for (let i=0; i<new_definitions.length; i++) {
            if ( $.inArray(new_definitions[i], existing_definitions) !== -1 ) {
                exists.push(new_definitions[i]);
                console.log(exists);
            }
            console.log(exists);
        }
        console.log(exists);

        if ( exists.length > 0 ) {
            $('input[name="dfn-definition-add"]').prop('disabled', true);
            var notice_html = '<div class="dfn-icon-bullet-red"></div><span class="dfn-comment">' + exists + ' is already in use. Choose another</span>';
            $('.dfn-definition-add-notice').html(notice_html);
        } else {
            $('input[name="dfn-definition-add"]').prop('disabled', false);
            var notice_html = '<div class="dfn-icon-bullet-green"></div><span class="dfn-comment">' + new_definition + ' has not been used before!</span>';
            $('.dfn-definition-add-notice').html(notice_html);
        }
    }

    add_performance_notice();
    performance_notice_ajax();
    $(document).on('click', 'input[name="dfn-definition-add"]', performance_notice_ajax );
    $(document).on('click', 'span[class="remove-tag-icon"]', performance_notice_ajax );

    $(document).on('click', 'input[name="dfn-definition-add"]', add_definition_to_all_list );
    $(document).on('click', 'span[class="remove-tag-icon"]', remove_definition_from_all_list );

    $(document).on('click', 'input[name="dfn-definition-add"]', update_add_definition_notice_state );
    $(document).on('click', 'span[class="remove-tag-icon"]', update_add_definition_notice_state );

    update_enable_checkbox_state();
    function update_enable_checkbox_state() {
        if ( get_post_definitions_list().length == 0 ) {
            $('input[class="dfn-enable"]').prop('disabled', true);
        } else {
            $('input[class="dfn-enable"]').prop('disabled', false);
        }
    }


    function add_definition_to_all_list() {
        var add_definition = get_post_definitions_list().pop();
        existing_definitions.push( add_definition );
        update_enable_checkbox_state();
    }

    function remove_definition_from_all_list() {
        var remove_definition = $(this).next().html().split(' ').pop();
        existing_definitions = existing_definitions.filter(function(elem){
            return elem != remove_definition;
        });
        update_enable_checkbox_state();
    }


    function performance_notice_ajax() {
        var post_definitions = get_post_definitions_list();
        $.ajax({
            type: "GET",
            url: wpdef.url,
            dataType: 'json',
            data: ({
                action: 'wpdef_scan_definition_count',
                definitions: post_definitions,
            }),
            success: function (response) {
                if (response.success) {
                    definition_count_new = response.count;
                    start_animation();
                }
            }
        });

    }


    function start_animation() {
        if (definition_count_current == definition_count_new) return;
        animation_time = -40;
        step = (definition_count_new - definition_count_current) / 80;
        animate_performance_notice();
    }

    function anitation_time_to_wait(x) {
        return 0.1*x*x + x + 1;
    }

    function animate_performance_notice() {
        if ( animation_time != 40 ) {
            animation_time++;
            definition_count_current += step;
            if (performance_level != calculate_performance_level( Math.round(definition_count_current), post_count ) ) {
                add_performance_notice();
                setTimeout(animate_performance_notice, anitation_time_to_wait(animation_time) );
            } else {
                $('.dfn-definition-count').html( Math.round(definition_count_current) );
                setTimeout(animate_performance_notice, anitation_time_to_wait(animation_time) );
            }
        } else {
            if (performance_level != calculate_performance_level( Math.round(definition_count_current), post_count ) ) {
                definition_count_current = definition_count_new;
                add_performance_notice();
            } else {
                $('.dfn-definition-count').val(definition_count_new);
                definition_count_current = definition_count_new;
            }
        }
    }


    function add_performance_notice() {
        var html = get_performance_notice_html( definition_count_current )
        $('.dfn-performance-notice').html( html );
    }

    
    function get_performance_notice_html( definition_count ) {
        definition_count = Math.round(definition_count);
        var performance_level = calculate_performance_level( definition_count, post_count );

        var icon    = "";
        var notice  = "<span class='dfn-definition-count-notice'><span class='dfn-definition-count'>" + definition_count + "</span> terms in " + post_count + " posts<span>";
        var warning = "";

        if ( performance_level == 3 ) {
            icon = "<div class='dfn-icon-bullet-red'></div>";
            warning = "<span class='dfn-comment'>There are too many terms per post. This might affect resources. Try to be more specific. <a>Read more</a><span>";
        }

        if ( performance_level == 2 ) {
            icon = "<div class='dfn-icon-bullet-orange'></div>";
            warning = "<span class='dfn-comment'>There might too many terms per post. This might affect resources. Try to be more specific. <a>Read more</a><span>";
        }

        if ( performance_level == 1 ) {
            icon = "<div class='dfn-icon-bullet-green'></div>";
            warning = "<span class='dfn-comment'></span>";
        }

        var html = "";

        html += "<div class='dfn-field'>";
        html += icon;
        html += notice;
        html += "</div>";
        html += warning;
        html += "<input type='hidden' class='dfn-definition-ids' value='" + definition_count + "'>";

        return html;
    }


    function calculate_performance_level( definition_count, post_count ) {
        if (definition_count / post_count > 3) return 3;
        if (definition_count / post_count > 2) return 2;
        return 1;
    }


    function get_post_definitions_list() {
        var post_definitions = [];
        $('.dfn-post-definition-list li').each( function() {
            post_definitions.push( $(this).html().split('&nbsp;').pop() );
        });
        return post_definitions;
    }

});