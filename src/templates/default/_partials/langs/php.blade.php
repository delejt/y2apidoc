$curl = curl_init();
<?php $url = $url  . '/' . $endpoint ?>
@switch($request_type)
    @case('post')
        curl_setopt($curl, CURLOPT_POST, 1);
        @if($body_params)curl_setopt($curl, CURLOPT_POSTFIELDS, [
            @foreach($body_params as $param => $value)
                &nbsp;&nbsp;'{{ $param }}' => '{{ $value }}',
            @endforeach
        ]));
        @endif
        @break
    @case('put')
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        @if($body_params)curl_setopt($curl, CURLOPT_POSTFIELDS, [
        @foreach($body_params as $param => $value)
            &nbsp;&nbsp;'{{ $param }}' => '{{ $value }}',
        @endforeach
        ])
        @endif
        @break
    @default
        @if($body_params){{ $url = sprintf("%s?%s", $url, http_build_query($body_params)) }}@endif
@endswitch

curl_setopt($curl, CURLOPT_URL, "{{ $url }}");
@if(count($default_headers))
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    @foreach($default_headers as $header => $value)
        &nbsp;&nbsp;'{{ $header }}: {{ $value }}',
    @endforeach
]);
@endif
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($curl);

if(!$result){die("Connection Failure");}

curl_close($curl);
var_dump($result);