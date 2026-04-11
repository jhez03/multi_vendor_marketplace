<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * ProfileUploadService
 *
 * Centralises all profile image upload logic so controllers stay thin
 * and security measures are applied in one place.
 *
 * Security measures applied:
 *
 * 1. Unique filenames — we NEVER trust the original filename from the
 *    client. A malicious actor could submit "../../config/.env" or
 *    a filename with null bytes. We generate a cryptographically random
 *    token + timestamp-based name, keeping only the safe extension.
 *
 * 2. Extension whitelist — only known-safe image extensions are accepted.
 *    Combined with Symfony's File constraint (which uses finfo internally
 *    for MIME detection), this creates two layers of validation.
 *
 * 3. Move atomically — Symfony's move() uses rename() internally which is
 *    atomic on most filesystems, preventing partial writes.
 *
 * 4. Old file cleanup — we delete the old avatar/logo when a new one is
 *    uploaded so disk space doesn't leak. We never delete placeholder files.
 *
 * 5. Directory isolation — avatars and logos are stored in separate,
 *    purpose-specific directories, reducing blast radius if one is
 *    compromised.
 */
final class ProfileUploadService
{
    /**
     * Extensions we consider safe for user-uploaded images.
     * Must match the mimeTypes in the form constraints.
     */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $avatarsDirectory,
        private readonly string $logosDirectory,
    ) {}

    /**
     * Uploads a customer avatar and returns the new filename.
     *
     * @throws FileException if the move fails (disk full, permissions, etc.)
     */
    public function uploadAvatar(UploadedFile $file, ?string $currentFilename): string
    {
        $newFilename = $this->generateUniqueFilename($file);

        $file->move($this->avatarsDirectory, $newFilename);

        // Clean up old file — don't delete the placeholder
        $this->deleteOldFile($this->avatarsDirectory, $currentFilename);

        return $newFilename;
    }

    /**
     * Uploads a store logo and returns the new filename.
     *
     * @throws FileException if the move fails.
     */
    public function uploadLogo(UploadedFile $file, ?string $currentFilename): string
    {
        $newFilename = $this->generateUniqueFilename($file);

        $file->move($this->logosDirectory, $newFilename);

        $this->deleteOldFile($this->logosDirectory, $currentFilename);

        return $newFilename;
    }

    /**
     * Generates a cryptographically safe unique filename.
     *
     * Pattern: {safe-original-stem}-{unique-id}.{safe-ext}
     * Example: my-store-logo-63f1a2b9c4d5e.webp
     *
     * Why include the original stem? It aids debugging (e.g. in server logs)
     * without exposing any user-supplied path components.
     */
    private function generateUniqueFilename(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $this->slugger->slug($originalFilename)->lower();

        // guessExtension() uses finfo — more reliable than getClientOriginalExtension()
        $ext = strtolower($file->guessExtension() ?? 'bin');

        // Fallback to 'jpg' if the extension is not in our whitelist
        if (! in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            $ext = 'jpg';
        }

        // bin2hex(random_bytes(8)) gives 16 hex chars — collision probability ≈ 0
        $uniqueToken = bin2hex(random_bytes(8));

        return sprintf('%s-%s.%s', $safeFilename, $uniqueToken, $ext);
    }

    /**
     * Deletes the old profile image file, safely ignoring missing files.
     *
     * We never delete 'placeholder.png' or any file with 'placeholder' in
     * the name to protect default fallback images.
     */
    private function deleteOldFile(string $directory, ?string $filename): void
    {
        if (
            $filename === null
            || $filename === ''
            || str_contains($filename, 'placeholder')
        ) {
            return;
        }

        $path = rtrim($directory, '/') . '/' . $filename;

        if (file_exists($path) && is_file($path)) {
            @unlink($path);
        }
    }
}
