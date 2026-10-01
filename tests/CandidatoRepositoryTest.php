<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/CandidatoRepository.php';

function assert_true(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException("Assertion failed: " . $message);
    }
    echo "✓ PASS: $message\n";
}

$tempDir = sys_get_temp_dir() . '/test_selezione_' . uniqid();
mkdir($tempDir);
$testJsonFile = $tempDir . '/candidati_test.json';

try {
    echo "--- Inizio Test CandidatoRepository ---\n";
    $repo = new CandidatoRepository($testJsonFile);

    // 1. Inizializzazione file vuoto
    $all = $repo->getAll();
    assert_true(is_array($all) && count($all) === 0, "Archivio vuoto iniziale");

    // 2. Creazione candidato valido
    $cand1 = $repo->create([
        'nome' => 'Mario Rossi',
        'numero_telefonico' => '+39 333 1234567',
        'contatto_di_provenienza' => 'LinkedIn',
        'zona_di_residenza' => 'Milano',
        'valutazione' => 85,
    ]);
    assert_true(!empty($cand1['id']), "Candidato 1 ha un ID generato");
    assert_true($cand1['valutazione'] === 85, "Candidato 1 ha valutazione 85");

    // 3. Creazione candidato con punteggio più alto
    $cand2 = $repo->create([
        'nome' => 'Laura Bianchi',
        'numero_telefonico' => '340 9876543',
        'contatto_di_provenienza' => 'Passaparola',
        'zona_di_residenza' => 'Roma',
        'valutazione' => 95,
    ]);

    // 4. Creazione candidato con punteggio più basso
    $cand3 = $repo->create([
        'nome' => 'Giuseppe Verdi',
        'numero_telefonico' => '02 123456',
        'contatto_di_provenienza' => 'Annuncio',
        'zona_di_residenza' => 'Torino',
        'valutazione' => 60,
    ]);

    assert_true(count($repo->getAll()) === 3, "Totale candidati inseriti: 3");

    // 5. Test ordinamento Decrescente (default o esplicito)
    $desc = $repo->getAll('valutazione_desc');
    assert_true($desc[0]['valutazione'] === 95, "Primo in decrescente ha 95 (Laura)");
    assert_true($desc[1]['valutazione'] === 85, "Secondo in decrescente ha 85 (Mario)");
    assert_true($desc[2]['valutazione'] === 60, "Terzo in decrescente ha 60 (Giuseppe)");

    // 6. Test ordinamento Crescente
    $asc = $repo->getAll('valutazione_asc');
    assert_true($asc[0]['valutazione'] === 60, "Primo in crescente ha 60 (Giuseppe)");
    assert_true($asc[1]['valutazione'] === 85, "Secondo in crescente ha 85 (Mario)");
    assert_true($asc[2]['valutazione'] === 95, "Terzo in crescente ha 95 (Laura)");

    // 7. Test findById
    $found = $repo->findById($cand1['id']);
    assert_true($found !== null && $found['nome'] === 'Mario Rossi', "findById trova Mario Rossi");
    assert_true($repo->findById('inesistente') === null, "findById su ID inesistente restituisce null");

    // 8. Test modifica
    $updated = $repo->update($cand1['id'], [
        'nome' => 'Mario Rossi Senior',
        'valutazione' => 88,
    ]);
    assert_true($updated !== null && $updated['nome'] === 'Mario Rossi Senior', "Aggiornato nome a Mario Rossi Senior");
    assert_true($updated['valutazione'] === 88, "Aggiornata valutazione a 88");
    assert_true($updated['contatto_di_provenienza'] === 'LinkedIn', "I campi non modificati restano invariati");

    // 9. Test validazione errori
    try {
        $repo->create(['nome' => '', 'valutazione' => 50]);
        throw new RuntimeException("Dovrebbe fallire con nome vuoto");
    } catch (InvalidArgumentException $e) {
        assert_true(true, "Rilevato errore su nome vuoto");
    }

    try {
        $repo->create(['nome' => 'Test', 'valutazione' => 150]);
        throw new RuntimeException("Dovrebbe fallire con valutazione > 100");
    } catch (InvalidArgumentException $e) {
        assert_true(true, "Rilevato errore su valutazione fuori scala (>100)");
    }

    try {
        $repo->create(['nome' => 'Test', 'valutazione' => -5]);
        throw new RuntimeException("Dovrebbe fallire con valutazione < 0");
    } catch (InvalidArgumentException $e) {
        assert_true(true, "Rilevato errore su valutazione fuori scala (<0)");
    }

    // 10. Test eliminazione
    $deleted = $repo->delete($cand3['id']);
    assert_true($deleted === true, "Eliminazione candidato 3 riuscita");
    assert_true(count($repo->getAll()) === 2, "Ora rimangono 2 candidati");
    assert_true($repo->findById($cand3['id']) === null, "Candidato 3 non è più presente");

    // 11. Test stato contattato e toggle
    $cand1Fresh = $repo->findById($cand1['id']);
    assert_true(isset($cand1Fresh['contattato']) && $cand1Fresh['contattato'] === false, "Candidato di default ha contattato = false");

    $newStatus = $repo->toggleContattato($cand1['id']);
    assert_true($newStatus === true, "toggleContattato imposta stato a true");
    $cand1Toggled = $repo->findById($cand1['id']);
    assert_true($cand1Toggled['contattato'] === true, "Verificato stato true nel repository");

    $newStatus2 = $repo->toggleContattato($cand1['id']);
    assert_true($newStatus2 === false, "toggleContattato reimposta stato a false");

    // 12. Test data e orario colloquio
    $candWithInterview = $repo->create([
        'nome' => 'Serena Neri',
        'valutazione' => 91,
        'data_colloquio' => '2026-10-15T15:30',
    ]);
    assert_true($candWithInterview['data_colloquio'] === '2026-10-15T15:30', "data_colloquio salvata correttamente alla creazione");

    $updatedInterview = $repo->update($candWithInterview['id'], [
        'data_colloquio' => '2026-10-16T10:00',
    ]);
    assert_true($updatedInterview['data_colloquio'] === '2026-10-16T10:00', "data_colloquio aggiornata correttamente con update()");

    echo "\n✓ TUTTI I TEST CANDIDATOREPOSITORY SONO PASSATI CON SUCCESSO!\n";
} finally {
    if (file_exists($testJsonFile)) {
        unlink($testJsonFile);
    }
    if (is_dir($tempDir)) {
        rmdir($tempDir);
    }
}
