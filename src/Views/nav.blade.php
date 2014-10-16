<ul>
	@foreach($items as $key => $value)
		@if (is_array($value))
			<li>
				{{$key}}/
				@include('nav', array('items', $value))
			</li>
		@else
			<li><a href="./{{$value}}">{{$value}}</a></li>
		@endif
	@endforeach
</ul>