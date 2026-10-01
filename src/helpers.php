<?php
declare(strict_types=1);

/**
 * Sanitizza una stringa per output HTML sicuro contro XSS
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Pulisce una stringa di numero telefonico per renderla un URI valido in href="tel:..."
 */
function sanitize_phone_for_tel(?string $phone): string
{
    if ($phone === null || trim($phone) === '') {
        return '';
    }

    $trimmed = trim($phone);
    $hasPlus = str_starts_with($trimmed, '+');
    $digits = preg_replace('/[^\d]/', '', $trimmed);

    return $hasPlus ? ('+' . $digits) : ($digits ?? '');
}

/**
 * Restituisce la classe CSS del badge in base alla valutazione in centesimi (0-100) o pending
 */
function badge_valutazione_class(?int $score): string
{
    if ($score === null) {
        return 'score-pending';
    }
    if ($score >= 80) {
        return 'score-high';
    }
    if ($score >= 60) {
        return 'score-mid';
    }
    return 'score-low';
}

/**
 * Renderizza il markup HTML del badge valutazione
 */
function render_badge_valutazione(?int $score): string
{
    if ($score === null) {
        return '<span class="score-badge score-pending" title="Valutazione non ancora assegnata (post-colloquio)">⏳ Da valutare</span>';
    }

    $badgeClass = badge_valutazione_class($score);
    return '<span class="score-badge ' . $badgeClass . '" title="Valutazione: ' . $score . '/100">'
         . $score . '<span class="score-label">/100</span></span>';
}

/**
 * Restituisce un'etichetta leggibile per l'ordinamento attivo
 */
function get_active_sort_label(string $sortBy): string
{
    return match ($sortBy) {
        'valutazione_asc' => 'Valutazione: Dal più basso al più alto',
        'nome_asc' => 'Nome: A - Z',
        'nome_desc' => 'Nome: Z - A',
        'data_asc' => 'Meno recenti prima',
        default => 'Valutazione: Dal più alto al più basso',
    };
}

/**
 * Genera un URL per chat WhatsApp diretta (wa.me)
 */
function whatsapp_url(?string $phone): string
{
    if ($phone === null || trim($phone) === '') {
        return '';
    }

    $trimmed = trim($phone);
    $digits = preg_replace('/[^\d]/', '', $trimmed);
    if ($digits === '') {
        return '';
    }

    // Se è un cellulare italiano di 10 cifre che inizia per 3, aggiungiamo il prefisso internazionale 39
    if (strlen($digits) === 10 && str_starts_with($digits, '3')) {
        $digits = '39' . $digits;
    }

    return 'https://wa.me/' . $digits;
}

/**
 * Restituisce classe CSS ed etichetta per lo stato di contatto
 * @return array{class: string, label: string, icon: string}
 */
function badge_contattato_info(bool $contattato): array
{
    if ($contattato) {
        return [
            'class' => 'contacted-yes',
            'label' => 'Contattato',
            'icon' => '✓'
        ];
    }
    return [
        'class' => 'contacted-no',
        'label' => 'Da contattare',
        'icon' => '⏳'
    ];
}

/**
 * Formatta la data e l'orario del colloquio per visualizzazione leggibile (es. 15/10/2026 alle 15:30)
 */
function format_datetime_colloquio(?string $dateTimeStr): string
{
    if ($dateTimeStr === null || trim($dateTimeStr) === '') {
        return '';
    }

    $timestamp = strtotime($dateTimeStr);
    if ($timestamp === false) {
        return $dateTimeStr;
    }

    return date('d/m/Y \a\l\l\e H:i', $timestamp);
}

/**
 * Formatta la data/ora per il valore di input type="datetime-local" (formato Y-m-d\TH:i)
 */
function format_datetime_for_input(?string $dateTimeStr): string
{
    if ($dateTimeStr === null || trim($dateTimeStr) === '') {
        return '';
    }

    $timestamp = strtotime($dateTimeStr);
    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d\TH:i', $timestamp);
}


