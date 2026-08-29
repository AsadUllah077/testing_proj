# AI Service Layer Documentation

## Architecture Overview

The AI system uses a **Manager/Driver pattern**. This decouples the ERP's business logic from the specific AI provider (Gemini, OpenAI, Claude).

### 1. Core Interface

All drivers implement the `AiDriverInterface` found in `app/Services/AI/Contracts/`. This ensures that `chat()`, `prompt()`, and `extractJson()` work the same way across all providers.

### 2. Usage via Facade

You should always use the `Ai` Facade for simplicity:

```php
use App\Facades\Ai;

// Simple prompt
$response = Ai::prompt("What is our revenue?");

// Extract JSON from an image (Invoice/Bill)
$json = Ai::extractJson($prompt, $base64Image, $mimeType);
```

### 3. Prompt Management

**Rule #1**: Never hardcode prompts in your Controllers.
Add all prompts and JSON schemas to `app/Services/AI/Prompts.php`. This allows you to update "AI behavior" without changing any code logic.

### 4. Adding New Providers (e.g. DeepSeek, Local Ollama)

To add a new AI provider, follow these 4 steps:

1. **Create Driver**: Create `app/Services/AI/Drivers/DeepSeekDriver.php`. It **MUST** extend `BaseDriver` to inherit JSON parsing and default methods.
    ```php
    class DeepSeekDriver extends BaseDriver {
        public function extractJson(...) { /* Implementation */ }
        public function chat(...) { /* Implementation */ }
    }
    ```
2. **Register in Manager**: Open `app/Services/AI/AiManager.php` and add a creation method:
    ```php
    public function createDeepseekDriver() {
        return new DeepSeekDriver($this->config->get('ai.drivers.deepseek'));
    }
    ```
3. **Update Config**: Add the credentials to `config/ai.php`:
    ```php
    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
    ],
    ```
4. **Update .env**: Add your API key:
    ```env
    AI_DRIVER=deepseek
    DEEPSEEK_API_KEY=your_key_here
    ```

---

### 5. Multi-Driver Usage

One of the key benefits of this architecture is the ability to use different AI models for different tasks within the same request.

#### Basic Syntax

```php
use App\Facades\Ai;

// Use the default driver (.env)
$res = Ai::prompt("Hello");

// Force a specific driver
$res = Ai::driver('openai')->prompt("Analyze this code");
$res = Ai::driver('claude')->prompt("Write a financial report");
$res = Ai::driver('gemini')->extractJson($prompt, $image, $mime);
```

#### Why use multiple drivers?

1. **Cost vs. Quality**: Use cheaper models (Gemini Flash) for simple parsing and expensive models (GPT-4o / Claude Opus) for critical financial insights.
2. **Specialization**: OpenAI is often better at generating SQL, while Claude is excellent at long-form document analysis.
3. **Fallback Protection**: If one provider returns a 503 (Overloaded), you can instantly failover to another.
    ```php
    try {
        return Ai::driver('gemini')->prompt($query);
    } catch (AiProviderException $e) {
        return Ai::driver('openai')->prompt($query);
    }
    ```

---

## Error Handling

Always wrap AI calls in a `try-catch` block for `AiProviderException`. This handles connectivity issues and provider-specific API errors gracefully.
