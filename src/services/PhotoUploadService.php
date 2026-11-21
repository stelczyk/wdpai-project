<?php

declare(strict_types=1);

namespace App\Service;

use App\Translator;
use finfo;
use RuntimeException;

/**
 * Zdjęcie z treningu: sprawdza rozmiar, prawdziwy typ MIME (finfo, nie nagłówek przeglądarki)
 * i rozszerzenie, zapisuje pod losową nazwą w public/uploads/activities. W bazie ląduje tylko ścieżka.
 */
final class PhotoUploadService
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    // Rozpoznany typ → rozszerzenie, pod którym zapisujemy plik (nigdy rozszerzenie od użytkownika).
    public const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    // Rozszerzenia nazwy od użytkownika, które dopuszczamy dla danego typu.
    private const EXTENSIONS = ['image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/webp' => ['webp']];
    // Względem public/ – ta sama ścieżka trafia do bazy i do atrybutu src w widoku.
    public const DIRECTORY = 'uploads/activities';

    private readonly string $publicDir;

    public function __construct(?string $publicDir = null)
    {
        $this->publicDir = $publicDir ?? dirname(__DIR__, 2) . '/public';
    }

    /**
     * Sprawdza plik z $_FILES. Null = brak pliku (zdjęcie jest opcjonalne).
     *
     * @return array{error: ?string, file: ?array{tmp: string, extension: string}}
     */
    public function validate(?array $upload): array
    {
        $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['error' => null, 'file' => null];
        }

        $tooLarge = Translator::get('photo.too_large', ['max' => self::MAX_BYTES / 1024 / 1024]);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return ['error' => $tooLarge, 'file' => null];
        }
        // is_uploaded_file: plik naprawdę przyszedł w tym żądaniu (nie ścieżka podsunięta w danych).
        if ($error !== UPLOAD_ERR_OK || !is_string($upload['tmp_name'] ?? null) || !is_uploaded_file($upload['tmp_name'])) {
            return ['error' => Translator::get('photo.failed'), 'file' => null];
        }

        $tmp = $upload['tmp_name'];
        if (filesize($tmp) > self::MAX_BYTES) {
            return ['error' => $tooLarge, 'file' => null];
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $extension = strtolower(pathinfo((string) ($upload['name'] ?? ''), PATHINFO_EXTENSION));
        $invalidType = Translator::get('photo.invalid_type');
        if (!isset(self::TYPES[$mime]) || !in_array($extension, self::EXTENSIONS[$mime], true)) {
            return ['error' => $invalidType, 'file' => null];
        }
        // Nagłówek pliku to jeszcze nie obraz – getimagesize musi umieć odczytać wymiary.
        if (@getimagesize($tmp) === false) {
            return ['error' => $invalidType, 'file' => null];
        }

        return ['error' => null, 'file' => ['tmp' => $tmp, 'extension' => self::TYPES[$mime]]];
    }

    /** Przenosi sprawdzony plik pod losową nazwą; zwraca ścieżkę względną do zapisania w bazie. */
    public function store(array $file): string
    {
        $path = self::DIRECTORY . '/' . bin2hex(random_bytes(16)) . '.' . $file['extension'];

        if (!move_uploaded_file($file['tmp'], $this->publicDir . '/' . $path)) {
            throw new RuntimeException('Could not store uploaded photo');
        }

        return $path;
    }

    /** Sprzątanie, gdy zapis aktywności w bazie się nie udał – nie zostawiamy osieroconych plików. */
    public function delete(string $path): void
    {
        $file = $this->publicDir . '/' . $path;
        if (str_starts_with($path, self::DIRECTORY . '/') && is_file($file)) {
            unlink($file);
        }
    }
}
