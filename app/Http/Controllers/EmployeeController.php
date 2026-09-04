<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Program;
use App\Support\PatronNameSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    private function generateNextQrCode(): string
    {
        $last = Employee::whereNotNull('qrcode')
            ->where('qrcode', 'like', 'E-%')
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($last && preg_match('/E-(\d+)/', $last->qrcode, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }

        return 'E-'.str_pad((string) $nextNumber, 8, '0', STR_PAD_LEFT);
    }

    /** @return list<int> */
    private function workStartYears(): array
    {
        $current = (int) date('Y');

        return range($current, 1980);
    }

    public function index(Request $request)
    {
        $programs = Program::orderBy('program_name')->get();
        $workStartYears = $this->workStartYears();

        $query = Employee::query();

        if ($request->filled('search')) {
            PatronNameSearch::apply($query, (string) $request->search, [
                'employee_id',
                'designation',
                'program',
                'department',
                'qrcode',
            ]);
        }

        if ($request->filled('program')) {
            $query->where('program', $request->program);
        }

        if ($request->filled('year_start_work')) {
            $query->where('year_start_work', $request->year_start_work);
        }

        $faculty = $query->orderBy('lastname')->paginate(15)->withQueryString();

        return view('employees.index', compact('faculty', 'programs', 'workStartYears'));
    }

    public function create()
    {
        $programs = Program::orderBy('program_name')->get();
        $workStartYears = $this->workStartYears();

        return view('employees.create', compact('programs', 'workStartYears'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middle_initial' => 'nullable|string|max:16',
            'employee_id' => 'required|string|max:255|unique:library_employees,employee_id',
            'designation' => 'required|string|max:255',
            'program' => 'required|string|max:64',
            'year_start_work' => 'required|string|max:16',
            'birth_date' => 'nullable|date',
            'mobile_number' => 'nullable|string|max:32',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:255',
            'emergency_address' => 'nullable|string',
            'formal_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'employee_signature' => 'nullable|string',
        ]);

        $program = Program::where('program_code', $validated['program'])->first();

        DB::beginTransaction();

        try {
            $validated['role_id'] = 2;
            $validated['department'] = $program?->program_name ?? $validated['program'];
            $validated['position'] = $validated['designation'];
            $validated['qrcode'] = $this->generateNextQrCode();

            if ($request->hasFile('formal_picture')) {
                $file = $request->file('formal_picture');
                $filename = time().'_profile_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
                $dest = public_path('images/formal_pictures');
                if (! is_dir($dest)) {
                    mkdir($dest, 0755, true);
                }
                $file->move($dest, $filename);
                $validated['formal_picture'] = 'images/formal_pictures/'.$filename;
            }

            if (! empty($validated['employee_signature']) && str_starts_with($validated['employee_signature'], 'data:')) {
                [$meta, $contents] = explode(',', $validated['employee_signature'], 2);
                $ext = preg_match('/jpeg|jpg/i', $meta) ? 'jpg' : 'png';
                $sigName = time().'_sig.'.$ext;
                $sigDest = public_path('images/signatures');
                if (! is_dir($sigDest)) {
                    mkdir($sigDest, 0755, true);
                }
                file_put_contents($sigDest.DIRECTORY_SEPARATOR.$sigName, base64_decode($contents));
                $validated['employee_signature'] = 'images/signatures/'.$sigName;
            }

            Employee::create($validated);

            DB::commit();

            return redirect()->route('employees.index')
                ->with('success', 'Faculty & staff registered successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);
        $programs = Program::orderBy('program_name')->get();
        $workStartYears = $this->workStartYears();

        return view('employees.edit', compact('employee', 'programs', 'workStartYears'));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'employee_id' => 'required|string|max:255|unique:library_employees,employee_id,'.$employee->id,
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middle_initial' => 'nullable|string|max:16',
            'designation' => 'required|string|max:255',
            'program' => 'required|string|max:64',
            'year_start_work' => 'required|string|max:16',
            'birth_date' => 'nullable|date',
            'mobile_number' => 'nullable|string|max:32',
            'address' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'emergency_contact_number' => 'nullable|string|max:255',
            'emergency_address' => 'nullable|string',
            'employee_signature' => 'nullable|string',
            'formal_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $program = Program::where('program_code', $validated['program'])->first();
        $validated['role_id'] = 2;
        $validated['department'] = $program?->program_name ?? $validated['program'];
        $validated['position'] = $validated['designation'];

        if ($request->hasFile('formal_picture')) {
            $file = $request->file('formal_picture');
            $filename = time().'_profile_'.preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move(public_path('images/formal_pictures'), $filename);
            $validated['formal_picture'] = 'images/formal_pictures/'.$filename;
        }

        if (! empty($validated['employee_signature']) && str_starts_with($validated['employee_signature'], 'data:')) {
            $data = $validated['employee_signature'];
            [$meta, $contents] = explode(',', $data, 2);
            $ext = 'png';
            if (preg_match('/data:image\/(jpeg|jpg)/i', $meta)) {
                $ext = 'jpg';
            }
            $sigName = time().'_sig.'.$ext;
            if (! file_exists(public_path('images/signatures'))) {
                mkdir(public_path('images/signatures'), 0755, true);
            }
            file_put_contents(public_path('images/signatures/'.$sigName), base64_decode($contents));
            $validated['employee_signature'] = 'images/signatures/'.$sigName;
        }

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Faculty & staff record updated.');
    }

    public function destroy($id)
    {
        Employee::findOrFail($id)->delete();

        return back()->with('success', 'Record deleted successfully.');
    }
}
