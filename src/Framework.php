<?php
class Framework {
	public static function routeCollections (&$app, &$route, $prefix='') {
		$cacheFile = $_SERVER['DOCUMENT_ROOT'] . '/collections/cache.json';
		if (!file_exists($cacheFile)) {
			return;
		}
		$collections = (array)json_decode(file_get_contents($cacheFile), true);
		if (!is_array($collections)) {
			return;
		}
	    foreach ($collections as $collection) {
	        if (isset($collection['p'])) {
	            $app->get($prefix . '/' . $collection['p'] . '(/:method(/:limit(/:skip(/:sort))))', function ($method='all', $limit=null, $skip=0, $sort=[]) use ($collection) {
		            if ($limit === null) {
		            	if (isset($collection['limit'])) {
		                	$limit = $collection['limit'];
		            	} else {
			            	$limit = 10;
			            }
		            }
		            foreach (['limit', 'skip', 'sort'] as $option) {
		            	$key = $collection['p'] . '-' . $method . '-' . $option;
		            	if (isset($_GET[$key])) {
		                	${$option} = $_GET[$key];
		            	}
		            }
		            $separation = Separation::layout($collection['p'] . '.html')->template()->write();
		        });
		    }
	        if (!isset($collection['s'])) {
	        	continue;
	        }
            $app->get($prefix . '/' . $collection['s'] . '/:slug', function ($slug) use ($collection) {
                $separation = Separation::layout($collection['s'] . '.html')->set([
                	['Sep' => $collection['p'], 'a' => ['slug' => basename($slug, '.html')]]
                ])->template()->write();
            });
            if (isset($collection['partials']) && is_array($collection['partials'])) {
            	foreach ($collection['partials'] as $template) {
					$app->get('/' . $collection['s'] . '-' . $template . '/:slug', function ($slug) use ($collection, $template) {
		               	$separation = Separation::html($collection['s'] . '-' . $template . '.html')->template()->write();
        			});
        		}
            }
	    }
	}

	public static function routeForms () {

	}

	public static function routeCustom (&$app, &$route) {
		if (!method_exists($route, 'custom')) {
			return;
		}
		$route->custom($app);
	}

	public static function route () {
		if (php_sapi_name() == 'cli') {
			if ($_SERVER['argv'][1] != 'build') {
				exit;
			}
			Build::project($_SERVER['PWD']);
		}
		\Slim\Slim::registerAutoloader();
		$app = new \Slim\Slim();
		$routePath = $_SERVER['DOCUMENT_ROOT'] . '/Route.php';
		if (!file_exists($routePath)) {
    		exit('Route.php file undefined in site.');
		}
		require $routePath;
		if (!class_exists('Route')) {
    		exit ('Route class not defined properly.');
		}
		Separation::config([
			'partials' 	=> $_SERVER['DOCUMENT_ROOT'] . '/partials/',
			'layouts' 		=> $_SERVER['DOCUMENT_ROOT'] . '/layouts/',
			'sep'			=> $_SERVER['DOCUMENT_ROOT'] . '/sep/'
		]);
		$route = new Route();
		self::routeCollections($app, $route);
		self::routeCustom($app, $route);
		CollectionRoute::json($app);
		$app->run();
	}
}