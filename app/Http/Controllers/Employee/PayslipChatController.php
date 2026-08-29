<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\AI\Prompts;
use App\Services\PayslipChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Facades\Ai;

/**
 * PayslipChatController - Handles employee chatbot interactions for payslip Q&A
 * Uses RAG (Retrieval-Augmented Generation) with Unified AI Manager
 */
class PayslipChatController extends Controller
{
    protected PayslipChatService $chatService;

    /**
     * Send chat message and get AI response
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'payslip_id' => 'nullable|integer',
            'conversation_history' => 'nullable|array',
        ]);

        $employee = auth('employee')->user();
        $message = $validated['message'];
        $payslipId = $validated['payslip_id'] ?? null;

        try {
            $this->chatService = new PayslipChatService($employee);
            $this->chatService->loadPayslipContext(yearsBack: 1);

            if ($payslipId) {
                $context = $this->chatService->buildQuestionContext($payslipId, $message);
            } else {
                $context = $this->chatService->buildContext();
            }

            $conversationHistory = $this->sanitizeConversationHistory($validated['conversation_history'] ?? []);
            $prompt = $this->buildGeminiPrompt($conversationHistory, $message, $context);

            $assistantMessage = Ai::prompt($prompt, [
                'temperature' => 0.2,
                'max_tokens' => 1024,
            ]);

            if (!$assistantMessage) {
                throw new \RuntimeException('AI response missing assistant content');
            }

            return response()->json([
                'success' => true,
                'message' => $assistantMessage,
                'conversation_id' => $this->generateConversationId(),
            ]);
        } catch (\Exception $e) {
            Log::error('Payslip Chatbot Error', [
                'employee_id' => $employee?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Unable to process your question. Please try again.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get initial chat context for employee
     */
    public function getContext(Request $request): JsonResponse
    {
        $employee = auth('employee')->user();
        $this->chatService = new PayslipChatService($employee);
        $payslips = $this->chatService->loadPayslipContext(yearsBack: 1);

        if ($payslips->isEmpty()) {
            return response()->json([
                'available_payslips' => 0,
                'message' => 'No payslips available yet.',
            ]);
        }

        $recentPayslips = $payslips->take(6)->map(fn($p) => [
            'id' => $p->id,
            'period' => $p->payRun->period_end->format('M Y'),
            'net_pay' => $p->net_pay,
            'status' => $p->status,
        ]);

        return response()->json([
            'available_payslips' => $payslips->count(),
            'recent_payslips' => $recentPayslips,
            'common_questions' => PayslipChatService::getCommonQuestionsTemplates(),
            'ytd_summary' => [
                'gross' => $payslips->first()?->ytd_gross ?? 0,
                'deductions' => $payslips->first()?->ytd_deductions ?? 0,
                'net' => $payslips->first()?->ytd_net ?? 0,
            ],
        ]);
    }

    /**
     * Get suggested questions based on payslip
     */
    public function getSuggestions(int $payslipId): JsonResponse
    {
        $employee = auth('employee')->user();
        $this->chatService = new PayslipChatService($employee);
        $payslip = $this->chatService->getPayslip($payslipId);

        if (!$payslip) {
            return response()->json(['error' => 'Payslip not found'], 404);
        }

        $suggestions = [];
        $taxAmount = $payslip->items
            ->filter(fn($i) => $this->isNamedPayslipItem($i, ['tax', 'income tax', 'paye', 'it']))
            ->sum('amount');

        if ($taxAmount > 0) {
            $suggestions[] = "Why did I pay $" . number_format($taxAmount, 2) . " in tax?";
            $suggestions[] = "How is my tax calculated?";
        }

        $deductionCount = $payslip->items->where('payItem.type', 'deduction')->count();
        if ($deductionCount > 0) {
            $suggestions[] = "What are all my deductions?";
            $suggestions[] = "Can you break down my deductions?";
        }

        if ($payslip->ytd_gross > 0) {
            $suggestions[] = "What's my YTD (year-to-date) gross?";
            $suggestions[] = "How much have I earned so far this year?";
        }

        $hasPF = $payslip->items->filter(fn($i) => $this->isNamedPayslipItem($i, ['pf', 'provident', 'pension']))->count() > 0;
        if ($hasPF) {
            $suggestions[] = "How much have I contributed to my provident fund?";
        }

        return response()->json([
            'payslip_id' => $payslipId,
            'period' => $payslip->payRun->period_end->format('M Y'),
            'suggestions' => array_filter($suggestions),
        ]);
    }

    /**
     * Build a single prompt from context and conversation history.
     */
    protected function buildGeminiPrompt(array $conversationHistory, string $userMessage, string $context): string
    {
        $prompt = Prompts::payslipHrAssistantSystem() . "\n\n" . $context . "\n\n";

        if (!empty($conversationHistory)) {
            foreach ($conversationHistory as $msg) {
                $role = $msg['role'] === 'assistant' ? 'Assistant' : 'User';
                $prompt .= $role . ': ' . $msg['content'] . "\n";
            }
        }
        $prompt .= 'User: ' . $userMessage . "\nAssistant:";

        return $prompt;
    }

    protected function sanitizeConversationHistory(array $conversationHistory): array
    {
        return collect($conversationHistory)
            ->take(-8)
            ->filter(fn($msg) => is_array($msg) && isset($msg['role'], $msg['content']))
            ->map(fn($msg) => [
                'role' => in_array($msg['role'], ['assistant', 'user'], true) ? $msg['role'] : 'user',
                'content' => mb_substr((string) $msg['content'], 0, 1000),
            ])
            ->values()
            ->all();
    }

    protected function isNamedPayslipItem($item, array $names): bool
    {
        $label = strtolower(trim($item->description ?: ($item->payItem->name ?? '')));

        return in_array($label, $names, true);
    }

    protected function generateConversationId(): string
    {
        return 'payslip_' . auth('employee')->id() . '_' . uniqid();
    }
}
