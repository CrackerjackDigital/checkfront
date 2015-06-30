<div class="checkfront-booking-page">
	<% if CheckfrontForm %>
		<h1>
			Booking $Package.Title
		</h1>
		<div class="message">$Message</div>
		$CheckfrontForm
	<% else %>
		<ul>
		<% loop $PackageList %>
			<li><a href="$Link">$Title</a></li>
		<% end_loop %>
		</ul>
	<% end_if %>
</div>