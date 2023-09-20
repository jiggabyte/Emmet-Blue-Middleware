<?php declare (strict_types = 1);

namespace EmmetBlueMiddleware\Middleware;

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

        if (is_bool($plugin)) {
            $this->globalResponse["status"] = 201;
            $this->globalResponse["body"]["http_response"]["status"] = 201;
        }

        if (is_array($plugin) && isset($plugin["_meta"])) {
            $pluginMeta = $plugin["_meta"];
            $this->globalResponse["body"]["contentData"] = $plugin["_data"];
            $this->globalResponse["status"] = $pluginMeta["status"] ?? 200;
            $this->globalResponse["body"]["message"] = $pluginMeta["message"] ?? "";
            $this->globalResponse["body"]["details"] = $pluginMeta["details"] ?? "";
			$this->globalResponse["body"]["code"] = $pluginMeta["code"] ?? 0;
            $this->globalResponse["body"]["status"] = $pluginMeta["statusMessage"] ?? "success";
            $this->globalResponse["body"]["http_response"]["status"] = $pluginMeta["status"] ?? 200;
        } else {
            $this->globalResponse["body"]["contentData"] = $plugin;
            $this->globalResponse["status"] = 200;
        }

    }

    private function callPlugin(array $options)
    {
        $module = $this->convertResourceToValidClassName($options["module"]);
        $resource = $this->convertResourceToValidClassName($options["resource"]);
        $options["action"] = self::convertActionToValidMethodName($options["action"]);
        $action = strtolower($options["action"]) . $resource;
        $plugin = $this->pluginsNamespace . "\\$module\\$resource";

        if (!method_exists(new $plugin(), $action)) {
            $action = $options["action"];
        }

        $plugin = $plugin . "::$action";
        try {
            unset($options['module'], $options['resource'], $options['action']);

            $pluginParameter = $options["resourceId"] ?? $options;

            if (isset($options["resourceId"])) {
                $id = $options["resourceId"];
                unset($options["resourceId"]);

                if (!empty($options)) {
                    array_walk_recursive($options, function (&$item, $key) {
                        $item = filter_var($item, FILTER_SANITIZE_STRING);
                    });
                    $pluginResponseData = $plugin((int) $id, $options);
                } else {
                    $pluginResponseData = $plugin((int) $id);
                }
            } else if (empty($options)) {
                $pluginResponseData = $plugin();
            } else {
                array_walk_recursive($options, function (&$item, $key) {
                    $item = filter_var($item, FILTER_SANITIZE_STRING);
                });
                $pluginResponseData = $plugin($options);
            }

            return $pluginResponseData;

        } catch (\TypeError $e) {
            $errorMeta = \json_decode($e->getMessage());
            // $errorMeta = self::toArrayUtil($errorMeta);
            // $errorMeta = \json_decode($e->getMessage());
            $this->globalResponse["body"]["contentData"] = [];
            $this->globalResponse["status"] = $errorMeta->status ?? 400;
            $this->globalResponse["body"]["message"] = $errorMeta->message ?? "Sorry, an error occurred! Please retry later.";
            $this->globalResponse["body"]["details"] = $errorMeta->details ?? "";
            $this->globalResponse["body"]["status"] = $errorMeta->statusMessage ?? "error";
			$this->globalResponse["body"]["code"] = $errorMeta->code ?? 2000;
            $this->globalResponse["body"]["http_response"]["status"] = $errorMeta->status ?? 400;

            if($this->globalResponse["body"]["code"] == 2000){
                $this->setLogger("Uncaught Error",$e->getMessage(),[],[]);
            }

        } catch (\Error $e) {
			$errorMeta = \json_decode($e->getMessage());
            // $errorMeta = self::toArrayUtil($errorMeta);
            // $errorMeta = \json_decode($e->getMessage());
            $this->globalResponse["body"]["contentData"] = [];
            $this->globalResponse["status"] = $errorMeta->status ?? 501;
            $this->globalResponse["body"]["message"] = $errorMeta->message ?? "Sorry, an error occurred! Please retry later.";
            $this->globalResponse["body"]["details"] = $errorMeta->details ?? "";
            $this->globalResponse["body"]["status"] = $errorMeta->statusMessage ?? "error";
			$this->globalResponse["body"]["code"] = $errorMeta->code ?? 2000;
            $this->globalResponse["body"]["http_response"]["status"] = $errorMeta->status ?? 501;

            if($this->globalResponse["body"]["code"] == 2000){
                $this->setLogger("Uncaught Error",$e->getMessage(),[],[]);
            }

        } catch (\PDOException $e) {
            $errorMeta = \json_decode($e->getMessage());
            // $errorMeta = self::toArrayUtil($errorMeta);
            // $errorMeta = \json_decode($e->getMessage());
            $this->globalResponse["body"]["contentData"] = [];
            $this->globalResponse["status"] = $errorMeta->status ?? 503;
            $this->globalResponse["body"]["message"] = $errorMeta->message ?? "Sorry, an error occurred! Please retry later.";
            $this->globalResponse["body"]["details"] = $errorMeta->details ?? "";
            $this->globalResponse["body"]["status"] = $errorMeta->statusMessage ?? "error";
			$this->globalResponse["body"]["code"] = $errorMeta->code ?? 2000;
            $this->globalResponse["body"]["http_response"]["status"] = $errorMeta->status ?? 503;

            if($this->globalResponse["body"]["code"] == 2000){
                $this->setLogger("Uncaught Error",$e->getMessage(),[],[]);
            }

        } catch (\Elasticsearch\Common\Exceptions\BadRequest400Exception $e) {
            $errorMeta = \json_decode($e->getMessage());
            // $errorMeta = self::toArrayUtil($errorMeta);
            // $errorMeta = \json_decode($e->getMessage());
            $this->globalResponse["body"]["contentData"] = [];
            $this->globalResponse["status"] = $errorMeta->status ?? 503;
            $this->globalResponse["body"]["message"] = $errorMeta->message ?? "Sorry, an error occurred! Please retry later.";
            $this->globalResponse["body"]["details"] = "Elasticsearch\Common\Exceptions\BadRequest400Exception ".$errorMeta->details ?? "";
            $this->globalResponse["body"]["status"] = $errorMeta->statusMessage ?? "error";
			$this->globalResponse["body"]["code"] = $errorMeta->code ?? 2000;
            $this->globalResponse["body"]["http_response"]["status"] = $errorMeta->status ?? 503;

            if($this->globalResponse["body"]["code"] == 2000){
                $this->setLogger("Uncaught Error",$e->getMessage(),[],[]);
            }

        } catch (\Exception $e) {
			$errorMeta = \json_decode($e->getMessage());
            // $errorMeta = self::toArrayUtil($errorMeta);
            // $errorMeta = \json_decode($e->getMessage());
            $this->globalResponse["body"]["contentData"] = [];
            $this->globalResponse["status"] = $errorMeta->status ?? 500;
            $this->globalResponse["body"]["message"] = $errorMeta->body->message ?? "Sorry, an error occurred! Please retry later.";
            $this->globalResponse["body"]["details"] = $errorMeta->body->details ?? "";
            $this->globalResponse["body"]["status"] = $errorMeta->body->statusMessage ?? "error";
			$this->globalResponse["body"]["code"] = $errorMeta->body->code ?? 2000;
            $this->globalResponse["body"]["http_response"]["status"] = $errorMeta->body->http_response->status ?? 500;

            if($this->globalResponse["body"]["code"] == 2000){
                $this->setLogger("Uncaught Error",$e->getMessage(),[],[]);
            }

        }

    }

    private function toArrayUtil(\stdClass $obj){
        $toArray = function ($arr) use (&$toArray) {
            return (is_scalar($arr) || is_null($arr))
            ? $arr
            : array_map($toArray, (array) $arr);
        };

        return $toArray($obj);
    }

    private function convertObjectNameToPsr2(string $objectName)
    {
        return ucfirst(strtolower($objectName));
    }

    private function convertResourceToValidClassName(string $resourceString)
    {
        $stringParts = explode("-", $resourceString);
        $firstIndex = ucfirst($stringParts[0]);
        unset($stringParts[0]);
        foreach ($stringParts as $key => $stringPart) {
            $stringParts[$key] = self::convertObjectNameToPsr2($stringPart);
        }

        return $firstIndex . implode("", $stringParts);
    }

    private function convertActionToValidMethodName(string $actionString)
    {
        $stringParts = explode("-", $actionString);
        $firstIndex = strtolower($stringParts[0]);
        unset($stringParts[0]);
        foreach ($stringParts as $key => $stringPart) {
            $stringParts[$key] = self::convertObjectNameToPsr2($stringPart);
        }

        return $firstIndex . implode("", $stringParts);
    }

    public function getStandardResponse()
    {
        return $this->globalResponse;
    }

    public function setLogger(string $errorChannel, string $errorMsg, array $context = [], array $extra = []){
        // Create the logger
        $logger = new \Logger($errorChannel);

        // Now add some handlers
        $stream_handler = new \StreamHandler('./logs/log-'.date('Y-m-d').'-file.log', \Logger::DEBUG);
        $stream_handler->setFormatter( new \Monolog\Formatter\JsonFormatter() );
        $logger->pushHandler($stream_handler);
        $logger->pushHandler(new \FirePHPHandler());

        $datetimeObj = new \DateTime();

        // Define a custom processor function to add data to the context key
        $logger->pushProcessor(function ($record) use ($datetimeObj) {
            // Add or modify data in the 'context' key
            $record['context'] = $context;
            $record['datetime'] = [
                "date" => $datetimeObj->format('Y-m-d H:i:s.uP') ?? date('Y-m-d H:i:s.Z'),
                "timezone_type" => 2,
                "timezone" => date_default_timezone_get() //"UTC"
            ];
            $record['extra'] = $extra;
            return $record;
        });

        // You can now use your logger
        $logger->info($errorMsg);
    }

}
