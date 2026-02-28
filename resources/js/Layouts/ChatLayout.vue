<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const conversations = computed(() => page.props.conversations as Array<{ id: number, title: string }>);
const user = computed(() => page.props.auth.user);
</script>

<template>
    <div class="flex h-screen bg-white">
        <aside class="w-64 bg-gray-900 text-white flex flex-col h-full border-r border-gray-800">
            <div class="p-4">
                <Link 
                    :href="route('dashboard')"
                    class="w-full flex items-center gap-2 px-4 py-3 bg-gray-800 hover:bg-gray-700 rounded-lg text-sm font-medium transition-colors border border-gray-700"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Chat
                </Link>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-2 space-y-1">
                <div v-if="conversations.length === 0" class="text-gray-500 text-sm px-2 mt-4">
                    No conversations yet.
                </div>
                
                <Link 
                    v-for="chat in conversations" 
                    :key="chat.id"
                    :href="route('chat.show', chat.id)"
                    :class="[ 
                        'block px-3 py-3 rounded-lg text-sm truncate transition-colors',
                        $page.url.startsWith(`/chat/${chat.id}`) 
                            ? 'bg-gray-800 text-white font-medium' 
                            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                    ]"
                >
                    {{ chat.title }}
                </Link>
            </div>

            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium truncate pr-2">{{ user?.name }}</span>
                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button"
                        class="text-gray-400 hover:text-white transition-colors"
                        title="Log Out"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </Link>
                </div>
            </div>
        </aside>

        <main class="flex-1 flex flex-col min-w-0 bg-white">
            <slot />
        </main>
    </div>
</template>