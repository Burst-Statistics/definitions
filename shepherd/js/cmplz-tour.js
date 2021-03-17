jQuery(document).ready(function($) {
	if (!window.Shepherd) return;

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

	var steps = cmplz_tour.steps;

	plugins_overview_tour.addStep('cmplz-step-0', {
		classes: 'shepherd-theme-arrows cmplz-plugins-overview-tour-container shepherd-has-cancel-link',
		title: steps[0]['title'],
		text: cmplz_tour.html.replace('{content}', steps[0]['text']),
		buttons: [
            {
                text: cmplz_tour.start,
                classes: 'button button-secondary',
                action: function() {
                    window.location = steps[0]['start_link'];
                }
            },
			{
				text: cmplz_tour.documentation,
				classes: 'button button-primary',
				action: function() {
					window.open(steps[0]['documentation_link'], '_blank');
				}
			},
		],
	});

	plugins_overview_tour.on('cancel', cancel_tour);

	// start tour when the settings link appears after plugin activation
	if ($('#deactivate-definitions').length) {
		plugins_overview_tour.start();
	}

	/**
	 * Cancel tour
	 */

	function cancel_tour() {
		// The tour is either finished or [x] was clicked
		plugins_overview_tour.canceled = true;

		$.ajax({
			type: "POST",
			url: cmplz_tour.ajaxurl,
			dataType: 'json',
			data: ({
				action: 'cmplz_cancel_tour',
				token: cmplz_tour.token,
			})
		});
	};


});
