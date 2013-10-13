<?php
namespace Framework;
use Container\Container;

class Framework {
	public function frontController () {
		$sapi = php_sapi_name();
		$root = (($sapi == 'cli') ? getcwd() : $_SERVER['DOCUMENT_ROOT']);
		$container = new Container($root . '/container.yml');
		if ($sapi == 'cli') {
			if (!isset($_SERVER['argv'][1]) || $_SERVER['argv'][1] != 'build') {
				exit;
			}
			$container->build->project($root);
		}
		$slim = $container->slim;
		
		//configuration cache
		$items = [$root . '-collections.json' => false, $root . '-filters.json' => false, $root . '-helpers.json' => false, $root . '-events.json' => false];
		$result = $container->cache->getBatch($items);
		if ($result === true) {
			$container->collectionRoute->cacheSet(json_decode($items[$root . '-collections.json'], true));
			$container->filter->cacheSet(json_decode($items[$root . '-filters.json'], true));
			$container->helperRoute->cacheSet(json_decode($items[$root . '-helpers.json'], true));
			$container->eventRoute->cacheSet(json_decode($items[$root . '-events.json'], true));
		}

		//smart routing
		$this->routeList($slim);
		$container->helperRoute->helpers($root);
		$container->collectionRoute->json($root);
		$container->collectionRoute->pages($root);
		$container->collectionRoute->collectionList($root);
		$container->eventRoute->events($root);
		$container->formRoute->json($root);
		$container->formRoute->pages($root);
		$container->imageResizer->route();
		
		//custom routing
		$routePath = $root . '/Route.php';
		if (!file_exists($routePath)) {
    		exit('Route.php file undefined for project.');
		}
		require $routePath;
		if (!class_exists('\Route')) {
    		exit ('Route class not defined properly.');
		}
		$myRoute = new \Route($container);
		if (method_exists($myRoute, 'custom')) {
			$myRoute->custom();
		}

		//generate output
		ob_start();
		$slim->run();
		$return = ob_get_clean();
		$container->filter->apply($root, $return);
		echo $return;
	}

	private function routeList ($slim) {
		$slim->get('/routes', function () use ($slim) {
			$routes = $slim->router()->getNamedRoutes();
			$paths = [];
			echo '<html><body>';
			foreach ($routes as $route) {
				$pattern = $route->getPattern();
				if (substr_count($pattern, '(')) {
					$pattern = explode('(', $pattern, 2)[0];
				}
				echo '<a href="', $pattern, '">', $route->getName(), '</a><br />';
			}
			echo '</body></html>';
			exit;
		})->name('routes');
	}
}