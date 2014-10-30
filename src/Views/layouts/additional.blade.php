@extends('layouts.master')

@section('middle')

	<div class="col-md-6 col-md-offset-3 main">
		
		<h1 class="page-header">
			{{{ $file_info->getRelativePathname() }}}
		</h1>
		
		@foreach ($blocks as $key => $block)
			
			<div id="block_{{ $key }}" class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">
						{{{ $block['title'] }}}
					</h3>
				</div>
				<div class="panel-body">
					{{ $block['html'] }}
				</div>
			</div>

		@endforeach

	</div>

	<div class="col-md-2 toc">
		<div class="list-group">
			@foreach ($blocks as $key => $block)
				<a class="list-group-item<?php if ($key == 0) echo ' active'; ?>" data-block-target="#block_{{ $key }}">
					{{{ $block['title'] }}}
				</a>
			@endforeach
		</div>
	</div>

@stop