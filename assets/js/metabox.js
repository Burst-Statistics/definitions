'use strict';

( function( $ ) {

    window.wpdef_metabox = {

        init : function() {
            if ( $('.block-editor__container').length ) {
                window.tagBox && window.tagBox.init();
            }

            wpdef_metabox.post_count                  = wpdef.post_count;
            wpdef_metabox.existing_definitions        = wpdef.existing_definitions;
            wpdef_metabox.animation_time              = -40;
            wpdef_metabox.animation_time_begin        = -20;
            wpdef_metabox.animation_time_end          = 20;
            wpdef_metabox.definition_count_current    = 0;
            wpdef_metabox.definition_count_new        = 0;
            wpdef_metabox.step                        = 0;
            wpdef_metabox.performance_level           = 1;
            wpdef_metabox.typingTimer;
            wpdef_metabox.doneTypingInterval = 800;
            wpdef_metabox.ajaxCallActive = false;
            wpdef_metabox.add_performance_notice();
            wpdef_metabox.performance_notice_ajax();
            wpdef_metabox.set_enable_checkbox_state();
            wpdef_metabox.set_add_tag_state();
            wpdef_metabox.conditionally_show_use_image();

            $(document).on('keyup', '#definitions_box_id input[name="newtag[definitions_title]"]', function(e){
                clearTimeout(wpdef_metabox.typingTimer);
                wpdef_metabox.typingTimer = setTimeout(wpdef_metabox.afterFinishedTyping, wpdef_metabox.doneTypingInterval);
            });

            //on keydown, clear the countdown
            $(document).on('keydown', '#definitions_box_id input[name="newtag[definitions_title]"]', function(e){
                clearTimeout(wpdef_metabox.typingTimer);
            });

            $(document).on('click', '#definitions_box_id input[name="dfn-definition-add"]',  wpdef_metabox.handle_add_tag );
            $(document).on('click', '.remove-tag-icon', wpdef_metabox.handle_remove_tag  );
            $(document).on('change', '#definitions_box_id select[name="dfn-link-type"]', wpdef_metabox.conditionally_show_use_image);
        },

        handle_remove_tag : function () {
            wpdef_metabox.set_add_tag_state();
            wpdef_metabox.remove_definition_from_all_list($(this));
            wpdef_metabox.set_add_definition_state();
            wpdef_metabox.performance_notice_ajax();
            wpdef_metabox.set_enable_checkbox_state();

        },

        handle_add_tag : function () {
            wpdef_metabox.add_definition_to_all_list();
            wpdef_metabox.performance_notice_ajax();
            wpdef_metabox.set_add_tag_state();
            wpdef_metabox.set_add_definition_state();
            wpdef_metabox.set_enable_checkbox_state();

        },

        /**
         *
         */
        afterFinishedTyping : function ( e ) {
            wpdef_metabox.performance_notice_ajax();
            wpdef_metabox.set_add_tag_state();
            wpdef_metabox.set_add_definition_state();
            wpdef_metabox.set_enable_checkbox_state();
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
            var exists = wpdef_metabox.get_existing_definitions_from_list( definitions );
            if ( exists.length > 0 ) {
                if ( exists.length > 1 ) {
                    exists = exists.join(', ');
                    var message = wpdef_metabox.localize_string('already-in-use-plural').replace('{definitions}', exists)
                } else {
                    var message = wpdef_metabox.localize_string('already-in-use-single').replace('{definitions}', exists)
                }
                var notice_html = '<div class="dfn-icon-bullet dfn-icon-bullet-red"></div><span class="dfn-comment">' + message + '</span>';
                $('#definitions_box_id .dfn-definition-add-notice').html(notice_html);
                $('#definitions_box_id input[name="dfn-definition-add"]').prop('disabled', true);
            } else {
                if ( new_definition.indexOf(",") >= 0 ) {
                    var message = wpdef_metabox.localize_string('not-in-use-plural').replace('{definitions}', new_definition)
                } else {
                    var message = wpdef_metabox.localize_string('not-in-use-single').replace('{definitions}', new_definition)
                }
                var notice_html = '<div class="dfn-icon-bullet dfn-icon-bullet-green"></div><span class="dfn-comment">' + message + '</span>';
                $('#definitions_box_id .dfn-definition-add-notice').html(notice_html);
                $('#definitions_box_id input[name="dfn-definition-add"]').prop('disabled', false);
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
                if ( $.inArray(definition, wpdef_metabox.existing_definitions) !== -1 ) {
                    exists.push(definition);
                }
            }
    
            return exists;
        },

        showSaveChanges : function () {
            $('#definitions_box_id .dfn-save-changes').show();
        },

        /**
         * Enable/disable enable checkbox depending on if definition tags were added
         */
        set_enable_checkbox_state : function () {
            var definitions = wpdef_metabox.get_post_definitions_list(false);
            if ( definitions.length > 0 ){
                $('#definitions_box_id .dfn-show-if-term ').removeClass('dfn-disabled');
                $('#definitions_box_id input.dfn-link-type').prop('disabled', false);
            } else {
                $('#definitions_box_id .dfn-show-if-term ').addClass('dfn-disabled');
                $('#definitions_box_id input.dfn-link-type').prop('disabled', true);
            }
        },

        /**
         * Enable/disable enable option to add tags, depending on if tags are already there or not
         */
        set_add_tag_state : function () {
            var current_definitions = wpdef_metabox.get_post_definitions_list(true);
            if (current_definitions.length > 0) {
                console.log("hide");

                $('#definitions_box_id .dfn-definition-add-notice').hide();
                $('#definitions_box_id input[name="dfn-definition-add"]').closest('.ajaxtag').hide();
            } else {
                console.log("show");
                var notice_html = '<div class="dfn-icon-bullet dfn-icon-bullet-red"></div><span class="dfn-comment">' + wpdef_metabox.localize_string('add-term') + '</span>';
                $('#definitions_box_id .dfn-definition-add-notice').html(notice_html).show();
                $('#definitions_box_id input[name="dfn-definition-add"]').closest('.ajaxtag').show();
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

            $('#definitions_box_id .dfn-post-definition-list li').each( function() {
                post_definitions.push( $(this).html().split('&nbsp;').pop() );
            });
            return post_definitions;
        },

        /**
         * @param index
         * @returns Translated string
         */
        localize_string : function ( index ) {
            if (wpdef.strings.hasOwnProperty(index)) {
                return wpdef.strings[index];
            }

            return '';
        },

        /**
         * Add the last added definition to the list of all definitions
         * Set enable checkbox state
         */
        add_definition_to_all_list : function () {
            var add_definition = wpdef_metabox.get_post_definitions_list().pop();
            wpdef_metabox.existing_definitions.push( add_definition );
        },

        /**
         * Remove the this (currently removed) definition to the list of all definitions
         * Set enable checkbox state
         */
        remove_definition_from_all_list : function (obj) {
            var remove_definition = obj.next().html().split(' ').pop();
            wpdef_metabox.existing_definitions = wpdef_metabox.existing_definitions.filter(function(elem){
                return elem != remove_definition;
            });
        },

        /**
         * Count how many times the current definitions are used in all posts
         * Start animation in the performance notice
         */
        performance_notice_ajax : function () {
            var post_definitions = wpdef_metabox.get_post_definitions_list();
            if (wpdef_metabox.ajaxCallActive) {
                return;
            }

            wpdef_metabox.ajaxCallActive = true;
            $.ajax({
                type: "GET",
                url: wpdef.url,
                dataType: 'json',
                data: ({
                    action: 'wpdef_scan_definition_count',
                    definitions: post_definitions,
                    post_id: $("#post_ID").val(),
                }),
                success: function (response) {
                    wpdef_metabox.ajaxCallActive = false;
                    if (response.success) {
                        wpdef_metabox.definition_count_new = response.count;
                        wpdef_metabox.start_animation();
                    }
                }
            });
        },

        /**
         * Start animation in the performance notice
         */
        start_animation : function () {
            if (wpdef_metabox.definition_count_current == wpdef_metabox.definition_count_new ) return;

            wpdef_metabox.animation_time = wpdef_metabox.animation_time_begin;
            wpdef_metabox.step = (wpdef_metabox.definition_count_new - wpdef_metabox.definition_count_current) / 40;
            wpdef_metabox.animate_performance_notice();
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
            var new_performance_level = wpdef_metabox.calculate_performance_level( Math.round(wpdef_metabox.definition_count_current), wpdef_metabox.post_count );
            if ( wpdef_metabox.animation_time != wpdef_metabox.animation_time_end ) {
                wpdef_metabox.animation_time++;
                wpdef_metabox.definition_count_current = Number(wpdef_metabox.definition_count_current) + Number(wpdef_metabox.step);
                if ( wpdef_metabox.performance_level != new_performance_level ) {
                    wpdef_metabox.performance_level = new_performance_level;
                    wpdef_metabox.add_performance_notice();
                    setTimeout(wpdef_metabox.animate_performance_notice, wpdef_metabox.anitation_time_to_wait(wpdef_metabox.animation_time) );
                } else {
                    $('#definitions_box_id .dfn-definition-count').html( Math.round(wpdef_metabox.definition_count_current) );
                    setTimeout(wpdef_metabox.animate_performance_notice, wpdef_metabox.anitation_time_to_wait(wpdef_metabox.animation_time) );
                }
            } else {
                if (wpdef_metabox.performance_level != wpdef_metabox.calculate_performance_level( Math.round(wpdef_metabox.definition_count_current), wpdef_metabox.post_count ) ) {
                    wpdef_metabox.performance_level = new_performance_level;
                    wpdef_metabox.definition_count_current = wpdef_metabox.definition_count_new;
                    wpdef_metabox.add_performance_notice();
                } else {
                    $('#definitions_box_id .dfn-definition-count').val(wpdef_metabox.definition_count_new);
                    wpdef_metabox.definition_count_current = wpdef_metabox.definition_count_new;
                }
            }
        },

        /**
         * Add performance notice to metabox
         */
        add_performance_notice : function () {
            var definitions = wpdef_metabox.get_post_definitions_list();
            if ( definitions.length > 0 ){
                $('#definitions_box_id .dfn-show-if-term ').removeClass('dfn-disabled');
            } else {
                $('#definitions_box_id .dfn-show-if-term ').addClass('dfn-disabled');
            }
            var html = wpdef_metabox.get_performance_notice_html( wpdef_metabox.definition_count_current )
            $('#definitions_box_id .dfn-performance-notice').html( html );
        },

        /**
         * @param definition_count
         * @returns {string} Performance notice according to definition count and post count
         */
        get_performance_notice_html : function ( definition_count ) {
            definition_count = Math.round(definition_count);

            var icon    = "";

            var notice = "<span class='dfn-definition-count-notice'>" + wpdef_metabox.localize_string('terms-in-posts') + "<span>";
            notice = notice.replace('{terms_count}', "<span class='dfn-definition-count'>" + definition_count + "</span>");
            notice = notice.replace('{posts_count}', "" + wpdef_metabox.post_count );

            var warning = "";
            if ( wpdef_metabox.performance_level == 4 ) {
                icon = "<div class='dfn-icon-bullet dfn-icon-bullet-red'></div>";
                warning = "<span class='dfn-comment'>" + wpdef_metabox.localize_string('way-too-many-terms') + " <a href='https://really-simple-plugins.com/internal-linkbuilder/'>" + wpdef_metabox.localize_string('read-more') + "</a><span>";
            }

            if ( wpdef_metabox.performance_level == 3 ) {
                icon = "<div class='dfn-icon-bullet dfn-icon-bullet-orange'></div>";
                warning = "<span class='dfn-comment'>" + wpdef_metabox.localize_string('too-many-terms') + " <a href='https://really-simple-plugins.com/internal-linkbuilder/'>" + wpdef_metabox.localize_string('read-more') + "</a><span>";
            }

            if ( wpdef_metabox.performance_level == 2 ) {
                icon = "<div class='dfn-icon-bullet dfn-icon-bullet-orange'></div>";
                warning = "<span class='dfn-comment'>" + wpdef_metabox.localize_string('few-terms') + " <a href='https://really-simple-plugins.com/internal-linkbuilder/'>" + wpdef_metabox.localize_string('read-more') + "</a><span>";
            }

            if ( wpdef_metabox.performance_level == 1 ) {
                icon = "<div class='dfn-icon-bullet dfn-icon-bullet-green'></div>";
                warning = "<span class='dfn-comment'>" + wpdef_metabox.localize_string('positive-ratio-terms') + " <a href='https://really-simple-plugins.com/internal-linkbuilder/'>" + wpdef_metabox.localize_string('read-more') + "</a><span>";
            }

            if ( wpdef_metabox.performance_level == 0 ) {
                icon = "<div class='dfn-icon-bullet dfn-icon-bullet-red'></div>";
                warning = "<span class='dfn-comment'>" + wpdef_metabox.localize_string('no-terms') + " <a href='https://really-simple-plugins.com/internal-linkbuilder/'>" + wpdef_metabox.localize_string('read-more') + "</a><span>";
            }

            var html = "";

            html += "<div class='dfn-field'>";
            html += icon;
            html += notice;
            html += "</div>";
            html += warning;
            html += "<input type='hidden' class='dfn-definition-ids' value='" + definition_count + "'>";
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
            if ( $('#definitions_box_id select[name="dfn-link-type"]').val()!=='hyperlink' ) {
                $('.dfn-disable-image').closest('.dfn-field').show();
            } else {
                $('.dfn-disable-image').closest('.dfn-field').hide();
            }
        },

    };

}( jQuery ));


jQuery(document).ready(function ($) {
    window.wpdef_metabox.init();
});