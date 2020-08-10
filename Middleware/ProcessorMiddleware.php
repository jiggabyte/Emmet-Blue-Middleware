<?php declare (strict_types=1);

Namespace EmmetBlueMiddleware\Middleware;

class ProcessorMiddleware implements \EmmetBlueMiddleware\MiddlewareInterface
{
	protected $globalResponse = [];

	protected $apiVersion;

	public function __construct(array $options = [])
	{
		$this->apiVersion = $options["version"];
		unset($options["version"]);

		$plugin = $this->callPlugin($options);

		if (is_bool($plugin))
		{
			$this->globalResponse["status"] = 201;
		}

		$this->globalResponse["body"]["contentData"] = $plugin;
	}

	private function callPlugin(array $options)
	{
		$module = $this->convertResourceToValidClassName($options["module"]);
		$resource = $this->convertResourceToValidClassName($options["resource"]);
		$options["action"] = self::convertActionToValidMethodName($options["action"]);
		$action = strtolower($options["action"]).$resource;
		$plugin = "EmmetBlue\\Plugins\\$module\\$resource";

		if (!method_exists(new $plugin(), $action)){
			$action = $options["action"];
		}

		$plugin = $plugin."::$action";

		try
		{
			unset($options['module'],$options['resource'],$options['action']);

			$pluginParameter = $options["resourceId"] ?? $options;

			if (isset($options["resourceId"]))
			{
				$id = $options["resourceId"];
				unset($options["resourceId"]);
				
				if (!empty($options))
				{
					array_walk_recursive($options, function(&$item, $key){
						$item = filter_var($item, FILTER_SANITIZE_STRING);
					});
					$pluginResponseData = $plugin((int)$id, $options);
				}
				else
				{
					$pluginResponseData = $plugin((int)$id);
				}
			}
			else if(empty($options))
			{
				$pluginResponseData = $plugin();
			}
			else
			{
				array_walk_recursive($options, function(&$item, $key){
					$item = filter_var($item, FILTER_SANITIZE_STRING);
				});
				$pluginResponseData = $plugin($options);
			}

			return $pluginResponseData;
		}
		catch(\TypeError $e)
		{
			$this->globalResponse["body"]["errorStatus"] = true;
			$this->globalResponse["body"]["errorMessage"] = "A type error has occurred. Please make sure you have provided the required data needed to process your request. ".$e->getMessage();
			$this->globalResponse["status"] = 400;
		}
		catch(\Error $e)
		{
			$this->globalResponse["body"]["errorStatus"] = true;
			$this->globalResponse["body"]["errorMessage"] = "Your request cannot be resolved. This occurs when you try to use resources that doesnt exist or when you call undefined actions on resources ".$e->getMessage();
			$this->globalResponse["status"] = 501;
		}
		catch(\PDOException $e)
		{
			$this->globalResponse["body"]["errorStatus"] = true;
			$this->globalResponse["body"]["errorMessage"] = "A database related error has occurred. Please make sure the database server allows connections and your connection data is valid. It is also possible there's a problem with your supplied data such as a duplicate record or invalid input, please contact an administrator if this error persists ".$e->getMessage();
			$this->globalResponse["status"] = 503;
		}
		catch(\Elasticsearch\Common\Exceptions\BadRequest400Exception $e)
		{

			$this->globalResponse["body"]["errorStatus"] = true;
			$this->globalResponse["body"]["errorMessage"] = "Unable to process data retrieval request. Please make sure the database server allows connections and your connection data is valid. It is also possible there's a problem with your supplied data, please contact an administrator if this error persists";
			$this->globalResponse["status"] = 503;
		}
		catch(\Exception $e){
			$this->globalResponse["body"]["errorStatus"] = true;
			$this->globalResponse["body"]["errorMessage"] = "A server error has occurred. ".$e->getMessage();
			$this->globalResponse["status"] = 500;
		}
	}

	private function convertObjectNameToPsr2(string $objectName)
	{
		return ucfirst(strtolower($objectName));
	}

	private function convertResourceToValidClassName(string $resourceString){
		$stringParts = explode("-", $resourceString);
		$firstIndex = ucfirst($stringParts[0]);
		unset($stringParts[0]);
		foreach ($stringParts as $key=>$stringPart)
		{
			$stringParts[$key] = self::convertObjectNameToPsr2($stringPart);
		}

		return $firstIndex.implode("", $stringParts);
	}

	private function convertActionToValidMethodName(string $actionString)
	{
		$stringParts = explode("-", $actionString);
		$firstIndex = strtolower($stringParts[0]);
		unset($stringParts[0]);
		foreach ($stringParts as $key=>$stringPart)
		{
			$stringParts[$key] = self::convertObjectNameToPsr2($stringPart);
		}

		return $firstIndex.implode("", $stringParts);
	}

	public function getStandardResponse()
	{
		return $this->globalResponse;
	}
}
