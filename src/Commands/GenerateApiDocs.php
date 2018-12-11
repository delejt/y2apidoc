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
                        'body' => $parser->parse($body)
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
     * @throws \Exception
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

}
