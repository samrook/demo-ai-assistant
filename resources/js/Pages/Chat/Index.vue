<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ChatLayout from '@/Layouts/ChatLayout.vue';

const prompt = ref('');
const useRag = ref(false);
const isSubmitting = ref(false);

const submitPrompt = () => {
    if (!prompt.value.trim() || isSubmitting.value) return;
    
    isSubmitting.value = true;

    router.post(route('chat.store'), {
        prompt: prompt.value,
        use_rag: useRag.value,
    }, {
        onFinish: () => isSubmitting.value = false,
    });
};
</script>

<template>
    <ChatLayout>
        <div class="flex flex-col h-full bg-gray-50 items-center justify-center p-6">
            
            <div class="max-w-2xl w-full text-center mb-12 space-y-4">
                <div class="w-16 h-16 bg-indigo-600 rounded-2xl mx-auto flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">How can I help you today?</h1>
                <p class="text-gray-500">I'm connected to your local 6700 XT. Ask me anything about Laravel.</p>
            </div>

            <div class="w-full max-w-3xl">
                <form @submit.prevent="submitPrompt" class="flex flex-col gap-3 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex items-center gap-2 px-2">
                        <input type="checkbox" id="rag-toggle" v-model="useRag" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="rag-toggle" class="text-sm text-gray-600 font-medium">Use Laravel 12 Docs Knowledge Base</label>
                    </div>
                    
                    <div class="flex gap-2">
                        <textarea 
                            v-model="prompt" 
                            @keydown.enter.prevent="submitPrompt"
                            rows="2"
                            placeholder="Message LaraPulse AI..."
                            class="flex-1 resize-none rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            :disabled="isSubmitting"
                        ></textarea>
                        
                        <button 
                            type="submit" 
                            :disabled="!prompt.trim() || isSubmitting"
                            class="bg-indigo-600 text-white px-6 rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg v-if="isSubmitting" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span v-else>Send</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </ChatLayout>
</template>