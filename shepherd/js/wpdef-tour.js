jQuery(document).ready(function($) {
	console.log('rspdef-tour');
	if (!window.Shepherd) return;
    console.log('rspdef-tour 2');

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

	var steps = rspdef_tour.steps;
    console.log('rspdef-tour 3');
	plugins_overview_tour.addStep('rspdef-step-0', {
		classes: 'shepherd-theme-arrows rspdef-plugins-overview-tour-container shepherd-has-cancel-link',
		title: steps[0]['title'],
		text: rspdef_tour.html.replace('{content}', steps[0]['text']),
		buttons: [
            {
                text: rspdef_tour.start,
                classes: 'button button-secondary',
                action: function() {
                    window.location = steps[0]['start_link'];
                }
            },
			{
				text: rspdef_tour.documentation,
				classes: 'button button-primary',
				action: function() {
					window.open(steps[0]['documentation_link'], '_blank');
				}
			},
		],
	});
    console.log('rspdef-tour 4');
	plugins_overview_tour.on('cancel', cancel_tour);

	// start tour when the settings link appears after plugin activation
	if ($('#deactivate-definitions-internal-linkbuilding').length) {
		plugins_overview_tour.start();
	}
    console.log('rspdef-tour 5');
	/**
	 * Cancel tour
	 */

	function cancel_tour() {
		// The tour is either finished or [x] was clicked
		plugins_overview_tour.canceled = true;

		$.ajax({
			type: "POST",
			url: rspdef_tour.ajaxurl,
			dataType: 'json',
			data: ({
				action: 'rspdef_cancel_tour',
				token: rspdef_tour.token,
			})
		});
	};


});
