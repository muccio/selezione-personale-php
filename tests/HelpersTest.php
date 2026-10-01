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

// 4. Etichette di ordinamento
assert_test(get_active_sort_label('valutazione_desc') === 'Valutazione: Dal più alto al più basso', "Etichetta decrescente corretta");
assert_test(get_active_sort_label('valutazione_asc') === 'Valutazione: Dal più basso al più alto', "Etichetta crescente corretta");

echo "\n✓ TUTTI I TEST HELPERS SONO PASSATI CON SUCCESSO!\n";
