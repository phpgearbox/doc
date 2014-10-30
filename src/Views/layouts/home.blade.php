@extends('layouts.master')

@section('middle')

	<div class="col-md-6 col-md-offset-3 main">
					
		{{ $content }}

	</div>

	<div class="col-md-2 toc">
		<div class="list-group">
			@foreach ($sections as $key => $section)
				<a class="list-group-item<?php if ($key == 0) echo ' active'; ?>" data-block-target="#block_{{ $key }}">
					{{ $section }}
				</a>
			@endforeach
		</div>
	</div>
	
@stop