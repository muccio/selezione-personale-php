<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/src/CandidatoRepository.php';
require_once __DIR__ . '/src/helpers.php';

$repo = new CandidatoRepository();

// Gestione messaggi flash
$successMsg = $_SESSION['flash_success'] ?? null;
$errorMsg = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Parametro di ordinamento (default: decrescente per valutazione)
$allowedSorts = ['valutazione_desc', 'valutazione_asc', 'nome_asc', 'nome_desc'];
$sortBy = (string)($_GET['sort'] ?? 'valutazione_desc');
if (!in_array($sortBy, $allowedSorts, true)) {
    $sortBy = 'valutazione_desc';
}

// Gestione Azioni POST (Pattern PRG: Post-Redirect-Get)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        try {
            $repo->create([
                'nome' => $_POST['nome'] ?? '',
                'valutazione' => $_POST['valutazione'] ?? null,
                'numero_telefonico' => $_POST['numero_telefonico'] ?? '',
                'contatto_di_provenienza' => $_POST['contatto_di_provenienza'] ?? '',
                'zona_di_residenza' => $_POST['zona_di_residenza'] ?? '',
                'contattato' => isset($_POST['contattato']),
                'data_colloquio' => $_POST['data_colloquio'] ?? null,
            ]);
            $_SESSION['flash_success'] = "Candidato inserito con successo!";
        } catch (InvalidArgumentException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = "Si è verificato un errore durante l'inserimento.";
        }

        header("Location: index.php?sort=" . urlencode($sortBy));
        exit;
    }

    if ($action === 'delete') {
        $idToDelete = (string)($_POST['id'] ?? '');
        if ($idToDelete !== '') {
            $repo->delete($idToDelete);
            $_SESSION['flash_success'] = "Candidato rimosso dall'archivio.";
        }
        header("Location: index.php?sort=" . urlencode($sortBy));
        exit;
    }

    if ($action === 'toggle_contattato') {
        $idToToggle = (string)($_POST['id'] ?? '');
        if ($idToToggle !== '') {
            $newState = $repo->toggleContattato($idToToggle);
            $statoTesto = $newState ? 'segnato come contattato' : 'segnato come da contattare';
            $_SESSION['flash_success'] = "Stato aggiornato: candidato $statoTesto.";
        }
        header("Location: index.php?sort=" . urlencode($sortBy));
        exit;
    }
}

