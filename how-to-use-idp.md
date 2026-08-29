# How to Use the Intelligent Document Processing (IDP) Pipeline

The new IDP pipeline uses AI to automatically read your financial documents (PDFs, Images) and convert them into Draft records that can be instantly synced into your ledger. 

Here is a step-by-step guide on how to use it:

## Step 1: Access the Dashboard
1. Look at the main sidebar navigation menu on the left side of your ERP.
2. Under the **Accounting** section, you will see a new group called **AI Features**.
3. Click on **Doc Ingestion**. 

## Step 2: Upload Documents
1. On the left side of the dashboard, you will see an **Upload Area**.
2. Select the **Document Type** from the dropdown menu (e.g., Supplier Invoices, Customer Receipts).
3. **Drag and Drop** your PDF or Image files into the designated dashed-border area, or click "Upload files" to browse your computer.
4. Click the blue **Process Documents** button. 
5. The AI will securely read your documents in the background. Once completed, your documents will appear in the **Processing Queue** on the right side of the screen as "Drafts".

## Step 3: Review the AI Extraction
1. In the **Processing Queue** table, locate the document you just uploaded.
2. You will see the AI has already extracted the Vendor name and Total amount.
3. Click the **Review** button next to the draft.
4. A modal will pop up. Here you can review exactly what the AI extracted:
   - Vendor / Payee Name
   - Invoice Reference Number
   - Date
   - Line Items and Amounts
5. **Chart of Accounts Mapping**: For each line item, the AI will try to automatically select the correct Chart of Account based on the description. If the AI is unsure, or if you want to change it, simply use the dropdown menu to select the correct account.

## Step 4: Confirm and Create Record
1. Once you are satisfied that the extracted data and Account mappings are correct, click the blue **Confirm & Create Record** button at the bottom of the modal.
2. The system will securely process this draft and use your existing accounting logic to create the official **Bill** (or Sale/Statement).
3. It will automatically generate the corresponding double-entry **Transactions** (e.g., debiting the expense account and crediting Accounts Payable).
4. You will then be redirected to the newly created record in your ERP!

---

**Troubleshooting:**
- **"Processing Failed" Error:** Make sure your document is a valid PDF, JPG, or PNG and is under 10MB. Also, ensure your `ANTHROPIC_API_KEY` (which is currently used for Gemini) is correctly set in your `.env` file.
- **Empty Line Items:** Sometimes invoices have very complex layouts. If the AI misses a line item, you can manually adjust the "Total" before confirming, or edit the final Bill after it's created.
