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
 * Restituisce la classe CSS del badge in base alla valutazione in centesimi (0-100)
 */
function badge_valutazione_class(int $score): string
{
    if ($score >= 80) {
        return 'score-high';
    }
    if ($score >= 60) {
        return 'score-mid';
    }
    return 'score-low';
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
