/**
 * Payslip Chat Widget
 * AI-powered chatbot for employee payslip Q&A
 *
 * Usage:
 * <div id="payslip-chat-widget" data-payslip-id="123"></div>
 * <script src="/js/payslip-chat-widget.js"></script>
 */

class PayslipChatWidget {
    constructor(elementId = "payslip-chat-widget", payslipId = null) {
        this.elementId = elementId;
        this.payslipId = payslipId;
        this.apiBase = "/my/payslips/chat";
        this.conversationHistory = [];
        this.isLoading = false;
        this.init();
    }

    async init() {
        const element = document.getElementById(this.elementId);
        if (!element) return;

        // Get payslip ID from data attribute if not provided
        if (!this.payslipId) {
            this.payslipId = element.getAttribute("data-payslip-id");
        }

        // Render widget UI
        this.render(element);

        // Load context and suggestions
        await this.loadContext();

        if (this.payslipId) {
            await this.loadSuggestions();
        }

        this.attachEventListeners();
    }

    render(container) {
        container.innerHTML = `
        <div class="flex flex-col h-screen md:h-[600px] 
                    bg-slate-900 rounded-3xl 
                    border border-slate-800/60 
                    shadow-[0_10px_40px_rgba(0,0,0,0.7)] 
                    overflow-hidden">

            <!-- Header -->
            <div class="bg-slate-800 text-white px-6 py-4 border-b border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse"></div>
                        <h2 class="text-lg font-semibold">Payslip Assistant</h2>
                    </div>

                    <button class="payslip-chat-close text-slate-400 hover:text-white p-2 transition">
                        ✕
                    </button>
                </div>

                <p class="text-slate-400 text-sm mt-2">
                    Ask me about your payslip, taxes, deductions, or earnings
                </p>
            </div>

            <!-- Messages -->
            <div class="flex-1 overflow-y-auto bg-slate-900 p-4 space-y-4" id="payslip-chat-messages">
                <div class="flex justify-start">
                    <div class="bg-slate-800/70 text-slate-200 rounded-xl px-4 py-3 max-w-xs text-sm">
                        Hi 👋 I'm your payslip assistant. Ask me anything about your earnings, deductions, taxes, or summaries.
                    </div>
                </div>
            </div>

            <!-- Suggestions -->
            <div id="payslip-chat-suggestions" class="hidden border-t border-slate-800 bg-slate-900 px-4 py-3">
                <p class="text-xs text-slate-400 font-semibold mb-2">Suggested questions:</p>
                <div class="space-y-2" id="suggestions-container"></div>
            </div>

            <!-- Input -->
            <div class="border-t border-slate-800 bg-slate-900 p-4">
                <div class="flex gap-3">
                    <input 
                        type="text" 
                        id="payslip-chat-input" 
                        placeholder="Ask about your payslip..."
                        class="flex-1 px-4 py-2 bg-slate-800 border border-slate-700 
                               rounded-xl text-white text-sm 
                               placeholder-slate-500 
                               focus:outline-none focus:ring-2 focus:ring-slate-500/40"
                    />
                    
                    <button 
                        id="payslip-chat-send" 
                        class="bg-slate-700 hover:bg-slate-600 
                               text-white px-4 py-2 rounded-xl text-sm font-medium transition">
                        Send
                    </button>
                </div>

                <p id="payslip-chat-error" class="text-red-400 text-xs mt-2 hidden"></p>
            </div>
        </div>
    `;
    }

