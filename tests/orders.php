<?php
/** Real MariaDB integration tests. Only use an explicitly named *_test_* database. */
if (!str_contains((string) getenv('DB_NAME'), '_test_')) {
    fwrite(STDERR, "DB_NAME doit désigner une base _test_ distincte.\n"); exit(2);
}
require __DIR__ . '/../Config/database.php';
require __DIR__ . '/../Services/OrderService.php';

$input = ['adresse_prestation' => 'Adresse fictive', 'lieu_prestation' => 'Bordeaux',
    'date_prestation' => date('Y-m-d', strtotime('+30 days')), 'heure_prestation' => '12:00',
    'nb_personnes' => 9, 'distance_km' => 20];
if (($argv[1] ?? '') === '--worker') {
    while (microtime(true) < (float) $argv[3]) { usleep(1000); }
    try { (new OrderService($pdo))->create(4, (int) $argv[2], $input); echo 'created'; }
    catch (DomainException $error) { echo 'sold-out'; }
    exit;
}
$count = 0;
function check(bool $condition, string $name): void {
    global $count;
    if (!$condition) { throw new RuntimeException('ÉCHEC: ' . $name); }
    $count++; echo "OK $name\n";
}
function rejects(callable $call, string $name): void {
    try { $call(); } catch (DomainException $error) { check(true, $name); return; }
    check(false, $name);
}
$service = new OrderService($pdo);
$pdo->exec("INSERT INTO menus (titre,description,prix,nb_personnes_min,stock_disponible,delai_commande_heures,actif) VALUES ('TEST METIER','Fixture',120,4,10,48,1)");
$menuId = (int) $pdo->lastInsertId();
try {
    $price = calculerPrixCommande(['prix'=>120,'nb_personnes_min'=>4],9,20);
    check($price['prix_total'] === 259.8 && $price['frais_livraison'] === 16.8 && $price['remise_pourcentage'] === 10, 'prix, seuil remise et livraison');
    check(calculerPrixCommande(['prix'=>120,'nb_personnes_min'=>4],8,0)['prix_total'] == 240, 'avant seuil, Bordeaux sans frais');
    rejects(fn()=> $service->create(4,$menuId,array_replace($input,['nb_personnes'=>3])), 'minimum côté serveur');
    rejects(fn()=> $service->create(4,$menuId,array_replace($input,['distance_km'=>-1])), 'distance négative rejetée');
    rejects(fn()=> $service->create(4,$menuId,array_replace($input,['date_prestation'=>date('Y-m-d')])), 'délai rejeté');
    rejects(fn()=> $service->create(4,$menuId,array_replace($input,['_quoted_total'=>1])), 'prix changé après récapitulatif');
    $id=$service->create(4,$menuId,$input);
    check((int)$pdo->query("SELECT stock_disponible FROM menus WHERE id=$menuId")->fetchColumn()===9,'décrément stock');
    rejects(fn()=> $service->transition($id,5,'terminée'), 'transition sautée rejetée');
    rejects(fn()=> $service->update($id,5,$input), 'modification autre propriétaire rejetée');
    $service->update($id,4,array_replace($input,['nb_personnes'=>10]));
    check((int)$pdo->query("SELECT nb_personnes FROM commandes WHERE id=$id")->fetchColumn()===10,'modification en attente');
    $service->cancel($id,4);
    rejects(fn()=> $service->cancel($id,4), 'double annulation rejetée');
    check((int)$pdo->query("SELECT stock_disponible FROM menus WHERE id=$menuId")->fetchColumn()===10,'stock restauré une seule fois');
    $id=$service->create(4,$menuId,$input);
    foreach(['accepté','en préparation','en cours de livraison','livré','en attente du retour de matériel'] as $state) $service->transition($id,5,$state);
    rejects(fn()=> $service->update($id,4,$input), 'modification après acceptation rejetée');
    rejects(fn()=> $service->cancel($id,4), 'annulation client après acceptation rejetée');
    rejects(fn()=> $service->transition($id,5,'terminée'), 'retour obligatoire pour clôturer prêt');
    $pdo->exec("UPDATE commandes SET date_debut_attente_retour=DATE_SUB(NOW(),INTERVAL 25 DAY) WHERE id=$id");
    $service->returnEquipment($id,5);
    $row=$pdo->query("SELECT * FROM commandes WHERE id=$id")->fetch();
    check($row['statut']==='terminée' && (int)$row['frais_retard_materiel']===600 && (int)$row['materiel_retourne']===1,'retour tardif, frais et clôture');
    rejects(fn()=> $service->returnEquipment($id,5), 'double retour rejeté');
    check(calculerJoursOuvres('2026-09-04','2026-09-18')===10 && calculerJoursOuvres('2026-09-04','2026-09-21')===11,'frontière 10 jours ouvrés et week-ends');
    $id2=$service->create(4,$menuId,$input);
    foreach(['accepté','en préparation','en cours de livraison','livré','terminée'] as $state) $service->transition($id2,5,$state);
    check($pdo->query("SELECT statut FROM commandes WHERE id=$id2")->fetchColumn()==='terminée','livraison sans prêt terminée');
    $id3=$service->create(4,$menuId,$input);
    rejects(fn()=> $service->cancel($id3,5,true,'',''), 'annulation employé exige contact');
    $service->cancel($id3,5,true,'Email','Demande du client');
    check($pdo->query("SELECT motif_annulation FROM commandes WHERE id=$id3")->fetchColumn()==='Demande du client','motif annulation conservé');
    $pdo->exec("UPDATE menus SET stock_disponible=1 WHERE id=$menuId");
    $at=microtime(true)+0.5; $processes=[];
    for($i=0;$i<2;$i++) {
        $process=proc_open([PHP_BINARY,__FILE__,'--worker',(string)$menuId,(string)$at],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        fclose($pipes[0]); $processes[]=[$process,$pipes];
    }
    $outputs=[];
    foreach($processes as [$process,$pipes]) {
        $outputs[]=stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]);
        fclose($pipes[1]);fclose($pipes[2]);
        check(proc_close($process)===0 && $error==='','worker concurrent sans erreur');
    }
    sort($outputs);
    check($outputs===['created','sold-out'],'dernière disponibilité : une seule commande concurrente');
    check((int)$pdo->query("SELECT stock_disponible FROM menus WHERE id=$menuId")->fetchColumn()===0,'stock concurrent jamais négatif');
    echo "$count assertions métier réussies.\n";
} finally {
    $pdo->exec("DELETE FROM commandes WHERE menu_id=$menuId");
    $pdo->exec("DELETE FROM menus WHERE id=$menuId");
}
