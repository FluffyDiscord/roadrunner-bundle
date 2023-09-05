<?php

namespace Baldinof\RoadRunnerBundle\Integration\XHProf;

use Baldinof\RoadRunnerBundle\Http\MiddlewareInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class XHProfMiddleware implements MiddlewareInterface
{
    public function process(Request $request, HttpKernelInterface $next): \Iterator
    {
        $enabled = isset($_ENV["XHPROF_URL"]) && $_ENV["XHPROF_URL"] !== "";
        if ($enabled) {
            xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
        }

        try {
            yield $next->handle($request);
        } finally {
            if ($enabled) {
                $content = xhprof_disable();

                $client = HttpClient::create();
                $client->request(Request::METHOD_POST, $_ENV["XHPROF_URL"], [
                    "json" => [
                        "profile"  => $content,
                        "tags"     => [],
                        "app_name" => $_ENV["XHPROF_APP_NAME"] ?? "app",
                        "hostname" => $request->getHost(),
                        "date"     => (new \DateTime())->getTimestamp(),
                    ],
                ]);
            }
        }

    }

    public static function getPriority(): int
    {
        return 2500;
    }
}