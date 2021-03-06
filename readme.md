# Yet Another ApiDoc Generator for Laravel

Automatically generate your API documentation from existing Laravel routing and Class/methods docblocks

```
php artisan y2apidoc:generate
```

## Some screenshots
- [y2apidoc](doc/1st.png) 
- [y2apidoc - method](doc/method.png) 

## Prerequisites

- PHP 7
- Laravel 5.6

## Installing

```
$ composer require delejt/y2apidoc
```
## Publishing config and template files
```
php artisan vendor:publish --provider="Delejt\Y2apidoc\ServiceProvider" --tag="config"
```
```
php artisan vendor:publish --provider="Delejt\Y2apidoc\ServiceProvider" --tag="template"
```

## Configuration
Before you can generate your documentation, 
you'll need to configure a few things in your config/y2apidoc.php:

- configure your api routing prefix:
```
 'route' => [
     'prefix' => 'api',
 ],
```

- change your documentation output path
```
'documentation' => [
    'output' => 'public/api-documentation',
 ...
```

- you can change template for documentation (default is bootstrap 3.3 simple template) 
```
'source' => 'resources/views/vendor/y2apidoc/default',
```

- path for custom tags special templates, name of the file must be [tag_name]tag.blade.php, ex.: tabletag.blade.php
```
'tags_template_path' => 'resources/views/vendor/y2apidoc/default/_partials/tags',
```

- specify custom languages templates (PHP/Shell included):
```
'languages' => 'resources/views/vendor/y2apidoc/default/_partials/langs',
```

- specify available tags for parser (or add your own), You can place here custom renderer class
```
'tags' => [
    "@table" => [
        'class' => '\\Delejt\\Y2apidoc\\Tags\\TableTag',
    ],
     "@notice" => [
         'class' => '\\Delejt\\Y2apidoc\\Tags\\NoticeTag',
     ],
     "@warning" => [
         'class' => '\\Delejt\\Y2apidoc\\Tags\\WarningTag',
     ],
     //"@api" => [],
     "@author" => [],

...     
```
- specify default headers added to all request:
```
 'request' => [
     'headers' => [
         'Accept' => 'application/json',
         'Content-Type' => 'application/x-www-form-urlencoded',
         'Authorization' => 'Bearer: {token}',
     ],
```

- specify bindings - variables will automatically replaced during documentation generation process:
```
'bindings' => [
     '{token}' => 'qwerty',
     '{page}' => '1',
     '{item_per_page}' => '30',
     '{page?}' => '1',
     ...
```

- specify bootstrap classes using for labelling method as PUT/POST/PATCH/DELETE/GET
```
 'classes' => [
     'get' => 'success',
     'post' => 'primary',
     'put' => 'warning',
     'delete' => 'danger',
 ],
```
- configure headers automatically added to each response:
```
 'response' => [
     'headers' => [
         'Content-Type' => 'application/json',
         'Accept' => 'application/json',
     ],
 ],
``` 

## Documentation
Y2apidoc uses HTTP controller doc blocks to create a table of contents and show descriptions for your API methods.
Package automatically create groups from controller names. All routes handled by that controller will placed 
under this group in the [sidebar menu](doc/aside.png) 

## Tags
This package uses standard php [DocBlock comments](http://docs.phpdoc.org/guides/docblocks.html).
Packages has custom Tags defined too.

- @notice sample notice message presented as bootstrap alert, example:
   ```
   @notice Simple Notice.
   ```
    
- @warning sample warning message presented as bootstrap alert
   ```
   @warning Simple Warning.
   ```
- @table tag to create table in your documentation - example for special params list
   ```
    @table Type|Name|Requirements|Description
    int|page|required|Page Number, example: 1
    int|item_per_page|required|Items per page, example: 32
    int|ean|optional|EAN13 if you specify this parameter
   ```
- @response tag to show example response from current method
   ```
   @response {
       "success": true,
       "data": {
         "id": 55597,
         "product_id": 59863,
         "warehouse_id": 1,
         "quantity": 1333,
         "delivery_time": 48,
         "created_at": "2018-09-25 10:43:25",
         "updated_at": "2018-12-07 12:29:09"
       },
       "message": "Record updated successfully."
     }
   ```
- @responsefile tag to show response from current method - see Response File section
    ```
    @responsefile product.index.json
    ```

# Custom Tags...
You can define custom tags by adding it's name to config file, example:
```
'tags' => [
    "@mikimouse",
...
```
You can specify custom renderer for this tag by putting class path:
```
 "@mikimouse" => [
     'class' => '\\Some\\Custom\\Namespace\\MikiMouseTag',
 ],
```
Next create class with 'parse' method in given path:
```
<?php namespace Some\Custom\Namespace;

class MikiMouseTag
{
    public function parse($body)
    {
        return 'Hello I am MikiMouse';
    }

}
```
Your custom tag is now available in your docblock. 
If you want create Tag with custom template,
```
<?php namespace Some\Custom\Namespace;

class MikiMouseTag
{
    public function parse($body)
    {
        return $this->render($body);
    }

    protected function render($body)
    {
        $template_name = 'mikimouse';

        try {
            return view($template_name)->with('text', $body);
        }
        catch (\Exception $e) {
            return $body;
        }
    }
    
}
```
Next, put template in your tags_template_path:
```
mikimouse.blade.php
```
with some html:
```
<div class="alert alert-info" role="alert">I'am Miki Mouse</div>
```
And that's all. Now You ready to generate Api Documentation :)

## Custom programming languages tabs
To define custom languages tabs, just create blade template in path declared in config file:
```
 /*
  * Path for templates with languages
  */
  'languages' => 'resources/views/vendor/y2apidoc/default/_partials/langs',

```
Name of this file should be name of current language:
```
javascript.blade.php
```
List of available variables in this template:
- url - parsed url with parameter ex.:  https://domain.xxx/api/product_stocks/1/30
- endpoint - endpoint route, ex.: https://domain.xxx/api/product_stocks/{page}/{item_per_page}
- request_type - GET/POST/PUT etc.
- default_headers - array of default headers defined in config file
- body_params - array of body params

### Response File
You can specify response file for current method by adding @responsefile to your docblock:
```
@responsefile product.stocks.store.json
```
During documentation generation process package will try to find file product.stocks.index.json in storage/api directory
Response file example:
```
{
  "success": true,
  "data": {
    "product_id": 16,
    "warehouse_id": 2,
    "quantity": 10,
    "delivery_time": 48,
    "updated_at": "2018-12-07 12:24:11",
    "created_at": "2018-12-07 12:24:11",
    "id": 204022
  },
  "message": "Record created successfully."
}
```


## Built With

* [ReflectionDocBlock](https://github.com/phpDocumentor/ReflectionDocBlock) - 
The ReflectionDocBlock component of phpDocumentor
* [highlight.php](https://github.com/scrivo/highlight.php) - server side code highlighter written in PHP
* [Bootstrap 3.3](https://getbootstrap.com/docs/3.3/) - Bootstrap is the most popular HTML, CSS, and JS framework

## Authors

* **Krzysztof Chełchowski** - *Initial work*

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Todo
- tests
- better documentation 
- more examples
- demo
