jQuery(document).ready(function($) {
	console.log('wpdef-tour');
	if (!window.Shepherd) return;
    console.log('wpdef-tour 2');

	var plugins_overview_tour = new Shepherd.Tour();
	plugins_overview_tour.options.defaults =
			{
		classes: 'shepherd-theme-arrows',
		scrollTo: true,
		scrollToHandler: function(e) {
			if (typeof ($(e).offset()) !== "undefined" ) {
				$('html, body').animate({
					scrollTop: $(e).offset().top - 200
				}, 1000);
			}
		},
		showCancelLink: true,
		tetherOptions: {
			constraints: [
				{
					to: 'scrollParent',
					attachment: 'together',
					pin: false
				}
			]
		}
	};

	var steps = wpdef_tour.steps;
    console.log('wpdef-tour 3');
	plugins_overview_tour.addStep('wpdef-step-0', {
		classes: 'shepherd-theme-arrows wpdef-plugins-overview-tour-container shepherd-has-cancel-link',
		title: steps[0]['title'],
		text: wpdef_tour.html.replace('{content}', steps[0]['text']),
		buttons: [
            {
                text: wpdef_tour.start,
                classes: 'button button-secondary',
                action: function() {
                    window.location = steps[0]['start_link'];
                }
            },
			{
				text: wpdef_tour.documentation,
				classes: 'button button-primary',
				action: function() {
					window.open(steps[0]['documentation_link'], '_blank');
				}
			},
		],
	});
    console.log('wpdef-tour 4');
	plugins_overview_tour.on('cancel', cancel_tour);

	// start tour when the settings link appears after plugin activation
	if ($('#deactivate-definitions-internal-linkbuilding').length) {
		plugins_overview_tour.start();
	}
    console.log('wpdef-tour 5');
	/**
	 * Cancel tour
	 */

	function cancel_tour() {
		// The tour is either finished or [x] was clicked
		plugins_overview_tour.canceled = true;

		$.ajax({
			type: "POST",
			url: wpdef_tour.ajaxurl,
			dataType: 'json',
			data: ({
				action: 'wpdef_cancel_tour',
				token: wpdef_tour.token,
			})
		});
	};


});
