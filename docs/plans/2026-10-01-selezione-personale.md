# Selezione Personale PHP & JSON Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Creare un'applicazione web PHP mobile-first per la selezione del personale con salvataggio su file JSON, ordinamento dinamico per valutazione (0-100) e gestione completa dei candidati (CRUD).

**Architecture:** Approccio modulare senza database: classe `CandidatoRepository` per persistenza su `data/candidati.json` con lock atomici (`flock`), funzioni helper di formattazione/sicurezza in `src/helpers.php`, foglio di stile mobile-first responsive `assets/style.css`, viste PHP per gestione e modifica (`index.php`, `edit.php`).

**Tech Stack:** PHP 8+, JSON, HTML5 semantico, CSS3 Moderno (Mobile-First responsive), Git, GitHub CLI (`gh`).

---

### Task 1: Core Storage & Repository con Test Automatizzati

**Files:**
- Create: `data/candidati.json`
- Create: `src/CandidatoRepository.php`
- Test: `tests/CandidatoRepositoryTest.php`

**Step 1: Scrivere i test automatici per `CandidatoRepository`**
Creare lo script di test che verifica:
- Inizializzazione file se assente
- Aggiunta candidato con validazione (nome obbligatorio, valutazione 0-100)
- Ricerca per ID
- Modifica candidato
- Eliminazione candidato
- Ordinamento per valutazione (`valutazione_desc`, `valutazione_asc`)

**Step 2: Eseguire il test per verificare il fallimento**
Run: `php tests/CandidatoRepositoryTest.php`
Expected: Fallimento (classe non esistente).

**Step 3: Implementare `CandidatoRepository.php`**
Implementare i metodi `getAll($sortBy = 'valutazione_desc')`, `findById($id)`, `create($data)`, `update($id, $data)`, `delete($id)` con blocco `flock` esclusivo per le scritture.

**Step 4: Eseguire il test per verificare il successo**
Run: `php tests/CandidatoRepositoryTest.php`
Expected: Tutti i test passano con successo (100% OK).

**Step 5: Commit**
```bash
git add data/ src/CandidatoRepository.php tests/CandidatoRepositoryTest.php
git commit -m "feat: implementa CandidatoRepository con persistenza JSON e test"
```

---

### Task 2: Funzioni Helper di Sicurezza e Formattazione

**Files:**
- Create: `src/helpers.php`
- Test: `tests/HelpersTest.php`

**Step 1: Scrivere il test per gli helper**
Test per:
- `e($string)`: sanitizzazione XSS `htmlspecialchars`
- `format_phone($phone)`: pulizia stringa per link `tel:`
- `badge_valutazione($score)`: classe colore in base al punteggio (es. >= 80 success, 60-79 info, < 60 warning)

**Step 2: Eseguire il test per verificare il fallimento**
Run: `php tests/HelpersTest.php`
Expected: Fallimento.

**Step 3: Implementare `src/helpers.php`**
Funzioni pure per sicurezza e rendering.

**Step 4: Eseguire il test per verificare il passaggio**
Run: `php tests/HelpersTest.php`
Expected: PASS.

**Step 5: Commit**
```bash
git add src/helpers.php tests/HelpersTest.php
git commit -m "feat: aggiungi helper per sanitizzazione e badge"
```

---

### Task 3: Foglio di Stile Mobile-First (`assets/style.css`)

**Files:**
- Create: `assets/style.css`

**Step 1: Creare il CSS responsive**
- Layout mobile-first con flexbox/grid
- Pulsanti grandi per interazione touch (almeno 44px)
- Stile per card (mobile) e tabella fluida (desktop tramite media query)
- Feedback visivo per ordinamento attivo e badge valutazione

**Step 2: Verifica CSS**
Verificare sintassi e conformità regole.

**Step 3: Commit**
```bash
git add assets/style.css
git commit -m "style: aggiungi foglio di stile mobile-first responsive"
```

---

### Task 4: Vista Principale (`index.php`) con Form, Lista e Ordinamento

**Files:**
- Create: `index.php`

**Step 1: Implementare logica controller e template**
- Gestione POST per inserimento nuovo candidato
- Gestione POST per eliminazione candidato (`action=delete`)
- Gestione GET per ordinamento: `?sort=valutazione_desc` (default) e `?sort=valutazione_asc`
- Form di inserimento responsive
- Selettore di ordinamento evidente per mobile
- Render dell'elenco (cards per mobile, tabella per schermi ampi)
- Notifiche flash per successo/errore

**Step 2: Verifica tramite script PHP CLI**
Simulare richieste e verificare output HTML corretto.

**Step 3: Commit**
```bash
git add index.php
git commit -m "feat: implementa index.php con form, ordinamento e lista candidati"
```

---

### Task 5: Vista di Modifica Candidato (`edit.php`)

**Files:**
- Create: `edit.php`

**Step 1: Implementare controller e template di modifica**
- Verifica ID e caricamento candidato esistente (redirect se non trovato)
- Gestione salvataggio modifiche POST
- Form pre-compilato con tutti i campi
- Pulsante Annulla / Torna alla lista

**Step 2: Verifica funzionamento**
Verificare aggiornamento dati nel JSON.

**Step 3: Commit**
```bash
git add edit.php
git commit -m "feat: implementa edit.php per modifica candidato"
```

---

### Task 6: Test di Integrazione End-to-End e Documentazione (`README.md`)

**Files:**
- Create: `tests/EndToEndTest.php`
- Create: `README.md`

**Step 1: Eseguire test end-to-end con server PHP**
Verificare creazione, ordinamento ASC/DESC, modifica ed eliminazione via HTTP.

**Step 2: Redigere documentazione `README.md`**
Istruzioni chiare su come avviare il server (`php -S localhost:8000`), struttura dei campi e spiegazione dell'estendibilità.

**Step 3: Commit**
```bash
git add tests/EndToEndTest.php README.md
git commit -m "docs: aggiungi README e test di integrazione end-to-end"
```

---

### Task 7: Creazione Repository Remoto su GitHub

**Files:**
- Repository GitHub creato tramite `gh repo create`

**Step 1: Creazione repo remoto**
Run: `gh repo create selezione-personale-php --public --source=. --remote=origin --push`

**Step 2: Verifica sincronizzazione remota**
Run: `git status && git remote -v`
Expected: Ramo `main` sincronizzato con `origin/main`.
