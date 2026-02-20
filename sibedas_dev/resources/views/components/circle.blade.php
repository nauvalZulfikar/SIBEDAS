@props(['document_url' => '#'])

@section('css')
@vite(['resources/scss/components/_circle.scss'])
@endsection

<div class="circle-container"  id="{{$document_id}}" style="--circle-color: {{$document_color}};{{$style}}" data-url="{{ $document_url }}"
     onclick="handleCircleClick(this)">
    <div class="circle-content">
        <p class="document-title {{$document_id}}" >{{$document_title}}</p>
        <p class="document-total {{$document_id}}" >Rp.0</p>
        <p class="document-count {{$document_id}}" >0</p>
        <p class="document-type {{$document_id}}" >{{$document_type}}</p>
    </div>
    @if ($visible_small_circle)
    <div class="small-circle-container">
        <div class="small-circle-content">
            <p class="small-percentage {{$document_id}}">0%</p>
        </div>
    </div>
    @endif
</div>

<script>
    function handleCircleClick(element) {
        const url = element.getAttribute('data-url') || '#';
        if (url !== '#') {
            window.location.href = url;
        }
    }
</script>