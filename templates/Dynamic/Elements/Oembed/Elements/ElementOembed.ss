<% if $Title && $ShowTitle %><h2 class="element__title">$Title</h2><% end_if %>

<% if $EmbeddedObject %>
    <div class="row element__oembed__object">
        <div class="col-md-12 card">
            <div class="embed-responsive embed-responsive-16by9">
                $EmbeddedObject
            </div>
            <div class="card-body">
                <% if $EmbeddedObject.Title %><h3 class="card-title">$EmbeddedObject.Title</h3><% end_if %>
                <% if $EmbeddedObject.Description %><p class="card-text">$EmbeddedObject.Description</p><% end_if %>
            </div>
        </div>
    </div>
<% end_if %>
