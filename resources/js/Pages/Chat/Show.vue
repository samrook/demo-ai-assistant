<script setup lang="ts">
import { ref, nextTick, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useMessagePolling } from '@/Composables/useMessagePolling';
import { renderMarkdown } from '@/Utils/Markdown';
import { Conversation, Message } from '@/Types';
import ChatLayout from '@/Layouts/ChatLayout.vue';

declare function route(name: string, params?: any): string;

const props = defineProps<{
    conversation: Conversation;
}>();

const messages = ref<Message[]>(props.conversation.messages || []);
const prompt = ref('');
const chatContainer = ref<HTMLElement | null>(null);
const isSubmitting = ref(false);
const lastUserMessage = [...messages.value].reverse().find(msg => msg.role === 'user');
const useRag = ref(
    lastUserMessage ? Boolean(lastUserMessage.used_rag) : false
);

const { pollStatus, stopPolling, isPolling } = useMessagePolling();

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const renderedMessages = ref<Record<number, string>>({});
const parseMessages = async () => {
    for (const msg of messages.value) {
        if (msg.content && !renderedMessages.value[msg.id]) {
            renderedMessages.value[msg.id] = await renderMarkdown(msg.content);
        }
    }
    scrollToBottom();
};

onMounted(() => {
    parseMessages();
    
    const lastMsg = messages.value[messages.value.length - 1];
    if (lastMsg && (lastMsg.status === 'pending' || lastMsg.status === 'processing')) {
        startPolling(lastMsg.id);
    }
});

onUnmounted(() => {
    stopPolling();
});

const submitPrompt = async () => {
    if (!prompt.value.trim() || isSubmitting.value || isPolling.value) return;

    const currentPrompt = prompt.value;
    prompt.value = '';
    isSubmitting.value = true;

    messages.value.push({
        id: Date.now(),
        role: 'user',
        content: currentPrompt,
        status: 'completed',
        used_rag: false,
        metadata: null,
        created_at: new Date().toISOString(),
    });
    scrollToBottom();

    try {
        const response = await axios.post(route('chat-message.store', props.conversation.id), {
            prompt: currentPrompt,
            use_rag: useRag.value,
        });

        const newAssistantMessage: Message = response.data.data;
        
        messages.value.push(newAssistantMessage);
        scrollToBottom();

        startPolling(newAssistantMessage.id);

    } catch (error) {
        console.error('Failed to send message:', error);
    } finally {
        isSubmitting.value = false;
    }
};

const startPolling = (messageId: number) => {
    pollStatus(messageId, async (updatedMessage) => {
        const index = messages.value.findIndex(m => m.id === messageId);
        if (index !== -1) {
            messages.value[index] = updatedMessage;
            
            if (updatedMessage.status === 'completed' && updatedMessage.content) {
                renderedMessages.value[messageId] = await renderMarkdown(updatedMessage.content);
                scrollToBottom();
            }
        }
    });
};
</script>

<template>
    <ChatLayout>
        <div class="flex flex-col h-screen bg-gray-50">
            <header class="bg-white shadow-sm px-6 py-4 flex justify-between items-center">
                <h1 class="text-xl font-bold text-gray-800">{{ conversation.title }}</h1>
                <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-1 rounded-full">
                    {{ conversation.model_used }}
                </span>
            </header>
    
            <main ref="chatContainer" class="flex-1 overflow-y-auto p-6 space-y-6">
                <div v-for="msg in messages" :key="msg.id" 
                     :class="['flex', msg.role === 'user' ? 'justify-end' : 'justify-start']">
                    
                    <div :class="[ 
                        'max-w-3xl rounded-2xl px-6 py-4 shadow-sm',
                        msg.role === 'user' ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-100'
                    ]">
                        <div v-if="msg.role === 'user'" class="whitespace-pre-wrap">{{ msg.content }}</div>
                        
                        <div v-else>
                            <div v-if="msg.status === 'pending'" class="text-gray-400 flex items-center gap-2">
                                <span class="animate-pulse">Waiting in queue...</span>
                            </div>
                            <div v-else-if="msg.status === 'processing'" class="text-indigo-500 flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"></svg>
                                <span>GPU is thinking...</span>
                            </div>
                            <div v-else-if="msg.status === 'failed'" class="text-red-500">
                                Generation failed. Check logs.
                            </div>
                            
                            <div v-else class="prose max-w-none" v-html="renderedMessages[msg.id]"></div>
                            
                            <div v-if="msg.status === 'completed'" class="mt-4 flex gap-3 text-xs text-gray-400 border-t pt-2">
                                <span v-if="msg.used_rag">📚 Used Laravel Docs</span>
                                <span v-if="msg.metadata?.total_tokens">⚡ {{ msg.metadata.total_tokens }} tokens</span>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
    
            <footer class="bg-white border-t p-4">
                <form @submit.prevent="submitPrompt" class="max-w-4xl mx-auto flex flex-col gap-3">
                    <div class="flex items-center gap-2 px-2">
                        <input type="checkbox" id="rag-toggle" v-model="useRag" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="rag-toggle" class="text-sm text-gray-600 font-medium">Use Laravel 12 Docs Knowledge Base</label>
                    </div>
                    
                    <div class="flex gap-2">
                        <textarea 
                            v-model="prompt" 
                            @keydown.enter.prevent="submitPrompt"
                            rows="2"
                            placeholder="Ask LaraPulse AI..."
                            class="flex-1 resize-none rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :disabled="isSubmitting || isPolling"
                        ></textarea>
                        
                        <button 
                            type="submit" 
                            :disabled="!prompt.trim() || isSubmitting || isPolling"
                            class="bg-indigo-600 text-white px-6 rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            Send
                        </button>
                    </div>
                </form>
            </footer>
        </div>
    </ChatLayout>
</template>
