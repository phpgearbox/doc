<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>{{ $project_name }}</title>
		<link rel="stylesheet" href="{{ $style_path }}">
	</head>
	<body>

		@include('snippets.header')

		<div class="container-fluid">
			<div class="row">

				@include('snippets.navigation')

				@yield('middle')

			</div>
		</div>

		@yield('source-modal')

		@include('snippets.search-modal')

		<script>
			var relative_urls = {{ $relative_urls }};
			var fancy_tree = {{ json_encode($nav) }};
			var lunr_index = {{ $lunr_index }};
			var lunr_index_lookup = {{ $lunr_index_lookup }};
		</script>
		<script src="{{ $script_path }}"></script>
	</body>
</html>