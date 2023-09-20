<?php declare(strict_types=1);

Namespace EmmetBlueMiddleware;

interface MiddlewareInterface
{
	public function getStandardResponse();

	public function setLogger(string $errorChannel, string $errorMsg, array $context = [], array $extra = []);
}
