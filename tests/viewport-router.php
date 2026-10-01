<?php
// Banc de recette exclusivement local, jamais un point d'entrée de production.
if (PHP_SAPI !== 'cli-server' || !str_contains((string) getenv('DB_NAME'), '_test_')
    || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit;
}
// Le cadre du banc local est autorisé ; les cadres de sites tiers restent interdits.
header_register_callback(static function () {
    header('X-Frame-Options: SAMEORIGIN');
});
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/__recette/viewport') {
    return false;
}
$width = max(320, min(1600, (int) ($_GET['width'] ?? 320)));
$path = $_GET['page'] ?? 'login.php';
if (!is_string($path) || !preg_match('~^[a-z-]+\.php(?:\?[a-zA-Z0-9_=&%-]*)?$~D', $path)) {
    http_response_code(400);
    exit;
}
?><!doctype html><html lang="fr"><meta charset="utf-8"><title>Recette locale - cadre <?= $width ?> px</title><style>body{margin:0}iframe{border:0;display:block;height:900px;width:<?= $width ?>px}</style><iframe id="preview" title="Application en recette" src="/<?= htmlspecialchars($path, ENT_QUOTES) ?>"></iframe><script>
const frame = document.querySelector('iframe');
frame.addEventListener('load', () => {
    const mode = new URLSearchParams(location.search).get('mode');
    if (mode === 'text200') frame.contentDocument.documentElement.style.fontSize = '200%';
    if (mode === 'spacing') {
        const style = frame.contentDocument.createElement('style');
        style.textContent = '*{line-height:1.5!important;letter-spacing:.12em!important;word-spacing:.16em!important}p{margin-bottom:2em!important}';
        frame.contentDocument.head.append(style);
    }
    // La recette attend ce marqueur avant de mesurer les styles réellement appliqués.
    frame.contentDocument.documentElement.dataset.recipeReady = 'true';
});
</script></html>
