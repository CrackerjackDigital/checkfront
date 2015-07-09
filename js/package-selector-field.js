/**
 * This template is included by LinkGenerator.makePackageSelectorField using
 * javascriptTemplate and so requires the '$' prefixed variables to be declared
 * as data on that call.
 *
 */
(function() {
	// from javascriptTemplate call
	var packages = $PackageEventMap,
		eventDropdowns = $('select.$PackageEventFieldSelector'),
		formActions = $('.Actions input');

	formActions.enable = function() {
		this.removeAttr('disabled');
	};
	formActions.disable = function() {
		this.attr('disabled', 'disabled');
	};

	// disable actions until data is entered
	formActions.disable();

	eventDropdowns.on('change', function(ev) {
		// count number of non-empty event dropdowns
		var eventsSelected = eventDropdowns.filter(function() { return $(this).val().length > 0 }).length;

		// if non-empty dropdowns == number of dropdowns then enable form actions, otherwise disable
		if (eventsSelected === eventDropdowns.length) {
			formActions.enable();
		} else {
			formActions.disable();
		}
	});

	$('select.$PackageFieldSelector').on('change', function(ev) {
		var packageID = $(this).val(),
			events;

		// stop it bubbling or parent div.package-selector will get it too
		ev.stopPropagation();

		// disable buttons until events are chosen
		formActions.disable();

		if (packages[packageID]) {
			// clear event dropdowns and add the 'select ... event' option at top
			eventDropdowns.each(function() {
				$(this).empty();
				$('<option />', {
					val: '',
					text: $(this).attr('placeholder')
				}).appendTo(this);
			});

			events = packages[packageID];

			// add available events to each dropdown
			eventDropdowns.each(function() {
				var event, i;

				for (i = 0; i < events.length; i++) {
					if (event = events[i]) {

						$('<option />', {
							val: event.id,
							text: event.name
						}).appendTo(this);

						$(this).removeAttr('disabled');
					}
				}
			});
		} else {
			// clear event dropdowns and add a 'no events available' option.
			eventDropdowns.each(function() {
				$(this).empty();
				$('<option />', {
					val: '',
					text: 'No events available'
				}).appendTo(this);
				$(this).attr('disabled', 'disabled');
			});
		}

	});
})(jQuery);