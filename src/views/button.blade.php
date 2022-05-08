<div class="text-left">
	<div class="mt-2" style="font-size: 12px;">{{ $data['text'] }}</div>
</div>
@foreach ($data['buttons'] as $key2 => $button)
<center>
	<a class="btn btn-secondary mt-1" href="{{ isset($button['url']) ? $button['url'] : "#" }}">{{ $button['title'] }}</a>
</center>
@endforeach