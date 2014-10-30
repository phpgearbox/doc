var lunr_index = {{ json_encode($lunr_index) }};

var lunr_index_lookup = {{ json_encode($lunr_index_lookup) }};

var lunrIndex = lunr(function()
{
	this.ref("id")
	this.field("title", {boost: 10})
	this.field("signature")
	this.field("body")
});

$.each(lunr_index, function(key, value)
{
	lunrIndex.add(value);
});