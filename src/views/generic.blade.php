<div class="carousel_wrap">
    <div class="carousel slide" id="carouselExampleControls" data-ride="carousel">
        <div class="carousel-inner">
            @foreach ($data['elements'] as $key => $element)
                <div class="carousel-item @if ($key == 0) active @endif">
                    <div class="text-left">
                        @if (isset($element['image_url']))
                            <img class="d-block w-100" src="{{ $element['image_url'] }}"
                                alt="{{ $element['title'] }}" />
                        @endif
                        <div class="mt-2" style="font-size: 12px;">{{ $element['title'] }}</div>
                        @if (isset($element['subtitle']))
                            <div class="mb-2" style="font-size: 10px;">{{ $element['subtitle'] }}</div>
                        @endif
                    </div>
                    @if (isset($element['buttons']))
                        @foreach ($element['buttons'] as $key2 => $button)
                            <center>
                                <a class="btn btn-secondary mt-1"
                                    href="{{ isset($button['url']) ? $button['url'] : '#' }}">{{ $button['title'] }}</a>
                            </center>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
        @if (count($data['elements']) > 1)
            <a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-slide="prev"><span
                    class="carousel-control-prev-icon" aria-hidden="true" style="background-color: red;"></span><span
                    class="sr-only">Previous</span></a>
            <a class="carousel-control-next" href="#carouselExampleControls" role="button" data-slide="next"><span
                    class="carousel-control-next-icon" aria-hidden="true" style="background-color: red;"></span><span
                    class="sr-only">Next</span></a>
        @endif
    </div>
</div>
