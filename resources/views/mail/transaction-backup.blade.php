{{ config('app.name') }} transaction backup

Exported: {{ $manifest['exported_at'] }} ({{ $manifest['timezone'] }})
Type: complete transaction history (every transaction, not just today's)
@if ($manifest['date_range']['from'])
Covers: {{ $manifest['date_range']['from'] }} to {{ $manifest['date_range']['to'] }}
@else
Covers: no transactions yet
@endif

Records
  Transactions:  {{ $manifest['record_counts']['transactions'] }}
  Items:         {{ $manifest['record_counts']['transaction_items'] }}
  Payments:      {{ $manifest['record_counts']['transaction_payments'] }}
  Deliveries:    {{ $manifest['record_counts']['deliveries'] }}

Attachment
  File:    {{ $backup->attachmentName }}
@if ($backup->compressed)
  Size:    {{ number_format($backup->attachmentBytes() / 1024, 1) }} KB (compressed with gzip; the JSON was {{ number_format($backup->uncompressedBytes / 1024, 1) }} KB, too big to attach as it was)
@else
  Size:    {{ number_format($backup->attachmentBytes() / 1024, 1) }} KB
@endif
  SHA-256: {{ $backup->sha256() }}

The file contains customer delivery addresses and sales records. Keep this email private.
@if ($backup->compressed)
To read the file, unzip it first (gunzip {{ $backup->attachmentName }}).
@endif
