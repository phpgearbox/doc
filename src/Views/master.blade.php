<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Brads Local Dev Server</title>
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
		<link rel="stylesheet" href="//cdn.jsdelivr.net/jquery.fancytree/2.3.0/skin-win8/ui.fancytree.min.css">
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.3/styles/github.min.css">
		@include('style')
	</head>
	<body>

		{{-- HEADER BAR --}}
		<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
			<div class="container-fluid">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="#">Project name</a>
				</div>
				<div class="navbar-collapse collapse">
					<ul class="nav navbar-nav navbar-right">
						<li><a href="#">Dashboard</a></li>
						<li><a href="#">Settings</a></li>
						<li><a href="#">Profile</a></li>
						<li><a href="#">Help</a></li>
					</ul>
				</div>
			</div>
		</div>

		<div class="container-fluid">
			<div class="row">

				{{-- SIDE BAR --}}
				<div class="col-sm-3 col-md-2 sidebar">
					<form class="form-inline search" role="form">
						<div class="form-group">
							<label class="sr-only" for="search-query">Search Query</label>
							<input type="text" name="q" class="form-control" id="search-query" placeholder="Search...">
						</div>
						<button type="submit" class="btn btn-default">Go</button>
					</form>
					<div class="fancytree"></div>
				</div>

				{{-- MAIN CONTENT --}}
				<div class="col-sm-9 col-sm-offset-3 col-md-8 col-md-offset-2 main">
					
					<h1 class="page-header">
						{{ $file_info->getRelativePathname() }}
						<a class="view-source" title="View Source" data-toggle="modal" data-target="#sourceModal">
							<i class="fa fa-file-code-o"></i>
						</a>
					</h1>
					
					@foreach ($blocks as $key => $block)
						
						<div id="block_{{ $key }}" class="panel panel-default {{ $block['context'] }}">
							@if (isset($block['title']))
								<div class="panel-heading">
									<h3 class="panel-title">
										{{ $block['title'] }}
										<a
											class="view-source"
											title="Take me to the code..."
											data-start-line="{{ $block['lines'][0] }}"
											data-end-line="{{ $block['lines'][1] }}"
										>
											<i class="fa fa-file-code-o"></i>
										</a>
									</h3>
								</div>
							@endif
							<div class="panel-body">
								@if (isset($block['signature']))
									<pre><code class="{{ $file_info->getExtension() }}">{{{ $block['signature'] }}}</code></pre>
								@endif
								{{ $block['html'] }}
							</div>
						</div>

					@endforeach

					<div class="modal fade" id="sourceModal" tabindex="-1" role="dialog" aria-labelledby="sourceModalLabel" aria-hidden="true">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal">
										<span aria-hidden="true">&times;</span>
										<span class="sr-only">Close</span>
									</button>
									<h4 class="modal-title" id="sourceModalLabel}">
										{{ $file_info->getFileName() }}
									</h4>
								</div>
								<div class="modal-body">
									<pre><code class="{{ $file_info->getExtension() }}">{{{ $file_info->getContents() }}}</code></pre>
								</div>
							</div>
						</div>
					</div>

				</div>

				{{-- TABLE OF CONTENTS NAV --}}
				<div class="col-sm-3 col-md-2 toc">
					<div class="list-group">
						@foreach ($blocks as $key => $block)
							@if (isset($block['title']))
								<a class="list-group-item<?php if ($key == 0) echo ' active'; ?>" data-block-target="#block_{{ $key }}">
									{{ $block['title'] }}
								</a>
							@endif
						@endforeach
					</div>
				</div>

			</div>
		</div>

		<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
		<script src="//code.jquery.com/ui/1.11.1/jquery-ui.min.js"></script>
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
		<script src="//cdn.jsdelivr.net/jquery.fancytree/2.3.0/jquery.fancytree-all.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.3/highlight.min.js"></script>
		@include('script')

	</body>
</html>