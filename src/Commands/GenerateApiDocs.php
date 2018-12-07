<?php namespace Delejt\Y2apidoc\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use phpDocumentor\Reflection\DocBlockFactory;
use Delejt\Y2apidoc\Renderer\LanguageRenderer;

/**
 * Class GenerateApiDocs
 *
 * @package Delejt\Y2apidoc\Commands
 */
class GenerateApiDocs  extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'y2apidoc:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create beautiful Api Documentation';

    /**
     * An array of all the registered routes.
     *
     * @var \Illuminate\Routing\RouteCollection
     */
    protected $router;

    /**
     * @var \Illuminate\Config\Repository|mixed|string
     */
    protected $prefix;

    /**
     * @var \Illuminate\Config\Repository|mixed
     */
    protected $custom_tags;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;

        $_prefix = config('y2apidoc.route.prefix');
        $this->prefix = ($_prefix == '*') ? '' : $_prefix;
        $this->custom_tags = config('y2apidoc.route.prefix');

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function handle()
    {
        $routes = $this->getRoutes();

        $output = base_path() . DIRECTORY_SEPARATOR . config('y2apidoc.documentation.output');
        $template_path = config('y2apidoc.documentation.source');

        $this->prepareEnvironment($output);
        $view = $this->renderView($routes, $template_path);
        $this->saveDocumentation($view, $template_path, $output);
    }

    /**
     * @param $output
     */
    protected function prepareEnvironment($output)
    {
        $this->prepareOutputDirectory($output);
    }

    /**
     * @param $path
     */
    protected function prepareOutputDirectory($path)
    {
        \File::deleteDirectory($path, false);
        \File::makeDirectory($path, $mode = 0777, true, true);
    }

    /**
     * @param $routes
     * @param $template_path
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \ReflectionException
     */
    protected function renderView($routes, $template_path)
    {
        $documentation = $this->prepareDocumentation($routes);

        view()->addLocation($template_path);

        return view('y2apidoc')
            ->with('documentation', $documentation);
    }

    /**
     * @param $routes
     * @return array
     * @throws \ReflectionException
     */
    protected function prepareDocumentation($routes)
    {
        $result = [];

        $factory  = DocBlockFactory::createInstance();

        foreach ($routes as $route) {

            $tmp = explode("@", $route['action']);
            $class = $tmp[0];
            $action = $tmp[1];

            $controller = explode('\\', $class);
            $controller = str_replace('Controller', '', end($controller));

            $reflector = new \ReflectionClass($class);

            // controller description
            if (!isset($result[$controller]['description'])) {
                $controller_description = $factory->create($reflector->getDocComment());
                $result[$controller]['description'] = nl2br($controller_description->getDescription());
            }

            // current method description
            $docblock = $factory->create($reflector->getMethod($action)->getDocComment());

            $form_data = $docblock->getTagsByName('formdata');

            $result[$controller]['methods'][] = [
                'action' => $action,
                'endpoint' => $route['original_uri'],
                'request_type' => $route['request_type'],
                'request_class' => $this->getRequestClass($route['request_type']),
                'description' => nl2br($docblock->getDescription()),
                'request_examples' => $this->prepareExamples($route, $form_data),
                'tags' => $this->prepareTags($docblock->getTags()),
            ];

        }

        return $result;
    }

    /**
     * @param $document_tags
     * @return array
     */
    protected function prepareTags($document_tags)
    {
        $config_tags = config('y2apidoc.documentation.tags', null);

        if (is_null($config_tags) || is_null($document_tags)) {
            return [];
        }

        $result = [];
        foreach ($document_tags as $tag) {

            $name = $tag->getName();
            $body = (string) $tag->getDescription();

            if (empty($name)) {
                continue;
            }

            // tagname @notice
            $name = "@{$name}";

            if (isset($config_tags[$name])) {

                if (isset($config_tags[$name]['class'])) {
                    $parser = new $config_tags[$name]['class'];
                    $result[] = [
                        'name' => $name,
                        'body' => (string) $parser->parse($body)
                    ];
                }
                else {
                    $result[] = [
                        'name' => $name,
                        'body' => $body,
                    ];
                }

            }

        }

        return $result;
    }

    /**
     * @param $route
     * @param array $form_data
     * @return array|null
     */
    protected function prepareExamples($route, $form_data = [])
    {
        $url = config('app.url');
        $endpoint = $route['uri'];
        $request_type = $route['request_type'];
        $default_headers = config('y2apidoc.documentation.request.headers', null);

        $languages_path =  config('y2apidoc.documentation.languages', null);

        if (is_null($languages_path)) {
            return null;
        }

        $params = [];
        foreach ($form_data as $item) {
            $param = explode('|', (string) $item);
            $params[$param[0]] = $param[1] ?? '';
        }

        // add tempates location
        view()->addLocation($languages_path);

        $result = [];
        foreach (glob($languages_path . DIRECTORY_SEPARATOR . "*.blade.php") as $filename) {
            $template_name = basename($filename, ".blade.php");
            $view = view($template_name)
                ->with('url', $url)
                ->with('endpoint', $endpoint)
                ->with('request_type', strtolower($request_type))
                ->with('default_headers', $default_headers)
                ->with('body_params', $params);

            $result[$template_name] = LanguageRenderer::prettyPrint((string) $view);
        }

        return $result;
    }


    /**
     * @param $request_type
     * @return mixed|string
     */
    protected function getRequestClass($request_type)
    {
        $type = strtolower($request_type);
        $classes = config('y2apidoc.documentation.request.classes', null);

        if (is_null($classes) || empty($classes[$type])) {
            return 'default';
        }

        return $classes[$type];
    }

    /**
     * @param $view
     * @param $template_path
     * @param $output
     */
    protected function saveDocumentation($view, $template_path, $output)
    {
        // copy css
        \File::copyDirectory($template_path . DIRECTORY_SEPARATOR . 'css', $output . DIRECTORY_SEPARATOR . 'css');

        // copy fonts
        \File::copyDirectory($template_path . DIRECTORY_SEPARATOR . 'fonts', $output . DIRECTORY_SEPARATOR . 'fonts');

        // copy js
        \File::copyDirectory($template_path . DIRECTORY_SEPARATOR . 'js', $output . DIRECTORY_SEPARATOR . 'js');

        // copy images
        \File::copyDirectory($template_path . DIRECTORY_SEPARATOR . 'img', $output . DIRECTORY_SEPARATOR . 'img');

        // save view
        file_put_contents($output . DIRECTORY_SEPARATOR . 'index.html', $view);
    }

    /**
     * @return array
     */
    protected function getRoutes()
    {
        $result = [];
        $routes = $this->router->getRoutes();

        foreach ($routes as $route) {
            if (str_contains($route->getPrefix(), $this->prefix)) {
                $result[] = $this->prepareRoutes($route);
            }
        }

        return $result;
    }

    /**
     * @param Route $route
     * @return array
     */
    protected function prepareRoutes(Route $route)
    {
        $uri = $this->parseBindings($route->uri());
        return [
            'host'   => $route->domain(),
            'original_uri' => $route->uri(),
            'uri'    => $uri,
            'name'   => $route->getName(),
            'action' => $route->getActionName(),
            'prefix' => $route->getPrefix(),
            'request_type' => $route->methods()[0],
        ];
    }

    /**
     * @param $uri
     * @return mixed
     */
    protected function parseBindings($uri)
    {
        $bindings = config('y2apidoc.documentation.request.bindings', null);

        if (is_null($bindings)) {
            return $uri;
        }

        foreach ($bindings as $parameter => $binding) {
            $uri = str_replace($parameter, $binding, $uri);
        }

        return $uri;
    }
















    /**
     * Generates the API Documentation based upon a prefix
     *
     * @param  string $prefix
     * @return void
     */

    public function make($prefix)
    {
        $this->prefix = $prefix;
        $this->dotPrefix = str_replace('/', '.', $this->prefix);

        $this->routes = $this->getRoutes();

        if (count($this->routes) == 0) {
            return;
        }

        $endpoints = $this->getEndpoints();

        $this->generateDirectoryStructure();
        $this->generateHTMLCode($endpoints);

        return;
    }

    /**
     * Returns an array of endpoints
     *
     * @return array
     */

    protected function getEndpoints(){

        $endpoints = [];

        foreach ($this->routes as $route) {
            $array = explode("@", $route['action']);
            $class = $array[0];

            if ($class == "Closure") {

                // check for api/v1/docs
                if(strpos($route['uri'], $this->prefix . '/docs') !== false){
                    continue;
                }
            }


            $reflector = new ReflectionClass($class);
            $docBlock = new DocBlock($reflector);

            // remove Controller
            $class = str_replace('Controller', '', $class);

            $endpoints["${class}"]['methods'] = [];
            $endpoints["${class}"]['description'] = $docBlock->getDescription();

        }
        return $this->getEndpointMethods($endpoints);
    }

    /**
     * Returns functions for the endpoints
     *
     * @param  $endpoints
     * @return array
     */

    protected function getEndpointMethods($endpoints){

        foreach ($this->routes as $route) {

            $array = explode("@", $route['action']);
            $class = $array[0];

            if ($class == "Closure") {
                continue;
            }

            $methodName = (count($array) > 1) ? $array[1] : '';
            $endpointName = str_replace('Controller', '', $class);

            $reflector = new ReflectionClass($class);
            $docBlock = new DocBlock($reflector->getMethod($methodName));
            $controllerDocBlock = new DocBlock($reflector);

            $endpointNameCamelCase= $this->convertToSnakeCase($endpointName);
            $endpointNameCamelCasePlural = $this->convertToSnakeCase($endpointName) . 's';

            $route['uri'] = str_replace('{' . strtolower($endpointName) . '}', '{id}', $route['uri']);
            $route['uri'] = str_replace('{' . strtolower($endpointName) . 's}', '{id}', $route['uri']);
            $route['uri'] = str_replace('{' . strtolower($endpointNameCamelCase) . '}', '{id}', $route['uri']);
            $route['uri'] = str_replace('{' . strtolower($endpointNameCamelCasePlural) . '}', '{id}', $route['uri']);

            $route['function'] = $methodName;
            $route['docBlock'] = $docBlock;
            $route['controllerDocBlock'] = $controllerDocBlock;

            array_push($endpoints["${endpointName}"]['methods'], $route);
        }


        return $endpoints;
    }


    /**
    * Returns the path for the view based upon View Type
    *
    * @param  $viewType
    * @return array
    */

    protected  function viewPathForType($viewType){

        $docs = 'docs/';

        if ($viewType == 'docs')
        {
            $docs = '';
        }

        return base_path() . '/resources' . '/views/' . $viewType .'/' . $docs  . $this->prefix . '/';
    }

    /**
    * Generates the HTML Code from the templates and saves them
    *
    * @param  $endpoints
    * @return void
    */

    protected function generateHTMLCode($endpoints)
    {
        /*
        * Docs Index
        */
        $this->updatePrefixAndSaveTemplate('docs', Config::get('apidocs.index_template_path'));

        /*
        * Default Layout
        */

        $this->updatePrefixAndSaveTemplate('layouts', Config::get('apidocs.default_layout_template_path'));

        /*
        * Head
        */

        $this->updatePrefixAndSaveTemplate('includes', Config::get('apidocs.head_template_path'));

        /*
        * Introduction
        */

        $this->updatePrefixAndSaveTemplate('includes', Config::get('apidocs.introduction_template_path'));

        // let's generate the body
        $content = $this->createContentForTemplate($endpoints);


       // Save the default layout
       $this->updateAndSaveDefaultLayoutTemplate($content);

    }

    /**
    * Copies template type from filepath to target
    *
    * @param  string $type, string $filepath
    * @return void
    */

    protected function copyAndSaveTemplate($type, $filepath)
    {
        $target = $this->viewPathForType($type) . basename($filepath);
        File::copy($filepath, $target);
    }

    /**
    *  Retrieves the content from the template and saves it to a new file
    *
    * @param  string $type, string $filepath
    * @return void
    */

    protected function updatePrefixAndSaveTemplate($type, $filepath)
    {

        $content = File::get($filepath);

        $content = str_replace('{prefix}', $this->dotPrefix, $content);
        $newPath = $this->viewPathForType($type) . basename($filepath);

        File::put($newPath, $content);

    }

    /**
    *  Saves the default layout with HTML content
    *
    * @param  string $content
    * @return void
    */

    protected function updateAndSaveDefaultLayoutTemplate($content){

        $type = 'layouts';

        $path = Config::get('apidocs.default_layout_template_path');

        $file = File::get($path);
        $file = str_replace('{prefix}', $this->dotPrefix, $file);
        $file = str_replace('{navigation}', $content['navigation'], $file);
        $file = str_replace('{body-content}', $content['body-content'], $file);
        $logo_path = str_replace('{prefix}', $this->dotPrefix, Config::get('apidocs.logo_path'));
        $file = str_replace('{logo-path}', $logo_path, $file);
        $newPath = $this->viewPathForType($type) . basename($path);

        File::put($newPath, $file);
    }

    /**
    *  Generates the directory structure for the API documentation
    *
    * @param  string $content
    * @return void
    */

    protected function generateDirectoryStructure()
    {
        $docs_views_path = base_path() . '/resources/views/docs/' . $this->prefix;
        $docs_includes_path = base_path() . '/resources/views/includes/docs/' . $this->prefix;
        $docs_layouts_path = base_path() . '/resources/views/layouts/docs/' . $this->prefix;

        $paths = [$docs_views_path, $docs_includes_path, $docs_layouts_path];

        foreach ($paths as $path)
        {
            // delete current directory
            File::deleteDirectory($path, false);

            // create directory structure
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        $this->generateAssetsDirectory();

    }

    /**
    *  Generates the assets directory
    *  by copying the files from the template directory to a public diretory
    *
    * @param  string $content
    * @return void
    */

    private function generateAssetsDirectory()
    {

        $destinationPath = public_path(). '/assets/docs/' . $this->dotPrefix;

        // create assets directory
        File::makeDirectory($destinationPath, $mode = 0777, true, true);

         $targetPath = Config::get('apidocs.assets_path');
         $directories = ['css', 'img', 'js'];

         foreach ($directories as $directory)
         {
            $target = $targetPath . '/' . $directory;
            $dest = $destinationPath . '/' . $directory;
            File::copyDirectory($target, $dest);
         }

    }

    /**
    *  Generates the content for the templates
    *
    * @param  array $endpoints
    * @return void
    */

     private function createContentForTemplate($endpoints = array())
     {

       if(!$endpoints) return FALSE;

        $navigation     = '';
        $body           = '';

        foreach ($endpoints as $endpoint_name => $array) {

            $sectionName = $this->normalizeSectionName($endpoint_name);

            $sectionItem    = '';
            $sectionHead    = '';
            $bodySection    = '';
            $navSections    = '';
            $navItems       = '';

            $navSections .= File::get(config::get('apidocs.navigation_template_path'));
            $navSections = str_replace('{column-title}', $sectionName, $navSections);

            $sectionHead .= File::get(config::get('apidocs.section_header_template_path'));
            $sectionHead = str_replace('{column-name}', $sectionName, $sectionHead);
            $sectionHead = str_replace('{main-description}', $endpoints[$endpoint_name]['description'], $sectionHead);


            if(isset($array['methods'])) {

                foreach ($array['methods'] as $key => $value) {

                    $endpoint = $value;

                    $uri = explode(' ', $endpoint['uri']);

                    $navItems .= File::get(config::get('apidocs.nav_items_template_path'));
                    $navItems = str_replace('{column-title}',  $sectionName, $navItems);
                    $navItems = str_replace('{function}', $endpoint['function'], $navItems);

                    $sectionItem .= File::get(config::get('apidocs.body_content_template_path'));
                    $sectionItem = str_replace('{column-name}', $sectionName, $sectionItem);
                    $sectionItem = str_replace('{request-type}', $endpoint['method'], $sectionItem);
                    $sectionItem = str_replace('{endpoint-short-description}', $endpoint['docBlock']->getShortDescription(),      $sectionItem);
                    $sectionItem = str_replace('{endpoint-long-description}', $endpoint['docBlock']->getLongDescription(),      $sectionItem);

                    $sectionItem = str_replace('{function}', $endpoint['function'], $sectionItem);
                    $sectionItem = str_replace('{request-uri}',  end($uri),  $sectionItem);

                    $method_params =  $endpoint['docBlock']->getTagsByName('param');
                    $controller_params =  $endpoint['controllerDocBlock']->getTagsByName('param');

                    $params = array_merge($method_params, $controller_params);

                    $parameters = '';

                    foreach ($params as $param)
                    {
                        $param_name = str_replace($param->getDescription(), '', $param->getContent());
                        $param_name = str_replace($param->getType(), '', $param_name);
                        $param_name = str_replace(' ', '', $param_name);
                        $param_name = urldecode($param_name);

                        if($param_name[0] == '$'){
                            $param_name = str_replace('$', '', $param_name);
                        }

                        if ($param->getType() == 'array' && strpos($param_name,'[') == false) {
                            $param_name .= '[]';
                        }

                        $parameters .= File::get(config::get('apidocs.parameters_template_path'));
                        $parameters = str_replace('{param-name}', $param_name , $parameters);
                        $parameters = str_replace('{param-type}',  $param->getType(),  $parameters);
                        $parameters = str_replace('{param-desc}',  $param->getDescription(),  $parameters);

                        if(strpos(strtolower($param_name),'password') !== false ){
                            $parameters = str_replace('type="text" class="parameter-value-text" name="' . $param_name . '"', 'type="password" class="parameter-value-text" name="'. $param_name . '"' , $parameters);
                        }
                    }

                    if(strlen($parameters) > 0){
                        $sectionItem = str_replace('{request-parameters}', $parameters, $sectionItem); // insert the parameters into the section items
                    } else {

                        $sectionItem = str_replace('<h4>Parameters</h4>
                            <ul>
                              <li class="parameter-header">
                                <div class="parameter-name">PARAMETER</div>
                                <div class="parameter-type">TYPE</div>
                                <div class="parameter-desc">DESCRIPTION</div>
                                <div class="parameter-value">VALUE</div>
                              </li>
                              {request-parameters}
                            </ul>', '', $sectionItem);
                    }

                }

                $navSections = str_replace('{nav-items}', '<ul>'.$navItems.'</ul>', $navSections); // add the navigation items to the nav section

            } else {

                $navSections = str_replace('{nav-items}', '', $navSections); // add the navigation items to the nav section
            }

            $navigation .= $navSections;

            $bodySection .= File::get(config::get('apidocs.compile_content_template_path'));
            $bodySection = str_replace('{section-header}',   $sectionHead, $bodySection);
            $bodySection = str_replace('{section-details}', $sectionItem, $bodySection);

            $body        .= $bodySection;
        }

        $data = array(
            'navigation'   => $navigation,
            'body-content' => $body
        );

        return $data;
    }


    /**
     * Retuns the last part of the section name
     *
     * @return string
     */
    protected function normalizeSectionName($name){

        $sectionName = explode("\\", $name);
        $c = count($sectionName)-1;
        if ($c < 0) $c = 0;
        $sectionName = $sectionName[$c];

        return $sectionName;
    }

    /**
     * Compile the routes into a displayable format.
     *
     * @return array
     */

//    protected function getRoutes()
//    {
//        $results = array();
//        $routes = $this->router->getRoutes();
//
//        foreach ($routes as $route) {
//            $results[] = $this->getRouteInformation($route);
//        }
//
//        dd($results);
//
//        return array_filter($results);
//    }

    /**
     * Get before filters
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    
    /*
    protected function getBeforeFilters($route)
    {
        $before = array_keys($route->beforeFilters());

        $before = array_unique(array_merge($before, $this->getPatternFilters($route)));

        return implode(', ', $before);
    }
    */

    /**
     * Get all of the pattern filters matching the route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function getPatternFilters($route)
    {
        $patterns = array();

        foreach ($route->methods() as $method)
        {
            // For each method supported by the route we will need to gather up the patterned
            // filters for that method. We will then merge these in with the other filters
            // we have already gathered up then return them back out to these consumers.
            $inner = $this->getMethodPatterns($route->uri(), $method);

            $patterns = array_merge($patterns, array_keys($inner));
        }

        return $patterns;
    }

    /**
     * Get the route information for a given route.
     *
     * @param  string  $name
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function getRouteInformation(Route $route)
    {

        $uri = implode('|', $route->methods()).' '.$route->uri();

        dump($uri);
        return $this->filterRoute(array(
            'host'   => $route->domain(),
            'uri'    => $uri,
            'name'   => $route->getName(),
            'action' => $route->getActionName(),
            // 'before' => $this->getBeforeFilters($route),
            // 'after'  => $this->getAfterFilters($route),
            'prefix' => $route->getPrefix(),
            'method' => $route->methods()[0],
        ));
    }

    /**
     * Filter the route by URI and / or name.
     *
     * @param  array  $route
     * @return array|null
     */
    protected function filterRoute(array $route)
    {

        if (! str_contains($route['prefix'], $this->prefix)) {
            return null;
        }

        return $route;
    }

        /**
     * Get the pattern filters for a given URI and method.
     *
     * @param  string  $uri
     * @param  string  $method
     * @return array
     */
    protected function getMethodPatterns($uri, $method)
    {
        return $this->router->findPatternFilters(Request::create($uri, $method));
    }

    /**
     * Get after filters
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return string
     */
    /*
    protected function getAfterFilters($route)
    {
        return implode(', ', array_keys($route->afterFilters()));
    }
    */

    /**
    * Converts a CamelCase String to Snake Case
    *
    * @param  string $input
    * @return string
    */

    private function convertToSnakeCase($input)
    {
      preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
      $ret = $matches[0];
      foreach ($ret as &$match) {
        $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
      }
      return implode('_', $ret);
    }

}
