<?php

require_once __DIR__ . '/env.php';

loadEnv(__DIR__ . '/../.env');

define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));
define('BASE_URL', getenv('BASE_URL'));