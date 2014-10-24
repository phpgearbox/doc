<script>
	var relative_urls = {{ $relative_urls }};

	var lunr_index = {{ $lunr_index }};

	var lunr_index_lookup = {{ $lunr_index_lookup }};
	
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

	$(document).ready(function()
	{
		if (window.location.hash != '')
		{
			var target = '#block_'+window.location.hash.split('#')[1];
			var top = $(target).offset().top - 55;
			$('html, body').animate({scrollTop: top}, 500);
		}

		$('form.search').submit(function(event)
		{
			event.preventDefault();
		});

		$('form.search button').click(function()
		{
			var query = $('form.search input').val();

			var results = lunrIndex.search(query);

			var html = '';

			$.each(results, function(index, result)
			{
				var doc = lunr_index[lunr_index_lookup[result.ref]];
				var ref = result.ref.split('--gearsdoc--');
				var file = ref[0];
				var block = ref[1];
				var relative_url = relative_urls[file];

				if (relative_url == '#')
				{
					var link = relative_url+block;
				}
				else
				{
					var link = relative_url+'#'+block;
				}

				html += '<h4>File: <a class="search-result-link" href="'+link+'">'+file+'</a></h4><ul>';

				if (typeof doc.title != 'undefined')
				{
					html += '<li><b>'+doc.title+'</b></li>';
				}

				if (typeof doc.signature != 'undefined')
				{
					html += '<li><code>'+doc.signature+'</code></li>';
				}

				if (doc.body.length > 140)
				{
					html += '<li>'+doc.body.substr(0, 140)+'&hellip;</li>';
				}
				else
				{
					html += '<li>'+doc.body+'</li>';
				}

				html += '</ul><hr>';
			});

			$('#searchModalLabel').html('Search Results for: '+query);

			$('#searchModal .modal-body').html(html);

			$('#searchModal').modal('show');
		});

		$(document).on('click', 'a.search-result-link', function()
		{
			if ($(this).attr('href').indexOf('#') == 0)
			{
				$('#searchModal').modal('hide');
				var target = '#block_'+$(this).attr('href').split('#')[1];
				var top = $(target).offset().top - 55;
				$('html, body').animate({scrollTop: top}, 500);
			}
		});

		$(".fancytree").fancytree
		({
			source: {{ json_encode($nav) }},

			activate: function(event, data)
			{
				if (typeof data.node.data.href != 'undefined')
				{
					window.location = data.node.data.href;
				}
			}
		});

		$('.toc .list-group-item').click(function()
		{
			var top = $($(this).attr('data-block-target')).offset().top - 55;
			$('html, body').animate({scrollTop: top}, 500);
		});

		$(window).scroll(function()
		{
			var top = $(window).scrollTop();

			var current_toc = null;

			$('.panel').each(function(index, el)
			{
				var panel_top = $(el).offset().top - 56;

				if (top >= panel_top)
				{
					current_toc = $('[data-block-target="#'+$(el).attr('id')+'"]');
				}
			});

			if (current_toc == null)
			{
				current_toc = $('[data-block-target="#block_0"]');
			}

			$('.toc .list-group-item').removeClass('active');
			current_toc.addClass('active');
		});

		$('.panel .view-source').click(function()
		{
			var start = parseInt($(this).attr('data-start-line'));
			var end = parseInt($(this).attr('data-end-line'));

			var lines = [];

			$('#sourceModal .line-number').each(function(index, el)
			{
				var lineno = parseInt($(el).attr('data-num'));

				if (lineno >= start && lineno <= end)
				{
					lines[lines.length] = el;
				}
			});

			$('#sourceModal').modal();

			setTimeout(function()
			{
				$('#sourceModal').animate({scrollTop: $(lines[0]).position().top}, 500, function()
				{
					$.each(lines, function(index, el)
					{
						$(el).animate({backgroundColor: 'yellow'}, 1000, function()
						{
							$(this).animate({backgroundColor: 'transparent'}, 1000);
						});
					});
				});
			}, 500);
		});

		// Highlight all our code blocks
		hljs.initHighlighting();

		// For the main source modal lets add some line numbers
		// highlight.js does not support line numbers.
		// see: http://highlightjs.readthedocs.org/en/latest/line-numbers.html
		var code = '';

		$.each($('#sourceModal code').html().split(/\n/), function(index, value)
		{
			code += '<span class="line-number" data-num="'+(index+1)+'">'+value+'</span>'+"\n";
		});

		$('#sourceModal code').html(code);
	});
</script>