curl -X {{ $url }}/{{ $endpoint }} {{$request_type == 'get' ? '-G ' : ''}}" @if(count($default_headers))\
@foreach($default_headers as $header => $value)
    -H "{{$header}}: {{$value}}" @if(! ($loop->last) || ($loop->last && count($body_params))) \
@endif
@endforeach
@endif
@foreach($body_params as $param => $value)
    -d "{{ $param }}"="{{ $value }}" @if(! ($loop->last))\
@endif
@endforeach