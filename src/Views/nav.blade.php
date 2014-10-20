<ul>
	@foreach($items as $key => $value)
		@if (is_array($value) && !isset($value['href']))
			<li>
				{{$key}}/
				@include('nav', array('items' => $value))
			</li>
		@else
			<li><a href="{{ $value['href'] }}">{{ $value['title'] }}</a></li>
		@endif
	@endforeach
</ul>