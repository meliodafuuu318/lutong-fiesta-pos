<?php

namespace App\Services;

/**
 * A finished backup: what gets attached to the email, and what it contains.
 */
final readonly class TransactionBackup
{
    /**
     * @param  array<string, mixed>  $manifest  The manifest written at the top of the file
     */
    public function __construct(
        public array $manifest,
        public string $attachmentName,
        public string $attachmentData,
        public bool $compressed,
        public int $uncompressedBytes,
    ) {}

    public function attachmentBytes(): int
    {
        return strlen($this->attachmentData);
    }

    public function attachmentMime(): string
    {
        return $this->compressed ? 'application/gzip' : 'application/json';
    }

    /** Lets the person who receives the email check the download. */
    public function sha256(): string
    {
        return hash('sha256', $this->attachmentData);
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return $this->manifest['record_counts'];
    }
}
