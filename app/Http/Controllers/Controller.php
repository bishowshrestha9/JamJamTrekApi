<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "JamJam Trek API",
    description: "API documentation for JamJam Trek application"
)]
#[OA\Server(
    url: "http://161.97.167.73:8001",
    description: "Server"
)]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter your bearer token in the format: Bearer {token}"
)]
abstract class Controller
{
    //
}
