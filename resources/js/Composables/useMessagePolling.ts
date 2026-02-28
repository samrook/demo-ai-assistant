import { onBeforeUnmount, ref } from 'vue';
import axios from 'axios';
import { Message } from '@/Types';

export function useMessagePolling() {
    const isPolling = ref(false);
    const activeMessageId = ref<number | null>(null);
    const timeoutId = ref<ReturnType<typeof setTimeout> | null>(null);

    const stopPolling = () => {
        if (timeoutId.value) {
            clearTimeout(timeoutId.value);
            timeoutId.value = null;
        }

        activeMessageId.value = null;
        isPolling.value = false;
    };

    const pollStatus = async (messageId: number, onUpdate: (msg: Message) => void) => {
        stopPolling();
        activeMessageId.value = messageId;
        isPolling.value = true;
        
        const check = async () => {
            if (activeMessageId.value !== messageId) {
                return;
            }

            try {
                const { data } = await axios.get(`/api/message/${messageId}/status`);
                const message: Message = data.data || data;

                if (activeMessageId.value !== messageId) {
                    return;
                }

                onUpdate(message);

                if (message.status === 'completed' || message.status === 'failed') {
                    stopPolling();
                    return;
                }

                // Wait 2 seconds and check again
                timeoutId.value = setTimeout(check, 2000);
            } catch (error) {
                console.error('Polling failed', error);
                stopPolling();
            }
        };

        await check();
    };

    onBeforeUnmount(stopPolling);

    return { pollStatus, stopPolling, isPolling };
}
