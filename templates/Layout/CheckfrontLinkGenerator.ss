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
					<td>Start Date</td><td>$Posted.StartDate</td>
				</tr>
				<tr>
					<td>End Date</td><td>$Posted.EndDate</td>
				</tr>
			</table>
			<table>
				<tr>
					<td>Link</td>
					<td colspan="5"><a href="$BookingLink">$BookingLink</a></td>
				</tr>
				<tr>
					<td>Access Key</td>
					<td colspan="5">$AccessKey</td>
				</tr>
			</table>
		</div>

	<% else %>

		$CheckfrontForm

	<% end_if %>

    </div>
</div>