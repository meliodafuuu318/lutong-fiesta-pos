<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait Pagination
{
    protected function pagePaginationData(LengthAwarePaginator $paginated): array
    {
        return [
            'current_page' => $paginated->currentPage(),
            'total_pages' => $paginated->lastPage(),
            'count' => $paginated->count(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ];
    }

    protected function cursorPaginationData(CursorPaginator $paginated): array
    {
        return [
            'prev_cursor' => $paginated->previousCursor()?->encode(),
            'next_cursor' => $paginated->nextCursor()?->encode(),
            'prev_page_url' => $paginated->previousPageUrl(),
            'next_page_url' => $paginated->nextPageUrl(),
        ];
    }
}
