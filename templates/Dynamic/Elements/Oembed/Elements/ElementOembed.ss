<% if $Title && $ShowTitle %><h2 class="element__title">$Title</h2><% end_if %>

<div class="col-md-12 element__oembed__object">
    <div class="card mb-3">
        <div class="ratio ratio-16x9">
            <iframe class="card-img-top" src="$EmbedURL" title="$EmbedTitle.ATT" allowfullscreen></iframe>
        </div>
        <div class="card-body">
            <% if $EmbedTitle %><h3 class="card-title">$EmbedTitle</h3><% end_if %>
            <% if $EmbedDescription %><p class="card-text">$EmbedDescription</p><% end_if %>
        </div>
    </div>
</div>