// Lettura candidati ordinati
$candidati = $repo->getAll($sortBy);
$totaleCandidati = count($candidati);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selezione Personale - Gestione Candidati</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">

    <!-- Header Applicazione -->
    <header class="app-header" id="top">
        <div class="header-content">
            <h1 class="app-title">
                <span>📋</span> Selezione Personale
            </h1>
            <p class="app-subtitle">
                Archivio candidati e graduatoria per punteggio di valutazione
            </p>
        </div>
        <div class="header-actions">
            <a href="#nuovo-candidato" class="btn btn-primary btn-header-add">
                <span>➕</span> Nuovo Candidato
            </a>
        </div>
    </header>

    <!-- Messaggi Flash di Feedback -->
    <?php if ($successMsg): ?>
        <div class="alert alert-success">
            <span>✓</span> <?= e($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="alert alert-error">
            <span>⚠</span> <?= e($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Contenuto Principale: Graduatoria ed Elenco Candidati -->
    <main class="main-content">
        <!-- Barra di Ordinamento per Valutazione -->
        <section class="sort-toolbar" aria-label="Ordinamento graduatoria">
            <div class="sort-toolbar-header">
                <span class="sort-count-badge">Graduatoria Candidati (<?= $totaleCandidati ?>)</span>
                <span class="sort-active-label"><?= e(get_active_sort_label($sortBy)) ?></span>
            </div>
            <div class="sort-controls-right">
                <div class="sort-buttons-group">
                    <a href="index.php?sort=valutazione_desc"
                       class="sort-btn <?= $sortBy === 'valutazione_desc' ? 'active' : '' ?>"
                       title="Ordina per punteggio più alto prima">
                        ⬇ Più Alti (100 → 0)
                    </a>
                    <a href="index.php?sort=valutazione_asc"
                       class="sort-btn <?= $sortBy === 'valutazione_asc' ? 'active' : '' ?>"
                       title="Ordina per punteggio più basso prima">
                        ⬆ Più Bassi (0 → 100)
                    </a>
                </div>
                <a href="#nuovo-candidato" class="btn btn-secondary btn-sm sort-add-link" title="Aggiungi un nuovo candidato in fondo">
                    ➕ Aggiungi
                </a>
            </div>
        </section>

            <?php if ($totaleCandidati === 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📂</div>
                    <h3>Nessun candidato presente</h3>
                    <p>Compila il modulo a fianco o in alto per aggiungere il primo candidato alla graduatoria.</p>
                </div>
            <?php else: ?>

                <!-- VISTA MOBILE: Cards con bottoni touch grandi -->
                <div class="mobile-cards-wrapper">
                    <?php foreach ($candidati as $c): ?>
                        <?php
                            $punteggio = isset($c['valutazione']) && $c['valutazione'] !== null ? (int)$c['valutazione'] : null;
                            $telUri = sanitize_phone_for_tel($c['numero_telefonico'] ?? '');
                            $waUrl = whatsapp_url($c['numero_telefonico'] ?? '');
                            $isContacted = !empty($c['contattato']);
                        ?>
                        <article class="candidate-card <?= $isContacted ? 'is-contacted' : '' ?>">
                            <div class="candidate-card-header">
                                <h3 class="candidate-name"><?= e($c['nome']) ?></h3>
                                <?= render_badge_valutazione($punteggio) ?>
                            </div>

                            <div class="candidate-details">
                                <?php if (!empty($c['numero_telefonico'])): ?>
                                    <div class="detail-item" style="flex-direction: column; align-items: flex-start; gap: 6px;">
                                        <div>
                                            <span>📞</span>
                                            <?php if ($telUri !== ''): ?>
                                                <a href="tel:<?= e($telUri) ?>" class="phone-link">
                                                    <?= e($c['numero_telefonico']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span><?= e($c['numero_telefonico']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($telUri !== ''): ?>
                                            <div class="phone-action-row">
                                                <a href="tel:<?= e($telUri) ?>" class="btn-quick-call" title="Chiama direttamente da cellulare">
                                                    📞 Chiama
                                                </a>
                                                <?php if ($waUrl !== ''): ?>
                                                    <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-quick-whatsapp" title="Scrivi su WhatsApp">
                                                        💬 WhatsApp
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($c['contatto_di_provenienza'])): ?>
                                    <div class="detail-item">
                                        <span>🌐</span>
                                        <span>Provenienza: <strong><?= e($c['contatto_di_provenienza']) ?></strong></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($c['zona_di_residenza'])): ?>
                                    <div class="detail-item">
                                        <span>📍</span>
                                        <span>Zona: <strong><?= e($c['zona_di_residenza']) ?></strong></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($c['data_colloquio'])): ?>
                                    <div class="detail-item interview-badge">
                                        <span>📅</span>
                                        <span>Colloquio fissato: <strong><?= e(format_datetime_colloquio($c['data_colloquio'])) ?></strong></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Spunta Contattato + Azioni Modifica/Elimina -->
                            <div class="candidate-actions" style="justify-content: space-between; align-items: center;">
                                <form action="index.php?sort=<?= e($sortBy) ?>" method="POST" class="contact-toggle-form">
                                    <input type="hidden" name="action" value="toggle_contattato">
                                    <input type="hidden" name="id" value="<?= e((string)$c['id']) ?>">
                                    <label class="contact-toggle-label <?= $isContacted ? 'is-checked' : '' ?>">
                                        <input type="checkbox" onchange="this.form.submit()" <?= $isContacted ? 'checked' : '' ?>>
                                        <span><?= $isContacted ? '✓ Contattato' : 'Da contattare' ?></span>
                                    </label>
                                </form>

                                <div style="display: flex; gap: 8px;">
                                    <a href="edit.php?id=<?= urlencode((string)$c['id']) ?>&sort=<?= urlencode($sortBy) ?>"
                                       class="btn btn-secondary btn-sm">
                                        ✏️ Modifica
                                    </a>
                                    <form action="index.php?sort=<?= e($sortBy) ?>" method="POST"
                                          onsubmit="return confirm('Confermi l\'eliminazione del candidato <?= e(addslashes($c['nome'])) ?>?');"
                                          style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e((string)$c['id']) ?>">
                                        <button type="submit" class="btn btn-danger-sm">
                                            🗑️ Elimina
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- VISTA DESKTOP: Tabella Dati Ordinabile -->
                <div class="desktop-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>
                                    <a href="index.php?sort=<?= $sortBy === 'valutazione_desc' ? 'valutazione_asc' : 'valutazione_desc' ?>"
                                       title="Clicca per invertire l'ordine del punteggio">
                                        Valutazione <?= $sortBy === 'valutazione_desc' ? '⬇' : ($sortBy === 'valutazione_asc' ? '⬆' : '↕') ?>
                                    </a>
                                </th>
                                <th>Telefono & Chiamata</th>
                                <th>Stato Contatto</th>
                                <th>Colloquio</th>
                                <th>Provenienza</th>
                                <th>Zona</th>
                                <th style="text-align: right;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidati as $c): ?>
                                <?php
                                    $punteggio = isset($c['valutazione']) && $c['valutazione'] !== null ? (int)$c['valutazione'] : null;
                                    $telUri = sanitize_phone_for_tel($c['numero_telefonico'] ?? '');
                                    $waUrl = whatsapp_url($c['numero_telefonico'] ?? '');
                                    $isContacted = !empty($c['contattato']);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= e($c['nome']) ?></strong>
                                    </td>
                                    <td>
                                        <?= render_badge_valutazione($punteggio) ?>
                                    </td>
                                    <td>
                                        <?php if ($telUri !== ''): ?>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <a href="tel:<?= e($telUri) ?>" class="phone-link" title="Chiama dal cellulare">
                                                    📞 <?= e($c['numero_telefonico']) ?>
                                                </a>
                                                <div class="phone-action-row" style="margin-top: 2px;">
                                                    <a href="tel:<?= e($telUri) ?>" class="btn-quick-call" style="min-height: 32px; padding: 4px 10px; font-size: 0.8rem;" title="Chiama direttamente">
                                                        Chiama
                                                    </a>
                                                    <?php if ($waUrl !== ''): ?>
                                                        <a href="<?= e($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn-quick-whatsapp" style="min-height: 32px; padding: 4px 10px; font-size: 0.8rem;" title="Chat WhatsApp">
                                                            WhatsApp
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="index.php?sort=<?= e($sortBy) ?>" method="POST" class="contact-toggle-form">
                                            <input type="hidden" name="action" value="toggle_contattato">
                                            <input type="hidden" name="id" value="<?= e((string)$c['id']) ?>">
                                            <label class="contact-toggle-label <?= $isContacted ? 'is-checked' : '' ?>">
                                                <input type="checkbox" onchange="this.form.submit()" <?= $isContacted ? 'checked' : '' ?>>
                                                <span><?= $isContacted ? '✓ Contattato' : 'Da contattare' ?></span>
                                            </label>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if (!empty($c['data_colloquio'])): ?>
                                            <span class="interview-pill" title="Data e ora colloquio">
                                                📅 <?= e(format_datetime_colloquio($c['data_colloquio'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.88rem;">Non fissato</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($c['contatto_di_provenienza'] ?? '-') ?></td>
                                    <td><?= e($c['zona_di_residenza'] ?? '-') ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="edit.php?id=<?= urlencode((string)$c['id']) ?>&sort=<?= urlencode($sortBy) ?>"
                                               class="btn btn-secondary btn-sm">
                                                Modifica
                                            </a>
                                            <form action="index.php?sort=<?= e($sortBy) ?>" method="POST"
                                                  onsubmit="return confirm('Confermi l\'eliminazione del candidato <?= e(addslashes($c['nome'])) ?>?');"
                                                  style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= e((string)$c['id']) ?>">
                                                <button type="submit" class="btn btn-danger-sm">
                                                    Elimina
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </main>

        <!-- Sezione Inserimento Candidato (in coda alla pagina) -->
        <section id="nuovo-candidato" class="card form-card-bottom">
            <div class="card-header-with-actions">
                <h2 class="card-title">
                    <span>➕</span> Nuovo Candidato
                </h2>
                <a href="#top" class="back-to-top-link" title="Torna in cima alla graduatoria">
                    ↑ Torna in cima
                </a>
            </div>
            <p class="form-card-subtitle">
                Compila i campi per inserire un candidato. Il voto può essere lasciato vuoto e assegnato successivamente dopo il colloquio.
            </p>

            <form action="index.php?sort=<?= e($sortBy) ?>" method="POST" novalidate class="form-bottom">
                <input type="hidden" name="action" value="create">

                <div class="form-grid-layout">
                    <div class="form-group">
                        <label class="form-label" for="nome">Nome e Cognome <span class="req">*</span></label>
                        <input type="text" id="nome" name="nome" class="form-control" placeholder="es. Mario Rossi" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="valutazione">Valutazione (in centesimi 0-100)</label>
                        <input type="number" id="valutazione" name="valutazione" class="form-control" min="0" max="100" placeholder="es. 85 (lascia vuoto se prima del colloquio)">
                        <div class="form-hint">Opzionale: il voto viene assegnato dopo aver svolto il colloquio</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="numero_telefonico">Numero Telefonico</label>
                        <input type="tel" id="numero_telefonico" name="numero_telefonico" class="form-control" placeholder="es. +39 333 1234567">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contatto_di_provenienza">Contatto di Provenienza</label>
                        <input type="text" id="contatto_di_provenienza" name="contatto_di_provenienza" class="form-control" placeholder="es. LinkedIn, Annuncio, Passaparola">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="zona_di_residenza">Zona di Residenza</label>
                        <input type="text" id="zona_di_residenza" name="zona_di_residenza" class="form-control" placeholder="es. Milano Centro, Roma Est">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="data_colloquio">Data e Ora Colloquio</label>
                        <input type="datetime-local" id="data_colloquio" name="data_colloquio" class="form-control">
                        <div class="form-hint">Opzionale: fissa giorno e orario del colloquio</div>
                    </div>
                </div>

                <div class="form-group form-checkbox-group">
                    <input type="checkbox" id="contattato" name="contattato" value="1" class="custom-checkbox">
                    <label for="contattato" class="form-label mb-0 cursor-pointer">
                        Candidato già contattato
                    </label>
                </div>

                <div class="form-submit-row">
                    <button type="submit" class="btn btn-primary btn-submit-candidato">
                        <span>💾</span> Salva Candidato
                    </button>
                </div>
            </form>
        </section>
</div>
</body>
</html>
