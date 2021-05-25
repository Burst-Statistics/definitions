'use strict';

( function( $ ) {

    window.rspdef_metabox = {

        init : function() {
            if ( $('.block-editor__container').length ) {
                window.tagBox && window.tagBox.init();
            }

            rspdef_metabox.post_count                  = rspdef.post_count;
            rspdef_metabox.existing_definitions        = rspdef.existing_definitions;
            rspdef_metabox.animation_time              = -40;
            rspdef_metabox.animation_time_begin        = -20;
            rspdef_metabox.animation_time_end          = 20;
            rspdef_metabox.definition_count_current    = 0;
            rspdef_metabox.definition_count_new        = 0;
            rspdef_metabox.step                        = 0;
            rspdef_metabox.performance_level           = 0;
            rspdef_metabox.typingTimer;
            rspdef_metabox.doneTypingInterval          = 800;
            rspdef_metabox.last_checked_definition;
            rspdef_metabox.ajaxCallActive = false;
            rspdef_metabox.add_performance_notice();
            rspdef_metabox.performance_notice_ajax();
            rspdef_metabox.set_enable_checkbox_state();
            rspdef_metabox.set_add_tag_state();
            rspdef_metabox.conditionally_show_use_image();

            $(document).on('keyup', '#definitions_box_id input[name="newtag[definitions_title]"]', function(e){
                clearTimeout(rspdef_metabox.typingTimer);
                rspdef_metabox.typingTimer = setTimeout(rspdef_metabox.afterFinishedTyping, rspdef_metabox.doneTypingInterval);
            });

            //on keydown, clear the countdown
            $(document).on('keydown', '#definitions_box_id input[name="newtag[definitions_title]"]', function(e){
                clearTimeout(rspdef_metabox.typingTimer);
            });

            $(document).on('click', '#definitions_box_id input[name="rspdef-definition-add"]',  rspdef_metabox.handle_add_tag );
            $(document).on('click', '.remove-tag-icon', rspdef_metabox.handle_remove_tag  );
            $(document).on('change', '#definitions_box_id select[name="rspdef-link-type"]', rspdef_metabox.conditionally_show_use_image);
        },

        handle_remove_tag : function () {
            rspdef_metabox.set_add_tag_state();
            rspdef_metabox.remove_definition_from_all_list($(this));
            rspdef_metabox.set_add_definition_state();
            rspdef_metabox.performance_notice_ajax();
            rspdef_metabox.set_enable_checkbox_state();
        },

        handle_add_tag : function () {
            rspdef_metabox.add_definition_to_all_list();
            rspdef_metabox.performance_notice_ajax();
            rspdef_metabox.set_add_tag_state();
            rspdef_metabox.set_add_definition_state();
            rspdef_metabox.set_enable_checkbox_state();

        },

        afterFinishedTyping : function ( e ) {
            rspdef_metabox.performance_notice_ajax();
            rspdef_metabox.set_add_tag_state();
            rspdef_metabox.set_add_definition_state();
            rspdef_metabox.set_enable_checkbox_state();
        },

        /**
         * Show notice about the definitions you're trying to add
         */
        set_add_definition_state : function () {
            var new_definition = $('#definitions_box_id input[name="newtag[definitions_title]"]').val();
            if ( new_definition.length == 0 ) {
                return;
            }

            var definitions = new_definition.split(',');
            var exists = rspdef_metabox.get_existing_definitions_from_list( definitions );
            if ( exists.length > 0 ) {
                if ( exists.length > 1 ) {
                    exists = exists.join(', ');
                    var message = rspdef_metabox.localize_string('already-in-use-plural').replace('{definitions}', exists)
                } else {
                    var message = rspdef_metabox.localize_string('already-in-use-single').replace('{definitions}', exists)
                }
                var notice_html = '<div class="rspdef-icon-bullet rspdef-icon-bullet-red"></div><span class="rspdef-comment">' + message + '</span>';
                $('#definitions_box_id .rspdef-definition-add-notice').html(notice_html);
                $('#definitions_box_id input[name="rspdef-definition-add"]').prop('disabled', true);
            } else {
                if ( new_definition.indexOf(",") >= 0 ) {
                    var message = rspdef_metabox.localize_string('not-in-use-plural').replace('{definitions}', new_definition)
                } else {
                    var message = rspdef_metabox.localize_string('not-in-use-single').replace('{definitions}', new_definition)
                }
                var notice_html = '<div class="rspdef-icon-bullet rspdef-icon-bullet-green"></div><span class="rspdef-comment">' + message + '</span>';
                $('#definitions_box_id .rspdef-definition-add-notice').html(notice_html);
                $('#definitions_box_id input[name="rspdef-definition-add"]').prop('disabled', false);
            }
        },

        /**
         * @param definitions_list
         * @returns definitions that already exist in the database, from the definitions_list
         */
        get_existing_definitions_from_list : function ( definitions_list ) {
            var exists = [];
            for (let i=0; i<definitions_list.length; i++) {
                var definition = $.trim(definitions_list[i]);
                if ( $.inArray(definition, rspdef_metabox.existing_definitions) !== -1 ) {
                    exists.push(definition);
                }
            }

            return exists;
        },

        showSaveChanges : function () {
            $('#definitions_box_id .rspdef-save-changes').show();
        },

        /**
         * Enable/disable checkbox depending on if definition tags were added
         */
        set_enable_checkbox_state : function () {
            var definitions = rspdef_metabox.get_post_definitions_list(false);
            if ( definitions.length > 0 ){
                $('#definitions_box_id .rspdef-show-if-term ').removeClass('rspdef-disabled');
                $('#definitions_box_id input.rspdef-link-type').prop('disabled', false);
            } else {
                $('#definitions_box_id .rspdef-show-if-term ').addClass('rspdef-disabled');
                $('#definitions_box_id input.rspdef-link-type').prop('disabled', true);
            }
        },

        /**
         * Enable/disable option to add tags, depending on if tags are already there or not
         */
        set_add_tag_state : function () {
            var current_definitions = rspdef_metabox.get_post_definitions_list(true);
            if (current_definitions.length > 0) {
                $('#definitions_box_id .rspdef-definition-add-notice').hide();
                $('#definitions_box_id input[name="rspdef-definition-add"]').closest('.ajaxtag').hide();
            } else {
                var notice_html = '<div class="rspdef-icon-bullet rspdef-icon-bullet-red"></div><span class="rspdef-comment">' + rspdef_metabox.localize_string('add-term') + '</span>';
                $('#definitions_box_id .rspdef-definition-add-notice').html(notice_html).show();
                $('#definitions_box_id input[name="rspdef-definition-add"]').closest('.ajaxtag').show();
            }
        },

        /**
         * @returns All definition tags of this post
         */
        get_post_definitions_list : function (existing_only) {
            existing_only = (typeof (existing_only) !== "undefined" ) ? existing_only : false;
            var post_definitions = [];

            if ( !existing_only ) {
                var new_definition = $('#definitions_box_id input[name="newtag[definitions_title]"]').val();
                if (new_definition.length) {
                    new_definition = new_definition.split(',');
                    for (var key in new_definition) {
                        if (new_definition.hasOwnProperty(key)) {
                            post_definitions.push(new_definition[key]);
                        }
                    }
                }
            }

            $('#definitions_box_id .rspdef-post-definition-list li').each( function() {
                post_definitions.push( $(this).html().split('&nbsp;').pop() );
            });
            return post_definitions;
        },

        /**
         * @param index
         * @returns string translated
         */
        localize_string : function ( index ) {
            if (rspdef.strings.hasOwnProperty(index)) {
                return rspdef.strings[index];
            }

            return '';
        },

        /**
         * Add the last added definition to the list of all definitions
         * Set enable checkbox state
         */
        add_definition_to_all_list : function () {
            var add_definition = rspdef_metabox.get_post_definitions_list().pop();
            rspdef_metabox.existing_definitions.push( add_definition );
        },

        /**
         * Remove the this (currently removed) definition to the list of all definitions
         * Set enable checkbox state
         */
        remove_definition_from_all_list : function (obj) {
            var remove_definition = obj.next().html().split(' ').pop();
            rspdef_metabox.existing_definitions = rspdef_metabox.existing_definitions.filter(function(elem){
                return elem != remove_definition;
            });
        },

        /**
         * Count how many times the current definitions are used in all posts
         * Start animation in the performance notice
         */
        performance_notice_ajax : function () {
            var post_definitions = rspdef_metabox.get_post_definitions_list();

            if ( post_definitions.length==0 ){
                return;
            }

            if (rspdef_metabox.last_checked_definition === post_definitions[0]) {
                return;
            }

            if ( rspdef_metabox.ajaxCallActive ) {
                return;
            }

            $('.rspdef-performance-notice').html(rspdef_metabox.localize_string('retrieving-status'));
            rspdef_metabox.performance_level = -1;



            rspdef_metabox.ajaxCallActive = true;
            $.ajax({
                type: "GET",
                url: rspdef.url,
                dataType: 'json',
                data: ({
                    action: 'rspdef_scan_definition_count',
                    definitions: post_definitions,
                    post_id: $("#post_ID").val(),
                }),
                success: function (response) {
                    rspdef_metabox.last_checked_definition = post_definitions[0];
                    rspdef_metabox.ajaxCallActive = false;
                    if (response.success) {
                        rspdef_metabox.definition_count_new = response.count;
                        rspdef_metabox.start_animation();
                    }
                },
                error:function(response) {
                    console.log("error retrieving count");
                    rspdef_metabox.ajaxCallActive = false;
                }
            });
        },

        /**
         * Start animation in the performance notice
         */
        start_animation : function () {
            if (rspdef_metabox.definition_count_current == rspdef_metabox.definition_count_new ) {
                rspdef_metabox.animation_time = rspdef_metabox.animation_time_end;
            } else {
                rspdef_metabox.animation_time = rspdef_metabox.animation_time_begin;
                rspdef_metabox.step = (rspdef_metabox.definition_count_new - rspdef_metabox.definition_count_current) / 40;
            }

            rspdef_metabox.animate_performance_notice();
        },

        /**
         * @param x Time elapsed in animation
         * @returns double parabolic time to wait for next frame
         */
        anitation_time_to_wait : function (x) {
            return 0.1*x*x + x + 1;
        },

        /**
         * Animate the definition count and notice message in the performance notice
         */
        animate_performance_notice : function () {
            var new_performance_level = rspdef_metabox.calculate_performance_level( Math.round(rspdef_metabox.definition_count_current), rspdef_metabox.post_count );
            if ( rspdef_metabox.animation_time != rspdef_metabox.animation_time_end ) {
                rspdef_metabox.animation_time++;
                rspdef_metabox.definition_count_current = Number(rspdef_metabox.definition_count_current) + Number(rspdef_metabox.step);
                if ( rspdef_metabox.performance_level != new_performance_level ) {
                    rspdef_metabox.performance_level = new_performance_level;
                    rspdef_metabox.add_performance_notice();
                    setTimeout(rspdef_metabox.animate_performance_notice, rspdef_metabox.anitation_time_to_wait(rspdef_metabox.animation_time) );
                } else {
                    $('#definitions_box_id .rspdef-definition-count').html( Math.round(rspdef_metabox.definition_count_current) );
                    setTimeout(rspdef_metabox.animate_performance_notice, rspdef_metabox.anitation_time_to_wait(rspdef_metabox.animation_time) );
                }
            } else {
                if (rspdef_metabox.performance_level != new_performance_level ) {
                    rspdef_metabox.performance_level = new_performance_level;
                    rspdef_metabox.definition_count_current = rspdef_metabox.definition_count_new;
                    rspdef_metabox.add_performance_notice();
                } else {
                    $('#definitions_box_id .rspdef-definition-count').val(rspdef_metabox.definition_count_new);
                    rspdef_metabox.definition_count_current = rspdef_metabox.definition_count_new;
                }
            }
        },

        /**
         * Add performance notice to metabox
         */
        add_performance_notice : function () {
            var definitions = rspdef_metabox.get_post_definitions_list();
            if ( definitions.length > 0 ){
                $('#definitions_box_id .rspdef-show-if-term ').removeClass('rspdef-disabled');
            } else {
                $('#definitions_box_id .rspdef-show-if-term ').addClass('rspdef-disabled');
            }
            var html = rspdef_metabox.get_performance_notice_html( rspdef_metabox.definition_count_current )
            $('#definitions_box_id .rspdef-performance-notice').html( html );
        },

        /**
         * @param definition_count
         * @returns {string} Performance notice according to definition count and post count
         */
        get_performance_notice_html : function ( definition_count ) {
            definition_count = Math.round(definition_count);

            var icon    = "";

            var notice = "<span class='rspdef-definition-count-notice'>" + rspdef_metabox.localize_string('terms-in-posts') + "<span>";
            notice = notice.replace('{terms_count}', "<span class='rspdef-definition-count'>" + definition_count + "</span>");
            notice = notice.replace('{posts_count}', "" + rspdef_metabox.post_count );

            var warning = "";
            if ( rspdef_metabox.performance_level == 4 ) {
                icon = "<div class='rspdef-icon-bullet rspdef-icon-bullet-red'></div>";
                warning = "<span class='rspdef-comment'>" + rspdef_metabox.localize_string('way-too-many-terms') + " <a href='https://really-simple-plugins.com/internal-linkbuilder/'>" + rspdef_metabox.localize_string('read-more') + "</a><span>";
            }

            if ( rspdef_metabox.performance_level == 3 ) {
                icon = "<div class='rspdef-icon-bullet rspdef-icon-bullet-orange'></div>";
                warning = "<span class='rspdef-comment'>" + rspdef_metabox.localize_string('too-many-terms') + " <a href='https://really-simple-plugins.com/internal-linkbuilder/'>" + rspdef_metabox.localize_string('read-more') + "</a><span>";
            }

            if ( rspdef_metabox.performance_level == 2 ) {
                icon = "<div class='rspdef-icon-bullet rspdef-icon-bullet-orange'></div>";
                warning = "<span class='rspdef-comment'>" + rspdef_metabox.localize_string('few-terms') + " <a href='https://really-simple-plugins.com/internal-linkbuilder/'>" + rspdef_metabox.localize_string('read-more') + "</a><span>";
            }

            if ( rspdef_metabox.performance_level == 1 ) {
                icon = "<div class='rspdef-icon-bullet rspdef-icon-bullet-green'></div>";
                warning = "<span class='rspdef-comment'>" + rspdef_metabox.localize_string('positive-ratio-terms') + " <a href='https://really-simple-plugins.com/internal-linkbuilder/'>" + rspdef_metabox.localize_string('read-more') + "</a><span>";
            }

            if ( rspdef_metabox.performance_level == 0 ) {
                icon = "<div class='rspdef-icon-bullet rspdef-icon-bullet-red'></div>";
                warning = "<span class='rspdef-comment'>" + rspdef_metabox.localize_string('no-terms') + " <a href='https://really-simple-plugins.com/internal-linkbuilder/'>" + rspdef_metabox.localize_string('read-more') + "</a><span>";
            }

            var html = "";

            html += "<div class='rspdef-field'>";
            html += icon;
            html += notice;
            html += "</div>";
            html += warning;
            html += "<input type='hidden' class='rspdef-definition-ids' value='" + definition_count + "'>";
            html += "<br><br>";

            return html;
        },

        /**
         *
         * @param definition_count
         * @param post_count
         * @returns {number} Performance level according to definition count and post count
         */
        calculate_performance_level : function ( definition_count, post_count ) {
            if ( (definition_count==0) ) return 0;
            if ( (definition_count<5) ) return 2;
            if ( (definition_count>500) && (definition_count / post_count > 0.5) ) return 4;
            if ( (definition_count>100) && (definition_count / post_count > 0.25) ) return 3;

            return 1;
        },

        /**
         * Conditionally show the use image checkbox field depending if you use the tooltip or not
         */
        conditionally_show_use_image : function () {
            if ( $('#definitions_box_id select[name="rspdef-link-type"]').val()!=='hyperlink' ) {
                $('.rspdef-disable-image').closest('.rspdef-field').show();
            } else {
                $('.rspdef-disable-image').closest('.rspdef-field').hide();
            }
        },

    };

}( jQuery ));


jQuery(document).ready(function ($) {
    window.rspdef_metabox.init();
});