<div class="checkfront-booking-page">
	<div class="message">$Message</div>

	<% if $Booking %>
		<h1>Booking Details</h1>

	<% with $CurrentPackage %>
		<h1>Booking $Title</h1>

		<div>$Summary</div>

		<% if $Image %>
			<img src="$Image" alt=""/>
		<% end_if %>
	<% end_with %>

	<% if CheckfrontForm %>

		<% include $CheckfrontForm %>

	<% end_if %>

	<% if $PackageList %>

		<h1><%t CheckfrontBookingPage.Title 'Please choose a package' %></h1>

		<% include CheckfrontPackageList %>

	<% end_if %>
</div>