<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>{{ $project_name }}</title>
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
		<link rel="stylesheet" href="//cdn.jsdelivr.net/jquery.fancytree/2.3.0/skin-win8/ui.fancytree.min.css">
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.3/styles/github.min.css">
		@include('snippets.style')
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

		<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
		<script src="//code.jquery.com/ui/1.11.1/jquery-ui.min.js"></script>
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
		<script src="//cdn.jsdelivr.net/jquery.fancytree/2.3.0/jquery.fancytree-all.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.3/highlight.min.js"></script>
		<script src="https://raw.github.com/olivernn/lunr.js/master/lunr.min.js"></script>
		@include('snippets.script')

	</body>
</html>