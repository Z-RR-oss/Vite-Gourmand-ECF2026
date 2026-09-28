<?php
if (!str_contains((string) getenv('DB_NAME'), '_test_')) { exit("Utiliser une base _test_ uniquement.\n"); }
require __DIR__ . '/../Config/database.php';
$cookie = tempnam(sys_get_temp_dir(), 'vg-cookie-');
$file = tempnam(sys_get_temp_dir(), 'vg-image-');
$createdPath = null;
$createdId = null;
function imageRequest(string $path, ?array $fields = null): string {
    global $cookie;
    $curl = curl_init('http://127.0.0.1:8091/' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_TIMEOUT=>15]);
    if ($fields !== null) { curl_setopt($curl,CURLOPT_POSTFIELDS,$fields); }
    $result=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_close($curl);
    if ($result===false || $status>=500) { throw new RuntimeException('Erreur HTTP test images.'); }
    return $result;
}
function imageToken(string $body): string {
    if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $body,$matches)) { throw new RuntimeException('CSRF manquant.'); }
    return $matches[1];
}
try {
    $csrf=imageToken(imageRequest('login.php'));
    imageRequest('login.php',['csrf_token'=>$csrf,'email'=>'corentin@example.com','password'=>'Demo-Vg2026!']);
    $page='gerer-menu-images.php?id=1';
    $csrf=imageToken(imageRequest($page));
    $before=(int)$pdo->query('SELECT COUNT(*) FROM menu_images WHERE menu_id=1')->fetchColumn();
    file_put_contents($file,'<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    $body=imageRequest($page,['csrf_token'=>$csrf,'action'=>'ajouter','texte_alternatif'=>'Test interdit','image'=>new CURLFile($file,'image/svg+xml','script.svg')]);
    if (!str_contains($body,'non reconnue') || (int)$pdo->query('SELECT COUNT(*) FROM menu_images WHERE menu_id=1')->fetchColumn()!==$before) { throw new RuntimeException('SVG non rejeté.'); }
    $canvas=imagecreatetruecolor(16,16);imagepng($canvas,$file);imagedestroy($canvas);
    imageRequest($page,['csrf_token'=>$csrf,'action'=>'ajouter','texte_alternatif'=>'Image de test','image'=>new CURLFile($file,'image/png','nom-non-fiable.php')]);
    $row=$pdo->query('SELECT * FROM menu_images WHERE menu_id=1 ORDER BY id DESC LIMIT 1')->fetch();
    $createdPath=$row['chemin_image'];$createdId=(int)$row['id'];
    if (!preg_match('~^assets/uploads/[a-f0-9]{40}\.png$~',$createdPath) || getimagesize(__DIR__.'/../Public/'.$createdPath)['mime']!=='image/png') { throw new RuntimeException('Image non réencodée.'); }
    imageRequest($page,['csrf_token'=>$csrf,'action'=>'modifier','image_id'=>$createdId,'texte_alternatif'=>'Nouvelle description']);
    if ($pdo->query('SELECT texte_alternatif FROM menu_images WHERE id='.$createdId)->fetchColumn()!=='Nouvelle description') { throw new RuntimeException('Alt non modifié.'); }
    imageRequest($page,['csrf_token'=>$csrf,'action'=>'supprimer','image_id'=>$createdId]);
    if ((int)$pdo->query('SELECT COUNT(*) FROM menu_images WHERE menu_id=1')->fetchColumn()!==$before) { throw new RuntimeException('Suppression galerie échouée.'); }
    echo "4 contrôles images réussis : SVG rejeté, raster réencodé, texte alternatif modifié, galerie supprimée.\n";
} finally {
    if ($createdId) { $pdo->exec('DELETE FROM menu_images WHERE id='.$createdId); }
    if ($createdPath && preg_match('~^assets/uploads/[a-f0-9]{40}\.png$~',$createdPath)) { @unlink(__DIR__.'/../Public/'.$createdPath); }
    unlink($cookie);unlink($file);
}
