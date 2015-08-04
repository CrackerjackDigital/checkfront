<div class="checkfront-booking-page">
	<div class="message">$Message</div>

	<% if $CurrentPackage %>
		<h1>Booking $CurrentPackage.Title</h1>

		<% include $CheckfrontForm %>

	<% else_if $PackageList %>

		<h1><%t CheckfrontBookingPage.Title 'Please choose a package' %></h1>

		<% include CheckfrontPackageList %>

	<% else %>

		<div class="message">$Message</div>

	<% end_if %>
</div>