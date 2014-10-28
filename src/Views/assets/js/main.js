/**
 * Section: Lunr Index
 * =============================================================================
 * One of the first things we do is setup the lunr index
 * so that it is ready to go when someone makes a search.
 */
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

/**
 * Section: Dom Ready
 * =============================================================================
 * Everything inside here will be run on dom ready.
 */
$(document).ready(function()
{
	/**
	 * Section: Syntax Highlighting
	 * =========================================================================
	 * We are using https://highlightjs.org/ for our syntax highlighting needs.
	 * Because it has an effect to the visual layout of the dom, ie: effects
	 * height calculations. We run it high in the order.
	 */
	hljs.initHighlighting();

	/**
	 * For the main source modal lets add some line numbers.
	 * highlight.js does not support line numbers.
	 * see: http://highlightjs.readthedocs.org/en/latest/line-numbers.html
	 */
	if ($('#sourceModal code').length > 0)
	{
		var code = '';

		$.each($('#sourceModal code').html().split(/\n/), function(index, value)
		{
			code += '<span class="line-number" data-num="'+(index+1)+'">'+value+'</span>'+"\n";
		});

		$('#sourceModal code').html(code);
	}

	/**
	 * Section: OnLoad Hash Detection
	 * =========================================================================
	 * When the browser first loads we check for a hash, if one exists we
	 * scroll to that location for the user automatically.
	 */
	if (window.location.hash != '')
	{
		var target = '#block_'+window.location.hash.split('#')[1];
		var top = $(target).offset().top - 55;
		$('html, body').animate({scrollTop: top}, 500);
	}

	/**
	 * Section: Fancytree
	 * =========================================================================
	 * We are using https://github.com/mar10/fancytree to create our tree menu
	 * in the left hand sidebar. See [Method: generateJsonTree](Generator.php)
	 * for where the json tree is created.
	 */
	$(".fancytree").fancytree
	({
		source: fancy_tree,

		activate: function(event, data)
		{
			if (typeof data.node.data.href != 'undefined')
			{
				window.location = data.node.data.href;
			}
		}
	});

	/**
	 * Event: Internal Link Click
	 * =========================================================================
	 * When a user clicks on a link that is on the exact same document this will
	 * handle that event. It will scroll to the associated docblock.
	 */
	$('a.internal-link').click(function()
	{
		if ($(this).attr('href').indexOf('#') == 0)
		{
			var target = '#block_'+$(this).attr('href').split('#')[1];
			var top = $(target).offset().top - 55;
			$('html, body').animate({scrollTop: top}, 500);
		}
	});

	/**
	 * Event: Search Form Submit
	 * =========================================================================
	 * This stops the browser from actually submitting, as the search is all
	 * done client side. So AJAX requests are sent of any kind.
	 */
	$('form.search').submit(function(event)
	{
		event.preventDefault();
	});

	/**
	 * Event: Search Form Go Button
	 * =========================================================================
	 * When a user clicks the search "Go" button this will fire.
	 * 
	 * > NOTE: When a user hits the enter key on the search input this event
	 * > will also be fired. Even though I have no event handler for that,
	 * > I believe bootstrap is doing it for us...
	 * 
	 * This grabs the search query and send it to the lunr index and then
	 * generates the HTML that goes into the search results modal.
	 */
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
	
	/**
	 * Event: Search Result Link Click
	 * =========================================================================
	 * Because the search result links are loaded at runtime, the standard
	 * [Event: Internal Link Click](#) does not handle them for us.
	 * 
	 * This event is attached to the document but is
	 * filtered by the ```search-result-link``` class.
	 */
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

	/**
	 * Event: Table of Contents Click
	 * =========================================================================
	 * On pages that have a table of contents on the right hand side.
	 * We animate the scroll to the appropriate docblock.
	 */
	$('.toc .list-group-item').click(function()
	{
		var target = $(this).attr('data-block-target');
		var top = $(target).offset().top - 55;
		$('html, body').animate({scrollTop: top}, 500);
	});

	/**
	 * Event: Window Scroll
	 * =========================================================================
	 * When ever the window scrolls we update the Table of Contents
	 * in the right hand sidebar. Along with the hash in the address bar.
	 */
	$(window).scroll(function()
	{
		if ($('.toc .list-group-item').length > 0)
		{
			var top = $(window).scrollTop();

			var current_toc = null;

			$('.panel').each(function(index, el)
			{
				var panel_top = $(el).offset().top - 56;

				if (top >= panel_top)
				{
					current_toc = $(el).attr('id');
				}
			});

			if (current_toc == null)
			{
				window.location.hash = 0;
				current_toc = $('[data-block-target="#block_0"]');
			}
			else
			{
				window.location.hash = current_toc.split('block_')[1];
				current_toc = $('[data-block-target="#'+current_toc+'"]')
			}

			$('.toc .list-group-item').removeClass('active');
			current_toc.addClass('active');
		}
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
});