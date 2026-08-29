@extends('layouts.ai')

@section('title', 'Financial Intelligence Assistant')

@section('content')
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/highlight.js@11/highlight.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/highlight.js@11/styles/atom-one-dark.min.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <style>
            * {
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            body {
                background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            }

            .glass {
                background: rgba(30, 27, 75, 0.5);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(129, 140, 248, 0.1);
            }

            .glass-light {
                background: rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .scroll-smooth {
                scroll-behavior: smooth;
            }

            .message-enter {
                animation: slideIn 0.3s ease-out;
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .fade-in {
                animation: fadeIn 0.5s ease-out;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                }

                to {
                    opacity: 1;
                }
            }

            .typing-cursor {
                animation: blink 0.7s infinite;
            }

            @keyframes blink {

                0%,
                49%,
                100% {
                    opacity: 1;
                }

                50%,
                99% {
                    opacity: 0;
                }
            }

            .suggestion-chip {
                transition: all 0.2s ease;
            }

            .suggestion-chip:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3);
            }

            .chat-message {
                word-wrap: break-word;
                overflow-wrap: break-word;
            }

            .markdown-content h1,
            .markdown-content h2,
            .markdown-content h3 {
                font-weight: 600;
                margin-top: 1rem;
                margin-bottom: 0.5rem;
            }

            .markdown-content h1 {
                font-size: 1.5rem;
            }

            .markdown-content h2 {
                font-size: 1.25rem;
            }

            .markdown-content h3 {
                font-size: 1.1rem;
            }

            .markdown-content ul,
            .markdown-content ol {
                margin-left: 1.5rem;
                margin-bottom: 0.5rem;
            }

            .markdown-content li {
                margin-bottom: 0.25rem;
            }

            .markdown-content code {
                background: rgba(0, 0, 0, 0.3);
                padding: 0.2rem 0.4rem;
                border-radius: 0.25rem;
                font-family: 'Courier New', monospace;
                font-size: 0.9rem;
            }

            .markdown-content pre {
                margin: 1rem 0;
                border-radius: 0.5rem;
                overflow-x: auto;
            }

            .markdown-content table {
                width: 100%;
                border-collapse: collapse;
                margin: 1rem 0;
            }

            .markdown-content th,
            .markdown-content td {
                border: 1px solid rgba(129, 140, 248, 0.2);
                padding: 0.75rem;
                text-align: left;
            }

            .markdown-content th {
                background: rgba(129, 140, 248, 0.1);
            }

            .hljs {
                background: transparent !important;
            }

            .sidebar-scrollbar::-webkit-scrollbar {
                width: 6px;
            }

            .sidebar-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .sidebar-scrollbar::-webkit-scrollbar-thumb {
                background: rgba(129, 140, 248, 0.3);
                border-radius: 3px;
            }

            .sidebar-scrollbar::-webkit-scrollbar-thumb:hover {
                background: rgba(129, 140, 248, 0.5);
            }

            .messages-scrollbar::-webkit-scrollbar {
                width: 8px;
            }

            .messages-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .messages-scrollbar::-webkit-scrollbar-thumb {
                background: rgba(129, 140, 248, 0.2);
                border-radius: 4px;
            }

            .messages-scrollbar::-webkit-scrollbar-thumb:hover {
                background: rgba(129, 140, 248, 0.4);
            }

            .copy-btn {
                opacity: 0;
                transition: opacity 0.2s ease;
            }

            .message-item:hover .copy-btn {
                opacity: 1;
            }

            .theme-transition {
                transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
            }
        </style>
    </head>

    <body class="overflow-hidden">
        <div class="flex h-screen bg-gray-950 text-gray-100 theme-transition">
            <!-- Sidebar -->
            <div id="sidebar" class="w-64 flex flex-col glass border-r border-indigo-500/10 shadow-2xl theme-transition">
                <!-- Sidebar Header -->
                <div class="p-4 border-b border-indigo-500/10">
                    <div class="flex items-center justify-between mb-4">
                        <h1
                            class="text-lg font-bold bg-gradient-to-r from-indigo-400 to-blue-400 bg-clip-text text-transparent">
                            AI Assistant</h1>
                        <button id="newChatBtn"
                            class="p-2 hover:glass rounded-lg transition-all duration-200 text-indigo-400 hover:text-indigo-300"
                            title="New Chat">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Chat History -->
                <div class="flex-1 overflow-y-auto sidebar-scrollbar p-3">
                    <div id="chatList" class="space-y-2">
                        <!-- Chat items will be added here -->
                    </div>
                </div>

                <!-- Sidebar Footer -->
                <div class="p-4 border-t border-indigo-500/10 space-y-2">
                    <a href="{{ route('dashboard') }}"
                        class="w-full px-4 py-2 glass-light rounded-lg text-sm font-medium text-gray-300 hover:text-white transition-all duration-200 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m7 7v-6a3 3 0 00-3-3H5a3 3 0 00-3 3v6m14-12l-7 7m7-7v6a3 3 0 01-3 3h-5a3 3 0 01-3-3v-6m0-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        Dashboard
                    </a>
                    <button onclick="clearAllChats()"
                        class="w-full px-4 py-2 glass-light rounded-lg text-sm font-medium text-gray-300 hover:text-red-400 transition-all duration-200 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        Clear History
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col">
                <!-- Header -->
                <div
                    class="h-16 glass border-b border-indigo-500/10 px-8 flex items-center justify-between theme-transition">
                    <div class="flex items-center gap-4">
                        <button id="backBtn"
                            class="p-2 hover:glass rounded-lg transition-all duration-200 text-gray-400 hover:text-gray-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                                </path>
                            </svg>
                        </button>
                        <div>
                            <h2 id="chatTitle" class="text-lg font-semibold text-gray-100">Financial Intelligence Assistant
                            </h2>
                            <p id="chatSubtitle" class="text-sm text-gray-400">Cross-Module Analysis Engine</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            class="p-2 hover:glass rounded-lg transition-all duration-200 text-gray-400 hover:text-gray-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Messages Area -->
                <div id="messagesContainer" class="flex-1 overflow-y-auto messages-scrollbar p-8 space-y-4 scroll-smooth">
                    <!-- Initial Suggestions -->
                    <div id="suggestions" class="h-full flex flex-col items-center justify-center">
                        <div class="max-w-md text-center mb-8 fade-in">
                            <div
                                class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 mx-auto mb-4 flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-100 mb-2">Financial Analysis Ready</h3>
                            <p class="text-gray-400 text-sm">Ask me anything about your financial data. Choose a suggestion
                                or type your question.</p>
                        </div>
                        <div id="suggestionChips" class="grid grid-cols-2 gap-3 w-full max-w-md">
                            <!-- Suggestion chips -->
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="h-auto px-8 py-6 glass border-t border-indigo-500/10 theme-transition">
                    <form id="messageForm" class="max-w-4xl mx-auto" onsubmit="sendMessage(event)">
                        <div class="flex gap-3">
                            <div class="flex-1 relative">
                                <input id="messageInput" type="text"
                                    placeholder="Ask about labor costs, revenue trends, or statutory liabilities..."
                                    class="w-full px-4 py-3 glass-light rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50 transition-all duration-200 resize-none"
                                    autocomplete="off" />
                            </div>
                            <button id="sendBtn" type="submit"
                                class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 rounded-lg text-white font-medium transition-all duration-200 hover:shadow-lg hover:shadow-indigo-500/20 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-2.976 5.951 2.976a1 1 0 001.169-1.409l-7-14z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            // State Management
            const state = {
                isDarkMode: true,
                currentChatId: null,
                chats: [{
                    id: 1,
                    title: 'Financial Analysis',
                    timestamp: new Date(Date.now() - 3600000),
                    messages: []
                }],
                messages: [],
                isLoading: false
            };

            const suggestions = [
                'Labor cost analysis',
                'Revenue trends',
                'Statutory liabilities',
                'Budget forecast'
            ];

            // LocalStorage functions
            function saveChats() {
                localStorage.setItem('aiChats', JSON.stringify(state.chats));
                localStorage.setItem('currentChatId', state.currentChatId);
            }

            function loadSavedChats() {
                const saved = localStorage.getItem('aiChats');
                const savedChatId = localStorage.getItem('currentChatId');

                if (saved) {
                    try {
                        state.chats = JSON.parse(saved).map(chat => ({
                            ...chat,
                            timestamp: chat.timestamp ? new Date(chat.timestamp) : new Date(),
                            messages: Array.isArray(chat.messages) ?
                                chat.messages.map(msg => ({
                                    ...msg,
                                    timestamp: msg.timestamp ? new Date(msg.timestamp) : new Date()
                                })) :
                                []
                        }));
                        state.currentChatId = savedChatId ? parseInt(savedChatId, 10) : state.chats[0]?.id || 1;
                        const currentChat = state.chats.find(c => c.id === state.currentChatId);
                        state.messages = currentChat?.messages || [];
                    } catch (e) {
                        console.error('Failed to load saved chats', e);
                    }
                } else {
                    state.currentChatId = 1;
                }
            }

            function clearAllChats() {
                if (confirm('Are you sure? This will delete all chat history.')) {
                    state.chats = [{
                        id: Date.now(),
                        title: 'New Chat',
                        timestamp: new Date(),
                        messages: []
                    }];
                    state.messages = [];
                    state.currentChatId = state.chats[0].id;
                    saveChats();
                    renderChatList();
                    document.getElementById('messagesContainer').innerHTML = '';
                    renderSuggestions();
                    updateHeader();
                }
            }

            // Initialize
            document.addEventListener('DOMContentLoaded', () => {
                loadSavedChats();
                renderChatList();
                renderMessages();
                setupEventListeners();
                focusInput();
                loadInitialContext();
            });
            // Event Listeners
            function setupEventListeners() {
                document.getElementById('newChatBtn').addEventListener('click', createNewChat);
                document.getElementById('backBtn').addEventListener('click', goBack);
                document.getElementById('messageForm').addEventListener('submit', sendMessage);

                const input = document.getElementById('messageInput');
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage(e);
                    }
                });
            }

            // Chat Management
            function createNewChat() {
                const newChat = {
                    id: Date.now(),
                    title: 'New Chat',
                    timestamp: new Date(),
                    messages: []
                };
                state.chats.unshift(newChat);
                state.currentChatId = newChat.id;
                state.messages = [];
                saveChats();
                renderChatList();
                document.getElementById('messagesContainer').innerHTML = '';
                renderSuggestions();
                updateHeader();
            }

            function switchChat(chatId) {
                state.currentChatId = chatId;
                const chat = state.chats.find(c => c.id === chatId);
                state.messages = chat.messages || [];
                saveChats();
                renderMessages();
                renderChatList();
                updateHeader();
                document.getElementById('messagesContainer').scrollTop = document.getElementById('messagesContainer')
                    .scrollHeight;
            }

            function goBack() {
                window.history.back();
            }

            function updateHeader() {
                const chat = state.chats.find(c => c.id === state.currentChatId);
                const title = document.getElementById('chatTitle');
                const subtitle = document.getElementById('chatSubtitle');

                if (chat) {
                    title.textContent = chat.title;
                    const timeAgo = getTimeAgo(chat.timestamp);
                    subtitle.textContent = `${chat.messages?.length || 0} messages • ${timeAgo}`;
                }
            }

            function getTimeAgo(date) {
                const seconds = Math.floor((new Date() - date) / 1000);
                if (seconds < 60) return 'just now';
                if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
                if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
                return `${Math.floor(seconds / 86400)}d ago`;
            }

            // Rendering
            function renderChatList() {
                const chatList = document.getElementById('chatList');
                chatList.innerHTML = state.chats.map(chat => `
                <div class="chat-item ${state.currentChatId === chat.id ? 'active' : ''}" onclick="switchChat(${chat.id})">
                    <button class="w-full text-left px-3 py-2 rounded-lg transition-all duration-200 ${
                        state.currentChatId === chat.id
                            ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'
                            : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800/50'
                    }">
                        <div class="text-sm font-medium truncate">${escapeHtml(chat.title)}</div>
                        <div class="text-xs text-gray-500 mt-1">${getTimeAgo(chat.timestamp)}</div>
                    </button>
                </div>
            `).join('');
            }

            function renderSuggestions() {
                const container = document.getElementById('suggestionChips');
                if (!container) return;

                container.innerHTML = suggestions.map(suggestion => `
                <button
                    type="button"
                    class="suggestion-chip p-4 glass-light rounded-lg text-left text-sm font-medium text-gray-300 hover:text-gray-100 hover:border-indigo-400/50 transition-all duration-200"
                    onclick="insertSuggestion('${escapeHtml(suggestion)}')"
                >
                    ${suggestion}
                </button>
            `).join('');
            }

            function renderMessages() {
                const container = document.getElementById('messagesContainer');
                if (state.messages.length === 0) {
                    renderSuggestions();
                    container.innerHTML = `
                    <div id="suggestions" class="h-full flex flex-col items-center justify-center">
                        <div class="max-w-md text-center mb-8 fade-in">
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 mx-auto mb-4 flex items-center justify-center">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-100 mb-2">Financial Analysis Ready</h3>
                            <p class="text-gray-400 text-sm">Ask me anything about your financial data.</p>
                        </div>
                        <div id="suggestionChips" class="grid grid-cols-2 gap-3 w-full max-w-md">
                        </div>
                    </div>
                `;
                    renderSuggestions();
                    return;
                }

                // Check if we just need to update the last message
                const messageItems = container.querySelectorAll('.message-item');
                if (messageItems.length === state.messages.length) {
                    const lastMsg = state.messages[state.messages.length - 1];
                    const lastEl = messageItems[messageItems.length - 1];
                    const needsStructuredRender = !lastMsg.isStreaming &&
                        ((lastMsg.type === 'query_result' && lastMsg.data) || (lastMsg.type === 'chart' && lastMsg.data));

                    if (lastMsg.role === 'assistant' && !needsStructuredRender) {
                        const contentEl = lastEl.querySelector('.markdown-content');
                        if (contentEl) {
                            const newContent = marked.parse(lastMsg.content) + (lastMsg.isStreaming ? '<span class="typing-cursor">▌</span>' : '');
                            if (contentEl.innerHTML !== newContent) {
                                contentEl.innerHTML = newContent;
                                contentEl.querySelectorAll('pre code').forEach(block => {
                                    if (!block.classList.contains('hljs')) hljs.highlightElement(block);
                                });
                            }
                            return;
                        }
                    }
                }

                // Fallback: Full render if counts differ
                container.innerHTML = state.messages.map((msg, idx) => `
                    <div class="message-enter message-item mb-6" data-id="${msg.id}">
                        ${msg.role === 'user' ? renderUserMessage(msg) : renderAIMessage(msg)}
                    </div>
                `).join('');

                container.querySelectorAll('.markdown-content').forEach(el => {
                    const msgId = el.closest('.message-item').dataset.id;
                    const msg = state.messages.find(m => m.id == msgId);
                    el.innerHTML = marked.parse(msg.content) + (msg && msg.isStreaming ? '<span class="typing-cursor">▌</span>' : '');
                    el.querySelectorAll('pre code').forEach(block => {
                        hljs.highlightElement(block);
                    });
                });
            }

            function renderUserMessage(msg) {
                return `
                <div class="flex justify-end">
                    <div class="flex items-end gap-3 max-w-2xl">
                        <div class="px-4 py-3 rounded-2xl rounded-tr-sm bg-gradient-to-r from-indigo-600 to-blue-600 shadow-lg">
                            <p class="text-white text-sm">${escapeHtml(msg.content)}</p>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                            U
                        </div>
                    </div>
                </div>
            `;
            }

            function renderAIMessage(msg) {
                const isStreaming = msg.isStreaming;
                let dataHtml = '';

                if (!isStreaming && msg.type === 'query_result' && msg.data) {
                    dataHtml = renderDataTable(msg.data, msg.columns);
                } else if (!isStreaming && msg.type === 'chart' && msg.data) {
                    dataHtml = `<div class="mt-4 p-4 glass-light rounded-xl h-64"><canvas id="chart-${msg.id}"></canvas></div>`;
                    setTimeout(() => renderChart(msg), 100);
                }

                return `
                <div class="flex items-start gap-3 max-w-4xl">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        AI
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <div class="markdown-content text-gray-100 text-sm leading-relaxed chat-message glass-light rounded-2xl rounded-tl-sm p-4">
                            ${marked.parse(msg.content)}${isStreaming ? '<span class="typing-cursor">▌</span>' : ''}
                        </div>
                        ${dataHtml}
                        <div class="flex gap-2 mt-2 copy-btn">
                            <button class="text-xs px-2 py-1 text-gray-400 hover:text-gray-200 transition-colors" onclick="copyToClipboard('${escapeHtml(msg.content)}')">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            `;
            }

            function renderDataTable(data, columns) {
                if (!data || data.length === 0) return '<div class="mt-2 text-gray-500 italic text-xs">No records found.</div>';
                
                const cols = columns && columns.length > 0 ? columns : Object.keys(data[0]);
                
                return `
                    <div class="mt-4 overflow-x-auto glass-light rounded-xl border border-gray-700/50">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-gray-800/50 text-gray-400 uppercase tracking-wider">
                                <tr>
                                    ${cols.map(c => `<th class="px-4 py-2 border-b border-gray-700/50 font-semibold">${escapeHtml(c)}</th>`).join('')}
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700/30">
                                ${data.slice(0, 10).map(row => `
                                    <tr class="hover:bg-indigo-500/5 transition-colors">
                                        ${cols.map(c => `<td class="px-4 py-2 text-gray-300">${escapeHtml(row[c] !== null ? row[c] : '-')}</td>`).join('')}
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                        ${data.length > 10 ? `<div class="p-2 text-center text-gray-500 text-[10px]">Showing top 10 of ${data.length} results</div>` : ''}
                    </div>
                `;
            }

            function renderChart(msg) {
                const ctx = document.getElementById(`chart-${msg.id}`);
                if (!ctx) return;

                const config = msg.data;
                new Chart(ctx, {
                    type: msg.chart_type || 'bar',
                    data: {
                        labels: config.labels || [],
                        datasets: config.datasets || []
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: true, labels: { color: '#94a3b8' } }
                        },
                        scales: {
                            y: { grid: { color: 'rgba(148, 163, 184, 0.1)' }, ticks: { color: '#94a3b8' } },
                            x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                        }
                    }
                });
            }

            // Message Sending
            function sendMessage(event) {
                event.preventDefault();

                const input = document.getElementById('messageInput');
                const message = input.value.trim();

                if (!message || state.isLoading) return;

                const chat = state.chats.find(c => c.id === state.currentChatId);
                if (state.messages.length === 0) {
                    chat.title = message.substring(0, 30) + (message.length > 30 ? '...' : '');
                }

                state.messages.push({
                    id: Date.now(),
                    role: 'user',
                    content: message,
                    timestamp: new Date()
                });

                if (chat) chat.messages = state.messages;

                input.value = '';
                const suggestionsDiv = document.getElementById('suggestions');
                if (suggestionsDiv) suggestionsDiv.remove();

                saveChats();
                renderMessages();
                updateHeader();
                renderChatList();

                setTimeout(() => {
                    document.getElementById('messagesContainer').scrollTop = document.getElementById(
                        'messagesContainer').scrollHeight;
                }, 100);

                fetchAIResponse(message);
            }

            async function fetchAIResponse(userMessage) {
                state.isLoading = true;
                document.getElementById('sendBtn').disabled = true;
                document.getElementById('messageInput').disabled = true;

                const aiMessage = {
                    id: Date.now() + 1,
                    role: 'assistant',
                    content: '',
                    isStreaming: true,
                    timestamp: new Date()
                };
                state.messages.push(aiMessage);
                const chat = state.chats.find(c => c.id === state.currentChatId);
                if (chat) chat.messages = state.messages;

                renderMessages();

                try {
                    const response = await fetch('{{ route('ai.assistant.ask') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            query: userMessage,
                            history: state.messages.slice(0, -1).map(m => ({
                                role: m.role,
                                content: m.content
                            }))
                        })
                    });

                    if (!response.ok) throw new Error('Network response was not ok');

                    const data = await response.json();
                    const fullText = data.message || "I'm sorry, I couldn't process that request.";

                    // Store all response metadata
                    aiMessage.type = data.type;
                    aiMessage.data = data.data;
                    aiMessage.columns = data.columns;
                    aiMessage.visualization = data.visualization;
                    aiMessage.chart_type = data.chart_type;

                    // Simulate streaming for the real text to keep the UI feel
                    let charIndex = 0;
                    const container = document.getElementById('messagesContainer');
                    
                    const streamInterval = setInterval(() => {
                        if (charIndex < fullText.length) {
                            // Append chunk of text instead of character by character to speed up slightly and look smoother
                            const chunkSize = Math.max(1, Math.floor(fullText.length / 50) || 1); 
                            aiMessage.content += fullText.slice(charIndex, charIndex + chunkSize);
                            charIndex += chunkSize;
                            
                            if (charIndex > fullText.length) {
                                aiMessage.content = fullText;
                                charIndex = fullText.length;
                            }
                            
                            renderMessages();
                            container.scrollTop = container.scrollHeight;
                        } else {
                            clearInterval(streamInterval);
                            aiMessage.isStreaming = false;
                            state.isLoading = false;
                            document.getElementById('sendBtn').disabled = false;
                            document.getElementById('messageInput').disabled = false;
                            saveChats();
                            renderMessages();
                            container.scrollTop = container.scrollHeight;
                            focusInput();
                            
                            if (data.suggestions) {
                                suggestions.length = 0;
                                suggestions.push(...data.suggestions);
                                renderSuggestions();
                            }
                        }
                    }, 15);

                } catch (error) {
                    console.error('Error fetching AI response:', error);
                    aiMessage.content = "I'm sorry, I encountered an error. Please check your connection and try again.";
                    aiMessage.isStreaming = false;
                    state.isLoading = false;
                    document.getElementById('sendBtn').disabled = false;
                    document.getElementById('messageInput').disabled = false;
                    renderMessages();
                }
            }

            function insertSuggestion(text) {
                document.getElementById('messageInput').value = text;
                focusInput();
            }

            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Copied to clipboard!');
                });
            }

            function focusInput() {
                document.getElementById('messageInput').focus();
            }

            function escapeHtml(text) {
                if (text === null || text === undefined) return '';
                const str = String(text);
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return str.replace(/[&<>"']/g, m => map[m]);
            }

            async function loadInitialContext() {
                try {
                    const res = await fetch('{{ route('ai.assistant.context') }}');
                    const data = await res.json();
                    if (data.suggestions) {
                        suggestions.length = 0;
                        suggestions.push(...data.suggestions);
                        renderSuggestions();
                    }
                } catch (e) {
                    console.error('Failed to load initial context', e);
                }
            }

            marked.setOptions({
                breaks: true,
                gfm: true
            });
        </script>
    </body>

    </html>

@endsection

@push('scripts')
@endpush
