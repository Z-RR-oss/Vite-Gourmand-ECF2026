<?php

declare(strict_types=1);

/** Accès REST Firebase uniquement : ni PDO ni fichier local remplaçant les statistiques. */
final class StatisticsRepository
{
    private array $config;
    private Closure $transport;
    private ?string $accessToken = null;
    private int $tokenExpiresAt = 0;

    /** Le transport injectable permet de tester les erreurs réseau sans contacter Firebase. */
    public function __construct(array $config, ?callable $transport = null)
    {
        $url = rtrim((string) ($config['database_url'] ?? ''), '/');
        if (!preg_match('~^https://[a-z0-9-]+(?:\.[a-z0-9-]+)*\.(?:firebaseio\.com|firebasedatabase\.app)$~D', $url)) {
            throw new RuntimeException('Firebase non configuré : renseignez une URL HTTPS Realtime Database valide.');
        }
        $path = trim((string) ($config['path'] ?? 'vite_gourmand/statistics_v1'), '/');
        if (!preg_match('~^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*$~D', $path)) {
            throw new RuntimeException('Le chemin des statistiques Firebase est invalide.');
        }
        if (empty($config['service_account_file']) && empty($config['database_secret'])) {
            throw new RuntimeException('Authentification Firebase non configurée.');
        }
        $this->config = array_replace($config, [
            'database_url' => $url,
            'path' => $path,
            'timeout' => max(1, min(30, (int) ($config['timeout'] ?? 10))),
        ]);
        $this->transport = $transport !== null
            ? Closure::fromCallable($transport)
            : fn (string $method, string $url, array $headers, ?string $body, int $timeout): array =>
                self::httpsRequest(
                    $method,
                    $url,
                    $headers,
                    $body,
                    $timeout,
                    filter_var($this->config['force_ipv4'] ?? false, FILTER_VALIDATE_BOOLEAN)
                );
    }

    public function read(): array
    {
        $data = $this->request('GET');
        if ($data === null) {
            throw new RuntimeException('Aucune synchronisation disponible dans Firebase.');
        }
        if (!is_array($data) || ($data['schema_version'] ?? null) !== 1
            || !is_string($data['synced_at'] ?? null)
            || !is_array($data['menus'] ?? []) || !is_array($data['days'] ?? [])) {
            throw new RuntimeException('Le format des statistiques Firebase est invalide.');
        }
        // Firebase omet les collections vides : les restaurer sans inventer de chiffres.
        $data['menus'] = $data['menus'] ?? [];
        $data['days'] = $data['days'] ?? [];
        return $data;
    }

    public function publish(array $snapshot): void
    {
        // Remplacer l'instantané en une fois évite les résultats partiels et les cumuls à la relance.
        $this->request('PUT', $snapshot);
    }

    private function request(string $method, ?array $data = null): mixed
    {
        $url = $this->config['database_url'] . '/' . $this->config['path'] . '.json';
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        $query = $method === 'PUT' ? ['print' => 'silent'] : [];
        if (!empty($this->config['service_account_file'])) {
            $headers[] = 'Authorization: Bearer ' . $this->getAccessToken();
        } else {
            $query['auth'] = $this->config['database_secret'];
        }
        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        $body = $data === null ? null : json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $response = $this->send($method, $url, $headers, $body);
        if ($method === 'PUT' && $response['status'] === 204) {
            return null;
        }
        return $this->decode($response['body']);
    }

    private function getAccessToken(): string
    {
        // Renouveler une minute avant l'expiration pour couvrir la durée d'une requête réseau.
        if ($this->accessToken !== null && time() < $this->tokenExpiresAt - 60) {
            return $this->accessToken;
        }
        $path = realpath((string) $this->config['service_account_file']);
        $publicPath = realpath(__DIR__ . '/../Public');
        if ($path === false || !is_readable($path)
            || ($publicPath !== false && str_starts_with($path, $publicPath . DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Clé de compte de service inaccessible ou placée dans le répertoire public.');
        }
        $contents = @file_get_contents($path);
        $account = $this->decode($contents === false ? '' : $contents);
        if (!is_array($account) || ($account['type'] ?? '') !== 'service_account'
            || !filter_var($account['client_email'] ?? '', FILTER_VALIDATE_EMAIL)
            || !is_string($account['private_key'] ?? null)) {
            throw new RuntimeException('La clé de compte de service Firebase est invalide.');
        }
        if (!function_exists('openssl_sign')) {
            throw new RuntimeException('OpenSSL est requis pour authentifier le compte de service.');
        }
        $now = time();
        $header = self::base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = self::base64url(json_encode([
            'iss' => $account['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.database https://www.googleapis.com/auth/userinfo.email',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));
        // Le JWT signé est échangé contre un jeton OAuth ; la clé privée reste sur le serveur.
        $unsigned = $header . '.' . $claims;
        if (!@openssl_sign($unsigned, $signature, $account['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Impossible de signer la demande OAuth Firebase.');
        }
        $response = $this->send('POST', 'https://oauth2.googleapis.com/token', [
            'Content-Type: application/x-www-form-urlencoded', 'Accept: application/json',
        ], http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned . '.' . self::base64url($signature),
        ], '', '&', PHP_QUERY_RFC3986));
        $token = $this->decode($response['body']);
        if (!is_array($token) || !is_string($token['access_token'] ?? null)
            || $token['access_token'] === '' || preg_match('/[\r\n]/', $token['access_token'])) {
            throw new RuntimeException('Réponse OAuth Firebase invalide.');
        }
        $this->accessToken = $token['access_token'];
        $this->tokenExpiresAt = $now + max(1, min(3600, (int) ($token['expires_in'] ?? 3600)));
        return $this->accessToken;
    }

    private function send(string $method, string $url, array $headers, ?string $body): array
    {
        try {
            $response = ($this->transport)($method, $url, $headers, $body, $this->config['timeout']);
        } catch (Throwable $exception) {
            // Les exceptions du transport peuvent contenir des jetons/URLs : ne pas les propager.
            throw new RuntimeException('Connexion sécurisée à Firebase impossible ou délai dépassé.');
        }
        $status = (int) ($response['status'] ?? 0);
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Le service Firebase a refusé la requête (HTTP ' . $status . ').');
        }
        if (!is_string($response['body'] ?? null)) {
            throw new RuntimeException('Réponse Firebase illisible.');
        }
        return ['status' => $status, 'body' => $response['body']];
    }

    private function decode(string $json): mixed
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Réponse JSON Firebase invalide.');
        }
    }

    private static function base64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function httpsRequest(string $method, string $url, array $headers, ?string $body, int $timeout, bool $forceIpv4 = false): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('L’extension PHP cURL est requise.');
        }
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        // Repli IPv4 propre à l'hébergement ; la vérification du certificat TLS reste active.
        if ($forceIpv4) {
            curl_setopt($handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }
        $result = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if ($result === false) {
            throw new RuntimeException('Échec HTTPS Firebase.');
        }
        return ['status' => $status, 'body' => $result];
    }
}
