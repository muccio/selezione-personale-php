<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/src/CandidatoRepository.php';
require_once __DIR__ . '/src/helpers.php';

$repo = new CandidatoRepository();

$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$sortBy = (string)($_GET['sort'] ?? $_POST['sort'] ?? 'valutazione_desc');

if ($id === '') {
    $_SESSION['flash_error'] = "ID candidato non specificato.";
    header("Location: index.php?sort=" . urlencode($sortBy));
    exit;
}

$candidato = $repo->findById($id);
if ($candidato === null) {
    $_SESSION['flash_error'] = "Candidato non trovato.";
    header("Location: index.php?sort=" . urlencode($sortBy));
    exit;
}

$errorMessage = null;

// Gestione Salvataggio Modifiche (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $repo->update($id, [
            'nome' => $_POST['nome'] ?? '',
            'valutazione' => $_POST['valutazione'] ?? null,
            'numero_telefonico' => $_POST['numero_telefonico'] ?? '',
            'contatto_di_provenienza' => $_POST['contatto_di_provenienza'] ?? '',
            'zona_di_residenza' => $_POST['zona_di_residenza'] ?? '',
            'contattato' => isset($_POST['contattato']),
            'data_colloquio' => $_POST['data_colloquio'] ?? null,
        ]);

        $_SESSION['flash_success'] = "Dati del candidato '{$_POST['nome']}' aggiornati con successo!";
        header("Location: index.php?sort=" . urlencode($sortBy));
        exit;
    } catch (InvalidArgumentException $e) {
        $errorMessage = $e->getMessage();
    } catch (Throwable $e) {
        $errorMessage = "Si è verificato un errore durante l'aggiornamento.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Candidato - <?= e($candidato['nome']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .edit-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .form-actions-inline {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .form-actions-inline .btn {
            flex: 1;
        }
    </style>
</head>
<body>
<div class="container edit-container">

    <header class="app-header">
        <h1 class="app-title">
            <span>✏️</span> Modifica Candidato
        </h1>
        <p class="app-subtitle">
            Aggiorna i dati o modifica la valutazione per la graduatoria
        </p>
    </header>

    <?php if ($errorMessage): ?>
        <div class="alert alert-error">
            <span>⚠</span> <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <main class="card">
        <form action="edit.php?id=<?= urlencode($id) ?>&sort=<?= urlencode($sortBy) ?>" method="POST" novalidate>
            <input type="hidden" name="id" value="<?= e($id) ?>">
            <input type="hidden" name="sort" value="<?= e($sortBy) ?>">

            <div class="form-group">
                <label class="form-label" for="nome">Nome e Cognome <span class="req">*</span></label>
                <input type="text" id="nome" name="nome" class="form-control"
                       value="<?= e($candidato['nome'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="valutazione">Valutazione (in centesimi 0-100)</label>
                <input type="number" id="valutazione" name="valutazione" class="form-control"
                       min="0" max="100" value="<?= e($candidato['valutazione'] !== null ? (string)$candidato['valutazione'] : '') ?>" placeholder="es. 85 (assegnato dopo il colloquio)">
                <div class="form-hint">Punteggio da 0 a 100 assegnato dopo il colloquio (lascia vuoto se in attesa)</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="numero_telefonico">Numero Telefonico</label>
                <input type="tel" id="numero_telefonico" name="numero_telefonico" class="form-control"
                       value="<?= e($candidato['numero_telefonico'] ?? '') ?>" placeholder="es. +39 333 1234567">
            </div>

            <div class="form-group">
                <label class="form-label" for="contatto_di_provenienza">Contatto di Provenienza</label>
                <input type="text" id="contatto_di_provenienza" name="contatto_di_provenienza" class="form-control"
                       value="<?= e($candidato['contatto_di_provenienza'] ?? '') ?>" placeholder="es. LinkedIn, Annuncio, Passaparola">
            </div>

            <div class="form-group">
                <label class="form-label" for="zona_di_residenza">Zona di Residenza</label>
                <input type="text" id="zona_di_residenza" name="zona_di_residenza" class="form-control"
                       value="<?= e($candidato['zona_di_residenza'] ?? '') ?>" placeholder="es. Milano Centro, Roma Est">
            </div>

            <div class="form-group">
                <label class="form-label" for="data_colloquio">Data e Ora Colloquio</label>
                <input type="datetime-local" id="data_colloquio" name="data_colloquio" class="form-control"
                       value="<?= e(format_datetime_for_input($candidato['data_colloquio'] ?? null)) ?>">
                <div class="form-hint">Giorno e orario fissato per il colloquio (lascia vuoto se non fissato)</div>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 14px; margin-bottom: 22px;">
                <input type="checkbox" id="contattato" name="contattato" value="1"
                       <?= !empty($candidato['contattato']) ? 'checked' : '' ?>
                       style="width: 20px; height: 20px; accent-color: #16a34a; cursor: pointer;">
                <label for="contattato" class="form-label" style="margin-bottom: 0; cursor: pointer;">
                    Candidato già contattato
                </label>
            </div>

            <div class="form-actions-inline">
                <a href="index.php?sort=<?= urlencode($sortBy) ?>" class="btn btn-secondary">
                    Annulla
                </a>
                <button type="submit" class="btn btn-primary">
                    Salva Modifiche
                </button>
            </div>
        </form>
    </main>

</div>
</body>
</html>
