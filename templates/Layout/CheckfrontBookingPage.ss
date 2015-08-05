<div class="checkfront-booking-page">
	<div class="message">$Message</div>

	<% if $CurrentPackage %>
		<h1>Booking $CurrentPackage.Title</h1>

		<div>$CurrentPackage.Summary</div>

		<% if $CurrentPackage.Image %>
			<img src="$CurrentPackage.Image" alt=""/>
		<% end_if %>

		<% include $CheckfrontForm %>

	<% else_if $PackageList %>

		<h1><%t CheckfrontBookingPage.Title 'Please choose a package' %></h1>

		<% include CheckfrontPackageList %>

	<% end_if %>
</div>