<div class="checkfront-link-generator">
    $CheckfrontLinkGeneratorForm

	<div class="checkfront-link-info">
		<% if $Package %>
			<table>
				<tr>
					<td>Name</td><td>$Package.Title</td>
					<td>SKU</td><td>$Package.SKU</td>
					<td>Type</td><t>$Posted.Type</t>
					<td>Start Date</td><td>$Posted.StartDate</td>
					<td>End Date</td><td>$Posted.EndDate</td>
					<td>AccessKey</td><td>$Posted.AccessKey</td>
				</tr>
				<tr>
					<td>Link</td>
					<td colspan="5">$Link</td>
				</tr>
			</table>
		<% end_if %>
	</div>
</div>