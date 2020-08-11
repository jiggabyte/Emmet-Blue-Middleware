<?php declare(strict_types=1);

Namespace EmmetBlueMiddleware\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use EmmetBlue\Core\Constant;


class RequestValidatorMiddleware implements \EmmetBlueMiddleware\MiddlewareInterface
{
	public static $requestActions = [
	];

	public function __construct(){
		self::loadWhiteLists();
	}

	protected function loadWhiteLists(){
		$whitelists = ["PUT", "OPTIONS", "GET", "POST", "DELETE"];

		try {
			$whiteListsFile = Constant::getGlobals()["config-dir"]["whitelists"];
			if (file_exists($whiteListsFile)){
				$whitelists = json_decode(file_get_contents($whiteListsFile));
			}
		}
		catch (\TypeError $e) {
		}

		self::$requestActions = (array)$whitelists;
		
		return;
	}

	public function getStandardResponse()
	{
		return function(RequestInterface $request, ResponseInterface $response, callable $next)
		{
			$args = $request->getAttribute('routeInfo')[2];
			$requestMethod = $request->getMethod();
			
			if (array_key_exists($request->getMethod(), self::$requestActions))
			{
				foreach (self::$requestActions[$requestMethod] as $value)
				{
					if (stripos($args["action"], $value) !== false)
					{
						return $next($request, $response);
					}	
				}
			}
			
			return $next($request, $response);
		};
	}
}