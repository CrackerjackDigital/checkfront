<%-- vanilla booking page which shows form --%>
<div class="checkfront-booking-page">
	<% if $CurrentPackage %>
		<h1>Booking $CurrentPackage.Title</h1>

		<div class="message">$Message</div>

		<% include $CheckfrontForm %>

	<% else %>

		<h1><%t CheckfrontBookingPage.Title 'Please choose a package' %></h1>

		<% include CheckfrontPackageList %>

	<% end_if %>
</div>