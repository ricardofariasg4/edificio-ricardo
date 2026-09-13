<?php

namespace App\Services;

class LogService
{
    private const DEFAULT_PER_PAGE = 50;
    private const MAX_PER_PAGE = 200;

    private string $logPath;

    public function __construct(?string $logPath = null)
    {
        $this->logPath = $logPath ?? (string) config('logging.channels.critical.path', storage_path('logs/critical.log'));
    }

    /**
     * Lê o arquivo de log crítico e devolve uma página de entradas, das mais
     * recentes para as mais antigas.
     */
    public function paginate(int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        $entries = $this->readEntries();
        $total = count($entries);

        $data = array_slice($entries, ($page - 1) * $perPage, $perPage);

        return [
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $total === 0 ? 1 : (int) ceil($total / $perPage),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readEntries(): array
    {
        if (!is_file($this->logPath)) {
            return [];
        }

        $lines = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        $entries = [];
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return array_reverse($entries);
    }
}
