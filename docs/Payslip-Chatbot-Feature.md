# Payslip Chatbot Feature Documentation

## Overview

The **Payslip Chatbot** is an AI-powered employee assistant integrated into the payslip portal. It uses **RAG (Retrieval-Augmented Generation)** with Claude AI to answer employee questions about their payslips, taxes, deductions, and earnings.

## Architecture

### Components

1. **PayslipChatService** (`app/Services/PayslipChatService.php`)
   - Retrieves employee payslip data
   - Builds comprehensive RAG context from 1 year of payslip history
   - Formats payslip information for AI consumption
   - Provides YTD summaries, earnings/deductions breakdown, and tax calculations

2. **PayslipChatController** (`app/Http/Controllers/Employee/PayslipChatController.php`)
   - REST API endpoints for chat functionality
   - Handles message sending and context retrieval
   - Integrates with Anthropic Claude API
   - Maintains conversation history

3. **Chat Widget** (`resources/js/payslip-chat-widget.js`)
   - Interactive JavaScript component
   - Real-time messaging UI with Tailwind CSS
   - Conversation persistence in browser
   - Suggested questions based on payslip data

4. **Views Integration**
   - Payslip index view: `resources/views/pages/employee/payslips/index.blade.php`
   - Payslip detail view: `resources/views/pages/employee/payslips/show.blade.php`

### Routes

```
POST   /my/payslips/chat           - Send chat message
GET    /my/payslips/chat/context   - Get payslip context & YTD summary
GET    /my/payslips/chat/suggestions/{payslip} - Get contextual suggestions
```

## How It Works

### Data Flow

1. **Employee opens payslip** → Chat widget initializes
2. **Widget loads context** → Fetches YTD summary and recent payslips
3. **Employee sends message** → Message sent to PayslipChatController
4. **Context retrieval** → PayslipChatService builds comprehensive payslip context
5. **API call** → Message + context sent to Anthropic Claude
6. **Response generation** → Claude generates contextual answer
7. **Display response** → Widget displays response to employee

### RAG Context Includes

- **Employee Information**: Name, ID, Department
- **Current Year YTD**: Gross, Deductions, Net Pay
- **Recent Payslips**: Last 6 months summary
- **Latest Payslip Details**:
  - Earnings breakdown (salary, bonuses, reimbursements)
  - Deductions breakdown (tax, benefits, pension)
  - Tax calculations
  - Period summary

### Security

✅ **Employee-scoped data access** - Employees can only see their own payslips
✅ **Auth middleware** - Routes require `auth:employee` middleware
✅ **Model validation** - PayslipChatService verifies employee ownership before returning data
✅ **No data persistence** - Chat messages not stored (history kept in-browser only)

## Supported Questions

### Tax Questions
- "Why is my tax higher this month?"
- "How is my income tax calculated?"
- "What's my YTD tax?"

### YTD Questions
- "What's my YTD gross?"
- "How much have I earned so far this year?"
- "What's my YTD net pay?"

### Deduction Questions
- "What are my deductions?"
- "How is my provident fund calculated?"
- "Can you break down my deductions?"

### General Questions
- "When is my salary paid?"
- "Why is this deduction on my payslip?"
- "How is my net pay calculated?"

## Configuration

### AI Model Setup

Edit `config/ai.php`:

```php
'anthropic' => [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'model'   => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
],
```

### Environment Variables

Add to `.env`:

```env
ANTHROPIC_API_KEY=your_api_key_here
ANTHROPIC_MODEL=claude-3-5-sonnet-20241022
```

## Features

### 1. Contextual Suggestions
- Widget generates 3 relevant questions based on payslip content
- Questions appear below chat messages
- Clickable to pre-fill chat input

### 2. Multi-turn Conversations
- Conversation history maintained in browser
- Context carried across messages
- User can ask follow-up questions

### 3. Responsive Design
- Desktop: Side-by-side layout (payslip details + chat)
- Mobile: Stacked layout with chat below
- Sticky chat widget for easy access

### 4. Rich Formatting
- Markdown-style formatting (bold, underline)
- Currency formatting with proper symbols
- Line breaks for readability

## Integration Example

### In Blade Template
```blade
<div id="payslip-chat-widget" data-payslip-id="{{ $payslip->id }}"></div>

@push('scripts')
<script src="{{ asset('js/payslip-chat-widget.js') }}"></script>
@endpush
```

