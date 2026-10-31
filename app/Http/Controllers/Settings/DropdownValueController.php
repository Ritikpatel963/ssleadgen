<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LookupValue;
use Illuminate\Http\Request;

class DropdownValueController extends Controller
{
    public static function ensureDefaultPipelineStages(): void
    {
        try {
            if (LookupValue::where('group', 'pipeline_stage')->exists()) {
                return;
            }

            $defaultStages = [
                ['name' => 'New', 'sort_order' => 1],
                ['name' => 'Contacted', 'sort_order' => 2],
                ['name' => 'Proposal', 'sort_order' => 3],
                ['name' => 'Won / Converted', 'sort_order' => 4],
            ];

            foreach ($defaultStages as $stage) {
                LookupValue::firstOrCreate(
                    ['group' => 'pipeline_stage', 'name' => $stage['name']],
                    ['sort_order' => $stage['sort_order'], 'is_active' => true]
                );
            }
        } catch (\Throwable $e) {
            // Gracefully ignore if database is temporarily unavailable during migrations
        }
    }

    public function index(Request $request, ?string $group = null)
    {
        self::ensureDefaultPipelineStages();

        $selectedGroup = $group;
        $rawGroups = ['lead_category', 'pipeline_stage', 'insurance_company', 'company_type'];
        foreach (LookupValue::query()->select('group')->distinct()->pluck('group') as $existingGroup) {
            if ($existingGroup !== 'lead_source' && !in_array($existingGroup, $rawGroups, true)) {
                $rawGroups[] = $existingGroup;
            }
        }
        $groups = array_values(array_unique($rawGroups));

        if ($request->ajax()) {
            if ($selectedGroup === 'lead_category' || $selectedGroup === 'lead_source') {
                $items = LookupValue::whereIn('group', ['lead_category', 'lead_source'])
                    ->orderBy('sort_order')
                    ->orderBy('group')
                    ->orderBy('name')
                    ->get();
                $items->each(function ($item, $index): void {
                    $item->display_order = $index + 1;
                });
                $selectedGroup = 'lead_category';
            } elseif ($selectedGroup) {
                $items = LookupValue::where('group', $selectedGroup)->orderBy('sort_order')->orderBy('name')->get();
            } else {
                $items = collect([]);
            }

            return view('pages.settings.dropdown-values', [
                'group' => $selectedGroup,
                'groups' => $groups,
                'items' => $items,
            ]);
        }

        return redirect()->route('settings.access', array_filter([
            'tab' => 'dropdowns',
            'group' => $selectedGroup === 'lead_source' ? 'lead_category' : $selectedGroup,
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $isActive = (bool) ($data['is_active'] ?? true);

        LookupValue::query()->updateOrCreate(
            [
                'group' => $data['group'],
                'name' => $name,
            ],
            [
                'is_active' => $isActive,
                'sort_order' => LookupValue::query()->where('group', $data['group'])->max('sort_order') + 1,
            ]
        );

        if ($data['group'] === 'lead_source' || $data['group'] === 'lead_category') {
            LeadSource::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => $isActive]
            );
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Category added successfully.']);
        }

        return redirect()->route('settings.dropdowns', ['group' => $data['group']])
            ->with('success', 'Category added successfully.');
    }

    public function update(Request $request, LookupValue $lookupValue)
    {
        $data = $request->validate([
            'group' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldGroup = $lookupValue->group;
        $oldName = $lookupValue->name;
        $name = trim($data['name']);
        $isActive = (bool) ($data['is_active'] ?? true);

        $lookupValue->update([
            'group' => $data['group'],
            'name' => $name,
            'is_active' => $isActive,
        ]);

        if ($data['group'] === 'lead_source' || $data['group'] === 'lead_category') {
            $leadSource = LeadSource::where('name', $oldName)->first();
            if ($leadSource) {
                $leadSource->update(['name' => $name, 'is_active' => $isActive]);
            } else {
                LeadSource::query()->updateOrCreate(['name' => $name], ['is_active' => $isActive]);
            }
        } elseif ($data['group'] === 'pipeline_stage' || $oldGroup === 'pipeline_stage') {
            $oldKey = $lookupValue->stage_key;
            $newKey = $lookupValue->fresh()->stage_key;
            if ($oldKey !== $newKey || $oldName !== $name) {
                Lead::where('status', $oldKey)->orWhere('status', $oldName)->update(['status' => $newKey]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
        }

        return redirect()->route('settings.dropdowns', ['group' => $data['group']])
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, LookupValue $lookupValue)
    {
        $group = $lookupValue->group;
        $name = $lookupValue->name;
        $stageKey = $lookupValue->stage_key;
        $lookupValue->delete();

        if ($group === 'lead_source' || $group === 'lead_category') {
            LeadSource::where('name', $name)->delete();
        } elseif ($group === 'pipeline_stage') {
            Lead::where('status', $stageKey)->orWhere('status', $name)->update(['status' => 'new']);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
        }

        return redirect()->route('settings.dropdowns', ['group' => $group])
            ->with('success', 'Category deleted successfully.');
    }
}
