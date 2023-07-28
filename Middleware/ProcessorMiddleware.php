<?php declare (strict_types=1);

Namespace EmmetBlueMiddleware\Middleware;

class ProcessorMiddleware implements \EmmetBlueMiddleware\MiddlewareInterface
{
	protected $globalResponse = [];

	protected $apiVersion;
	protected $pluginsNamespace;

	public function __construct(array $options = [], string $pluginsNamespace)
	{
		$this->apiVersion = $options["version"];
		unset($options["version"]);

		$this->pluginsNamespace = $pluginsNamespace;

		$plugin = $this->callPlugin($options);

		if (is_bool($plugin))
		{
			$this->globalResponse["status"] = 201;
			$this->globalResponse["body"]["http_response"]["status"] = 201;
		}

		if(is_array($plugin) && isset($plugin["_meta"]))
		{
			$pluginMeta = $plugin["_meta"];
			$this->globalResponse["body"]["contentData"] = $plugin["_data"];
			$this->globalResponse["status"] = $pluginMeta["status"] ?? 200;
			$this->globalResponse["body"]["message"] = $pluginMeta["message"] ?? "";
			$this->globalResponse["body"]["details"] = $pluginMeta["details"] ?? "";
			$this->globalResponse["body"]["status"] = $pluginMeta["statusMessage"] ?? "success";
			$this->globalResponse["body"]["http_response"]["status"] = $pluginMeta["status"] ?? 200;
		} else {
			$this->globalResponse["body"]["contentData"] = $plugin;
			$this->globalResponse["status"] =  200;
		}

	}

	private function callPlugin(array $options)
	{
		$module = $this->convertResourceToValidClassName($options["module"]);
		$resource = $this->convertResourceToValidClassName($options["resource"]);
		$options["action"] = self::convertActionToValidMethodName($options["action"]);
		$action = strtolower($options["action"]).$resource;
		$plugin = $this->pluginsNamespace."\\$module\\$resource";

		if (!method_exists(new $plugin(), $action)){
			$action = $options["action"];
		}

		$plugin = $plugin."::$action";


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
