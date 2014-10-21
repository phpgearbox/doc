<script>
	$(document).ready(function()
	{
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