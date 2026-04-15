<?php
require_once '../config/config.php';
redirectIfNotLoggedIn();
?>
<div id="chatbot" class="fixed bottom-6 right-6 z-50">
    <button onclick="toggleChatbot()" class="w-14 h-14 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition">
        <i data-lucide="message-circle" class="w-7 h-7"></i>
    </button>
    <div id="chatWindow" class="hidden absolute bottom-16 right-0 w-80 glass rounded-2xl p-4" style="max-height: 400px; overflow-y: auto;">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-bold">Sports Bot</h3>
            <button onclick="toggleChatbot()" class="p-1 hover:bg-gray-700 rounded">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div id="chatMessages" class="space-y-2 mb-3" style="min-height: 200px; max-height: 250px; overflow-y: auto;">
            <p class="text-gray-400 text-sm">Namaste! Mai Sports Bot hoon. Kaise help kar sakta hoon?</p>
        </div>
        <div class="flex gap-2">
            <input type="text" id="chatInput" placeholder="Type message..." 
                class="flex-1 px-3 py-2 rounded-lg text-sm"
                onkeyup="if(event.key==='Enter')sendMessage()">
            <button onclick="sendMessage()" class="px-3 py-2 bg-blue-500 rounded-lg">
                <i data-lucide="send" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</div>

<script>
let chatOpen = false;

function toggleChatbot() {
    chatOpen = !chatOpen;
    document.getElementById('chatWindow').classList.toggle('hidden', !chatOpen);
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;
    
    const messages = document.getElementById('chatMessages');
    messages.innerHTML += `<p class="text-sm"><strong>Aap:</strong> ${message}</p>`;
    
    fetch('../api/chatbot.php?message=' + encodeURIComponent(message))
        .then(r => r.json())
        .then(data => {
            messages.innerHTML += `<p class="text-sm text-blue-400">Bot: ${data.reply}</p>`;
            messages.scrollTop = messages.scrollHeight;
        });
    
    input.value = '';
}

lucide.createIcons();
</script>
<style>
.modal-backdrop { background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); }
</style>