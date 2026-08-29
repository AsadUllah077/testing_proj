# 🤖 ERP Unified AI Intelligence System: Comprehensive Documentation

This document provides a production-quality technical and functional overview of the AI Intelligence layer implemented in the ERP system.

---

## 📂 1. Changed & Created Files

### File: `app/Services/AI/Contracts/AiDriverInterface.php`
**Purpose**: Defines the standard contract for all AI providers.
**Changes**: Initial creation.
**How It Works**: Forces all drivers to implement `chat()`, `prompt()`, and `extractJson()` methods, ensuring the application logic is provider-agnostic.
**Dependencies**: None.

### File: `app/Services/AI/Drivers/BaseDriver.php`
**Purpose**: Provides shared logic for all AI drivers.
**Changes**: Initial creation.
**How It Works**: Handles common tasks like stripping Markdown JSON fences and providing a default `prompt()` implementation that uses `chat()`.
**Used By**: All AI Drivers.

### File: `app/Services/AI/Drivers/GeminiDriver.php`
**Purpose**: Driver for Google Gemini API.
**Changes**: Refactored for v1beta compatibility and dynamic parameter normalization (`responseMimeType` vs `response_mime_type`).
**Important Classes**: `GeminiDriver`.
**Dependencies**: `Illuminate\Support\Facades\Http`.

### File: `app/Services/AI/AiManager.php`
**Purpose**: The central orchestrator (Manager Pattern).
**Changes**: Implemented factory methods for Gemini, OpenAI, and Claude.
**How It Works**: Resolves the correct driver instance based on the configuration or a manual override.
**Dependencies**: `config/ai.php`.

### File: `app/Facades/Ai.php`
**Purpose**: Laravel Facade for the AI system.
**Changes**: Initial creation.
**How It Works**: Provides a clean static interface (`Ai::prompt()`) to access the `AiManager`.

### File: `app/Services/AI/FinancialIntelligenceService.php`
**Purpose**: Data provider for financial context.
**Changes**: Added methods to fetch real-time Revenue, Labor Costs, and Statutory Liabilities.
**Used By**: `FinancialAssistantController`.

### File: `app/Services/AI/Prompts.php`
**Purpose**: Centralized Prompt Management.
**Changes**: Added system prompts for the Financial Assistant, SQL generation schemas, and Payslip analysis logic.

### File: `resources/views/pages/ai/assistant.blade.php`
**Purpose**: AI Assistant Frontend.
**Changes**: Implemented streaming UI, **Chart.js** integration, and **Tailwind** data tables for visualization.

---

## 🛠️ 2. Developer Documentation

### Setup & Installation
1.  **Dependencies**: Ensure `guzzlehttp/guzzle` is installed (standard in Laravel).
2.  **Environment Variables**:
    ```env
    AI_DRIVER=gemini
    GEMINI_API_KEY=your_key
    GEMINI_MODEL=gemini-2.5-flash
    OPENAI_API_KEY=your_key
    ```
3.  **Config**: Run `php artisan config:cache` after updating `.env`.

### Project Structure
*   `app/Services/AI/`: Core logic and service classes.
*   `app/Services/AI/Drivers/`: Provider-specific implementations.
*   `app/Services/AI/Contracts/`: Interfaces.
*   `app/Facades/`: Global Facades.

### How to Extend
*   **Add Features**: Add a new method to `Prompts.php` and call it via the `Ai` Facade.
*   **Modify Models**: Update the `[PROVIDER]_MODEL` key in your `.env` file.

---

## 👥 3. Administrator & User Documentation

### Administrator Guide
*   **Model Management**: Administrators can switch the entire system's AI provider by changing `AI_DRIVER` in the environment settings.
*   **Monitoring**: Check `storage/logs/laravel.log` for API failures or quota issues.

### User Guide
*   **Ask Anything**: Use natural language to query financial data (e.g., "Show me labor costs for Q1").
*   **Visualize**: Click on suggestions to generate instant bar/line charts of company trends.
*   **Context**: The AI is "Company Aware"—it only sees data for your specific organization.

---

## 🤖 4. AI Model Documentation

### How to Create a New AI Model (e.g. DeepSeek)
1.  **Create File**: `app/Services/AI/Drivers/DeepSeekDriver.php`.
2.  **Extend Base**: `class DeepSeekDriver extends BaseDriver`.
3.  **Register**: Add `createDeepseekDriver()` to `AiManager.php`.
4.  **Connect**: Use `Http` facade to call the DeepSeek API endpoint.

### How to Use Multiple AI Models Simultaneously
You can use the `driver()` method to force a specific provider for a single call:

```php
// User Request
//      ↓
//   AI Router (AiManager)
//      ↓
// ┌───────────────┐
// │ Gemini (OCR)  │ -> Ai::driver('gemini')->extractJson(...)
// │ OpenAI (SQL)  │ -> Ai::driver('openai')->prompt(...)
// └───────────────┘
//      ↓
//  Unified UI (assistant.blade.php)
```

---

## 🔌 5. API Documentation

### `POST /ai/assistant/ask`
*   **Body**: `{ "query": "string", "history": "array" }`
*   **Response**: `{ "message": "string", "type": "query_result|chart|stat", "data": "array" }`

---

## 🗄️ 6. Database Documentation
The AI has read-only access to:
*   `chart_of_accounts`: Categories and names.
*   `bills/sales`: Invoices, amounts, and statuses.
*   `transactions`: Ledger entries.
*   `employees`: Salary and designation data (for labor cost analysis).

---

## ⚙️ 7. Configuration Documentation
*   **`.env`**: Stores sensitive API keys and model versions.
*   **`config/ai.php`**: Maps drivers to their respective classes and config keys.

---

## 🚢 8. Deployment Documentation
1.  **Local**: Run `php artisan serve`. Ensure your local machine has outbound access to Google/OpenAI APIs.
2.  **Production**: Add API keys to your server's Environment Secrets. Use a queue worker if processing large batches of documents.

---

## ❓ 9. Troubleshooting
*   **404 Error**: Model name is outdated. Check `docs/ai-implementation-guide.md` for the latest 2026 model names.
*   **503 Error**: API is overloaded. The system has built-in 3x retry logic.
*   **Blank Chat**: Check browser console for JS errors (e.g., Chart.js not loading).

---

## ✨ 10. Best Practices
*   **Security**: Always scope queries with `company_id`.
*   **Cost**: Use Gemini Flash for repetitive tasks and Claude/GPT for high-level strategy.
*   **Safety**: Never allow the AI to perform `INSERT`, `UPDATE`, or `DELETE` on financial tables.
