<?php

namespace App\Services\AI;

use App\Facades\Ai;
use Illuminate\Support\Facades\Log;

class FinancialAssistantService
{
    protected $intelligence;

    public function __construct(FinancialIntelligenceService $intelligence)
    {
        $this->intelligence = $intelligence;
    }

    /**
     * Build a single prompt for Gemini combining system instructions, context, and history.
     */
    public function buildGeminiPrompt(string $query, string $context, array $history): string
    {
        $prompt = Prompts::financialAssistantSystem() . "\n\n";
        $prompt .= "FINANCIAL CONTEXT:\n" . $context . "\n\n";

        if (!empty($history)) {
            $prompt .= "CONVERSATION HISTORY:\n";
            foreach ($history as $msg) {
                $role = ($msg['role'] === 'assistant' || $msg['role'] === 'ai') ? 'Assistant' : 'User';
                $prompt .= "{$role}: {$msg['content']}\n";
            }
        }

        $prompt .= "User: {$query}\nAssistant:";

        return $prompt;
    }

    /**
     * Call AI to generate response.
     */
    public function getAIResponse(string $prompt): ?string
    {
        try {
            return Ai::prompt($prompt, [
                'temperature' => 0.3,
                'max_tokens' => 1024,
            ]);
        } catch (\Exception $e) {
            Log::error('Financial Assistant AI Error: ' . $e->getMessage());
        }

        return null;
    }

    public function summarizeQueryResult(string $query, string $explanation, array $rows, array $columns = []): ?string
    {
        if (empty($rows)) {
            return null;
        }

        $sampleRows = array_slice(array_map(function ($row) {
            return (array) $row;
        }, $rows), 0, 12);

        $prompt = "You are a financial ERP analyst. Explain the query result clearly for a CFO/admin.\n\n"
            . "Rules:\n"
            . "- Use only the rows provided; do not invent totals, names, dates, or causes.\n"
            . "- Start with a direct answer in one sentence.\n"
            . "- Add up to 3 concise observations if the data supports them.\n"
            . "- If the result is just a list, mention what it is sorted or filtered by when obvious.\n"
            . "- Do not mention SQL or implementation details.\n\n"
            . "User question: {$query}\n"
            . "Base explanation: {$explanation}\n"
            . "Columns: " . json_encode($columns) . "\n"
            . "Rows: " . json_encode($sampleRows);

        try {
            return Ai::prompt($prompt, [
                'temperature' => 0.2,
                'max_tokens' => 450,
            ]);
        } catch (\Exception $e) {
            Log::error('Financial Query Summary AI Error: ' . $e->getMessage());
        }

        return null;
    }
}
