<div class="checkfront-link-generator">
	<% if $ShowOutput %>
		<div class="checkfront-link-info">
			<table>
				<tr>
					<td>Name</td><td>$Package.Title</td>
				</tr>
				<tr>
					<td>Access Key</td>
					<td colspan="5">$AccessKey</td>
				</tr>
			</table>
			<table>
				<tr>
					<td>Organiser Start Date</td><td>$Posted.OrganiserStartDate</td>
				</tr>
				<tr>
					<td>Organiser End Date</td><td>$Posted.OrganiserEndDate</td>
				</tr>
				<tr>
					<td>OrganiserLink</td>
					<td colspan="5"><a href="$OrganiserLink">$OrganiserLink</a></td>
				</tr>
			</table>
			<table>
				<tr>
					<td>Individual Start Date</td><td>$Posted.IndividualStartDate</td>
				</tr>
				<tr>
					<td>Individual End Date</td><td>$Posted.IndividualEndDate</td>
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