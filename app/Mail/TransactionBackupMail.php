<?php

namespace App\Mail;

use App\Services\TransactionBackup;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The daily backup: the JSON export as an attachment, and a summary with the
 * record counts (no transaction contents) in the body.
 */
class TransactionBackupMail extends Mailable
{
    public function __construct(public readonly TransactionBackup $backup) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '%s transaction backup, %s',
                config('app.name'),
                substr(str_replace('T', ' ', $this->backup->manifest['exported_at']), 0, 16),
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.transaction-backup',
            with: ['manifest' => $this->backup->manifest],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->backup->attachmentData, $this->backup->attachmentName)
                ->withMime($this->backup->attachmentMime()),
        ];
    }
}
