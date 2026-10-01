# Design Document: Applicazione Selezione Personale (PHP + JSON)

## 1. Obiettivo e Requisiti
Creare un'applicazione web leggera in PHP per la gestione della selezione del personale, senza dipendere da database relazionali, ma memorizzando i record in un file JSON.

### Requisiti Funzionali
- Campi candidato:
  - **Nome** (stringa, obbligatorio)
  - **Numero telefonico** (stringa/tel, formattato, cliccabile su mobile)
  - **Contatto di provenienza** (stringa es. LinkedIn, Passaparola, Candidatura spontanea, Annuncio)
  - **Zona di residenza** (stringa)
  - **Valutazione** (numero intero in centesimi, range 0 - 100)
- Ordinamento dinamico per **valutazione**:
  - Ordine Decrescente (punteggio più alto in cima)
  - Ordine Crescente (punteggio più basso in cima)
- Operazioni di gestione (CRUD completo):
  - Inserimento nuovo candidato
  - Modifica dati candidato
  - Eliminazione candidato con conferma
- Interfaccia **Mobile-First**:
  - Layout responsive ottimizzato per smartphone e desktop
  - Visualizzazione a card touch-friendly su schermi ridotti e tabella su desktop
  - Pulsanti rapidi per chiamata telefonica (`tel:`)
  - Badge cromatici per la valutazione (es. verde >= 80, blu 60-79, ambra < 60)

---

## 2. Architettura del Sistema

```
_SELEZIONE_PERSONALE/
├── .gitignore
├── README.md
├── data/
│   └── candidati.json            # Archivio JSON con permessi di scrittura
├── src/
│   ├── CandidatoRepository.php   # Classe repository per lettura/scrittura/ordinamento/CRUD con flock
│   └── helpers.php               # Funzioni helper (sanitizzazione HTML, formattazione, badge)
├── assets/
│   └── style.css                 # CSS responsive mobile-first senza framework esterni pesanti
├── index.php                     # Vista principale: form aggiunta rapida, lista candidati, ordinamento
├── edit.php                      # Vista di modifica candidato
├── tests/
│   └── CandidatoRepositoryTest.php # Test di regressione per validazione, persistenza e sorting
└── docs/
    └── plans/
```

---

## 3. Modello Dati JSON (`data/candidati.json`)
Ogni record include:
```json
[
  {
    "id": "cand_6745a1b2c3d4e",
    "nome": "Mario Rossi",
    "numero_telefonico": "+39 333 1234567",
    "contatto_di_provenienza": "LinkedIn",
    "zona_di_residenza": "Milano Nord",
    "valutazione": 88,
    "created_at": "2026-10-01 09:30:00",
    "updated_at": "2026-10-01 09:30:00"
  }
]
```

---

## 4. Persistenza e Concorrenza
- `CandidatoRepository` gestisce il file `data/candidati.json`.
- Tutte le operazioni di scrittura utilizzano `flock($fp, LOCK_EX)` per prevenire race conditions e corruzioni del file JSON.
- Se il file non esiste, viene inizializzato automaticamente come array vuoto `[]`.

---

## 5. UI & Esperienza Utente (Mobile First)
- Mobile View (< 768px): Card verticali con dati in evidenza, link telefonico immediato, bottoni touch larghi, selettore ordinamento in testata.
- Desktop View (>= 768px): Layout a griglia con form laterale/superiore e tabella dettagliata con intestazioni cliccabili per il sort.
- Stile moderno e pulito (palette colori professionale, contrasto WCAG, icone/simboli chiari).

---

## 6. Piano di Versionamento e GitHub
- Inizializzazione repository Git locale (`main`).
- Configurazione `.gitignore` (mantenendo struttura `data/` con `.gitkeep` e file json predefinito).
- Creazione repository remoto su GitHub tramite `gh repo create`.
