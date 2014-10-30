@extends('layouts.master')

@section('middle')

	<div class="col-md-6 col-md-offset-3 main">
		
		<h1 class="page-header">
			{{{ $file_info->getRelativePathname() }}}
			<a class="view-source" title="View Source" data-toggle="modal" data-target="#sourceModal">
				<i class="fa fa-file-code-o"></i>
			</a>
		</h1>
		
		@foreach ($blocks as $key => $block)
			
			<div id="block_{{ $key }}" class="panel panel-default {{ $block['context'] }}">
				@if (isset($block['title']))
					<div class="panel-heading">
						<h3 class="panel-title">
							{{{ $block['title'] }}}
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

	</div>

	<div class="col-md-2 toc">
		<div class="list-group">
			@foreach ($blocks as $key => $block)
				@if (isset($block['title']))
					<a class="list-group-item<?php if ($key == 0) echo ' active'; ?>" data-block-target="#block_{{ $key }}">
						{{{ $block['title'] }}}
					</a>
				@endif
			@endforeach
		</div>
	</div>

@stop

@section('source-modal')

	<div class="modal fade" id="sourceModal" tabindex="-1" role="dialog" aria-labelledby="sourceModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">
						<span aria-hidden="true">&times;</span>
						<span class="sr-only">Close</span>
					</button>
					<h4 class="modal-title" id="sourceModalLabel">
						{{{ $file_info->getRelativePathname() }}}
					</h4>
				</div>
				<div class="modal-body">
					<pre><code class="{{ $file_info->getExtension() }}">{{{ $file_info->getContents() }}}</code></pre>
				</div>
			</div>
		</div>
	</div>

@stop