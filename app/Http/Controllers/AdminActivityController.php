<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminActivityController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->input('category', 'patron');
        if (! in_array($category, ['patron', 'staff'], true)) {
            $category = 'patron';
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = AdminActivity::query()
            ->with('user')
            ->latest();

        if ($category === 'patron') {
            $query->patronNotifications();
        } else {
            $query->staffActivities();
        }

        if (is_string($dateFrom) && $dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if (is_string($dateTo) && $dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $activities = $query
            ->paginate(30)
            ->withQueryString();

        return view('admin.activities.index', [
            'activities' => $activities,
            'category' => $category,
            'dateFrom' => is_string($dateFrom) ? $dateFrom : null,
            'dateTo' => is_string($dateTo) ? $dateTo : null,
        ]);
    }
}
