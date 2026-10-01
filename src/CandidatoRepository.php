<?php
declare(strict_types=1);

class CandidatoRepository
{
    private string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? (__DIR__ . '/../data/candidati.json');
        $this->ensureStorageExists();
    }

    /**
     * Assicura che la directory e il file JSON esistano
     */
    private function ensureStorageExists(): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!file_exists($this->filePath)) {
            $this->saveAll([]);
        }
    }

    /**
     * Legge tutti i candidati dal file JSON con lock condiviso
     * @return array<int, array<string, mixed>>
     */
    private function readAll(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $fp = fopen($this->filePath, 'r');
        if (!$fp) {
            return [];
        }

        flock($fp, LOCK_SH);
        $size = filesize($this->filePath);
        $content = $size > 0 ? fread($fp, $size) : '';
        flock($fp, LOCK_UN);
        fclose($fp);

        if (empty($content)) {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Salva l'elenco dei candidati su file JSON con lock esclusivo
     * @param array<int, array<string, mixed>> $items
     */
    private function saveAll(array $items): bool
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $fp = fopen($this->filePath, 'c+');
        if (!$fp) {
            throw new RuntimeException("Impossibile aprire il file di memorizzazione: {$this->filePath}");
        }

        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        rewind($fp);

        $json = json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        fwrite($fp, $json);
        fflush($fp);

        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    /**
     * Recupera tutti i candidati con ordinamento specificato
     * @return array<int, array<string, mixed>>
     */
    public function getAll(string $sortBy = 'valutazione_desc'): array
    {
        $items = $this->readAll();

        usort($items, function (array $a, array $b) use ($sortBy): int {
            $scoreA = (int)($a['valutazione'] ?? 0);
            $scoreB = (int)($b['valutazione'] ?? 0);

            switch ($sortBy) {
                case 'valutazione_asc':
                    return $scoreA <=> $scoreB;

                case 'valutazione_desc':
                    return $scoreB <=> $scoreA;

                case 'nome_asc':
                    return strcasecmp((string)($a['nome'] ?? ''), (string)($b['nome'] ?? ''));

                case 'nome_desc':
                    return strcasecmp((string)($b['nome'] ?? ''), (string)($a['nome'] ?? ''));

                case 'data_asc':
                    return strcmp((string)($a['created_at'] ?? ''), (string)($b['created_at'] ?? ''));

                case 'data_desc':
                default:
                    return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
            }
        });

        return $items;
    }

    /**
     * Cerca un candidato per ID
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array
    {
        $items = $this->readAll();
        foreach ($items as $item) {
            if (($item['id'] ?? '') === $id) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Valida i dati di un candidato
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function validate(array $data, bool $isCreate = true): array
    {
        $validated = [];

        if ($isCreate || array_key_exists('nome', $data)) {
            $nome = trim((string)($data['nome'] ?? ''));
            if ($nome === '') {
                throw new InvalidArgumentException("Il campo 'nome' è obbligatorio.");
            }
            $validated['nome'] = $nome;
        }

        if ($isCreate || array_key_exists('valutazione', $data)) {
            $rawScore = $data['valutazione'] ?? null;
            if ($rawScore === null || !is_numeric($rawScore)) {
                throw new InvalidArgumentException("La valutazione deve essere un valore numerico tra 0 e 100.");
            }
            $score = (int)$rawScore;
            if ($score < 0 || $score > 100) {
                throw new InvalidArgumentException("La valutazione in centesimi deve essere compresa tra 0 e 100.");
            }
            $validated['valutazione'] = $score;
        }

        if ($isCreate || array_key_exists('numero_telefonico', $data)) {
            $validated['numero_telefonico'] = trim((string)($data['numero_telefonico'] ?? ''));
        }

        if ($isCreate || array_key_exists('contatto_di_provenienza', $data)) {
            $validated['contatto_di_provenienza'] = trim((string)($data['contatto_di_provenienza'] ?? ''));
        }

        if ($isCreate || array_key_exists('zona_di_residenza', $data)) {
            $validated['zona_di_residenza'] = trim((string)($data['zona_di_residenza'] ?? ''));
        }

        return $validated;
    }

    /**
     * Crea un nuovo candidato
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $validated = $this->validate($data, true);

        $now = date('Y-m-d H:i:s');
        $candidate = [
            'id' => uniqid('cand_', true),
            'nome' => $validated['nome'],
            'numero_telefonico' => $validated['numero_telefonico'] ?? '',
            'contatto_di_provenienza' => $validated['contatto_di_provenienza'] ?? '',
            'zona_di_residenza' => $validated['zona_di_residenza'] ?? '',
            'valutazione' => $validated['valutazione'],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $items = $this->readAll();
        $items[] = $candidate;
        $this->saveAll($items);

        return $candidate;
    }

    /**
     * Aggiorna un candidato esistente
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(string $id, array $data): ?array
    {
        $validated = $this->validate($data, false);
        $items = $this->readAll();
        $updatedCandidate = null;

        foreach ($items as $idx => $item) {
            if (($item['id'] ?? '') === $id) {
                $merged = array_merge($item, $validated);
                $merged['updated_at'] = date('Y-m-d H:i:s');
                $items[$idx] = $merged;
                $updatedCandidate = $merged;
                break;
            }
        }

        if ($updatedCandidate !== null) {
            $this->saveAll($items);
        }

        return $updatedCandidate;
    }

    /**
     * Elimina un candidato per ID
     */
    public function delete(string $id): bool
    {
        $items = $this->readAll();
        $initialCount = count($items);

        $filtered = array_values(array_filter($items, function (array $item) use ($id): bool {
            return ($item['id'] ?? '') !== $id;
        }));

        if (count($filtered) !== $initialCount) {
            $this->saveAll($filtered);
            return true;
        }

        return false;
    }
}