    attachEventListeners() {
        const input = document.getElementById("payslip-chat-input");
        const sendBtn = document.getElementById("payslip-chat-send");
        const closeBtn = document.querySelector(".payslip-chat-close");

        // Send message on button click
        sendBtn?.addEventListener("click", () => this.sendMessage());

        // Send message on Enter key
        input?.addEventListener("keypress", (e) => {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Close widget
        closeBtn?.addEventListener("click", () => {
            document.getElementById(this.elementId)?.remove();
        });

        // Suggestion buttons
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("payslip-suggestion-btn")) {
                input.value = e.target.getAttribute("data-question");
                this.sendMessage();
            }
        });
    }

    async loadContext() {
        try {
            const response = await fetch(`${this.apiBase}/context`);
            const data = await response.json();

            if (data.available_payslips > 0) {
                this.ytdSummary = data.ytd_summary;
            }
        } catch (error) {
            console.error("Error loading context:", error);
        }
    }

    async loadSuggestions() {
        try {
            const response = await fetch(
                `${this.apiBase}/suggestions/${this.payslipId}`,
            );
            const data = await response.json();

            if (data.suggestions && data.suggestions.length > 0) {
                this.displaySuggestions(data.suggestions);
            }
        } catch (error) {
            console.error("Error loading suggestions:", error);
        }
    }

    displaySuggestions(suggestions) {
        const suggestionsContainer = document.getElementById(
            "payslip-chat-suggestions",
        );
        const container = document.getElementById("suggestions-container");

        if (!suggestionsContainer || !container) return;

        container.innerHTML = suggestions
            .slice(0, 3)
            .map(
                (question) => `
                <button class="payslip-suggestion-btn w-full text-left text-xs text-blue-600 hover:text-blue-700 hover:bg-blue-50 px-3 py-2 rounded transition" 
                        data-question="${this.escapeHtml(question)}">
                    💬 ${this.escapeHtml(question)}
                </button>
            `,
            )
            .join("");

        suggestionsContainer.classList.remove("hidden");
    }

    async sendMessage() {
        const input = document.getElementById("payslip-chat-input");
        const message = input.value.trim();

        if (!message || this.isLoading) return;

        // Clear input
        input.value = "";

        // Add user message to UI
        this.addMessage(message, "user");

        // Show loading state
        this.isLoading = true;
        const loadingId = this.addMessage("Thinking...", "assistant");

        try {
            // Send message to server
            const response = await fetch(this.apiBase, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify({
                    message: message,
                    payslip_id: this.payslipId,
                    conversation_history: this.conversationHistory,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || "Failed to get response");
            }

            // Update loading message with actual response
            this.updateMessage(loadingId, data.message, "assistant");

            // Store in history
            this.conversationHistory.push(
                { role: "user", content: message },
                { role: "assistant", content: data.message },
            );
        } catch (error) {
            console.error("Error sending message:", error);
            this.updateMessage(
                loadingId,
                "❌ Sorry, I encountered an error. Please try again.",
                "assistant",
            );
            this.showError(error.message);
        } finally {
            this.isLoading = false;
        }
    }

    addMessage(content, role) {
        const messagesContainer = document.getElementById(
            "payslip-chat-messages",
        );
        const messageId = "msg-" + Date.now();

        const messageEl = document.createElement("div");
        messageEl.id = messageId;
        messageEl.className = `flex ${role === "user" ? "justify-end" : "justify-start"}`;

        const bubbleClass =
            role === "user"
                ? "bg-indigo-500 text-white"
                : "bg-[#0078C8] text-white";

        messageEl.innerHTML = `
            <div class="${bubbleClass} rounded-lg px-4 py-3 shadow-sm max-w-xs md:max-w-md text-sm break-words">
                ${this.formatMessage(content)}
            </div>
        `;

        messagesContainer.appendChild(messageEl);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        return messageId;
    }

    updateMessage(messageId, content, role) {
        const messageEl = document.getElementById(messageId);
        if (!messageEl) return;

        const bubble = messageEl.querySelector("div");
        bubble.innerHTML = this.formatMessage(content);

        // Auto-scroll
        const messagesContainer = document.getElementById(
            "payslip-chat-messages",
        );
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    formatMessage(content) {
        // Basic markdown-like formatting
        return content
            .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
            .replace(/__(.*?)__/g, "<u>$1</u>")
            .replace(/\n/g, "<br>")
            .replace(
                /\$([0-9,.]+)/g,
                '<span class="font-semibold">$$$1</span>',
            );
    }

    showError(message) {
        const errorEl = document.getElementById("payslip-chat-error");
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove("hidden");
            setTimeout(() => errorEl.classList.add("hidden"), 5000);
        }
    }

    escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute("content") : "";
    }
}

// Auto-initialize widget if element exists
document.addEventListener("DOMContentLoaded", () => {
    const widget = document.getElementById("payslip-chat-widget");
    if (widget) {
        new PayslipChatWidget();
    }
});