### API Usage (JavaScript)
```javascript
// Send message
fetch('/my/payslips/chat', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
    },
    body: JSON.stringify({
        message: 'Why is my tax higher?',
        payslip_id: 123,
        conversation_history: [],
    }),
});

// Get context
fetch('/my/payslips/chat/context');

// Get suggestions
fetch('/my/payslips/chat/suggestions/123');
```

## Error Handling

- **API errors** → Display user-friendly error message
- **Missing payslips** → Chat loads general context
- **Auth errors** → 401 redirect to login
- **Claude errors** → Logged and user notified

## Performance Considerations

- **One-time context load** → Initial load fetches 1 year of payslips
- **Lazy loading suggestions** → Loaded only when payslip detail page opens
- **Browser storage** → Conversation kept in memory (cleared on refresh)
- **Rate limiting** → No built-in rate limiting; recommend 5-10 requests/minute per employee

## Future Enhancements

- [ ] Persist conversation history to database
- [ ] Add payslip comparison (month-to-month, YoY)
- [ ] Export chat transcript as PDF
- [ ] Multi-language support
- [ ] Advanced filters (tax breakdown by category, etc.)
- [ ] HR can mark FAQs from chatbot for knowledge base
- [ ] Analytics dashboard (common questions, sentiment)
- [ ] Integration with HR ticket system for escalation

## Troubleshooting

### Chat widget not showing
- Check browser console for errors
- Verify `data-payslip-id` attribute is set
- Ensure `payslip-chat-widget.js` is loaded
- Check CSRF token is available

### API returns 401
- Employee not authenticated
- Session expired
- Check `auth:employee` middleware

### No suggestions appearing
- Payslip has minimal data
- Widget will show generic suggestions if none contextual

### Slow responses
- Check Claude API rate limits
- Verify network connection
- Check server logs for errors

## Testing

### Manual Testing Checklist
- [ ] Open payslip detail → Chat widget loads
- [ ] Ask "What's my net pay?" → Response includes actual amount
- [ ] Ask follow-up question → Context maintained
- [ ] Click suggestion → Pre-fills chat input
- [ ] View different payslips → Context changes
- [ ] Test on mobile → Layout responsive
- [ ] Ask unsupported question → Graceful response

### Sample Test Questions
1. "Why is my tax higher this month?" → Should explain tax deductions
2. "What's my YTD gross?" → Should show actual YTD figure
3. "Can you break down my deductions?" → Should list all deductions
4. "How is my provident fund calculated?" → Should explain PF logic
5. "What does PAYE mean?" → Should explain term clearly

## Database Migration (Optional)

To persist conversation history (future feature):

```php
Schema::create('payslip_chat_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('employee_id')->constrained('employees');
    $table->foreignId('payslip_id')->nullable()->constrained('payslips');
    $table->string('conversation_id');
    $table->enum('role', ['user', 'assistant']);
    $table->longText('message');
    $table->timestamps();
    
    $table->index(['employee_id', 'conversation_id']);
});
```

## API Reference

### Send Message
```
POST /my/payslips/chat

Request:
{
    "message": "Why is my tax higher?",
    "payslip_id": 123,
    "conversation_history": [
        {"role": "user", "content": "..."},
        {"role": "assistant", "content": "..."}
    ]
}

Response:
{
    "success": true,
    "message": "Your tax is higher due to...",
    "conversation_id": "payslip_5_..."
}
```

### Get Context
```
GET /my/payslips/chat/context

Response:
{
    "available_payslips": 12,
    "recent_payslips": [
        {"id": 1, "period": "Apr 2026", "net_pay": 5000, "status": "paid"},
        ...
    ],
    "common_questions": [...],
    "ytd_summary": {
        "gross": 50000,
        "deductions": 10000,
        "net": 40000
    }
}
```

### Get Suggestions
```
GET /my/payslips/chat/suggestions/123

Response:
{
    "payslip_id": 123,
    "period": "Apr 2026",
    "suggestions": [
        "Why did I pay $500 in tax?",
        "What are all my deductions?",
        "What's my YTD gross?"
    ]
}
```

## Summary

The **Payslip Chatbot** significantly reduces HR query volume by:
- Providing instant answers to common payslip questions
- Offering personalized context from employee data
- Reducing reliance on HR department for simple inquiries
- Improving employee self-service experience

**Impact**: Medium (expected 30-50% reduction in payslip-related HR tickets)
**Time to implement**: 4-6 hours
**Maintenance**: Minimal (mainly monitoring API costs)
