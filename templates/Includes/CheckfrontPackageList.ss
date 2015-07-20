<div class="checkfront-package-list">
    <ul>
    <% loop $PackageList %>
        <li><a href="$PublicLink('public')">$Title</a></li>
    <% end_loop %>
    </ul>
</div>