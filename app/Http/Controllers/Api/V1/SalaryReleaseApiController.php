<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\V1\SalaryReleaseResource;
use App\Http\Traits\ApiPagination;
use App\Http\Traits\ApiSorting;
use App\Models\SalaryRelease;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SalaryReleaseApiController extends BaseApiController
{
    use ApiPagination, ApiSorting;

    public function index(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view salary releases');
        }

        $query = SalaryRelease::where('user_id', $request->user()->id)->with(['employee', 'currency']);

        $this->applySorting($query, ['id', 'release_date', 'month', 'year', 'created_at'], 'created_at', 'desc');

        $salaryReleases = $this->applyPagination($query);

        return $this->paginated($salaryReleases, SalaryReleaseResource::class);
    }

    public function store(Request $request)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can create salary releases');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'currency_id' => 'required|exists:currencies,id',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
            'basic_salary' => 'required|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'commission' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
            'net_salary' => 'required|numeric|min:0',
            'release_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $validated['user_id'] = $request->user()->id;

        $salaryRelease = SalaryRelease::create($validated);
        $salaryRelease->load(['employee', 'currency']);

        return $this->created(new SalaryReleaseResource($salaryRelease), 'Salary release created successfully');
    }

    public function show(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can view salary releases');
        }

        $salaryRelease = SalaryRelease::where('user_id', $request->user()->id)
            ->with(['employee', 'currency'])
            ->find($id);

        if (!$salaryRelease) {
            return $this->notFound('Salary release not found');
        }

        return $this->resource(new SalaryReleaseResource($salaryRelease));
    }

    public function update(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can update salary releases');
        }

        $salaryRelease = SalaryRelease::where('user_id', $request->user()->id)->find($id);

        if (!$salaryRelease) {
            return $this->notFound('Salary release not found');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $salaryRelease->update($validated);
        $salaryRelease->load(['employee', 'currency']);

        return $this->resource(new SalaryReleaseResource($salaryRelease), 'Salary release updated successfully');
    }

    public function destroy(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can delete salary releases');
        }

        $salaryRelease = SalaryRelease::where('user_id', $request->user()->id)->find($id);

        if (!$salaryRelease) {
            return $this->notFound('Salary release not found');
        }

        $salaryRelease->delete();

        return $this->success(null, 'Salary release deleted successfully');
    }

    public function pdf(Request $request, $id)
    {
        if (!$request->user()->tokenCan('admin')) {
            return $this->forbidden('Only admin users can download salary release PDFs');
        }

        $salaryRelease = SalaryRelease::where('user_id', $request->user()->id)
            ->with(['employee', 'currency'])
            ->find($id);

        if (!$salaryRelease) {
            return $this->notFound('Salary release not found');
        }

        $pdf = Pdf::loadView('salary-releases.pdf', compact('salaryRelease'));
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'salary-release-' . $salaryRelease->id . '.pdf');
    }
}
