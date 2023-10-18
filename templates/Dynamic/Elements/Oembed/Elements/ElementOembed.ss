<% if $Title && $ShowTitle %><h2 class="element__title">$Title</h2><% end_if %>

<div class="row element__oembed__object">
    <div class="col-md-12 card">
        <div class="embed-responsive embed-responsive-16by9">
            $Embed
        </div>
        <div class="card-body">
            <% if $EmbedTitle %><h3 class="card-title">$EmbedTitle</h3><% end_if %>
            <% if $EmbedDescription %><p class="card-text">$EmbedDescription</p><% end_if %>
        </div>
    </div>
</div>
