<?php
declare(strict_types=1);

function assert_e2e(bool $cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException("E2E FAILED: " . $msg);
    }
    echo "✓ PASS E2E: $msg\n";
}

echo "=== Avvio Test End-to-End con PHP Built-in Server ===\n";

$port = 8999;
$docRoot = dirname(__DIR__);

// Avvia server PHP integrato in background
$cmd = sprintf('php -S 127.0.0.1:%d -t %s > /dev/null 2>&1 & echo $!', $port, escapeshellarg($docRoot));
$pid = (int)exec($cmd);

// Attendi che il server sia pronto
usleep(250000); // 250ms

try {
    $baseUrl = "http://127.0.0.1:{$port}";

    // Helper per richieste HTTP
    $httpGet = function(string $path) use ($baseUrl): string {
        $opts = ['http' => ['method' => 'GET', 'ignore_errors' => true]];
        $ctx = stream_context_create($opts);
        $res = @file_get_contents($baseUrl . $path, false, $ctx);
        if ($res === false) {
            throw new RuntimeException("Richiesta fallita a $path");
        }
        return $res;
    };

    $httpPost = function(string $path, array $data) use ($baseUrl): string {
        $content = http_build_query($data);
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($content) . "\r\n",
                'content' => $content,
                'follow_location' => 1,
                'ignore_errors' => true
            ]
        ];
        $ctx = stream_context_create($opts);
        $res = @file_get_contents($baseUrl . $path, false, $ctx);
        if ($res === false) {
            throw new RuntimeException("Richiesta POST fallita a $path");
        }
        return $res;
    };

    // 1. Verifica pagina iniziale
    $htmlIndex = $httpGet('/index.php');
    assert_e2e(str_contains($htmlIndex, 'Selezione Personale'), "Titolo applicazione presente");
    assert_e2e(str_contains($htmlIndex, 'Nuovo Candidato'), "Form inserimento presente");

    // 2. Verifica ordinamento decrescente (default)
    $htmlDesc = $httpGet('/index.php?sort=valutazione_desc');
    $posGiulia = strpos($htmlDesc, 'Giulia Ferrari'); // 92
    $posAlessia = strpos($htmlDesc, 'Alessia Colombo'); // 86
    $posMarco = strpos($htmlDesc, 'Marco Romano'); // 78
    assert_e2e($posGiulia < $posAlessia && $posAlessia < $posMarco, "In decrescente: Giulia (92) precede Alessia (86) che precede Marco (78)");

    // 3. Verifica ordinamento crescente
    $htmlAsc = $httpGet('/index.php?sort=valutazione_asc');
    $posMarcoAsc = strpos($htmlAsc, 'Marco Romano'); // 78
    $posAlessiaAsc = strpos($htmlAsc, 'Alessia Colombo'); // 86
    $posGiuliaAsc = strpos($htmlAsc, 'Giulia Ferrari'); // 92
    assert_e2e($posMarcoAsc < $posAlessiaAsc && $posAlessiaAsc < $posGiuliaAsc, "In crescente: Marco (78) precede Alessia (86) che precede Giulia (92)");

    // 4. Test inserimento nuovo candidato con punteggio massimo (100)
    $resPost = $httpPost('/index.php?sort=valutazione_desc', [
        'action' => 'create',
        'nome' => 'Candidato Top E2E',
        'valutazione' => '100',
        'numero_telefonico' => '+39 333 9999999',
        'contatto_di_provenienza' => 'Test Automatizzato',
        'zona_di_residenza' => 'Roma',
    ]);
    assert_e2e(str_contains($resPost, 'Candidato Top E2E'), "Nuovo candidato inserito e visibile nella lista");
    assert_e2e(str_contains($resPost, '100'), "Punteggio 100 presente nella graduatoria");

    // Verifica che in decrescente sia al primo posto
    $posTop = strpos($resPost, 'Candidato Top E2E');
    $posGiuliaAfter = strpos($resPost, 'Giulia Ferrari');
    assert_e2e($posTop < $posGiuliaAfter, "Candidato con 100/100 è al primo posto in graduatoria decrescente");

    // 5. Test modifica del candidato inserito
    require_once __DIR__ . '/../src/CandidatoRepository.php';
    $repo = new CandidatoRepository();
    $all = $repo->getAll();
    $topCandidate = null;
    foreach ($all as $c) {
        if ($c['nome'] === 'Candidato Top E2E') {
            $topCandidate = $c;
            break;
        }
    }
    assert_e2e($topCandidate !== null, "Trovato candidato Top nel file JSON");

    // Pagina edit
    $htmlEdit = $httpGet('/edit.php?id=' . urlencode($topCandidate['id']));
    assert_e2e(str_contains($htmlEdit, 'Modifica Candidato'), "Pagina di modifica caricata con successo");
    assert_e2e(str_contains($htmlEdit, 'Candidato Top E2E'), "Nome presente nel form di modifica");

    // Effettua modifica: declassa punteggio a 40
    $resEditPost = $httpPost('/edit.php', [
        'id' => $topCandidate['id'],
        'sort' => 'valutazione_desc',
        'nome' => 'Candidato Top E2E (Modificato)',
        'valutazione' => '40',
        'numero_telefonico' => '+39 333 9999999',
        'contatto_di_provenienza' => 'Test Modificato',
        'zona_di_residenza' => 'Napoli',
    ]);
    assert_e2e(str_contains($resEditPost, 'Candidato Top E2E (Modificato)'), "Nome aggiornato visibile");

    // In decrescente ora deve essere in fondo (dopo Marco Romano con 78)
    $posMod = strpos($resEditPost, 'Candidato Top E2E (Modificato)');
    $posMarcoAfter = strpos($resEditPost, 'Marco Romano');
    assert_e2e($posMod > $posMarcoAfter, "Con valutazione 40 è ora in fondo alla classifica decrescente");

    // 6. Test eliminazione del candidato di test
    $resDelete = $httpPost('/index.php?sort=valutazione_desc', [
        'action' => 'delete',
        'id' => $topCandidate['id'],
    ]);
    assert_e2e(!str_contains($resDelete, 'Candidato Top E2E (Modificato)'), "Candidato eliminato non più presente nella pagina");

    echo "\n✓ TUTTI I TEST END-TO-END SONO STATI COMPLETATI CON SUCCESSO!\n";

} finally {
    // Termina il server PHP integrato
    if ($pid > 0) {
        exec("kill -9 $pid > /dev/null 2>&1");
    }
}
