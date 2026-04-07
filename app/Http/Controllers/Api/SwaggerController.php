<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Generator;

class SwaggerController extends Controller
{
    /**
     * Serve the Swagger UI HTML page.
     */
    public function ui(): Response
    {
        $specUrl = url('/api/docs/openapi.json');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>GoodPunch API Docs</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css" />
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
<script>
    SwaggerUIBundle({
        url: '{$specUrl}',
        dom_id: '#swagger-ui',
        presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
        layout: 'BaseLayout',
        deepLinking: true,
    });
</script>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    /**
     * Generate and return the OpenAPI JSON spec.
     */
    public function spec(): JsonResponse
    {
        $scanPaths = [app_path('Http/Controllers/Api')];

        $openapi = Generator::scan($scanPaths);

        return response()->json(json_decode($openapi->toJson(), true));
    }
}
