<?php
 return [
     'route' => [

         /*
          * Your api routes prefix
          */

         'prefix' => 'api',

     ],
     'documentation' => [

         /*
          * Documentation output
          */
         'output' => 'public/api-documentation',

         /*
          * Documentation template source
          */
         'source' => 'resources/views/vendor/y2apidoc/default',

         /*
          * Custom Documentation Tags Templates Path
          * If you want customize rendering some tags just put blade template in this path.
          * Name of the file must be [tag_name]tag.blade.php, ex.: tabletag.blade.php
          */
         'tags_template_path' => 'resources/views/vendor/y2apidoc/default/_partials/tags',

         /*
          * Path for templates with languages
          */
         'languages' => 'resources/views/vendor/y2apidoc/default/_partials/langs',

         /*
          * Available Tags and custom tags parsers
          */
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
             //"@category" => [],
             "@copyright" => [],
             "@deprecated" => [],
             "@response" => [
                 'class' => '\\Delejt\\Y2apidoc\\Tags\\ResponseTag',
             ],
             "@responsefile" => [
                 'class' => '\\Delejt\\Y2apidoc\\Tags\\ResponseFileTag',
             ],
             //"@filesource" => [],
             //"@global" => [],
             //"@ignore" => [],
             //"@internal" => [],
             //"@license" => [],
             //"@method" => [],
             //"@package" => [],
             "@param" => [],
             //"@property" => [],
             //"@property-read => []",
             //"@property-write => []",
             //"@return" => [],
             //"@see" => [],
             //"@since" => [],
             //"@source" => [],
             //"@subpackage" => [],
             "@throws" => [],
             "@todo" => [],
             //"@uses & @used-by" => [],
             "@var" => [],
             "@version" => [],
         ],

         /*
          * examples requests & responses
          */
         'request' => [

             /*
              * Specify headers to be added to the all requests
              */
             'headers' => [
                 'Accept' => 'application/json',
                 'Content-Type' => 'application/x-www-form-urlencoded',
                 'Authorization' => 'Bearer: {token}',
             ],

            /*
             * Specify binding.
             */
             'bindings' => [
                 '{token}' => 'qwerty',
                 '{page}' => '1',
                 '{item_per_page}' => '30',
                 '{page?}' => '1',
                 '{item_per_page?}' => '30',
                 '{type}' => 'ean',
                 '{value}' => '1234567890123',
                 '{ean}' => '1234567890123',
                 '{ean?}' => '1234567890123',
             ],

             /*
              * bootstrap classes using for labelling method as PUT/POST/PATCH/DELETE/GET
              */
             'classes' => [
                 'get' => 'success',
                 'post' => 'primary',
                 'put' => 'warning',
                 'delete' => 'danger',
             ],

         ],

         'response' => [

             /*
              * Specify headers to be added to the all responses
              */
             'headers' => [
                 'Content-Type' => 'application/json',
                 'Accept' => 'application/json',
             ],
         ],
     ]
 ];