@props(['title' => 'title component', 'visible_data' => false, 'data_count' => '', 'visible_data_type' => false, 
'data_type' => '','style' => '', 'size' => '', 'data_id' => '','', 'document_url' => '#'])

@section('css')
@vite(['resources/scss/components/_custom_circle.scss'])
@endsection

<div class="custom-circle-wrapper {{ $size }}" style="{{ $style }}" data-url="{{ $document_url }}" onclick="handleCircleClick(this)">
    <div class="custom-circle-content">
        <p class="custom-circle-text">{{ $title }}</p>
        @if ($visible_data === "true")
            <div class="custom-circle-data" id="{{ $data_id }}">{{ $data_count }}</div>
        @endif
        @if ($visible_data_type === "true")
            <div class="custom-circle-data-type" id="{{ $data_id }}-amount">{{ $data_type }}</div>
        @endif
    </div>
</div>