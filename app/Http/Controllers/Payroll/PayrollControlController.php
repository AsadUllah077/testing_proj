<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\PayRun;
use App\Models\PayrollPreflightOverride;
use App\Services\Payroll\PayrollPreflightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollControlController extends Controller
{
    public function __construct(private PayrollPreflightService $preflight)
    {
    }

    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $payRuns = PayRun::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->with('createdBy')
            ->withCount('payslips')
            ->latest('period_end')
            ->paginate(20);

        $selectedPayRun = null;
        $result = null;
        if ($request->filled('pay_run_id')) {
            $selectedPayRun = $this->findPayRun((int) $request->pay_run_id);
            $result = $this->preflight->run($selectedPayRun);
        }

        return view('pages.payroll.control-center.index', compact('payRuns', 'selectedPayRun', 'result'));
    }

    public function run(PayRun $payRun)
    {
        $payRun = $this->findPayRun($payRun->id);
        $result = $this->preflight->run($payRun, true);

        return redirect()->route('payroll.control-center.index', ['pay_run_id' => $payRun->id])
            ->with($result['status'] === 'failed' ? 'error' : 'success', 'Payroll preflight completed with status: ' . ucfirst($result['status']) . '.');
    }

    public function overrideWarning(Request $request, PayRun $payRun)
    {
        $payRun = $this->findPayRun($payRun->id);
        $data = $request->validate([
            'check_code' => ['required', 'string', 'max:80'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);
        $result = $this->preflight->run($payRun);
        $check = collect($result['checks'])->firstWhere('code', $data['check_code']);
        abort_unless($check && $check['severity'] === 'warning', 422, 'Only review warnings can be acknowledged.');

        PayrollPreflightOverride::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $payRun->company_id, 'pay_run_id' => $payRun->id, 'check_code' => $data['check_code']],
            ['reason' => $data['reason'], 'overridden_by' => Auth::id()]
        );
        $this->preflight->run($payRun, true);

        return back()->with('success', 'Warning reviewed and recorded in the payroll audit trail.');
    }

    public function overrideWarnings(Request $request, PayRun $payRun)
    {
        $payRun = $this->findPayRun($payRun->id);
        $data = $request->validate(['reason' => ['required', 'string', 'min:15', 'max:2000']]);
        $warnings = collect($this->preflight->run($payRun)['checks'])
            ->where('severity', 'warning')->where('overridden', false);
        foreach ($warnings as $warning) {
            PayrollPreflightOverride::withoutGlobalScopes()->updateOrCreate(
                ['company_id' => $payRun->company_id, 'pay_run_id' => $payRun->id, 'check_code' => $warning['code']],
                ['reason' => $data['reason'], 'overridden_by' => Auth::id()]
            );
        }
        $this->preflight->run($payRun, true);
        return back()->with('success', $warnings->count() . ' payroll warnings reviewed and recorded individually.');
    }

    private function findPayRun(int $id): PayRun
    {
        return PayRun::withoutGlobalScopes()
            ->where('company_id', Auth::user()->company_id)
            ->with(['createdBy', 'approvedBy'])
            ->findOrFail($id);
    }
}
