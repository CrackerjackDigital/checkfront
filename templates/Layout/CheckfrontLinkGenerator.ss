<div class="checkfront-link-generator">
	<% if $BookingLink %>
		<div class="checkfront-link-info">
			<table>
				<tr>
					<td>Name</td><td>$Package.Title</td>
				</tr>
				<tr>
					<td>SKU</td><td>$Package.SKU</td>
				</tr>
				<tr>
					<td>Type</td><t>$Posted.Type</t>
				</tr>
				<tr>
					<td>Organiser Event</td><td>$OrganiserEvent</td>
				</tr>
				<tr>
					<td>Individual Event</td><td>$IndividualEvent</td>
				</tr>
			</table>
			<table>
				<tr>
					<td>Access Key</td>
					<td colspan="5">$AccessKey</td>
				</tr>
				<tr>
					<td>OrganiserLink</td>
					<td colspan="5"><a href="$OrganiserLink">$OrganiserLink</a></td>
				</tr>
				<tr>
					<td>IndividualLink</td>
					<td colspan="5"><a href="$IndividualLink">$IndividualLink</a></td>
				</tr>
			</table>
		</div>

	<% else %>

		$CheckfrontForm

	<% end_if %>

    </div>
</div>