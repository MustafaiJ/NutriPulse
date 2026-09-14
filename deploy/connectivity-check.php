<?php

/**
 * NutriPulse — outbound HTTPS connectivity check.
 *
 * USE: upload this file to your document root as e.g. `check.php`,
 * open https://<your-host>/check.php in a browser, read the result,
 * then DELETE the file. It must NOT remain on the server.
 *
 * Why: Hepsia/ResellersPanel shared hosting sometimes blocks OUTBOUND
 * connections by default. Our app calls Google's Gemini API for AI
 * calorie estimates and dashboard insights. If the Gemini host is
 * unreachable, the app still works (it falls back to a built-in
 * heuristic estimator) but the AI features silently stop.
 *
 * Pass = "HTTP <2xx/3xx/4xx> (curl)" or an OK line from
 * file_get_contents. A FAIL for generativelanguage.googleapis.com means
 * you should contact ResellersPanel support and ask them to whitelist
 * outbound HTTPS to `generativelanguage.googleapis.com` for your account.
 */
header('Content-Type: text/plain; charset=utf-8');

const TARGETS = [
    'Internet reachability (google.com)' => 'https://www.google.com/',
    'Gemini AI API host (calorie estimates & insights)' => 'https://generativelanguage.googleapis.com/',
];

echo "NutriPulse connectivity check\n";
echo str_repeat('-', 50)."\n";
echo 'PHP version        : '.PHP_VERSION."\n";
echo 'curl extension     : '.(function_exists('curl_init') ? 'yes' : 'no')."\n";
echo 'file_get_contents  : '.(ini_get('allow_url_fopen') ? 'yes (allow_url_fopen=On)' : 'no (allow_url_fopen=Off)')."\n";
echo 'pdo_mysql          : '.(extension_loaded('pdo_mysql') ? 'yes' : 'no')."  <- required for this app\n";
echo str_repeat('-', 50)."\n\n";

foreach (TARGETS as $label => $url) {
    $r = http_probe($url);
    printf("[%s] %s => %s\n", $r['ok'] ? 'PASS' : 'FAIL', $label, $r['detail']);
}

echo "\n";
echo "If the Gemini line says FAIL, ask support to allow outbound HTTPS to\n";
echo "generativelanguage.googleapis.com. Delete this file when done.\n";

function http_probe(string $url): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($body !== false) {
            return ['ok' => true, 'detail' => "HTTP {$code} (curl)"];
        }

        return ['ok' => false, 'detail' => 'curl error: '.$err];
    }

    $ctx = stream_context_create([
        'http' => ['timeout' => 15],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $res = @file_get_contents($url, false, $ctx);

    if ($res !== false) {
        return ['ok' => true, 'detail' => 'OK (file_get_contents)'];
    }

    $err = error_get_last()['message'] ?? 'unknown error';

    return ['ok' => false, 'detail' => 'file_get_contents: '.$err];
}
