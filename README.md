# 📋 Selezione Personale (PHP & JSON)

Applicazione web leggera in **PHP** per la gestione e la valutazione dei candidati durante i processi di selezione del personale.
Non richiede database (MySQL/PostgreSQL) né configurazioni complesse: tutti i dati vengono memorizzati e gestiti in modo sicuro e concorrente su file **JSON**.

L'interfaccia è progettata con approccio **Mobile-First**: cards touch-friendly e pulsanti rapidi di chiamata (`tel:`) su smartphone, e tabella espansa con ordinamento interattivo su schermi desktop.

---

## 🚀 Caratteristiche Principali

- **Campi Candidato**:
  - **Nome e Cognome** (obbligatorio)
  - **Numero telefonico** (cliccabile per chiamata rapida cellulare `tel:` e chat diretta **WhatsApp**)
  - **Stato Contatto** (spunta / checkbox interattiva per segnare i candidati già contattati)
  - **Data e Ora Colloquio** (campo `datetime-local` per fissare giorno e orario con visualizzazione formattata `📅 15/10/2026 alle 15:30`)
  - **Contatto di provenienza** (es. LinkedIn, candidatura spontanea, passaparola, annuncio)
  - **Zona di residenza** (es. Milano Centro, Roma Est, da remoto)
  - **Valutazione in centesimi** (punteggio da `0` a `100` con badge cromatico graduato)
- **Ordinamento Dinamico Graduatoria**:
  - ⬇ **Decrescente** (dal punteggio più alto al più basso - visualizzazione ideale per i profili migliori)
  - ⬆ **Crescente** (dal punteggio più basso al più alto)
- **Gestione Completa (CRUD & Toggle)**:
  - Inserimento rapido nuovo candidato
  - Spunta istantanea "Contattato" sia da card mobile che da tabella desktop
  - Modifica completa del record e aggiornamento punteggio
  - Eliminazione con richiesta di conferma
- **Architettura Aperta alle Modifiche**:
  - Logica di persistenza separata in `src/CandidatoRepository.php` con lock atomici (`flock`)
  - Aggiungere un nuovo campo richiede solo l'aggiunta al form e alla validazione

---

## 📂 Struttura del Progetto

```text
_SELEZIONE_PERSONALE/
├── data/
│   └── candidati.json            # Archivio dati JSON
├── src/
│   ├── CandidatoRepository.php   # Repository per lettura, salvataggio atomico e ordinamento
│   └── helpers.php               # Funzioni di supporto (sanitizzazione XSS, formato tel:, badge)
├── assets/
│   └── style.css                 # CSS responsive Mobile-First
├── index.php                     # Pagina principale: form inserimento, lista ordinabile, eliminazione
├── edit.php                      # Pagina modifica candidato
├── tests/
│   ├── CandidatoRepositoryTest.php # Test unitari su persistenza e ordinamento
│   ├── HelpersTest.php             # Test funzioni helper
│   └── EndToEndTest.php            # Test di integrazione end-to-end con server HTTP
└── docs/plans/                   # Documentazione di design e piano di implementazione
```

---

## 🛠️ Come Avviare l'Applicazione

È sufficiente avere PHP installato (PHP 8.0+):

1. Posizionati nella cartella del progetto:
   ```bash
   cd _SELEZIONE_PERSONALE
   ```

2. Avvia il server web integrato di PHP:
   ```bash
   php -S localhost:8000
   ```

3. Apri il browser al seguente indirizzo:
   ```text
   http://localhost:8000
   ```

---

## 🧪 Esecuzione dei Test

I test sono stati implementati senza necessità di librerie esterne:

```bash
# Test unitari sul repository
php tests/CandidatoRepositoryTest.php

# Test sugli helper
php tests/HelpersTest.php

# Test di integrazione End-to-End
php tests/EndToEndTest.php
```

---

## 🔧 Come Estendere o Modificare i Campi

Per aggiungere un nuovo campo (es. `email` o `anni_esperienza`):
1. Aggiungi il campo nel metodo `validate` e `create` in [`src/CandidatoRepository.php`](src/CandidatoRepository.php).
2. Aggiungi il campo nel form di inserimento in [`index.php`](index.php).
3. Aggiungi il campo nel form di modifica in [`edit.php`](edit.php).
4. Mostralo nella card mobile e nella colonna della tabella in [`index.php`](index.php).
