{{-- Watermark: logo pudar di tengah halaman, diulang di setiap halaman. Muncul paling awal di <body> dan z-index negatif supaya selalu di belakang isi invoice. --}}
@if (! empty($design['watermark']))
    <div style="position:fixed; top:346px; left:0; width:100%; text-align:center; z-index:-1;">
        <img src="{{ $design['watermark'] }}" style="width:430px; height:430px;">
    </div>
@endif
