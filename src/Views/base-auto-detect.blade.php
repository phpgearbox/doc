<script>
	function findBase(url)
	{
		$.get(url, function(data)
		{
			var base = url.replace('/base.html', '');

			$('ul.nav-sidebar a').each(function(index, element)
			{
				$(element).attr('href', base+$(element).attr('href').replace('./', '/'));
			});
		})
		.fail(function()
		{
			path.pop(); findBase(path.join('/') + '/base.html');
		});
	}

	var path = window.location.pathname.split('/'); path.pop();
	findBase(path.join('/') + '/base.html');
</script>