<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\WaiverTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The text of each waiver / consent form, kept up to date by the Veterinarian/Admin.
 * Editing a template never changes waivers that were already prepared (they keep a copy of the text).
 */
class WaiverTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.waiver-templates.index', [
            'templates' => WaiverTemplate::withCount('waivers')->orderByDesc('is_active')->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.waiver-templates.form', ['template' => new WaiverTemplate(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $template = WaiverTemplate::create($this->validated($request));
        ActivityLog::record('created', 'Waivers', 'Added waiver form "' . $template->title . '".', $template);

        return redirect()->route('admin.waiver-templates.index')->with('status', '"' . $template->title . '" was added.');
    }

    public function edit(WaiverTemplate $template): View
    {
        return view('admin.waiver-templates.form', compact('template'));
    }

    public function update(Request $request, WaiverTemplate $template): RedirectResponse
    {
        $template->update($this->validated($request, $template));
        ActivityLog::record('updated', 'Waivers', 'Updated waiver form "' . $template->title . '".', $template);

        return redirect()->route('admin.waiver-templates.index')->with('status', '"' . $template->title . '" was saved. Waivers already prepared keep their old text.');
    }

    private function validated(Request $request, ?WaiverTemplate $template = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255', Rule::unique('waiver_templates', 'title')->ignore($template)],
            'waiver_type' => ['required', Rule::in(WaiverTemplate::TYPES)],
            'body' => ['required', 'string', 'min:30', 'max:20000'],
            'is_active' => ['boolean'],
        ], [
            'title.unique' => 'A form with this title already exists.',
            'body.min' => 'The form text is too short.',
        ], ['waiver_type' => 'type', 'body' => 'form text']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
