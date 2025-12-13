<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Unit;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::all();
        return view('vendor-views.unit.index', compact('units'));
    }

    public function create()
    {
        return view('vendor-views.unit.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'unit' => 'required|string|max:255|unique:units,unit',
        ]);
        Unit::create(['unit' => $request->unit]);
        return redirect()->route('vendor.unit.list')->with('success', 'Unit created successfully.');
    }

    public function edit($id)
    {
        $unit = Unit::findOrFail($id);
        return view('vendor-views.unit.edit', compact('unit'));
    }

    public function update(Request $request, $id)
    {
        $unit = Unit::findOrFail($id);
        $request->validate([
            'unit' => 'required|string|max:255|unique:units,unit,' . $unit->id,
        ]);
        $unit->update(['unit' => $request->unit]);
        return redirect()->route('vendor.unit.list')->with('success', 'Unit updated successfully.');
    }

    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();
        return redirect()->route('vendor.unit.list')->with('success', 'Unit deleted successfully.');
    }

    public function export($type)
    {
        $filename = 'units.' . ($type === 'excel' ? 'xlsx' : 'csv');
        $units = Unit::all(['id', 'unit']);

        $callback = function () use ($units) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Unit']);
            foreach ($units as $unit) {
                fputcsv($handle, [$unit->id, $unit->unit]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
