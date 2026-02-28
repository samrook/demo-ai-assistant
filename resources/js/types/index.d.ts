export type MessageRole = 'user' | 'assistant' | 'system';
export type MessageStatus = 'pending' | 'processing' | 'completed' | 'failed';

export interface Message {
    id: number;
    role: MessageRole;
    content: string | null;
    status: MessageStatus;
    used_rag: boolean;
    metadata: {
        total_tokens?: number;
        prompt_tokens?: number;
        completion_tokens?: number;
        model_used?: string;
    } | null;
    created_at: string;
}

export interface Conversation {
    id: number;
    title: string;
    model_used: string;
    messages?: Message[];
    created_at: string;
}
