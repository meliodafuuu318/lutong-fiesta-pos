<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ItemAnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemAnalyticsController extends Controller
{
    /**
     * Item popularity (for a date range) and the daily item report (for one day), as two tabs of
     * one page. Everything comes from the query string so a filtered view can be bookmarked; a
     * value that doesn't make sense falls back to its default instead of showing an error.
     */
    public function index(Request $request, ItemAnalyticsService $analytics): View
    {
        $preset = $request->query('range');
        $preset = is_string($preset) && in_array($preset, ItemAnalyticsService::PRESETS, true) ? $preset : 'month';

        $sort = $request->query('sort');
        $sort = is_string($sort) && in_array($sort, ItemAnalyticsService::SORTS, true) ? $sort : 'total';

        $minAppearances = min(365, max(1, (int) $request->query('min_appearances', 1)));

        $range = $analytics->range(
            $preset,
            $this->date($request->query('date_from')),
            $this->date($request->query('date_to')),
        );

        $day = $this->date($request->query('date')) ?? CarbonImmutable::today();

        return view('analytics.items', [
            'preset' => $preset,
            'sort' => $sort,
            'minAppearances' => $minAppearances,
            'range' => $range,
            'popularity' => $analytics->popularity($range['from'], $range['to'], $sort, $minAppearances),
            'day' => $day,
            'daily' => $analytics->dailyReport($day),
        ]);
    }

    /** A Y-m-d date, or null for anything else (including dates that don't exist, like Feb 31). */
    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
