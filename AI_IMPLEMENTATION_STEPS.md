# Enterprise ERP AI Implementation Documentation

This document outlines the step-by-step process of how AI features were implemented across various files in the ERP system. The implementation follows a **Driver-Based Architecture**, allowing the system to switch between different AI providers (Gemini, OpenAI, Claude) seamlessly.

---

## 1. Core Infrastructure (The Foundation)

Before building specific features, a robust infrastructure was created to handle AI requests.

### **Step 1: The Contract (`AiDriverInterface.php`)**
Defined a standard interface that every AI provider must follow.
- **Location:** `app/Services/AI/Contracts/AiDriverInterface.php`
- **Purpose:** Ensures that whether we use Gemini or OpenAI, the rest of the application uses the same methods: `prompt()`, `extractJson()`, and `chat()`.

### **Step 2: The Orchestrator (`AiManager.php`)**
Implemented a Manager class to handle multiple AI drivers.
- **Location:** `app/Services/AI/AiManager.php`
- **Process:** 
    1. Extends Laravel's `Manager` class.
    2. Reads configuration from `config/ai.php`.
    3. Dynamically instantiates the correct driver (e.g., `createGeminiDriver`).
    4. Provides a facade-like access via `Ai::driver()`.

### **Step 3: Concrete Drivers (`GeminiDriver.php`, `OpenAiDriver.php`)**
Implemented the actual API communication logic.
- **Location:** `app/Services/AI/Drivers/`
- **Process:**
    1. **GeminiDriver:** Uses Google's Generative AI API. Supports multimodal inputs (sending images/PDFs directly).
    2. **BaseDriver:** Contains shared logic like JSON parsing and error handling.

---

## 2. Financial Assistant (Conversational AI)

The assistant allows users to ask questions like *"What was our labor cost last month?"*.

### **Step 1: Intent Parsing (`QueryIntentParser.php`)**
- **Process:** Analyzes the user's message to detect:
    - **Intent:** (e.g., `labor_cost`, `revenue`, `statutory_liabilities`).
    - **Period:** Detects "last month", "this year", etc., and converts them to start/end dates.
    - **Trend:** Detects if the user wants a chart/growth analysis.

### **Step 2: Data Intelligence (`FinancialIntelligenceService.php`)**
- **Process:**
    1. Receives the parsed intent and dates.
    2. Queries the ERP database (Sales, Bills, Payslips) to get real numbers.
    3. **Builds Context:** Formats these numbers into a "Context String" that tells the AI exactly what is happening in the business.

### **Step 3: Response Generation (`FinancialAssistantController.php`)**
- **Process:**
    1. **Ask Method:** The main entry point.
    2. Calls the Parser to understand the user.
    3. Calls the Intelligence Service to get the facts.
    4. Sends the **User Question + Business Facts** to the AI Driver.
    5. Returns a conversational response with stats, ratios, or charts to the frontend.

---

## 3. Smart Data Query (Text-to-SQL)

For complex questions like *"Show me all unpaid bills over $5000"*, the system generates SQL on the fly.

### **Step 1: Prompt Engineering (`Prompts.php`)**
- **Process:** Defines a specialized prompt that includes the database schema (table names and columns) so the AI knows how to write valid SQL.

### **Step 2: SQL Generation & Security (`FinancialQueryService.php`)**
- **Process:**
    1. **Generate:** Sends the schema + user query to the AI.
    2. **Security Check:** Validates that the generated SQL:
        - Starts with `SELECT` (Read-only).
        - Does NOT contain forbidden keywords (`DROP`, `DELETE`, etc.).
        - **Crucial:** Always contains a `company_id` filter to prevent data leaks between tenants.
    3. **Execute:** Runs the query and returns the raw data to the UI for table/chart visualization.

---

## 4. Document Ingestion (OCR & Data Extraction)

Automatically reading invoices and receipts using AI Vision.

### **Step 1: Multimodal Extraction (`DocumentProcessorService.php`)**
- **Process:**
    1. Uploaded file (PDF/Image) is converted to Base64.
    2. Sent to **Gemini 1.5 Flash/Pro** with a prompt to "Extract fields as JSON".
    3. AI returns structured data (Date, Invoice No, Vendor Name, Line Items).

### **Step 2: ERP Entity Mapping**
- **Process:**
    1. **Fuzzy Matching:** The service compares the extracted "Vendor Name" against the ERP's `vendors` table using a similarity algorithm.
    2. **Account Suggestion:** The AI suggests the most likely **Chart of Accounts** (e.g., "Office Supplies") based on the item description.
    3. **Tax Mapping:** Maps extracted tax percentages to the ERP's configured `tax_rates`.

---

## Summary of File Roles

| File | Primary Responsibility |
| :--- | :--- |
| `AiManager.php` | Manages AI provider connections (Gemini/OpenAI). |
| `AiDriverInterface.php` | The standard "Blueprint" for all AI drivers. |
| `GeminiDriver.php` | Communicates with Google's AI API. |
| `FinancialAssistantController.php` | Orchestrates the chat experience. |
| `FinancialIntelligenceService.php` | Fetches real ERP data for the AI to analyze. |
| `QueryIntentParser.php` | Understands "What" and "When" the user is asking. |
| `FinancialQueryService.php` | Handles Text-to-SQL generation and safety. |
| `DocumentProcessorService.php` | Extracts data from PDFs/Images using AI Vision. |
| `Prompts.php` | Central repository for all System Prompts. |
