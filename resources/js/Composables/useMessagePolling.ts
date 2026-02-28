import { ref } from 'vue';
import axios from 'axios';
import { Message } from '@/Types';

export function useMessagePolling() {
    const isPolling = ref(false);

    const pollStatus = async (messageId: number, onUpdate: (msg: Message) => void) => {
        isPolling.value = true;
        
        const check = async () => {
            try {
                const { data } = await axios.get(`/api/message/${messageId}/status`);
                const message: Message = data.data || data;

                onUpdate(message);

                if (message.status === 'completed' || message.status === 'failed') {
                    isPolling.value = false;
                    return;
                }

                // Wait 2 seconds and check again
                setTimeout(check, 2000);
            } catch (error) {
                console.error('Polling failed', error);
                isPolling.value = false;
            }
        };

        await check();
    };

    return { pollStatus, isPolling };
}
