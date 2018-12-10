<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>Y2apidoc - Yet Another ApiDoc Generator for Laravel</title>

    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/highlight.css" rel="stylesheet">
    <link href="css/main.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>

<div class="container bs-docs-container">

    <div class="row">
        <div class="col-md-9" role="main">
            <div class="bs-docs-section">
                <h1 class="page-header">Y2apidoc</h1>

                <p class="lead">
                    Welcome to the our Documentation! You can use our API to access API endpoints, which can get information on various products, stocks, and prices in our database.

                    We have language bindings in Php and Shell! You can view code examples and response examples.
                </p>

            </div>

            @foreach ($documentation as $class_name => $class)

                <div class="row">
                    <div class="col-md-12"><br><br></div>
                </div>

                <div class="bs-docs-section">
                    <h2 class="page-header">
                        {{ $class_name }}
                    </h2>

                    <p class="lead">{{ $class['description'] }}</p>
                </div>

                @foreach ($class['methods'] as $method)

                    <div class="bs-docs-section">
                        <h3 class="page-header">
                            <span class="label label-{{ $method['request_class'] }}">{{ $method['request_type'] }}</span>
                            <a name="{{ str_slug($class_name . ' '. $method['action']) }}">
                                {{ $class_name }}: {{ $method['action'] }}
                            </a>
                        </h3>

                        <p class="lead">{!! $method['description'] !!}</p>

                        <h5 class="well">
                            <i class="glyphicon glyphicon-star"></i>
                            <strong>{{ $method['endpoint'] }}</strong>
                        </h5>

                        @forelse ($method['tags'] as $tag)

                            @if ($tag['name'] == '@response' || $tag['name'] == '@responsefile')
                                @continue
                            @endif

                            <p class="lead">{!! $tag['body'] !!}</p>
                        @empty

                        @endforelse

                        @if (!empty($method['request_examples']))
                            <h4>Code examples</h4>
                            <ul class="nav nav-tabs">
                                @foreach ($method['request_examples'] as $lang => $example)
                                    <li class="@if ($loop->first) active @endif">
                                        <a data-toggle="tab" href="#{{ str_slug($class_name . ' '. $method['action']. ' '. $lang) }}">
                                            {{ strtoupper($lang) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content">

                                @foreach ($method['request_examples'] as $lang => $example)
                                    <div id="{{ str_slug($class_name . ' '. $method['action']. ' '. $lang) }}" class="tab-pane fade in @if ($loop->first) active @endif">
                                        <figure class="code-block">
                                            {!! nl2br($example) !!}
                                        </figure>
                                    </div>
                                @endforeach

                            </div>
                            <br>
                        @endif

                        @forelse ($method['tags'] as $tag)

                            @if ($tag['name'] != '@response' && $tag['name'] != '@responsefile')
                                @continue
                            @endif

                            <p>{!! $tag['body'] !!}</p>
                        @empty

                        @endforelse

                    </div>

                    <br>
                    <br>

                @endforeach

            @endforeach

            <div class="bs-docs-section">
                <h1 class="page-header">Y2apidoc</h1>

                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Error Code</th>
                        <th>Meaning</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>400</td>
                        <td>Bad Request – I don't understand your request - RTFM ;)</td>
                    </tr>
                    <tr>
                        <td>401</td>
                        <td>Unauthorized – Wrong API key</td>
                    </tr>
                    <tr>
                        <td>403</td>
                        <td>Forbidden – I don't have permissions for this endpoint</td>
                    </tr>
                    <tr>
                        <td>404</td>
                        <td>Not Found – The specified endpoint could not be found</td>
                    </tr>
                    <tr>
                        <td>405</td>
                        <td>Method Not Allowed – You tried to access a endpoint with an invalid method (POST, GET, PUT, DELETE)</td>
                    </tr>
                    <tr>
                        <td>406</td>
                        <td>Not Acceptable – You requested a format that isn’t json</td>
                    </tr>
                    <tr>
                        <td>500</td>
                        <td>Internal Server Error – We had a problem with our server. Try again later.</td>
                    </tr>
                    <tr>
                        <td>503</td>
                        <td>Service Unavailable – We’re temporarially offline for maintanance. Please try again later.</td>
                    </tr>
                    </tbody>
                </table>

            </div>

        </div>

        <div class="col-md-3" role="complementary">
            <nav id="column_right">
                <ul class="nav nav-list tree">
                    <li>
                        <a href="#home">Home</a>
                    </li>

                    @foreach ($documentation as $class_name => $class)
                    <li>
                        <a class="accordion-heading" data-toggle="collapse" data-target="#{{ str_slug($class_name) }}">
                            <span class="nav-header-primary">{{ $class_name }}</span>
                        </a>

                        <ul class="nav nav-list collapse" id="{{ str_slug($class_name) }}">
                            @foreach ($class['methods'] as $method)
                            <li>
                                <a href="#{{ str_slug($class_name . ' '. $method['action']) }}">{{ $method['action'] }}</a>
                            </li>
                            @endforeach
                        </ul>
                    </li>
                    @endforeach

                </ul>

            </nav>
        </div>
    </div>
</div>




<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="js/bootstrap.min.js"></script>
</body>
</html>