<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

function assert_test(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException("Assertion failed: " . $message);
    }
    echo "✓ PASS: $message\n";
}

echo "--- Inizio Test Helpers ---\n";

// 1. Sanitizzazione XSS con e()
assert_test(e('<script>alert("xss")</script>') === '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', "e() escapa tag html e apici");
assert_test(e(null) === '', "e(null) restituisce stringa vuota");
assert_test(e('Test & Prova') === 'Test &amp; Prova', "e() converte le ampersand");

// 2. Pulizia telefono per href tel:
assert_test(sanitize_phone_for_tel('+39 333-12.34.567') === '+393331234567', "sanitize_phone_for_tel preserva + e rimuove spazi/trattini");
assert_test(sanitize_phone_for_tel('02/123456') === '02123456', "sanitize_phone_for_tel rimuove slash");
assert_test(sanitize_phone_for_tel(null) === '', "sanitize_phone_for_tel(null) restituisce stringa vuota");

// 3. Classe badge valutazione
assert_test(badge_valutazione_class(90) === 'score-high', "Punteggio 90 ha classe score-high");
assert_test(badge_valutazione_class(80) === 'score-high', "Punteggio 80 ha classe score-high");
assert_test(badge_valutazione_class(79) === 'score-mid', "Punteggio 79 ha classe score-mid");
assert_test(badge_valutazione_class(60) === 'score-mid', "Punteggio 60 ha classe score-mid");
assert_test(badge_valutazione_class(59) === 'score-low', "Punteggio 59 ha classe score-low");
assert_test(badge_valutazione_class(0) === 'score-low', "Punteggio 0 ha classe score-low");
assert_test(badge_valutazione_class(null) === 'score-pending', "Punteggio null ha classe score-pending");
assert_test(str_contains(render_badge_valutazione(null), 'Da valutare'), "render_badge_valutazione con null mostra 'Da valutare'");
assert_test(str_contains(render_badge_valutazione(85), '85'), "render_badge_valutazione con 85 mostra '85'");

// 4. Etichette di ordinamento
assert_test(get_active_sort_label('valutazione_desc') === 'Valutazione: Dal più alto al più basso', "Etichetta decrescente corretta");
assert_test(get_active_sort_label('valutazione_asc') === 'Valutazione: Dal più basso al più alto', "Etichetta crescente corretta");

// 5. Link WhatsApp e Chiamata
assert_test(whatsapp_url('+39 333 1234567') === 'https://wa.me/393331234567', "whatsapp_url crea URL corretto per numero con +39");
assert_test(whatsapp_url('333 1234567') === 'https://wa.me/393331234567', "whatsapp_url aggiunge prefisso 39 per cellulare italiano a 10 cifre che inizia con 3");
assert_test(whatsapp_url(null) === '', "whatsapp_url su null restituisce stringa vuota");

// 6. Badge Contattato
$badgeContattato = badge_contattato_info(true);
assert_test($badgeContattato['class'] === 'contacted-yes' && $badgeContattato['label'] === 'Contattato', "badge_contattato_info per true restituisce label Contattato");

$badgeDaContattare = badge_contattato_info(false);
assert_test($badgeDaContattare['class'] === 'contacted-no' && $badgeDaContattare['label'] === 'Da contattare', "badge_contattato_info per false restituisce label Da contattare");

// 7. Formattazione data e ora colloquio
assert_test(format_datetime_colloquio('2026-10-15T15:30') === '15/10/2026 alle 15:30', "format_datetime_colloquio formatta data ISO in italiano");
assert_test(format_datetime_colloquio('2026-10-15 09:00:00') === '15/10/2026 alle 09:00', "format_datetime_colloquio formatta data SQL in italiano");
assert_test(format_datetime_colloquio(null) === '', "format_datetime_colloquio su null restituisce stringa vuota");
assert_test(format_datetime_for_input('2026-10-15 15:30:00') === '2026-10-15T15:30', "format_datetime_for_input converte per input HTML datetime-local");
assert_test(format_datetime_for_input(null) === '', "format_datetime_for_input su null restituisce stringa vuota");

echo "\n✓ TUTTI I TEST HELPERS SONO PASSATI CON SUCCESSO!\n";
