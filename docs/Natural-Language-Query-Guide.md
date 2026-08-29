# Natural Language Querying Guide

The Financial Intelligence Assistant now supports **Direct Data Querying**. Instead of navigating through multiple report pages, you can ask the AI to fetch specific data sets, filter results, and perform calculations using natural language.

## 1. How it Works
When you ask a question that requires specific records (e.g., "Show me...", "List...", "Who..."), the AI:
1.  **Analyzes your request** to identify the data source (Sales, Bills, Transactions, etc.).
2.  **Generates a secure SQL query** tailored to your question.
3.  **Executes the query** within your company's secure data scope.
4.  **Renders the results** as a styled table or a strategic summary.

---

## 2. Example Questions by Category

### Accounting & Expenses
*   *"Show me all bills from last month that are still unpaid."*
*   *"List my top 5 expenses by category for this year."*
*   *"What is the total tax we paid on bills in Q1?"*
*   *"Show me all transactions related to 'Marketing' or 'Advertising'."*
*   *"Which vendors have we paid more than 100,000 to this year?"*

### Sales & Revenue
*   *"List all unpaid invoices over 50,000."*
*   *"Show me our top 10 customers by total sales volume."*
*   *"What was our total revenue for each month in the last quarter?"*
*   *"Show me all sales invoices generated for [Customer Name]."*

### Payroll & Labor
*   *"What is the total salary expenditure for this month?"*
*   *"List all employees with a salary higher than 150,000."*
*   *"Show me the breakdown of labor costs by designation."*
*   *"How many employees do we have in the 'Operator' designation?"*

---

## 3. Advanced Querying Tips
*   **Be Specific with Dates**: Use phrases like "last quarter", "Q3 2025", "this month", or "since January".
*   **Use Thresholds**: You can filter by amounts, e.g., "greater than 10,000" or "below 500".
*   **Search for Names**: You can ask about specific vendors or customers, e.g., "Show bills from Vendor XYZ".
*   **Combine Filters**: e.g., *"Show me unpaid bills from last month over 20,000."*

---

## 4. Security & Safety
*   **Read-Only**: The AI can only read your data. It cannot create, update, or delete any records.
*   **Company Scoping**: The engine automatically ensures you only see data belonging to your own company.
*   **Privacy**: Specific sensitive fields (like passwords or full bank details) are excluded from the AI's query scope.

---
*Last Updated: May 02, 2026*
