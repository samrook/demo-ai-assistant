<?php

namespace App\Jobs;

use App\Enums\AiMessageStatus;
use App\Models\AiMessage;
use App\Services\OpenWebUIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAiResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AiMessage $message
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OpenWebUIService $aiService): void
    {
        try {
            $this->message->update([
                'status' => AiMessageStatus::PROCESSING,
            ]);

            $response = $aiService->generateResponse(
                $this->message->conversation,
                $this->message->used_rag
            );

            $this->message->update([
                'content' => $response['content'],
                'metadata' => $response['metadata'],
                'status' => AiMessageStatus::COMPLETED,
            ]);

        } catch (Throwable $e) {
            Log::error('AI Generation Failed: ' . $e->getMessage(), [
                'message_id' => $this->message->id,
            ]);

            $this->message->update([
                'status' => AiMessageStatus::FAILED,
            ]);
        }
    }
}
