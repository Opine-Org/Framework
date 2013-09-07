<?php
class Framework {
	public static function routeCollections (&$app, &$route, $prefix='') {
		$dirFiles = glob(__DIR__ . '/../collections/*.php');
		$collectios = [];
		foreach ($dirFiles as $collection) {
			require_once($collection);
			$class = basename($collection, '.php');
			$collections[] = [
				'p' => $class,
				's' => $class::$singular
			];
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
            if (isset($collection['templates']) && is_array($collection['templates'])) {
            	foreach ($collection['templates'] as $template) {
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
}