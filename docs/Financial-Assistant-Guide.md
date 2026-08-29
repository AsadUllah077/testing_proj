# Financial Intelligence Assistant: Query Guide

The Unified Financial Intelligence Assistant is a strategic tool designed for CFOs and Administrators to perform cross-module analysis between **Accounting** and **Payroll**. 

## Supported Question Types

### 1. Strategic Ratios & Efficiency
These queries analyze the relationship between your labor expenditure and your top-line revenue.
*   "What is our labor cost as % of revenue this year?"
*   "How has our efficiency ratio changed since last quarter?"
*   "Is our current labor-to-revenue ratio within the healthy 30% range?"

### 2. Revenue & Growth Trends
Analyze top-line performance with historical comparisons.
*   "Show me our revenue trend over the past 12 months."
*   "Why did revenue grow by [X]% last month?"
*   "Compare this year's revenue vs last year's trend."

### 3. Labor Cost & Payroll Analysis
Deep dive into your largest expense category.
*   "How much did we spend on total labor last month?"
*   "Which department has the highest labor cost year-to-date?"
*   "Show me the trend of labor expenses over the last 6 months."
*   "What is the average labor cost per employee in the Engineering department?"

### 4. Statutory & Compliance Queries
Track your liabilities and statutory deductions.
*   "What are our total statutory liabilities for this quarter?"
*   "How much did we pay in statutory deductions last month?"
*   "Give me a breakdown of all tax-related deductions for the current fiscal year."

### 5. Multi-Turn Strategic Analysis
Because the assistant maintains **Conversation History**, you can ask follow-up questions:
*   *User:* "Show me revenue trend."
*   *Assistant:* [Provides chart and message]
*   *User:* "Now compare that with our labor cost growth for the same period."

## How the AI Works
The assistant uses **Retrieval-Augmented Generation (RAG)** combined with **Google Gemini**.
1.  **Context Building**: Every query automatically pulls the latest high-density financial data from your database.
2.  **Intelligence Layer**: It combines data from `sales`, `bills`, `payslips`, and `pay_runs`.
3.  **Dynamic Suggestions**: The "Quick Action" buttons at the bottom of the chat are generated in real-time based on your company's actual performance metrics.

---
*Last Updated: April 30, 2026*
