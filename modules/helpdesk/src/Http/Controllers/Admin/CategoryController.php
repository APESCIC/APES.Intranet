<?php

namespace Modules\Helpdesk\Http\Controllers\Admin;

use App\Core\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Category;
use Modules\Helpdesk\Models\Ticket;

/**
 * Topics decide which queue a ticket goes to and its usual priority.
 */
class CategoryController
{
    public function index(): View
    {
        return view('helpdesk::admin.categories', [
            'categories' => Category::query()->ordered()->withCount('tickets')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $category = Category::query()->create($data);
        $audit->record('helpdesk.category_created', $category, ['queue' => $category->queue]);

        return back()->with('status', 'Topic added: '.$category->name.'.');
    }

    public function update(Request $request, Category $category, AuditLogger $audit): RedirectResponse
    {
        $category->update($this->validated($request));
        $audit->record('helpdesk.category_updated', $category, $category->getChanges());

        return back()->with('status', 'Topic saved: '.$category->name.'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'queue' => ['required', Rule::in(array_keys(Ticket::QUEUES))],
            'default_priority' => ['required', Rule::in(array_keys(Ticket::PRIORITIES))],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        return ['sort_order' => (int) ($data['sort_order'] ?? 0), 'active' => $request->boolean('active')] + $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'topic';
        $slug = $base;
        for ($n = 2; Category::query()->where('slug', $slug)->exists(); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }
}
