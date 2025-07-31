<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    public function chat(Request $request)
    {
        set_time_limit(0);
        ignore_user_abort(true);
        $prompt = $request->input('prompt');

        $datasetPath = storage_path('app/dataset_cpo_kpbn_prices.json');
        $cpoData = '';
        if (file_exists($datasetPath)) {
            $cpoData = file_get_contents($datasetPath);
        }

        // Buat system prompt dengan data tambahan
        $fullPrompt = "Kamu adalah konsultan keuangan, produksi minyak goreng, refinery & fraksinasi, dan marketing.
    Gunakan data berikut sebagai referensi utama:\n\n"
            . $cpoData
            . "\n\nSekarang jawab pertanyaan berikut:\n"
            . $prompt;

        $response = Http::withOptions([
            'stream' => true,
            'timeout' => 0, // unlimited
        ])
        ->post(env('OLLAMA_HOST') . '/api/generate', [
            'model' => 'llama3.1:8b',
            'prompt' => $fullPrompt,
            'stream' => true,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Gagal connect ke Ollama'], 500);
        }

        return response()->stream(function () use ($response) {
            $body = $response->getBody();
            $buffer = '';

            while (!$body->eof()) {
                $chunk = $body->read(256);
                $buffer .= $chunk;

                // pecah per baris
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);

                    if ($line) {
                        $json = json_decode($line, true);
                        if (isset($json['response'])) {
                            // filter <think>
                            $text = preg_replace('/<think>.*?<\/think>/s', '', $json['response']);
                            echo $text;
                            ob_flush();
                            flush();
                        }
                    }
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
