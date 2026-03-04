<?php

namespace App\Services;

use App\Helpers\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class ArtificialIntelligenceService
{
    protected string $baseUrl = 'https://api.groq.com/openai/v1/';
//    protected string $model = 'llama-3.1-8b-instant';
    protected string $model = 'llama-3.1-8b-instant';
    protected array $instructionPaths;

    public function __construct(
        protected ?string $logTraceId = null
    ) {
        $this->instructionPaths = [
            'pl_name_to_cars'    => resource_path('prompts/gemini_pl_name_to_for_cars.txt'),
            'ebay_to_cars'       => resource_path('prompts/gemini_ebay_name_to_for_cars.txt'),
            'tecdoc_to_cars'     => resource_path('prompts/gemini_tecdoc_to_for_cars.txt'),
            'ebay_to_type'       => resource_path('prompts/gemini_ebay_name_to_product_type.txt'),
            'tecdoc_to_type'     => resource_path('prompts/gemini_tecdoc_to_product_type.txt'),
            'shorten_name'       => resource_path('prompts/artificial_shorten_name.txt'),
        ];
    }

    /**
     * Базовый метод для отправки запроса в AI Service и парсинга JSON-ответа.
     */
    protected function requestService(string $rawText, $inputArrCount, string $instructionPath): array
    {
        if (!File::exists($instructionPath)) {
            throw new \Exception("System instruction file missing at: {$instructionPath}");
        }

        $systemInstruction = File::get($instructionPath);
        $apiKey = config('services.groq.key');

        $data = null;

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(60)
                ->post("{$this->baseUrl}chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemInstruction],
                        ['role' => 'user', 'content' => $rawText],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.1,
                ]);

            Log::add($this->logTraceId, "Attempt {$attempt}.", 3);
            if ($response->failed()) {
                $error = $response->json('error.message') ?? 'Unknown Error';
                Log::add($this->logTraceId, "Attempt {$attempt} failed with status {$response->status()}: " . ($response->json('error.message') ?? 'Unknown error'), 3);

                // Проверяем, не лимит ли это (429 Too Many Requests)
                if ($response->status() === 429 || str_contains($error, 'Rate limit reached')) {
                    // Ищем число секунд в тексте ошибки (например, "in 15.59s")
                    if (preg_match('/in (\d+\.?\d*)(ms|s|m)/', $error, $matches)) {
                        $secondsToWait = (float)$matches[1] + 20.5; // Добавляем запас, чтобы наверняка

                        Log::add($this->logTraceId, "Groq Rate Limit. Waiting {$secondsToWait}s...", 3);
                        dump("Groq Rate Limit. Attempt {$attempt}. Waiting {$secondsToWait}s...");

                        sleep((int)ceil($secondsToWait));
                        continue; // Идем на следующую попытку
                    }
                }

                dump($response, 'errorr');

                // Если это не лимит или мы не нашли время ожидания — вылетаем с ошибкой
                throw new \Exception("AI Service Error: " . $error);
            } else {
                // 1. Пробуем прямой декод (на случай, если пришел чистый JSON)
                $rawContent = $response->json('choices.0.message.content');

                // 1. Попытка №1: Прямой декод
                $decoded = json_decode($rawContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    // 2. Попытка №2: Очистка Markdown (более агрессивная)
                    // Убираем всё до ```json и всё после ```
                    $markdownClean = preg_replace('/(?s).*?```json\s*(.*?)\s*```.*/', '$1', $rawContent);
                    $decoded = json_decode(trim($markdownClean), true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // 3. Попытка №3: Поиск первого вхождения { и последнего }
                        // Это спасет, если модель выдала текст, а потом JSON без кавычек
                        if (preg_match('/({.*})/s', $rawContent, $matches)) {
                            $decoded = json_decode($matches[1], true);
                        }
                    }
                }

                $data = $decoded['result'] ?? $decoded['results'] ?? $decoded;
                dump($rawText, $data);

                if(!$data) {
                    Log::add($this->logTraceId, "data from Groq is empty, Attempt {$attempt}", 3);
                    dump($response);
                    dump("data from Groq is empty, Attempt {$attempt}");
                } else {
                    if (is_array($data) and count($data) === $inputArrCount) {
                        break;
                    } else {
                        dump("AI Error: Count mismatch or not an array, Attempt {$attempt}");
                        Log::add($this->logTraceId, "AI Error: Count mismatch or not an array, Attempt {$attempt}", 4);
                    }
                }
            }
        }

        return is_array($data) ? $data : [];
    }

    /**
     * Преобразует грязную строку запчасти (на польском) в строку совместимости автомобиля.
     * @throws \Exception
     */
    public function formatPolandNameForCars(string $rawText, int $inputArrCount): array
    {
        return $this->requestService($rawText, $inputArrCount, $this->instructionPaths['pl_name_to_cars']);
    }

    /**
     * Генерирует очищенную строку совместимости автомобиля на основе входных данных из eBay.de
     * @throws \Exception
     */
    public function generateCarCompatibilityFromEbayDeNames(string $rawText, int $inputArrCount): array
    {
        return $this->requestService($rawText, $inputArrCount, $this->instructionPaths['ebay_to_cars']);
    }

    /**
     * Генерирует очищенную строку совместимости автомобиля на основе входных данных из TecDoc
     * @throws \Exception
     */
    public function generateCarCompatibilityFromTecDoc(string $rawText, int $inputArrCount): array
    {
        return $this->requestService($rawText, $inputArrCount, $this->instructionPaths['tecdoc_to_cars']);
    }

    /**
     * Генерирует название - тип товара - на основе входных данных из eBay.de
     * @throws \Exception
     */
    public function generateProductTypeFromEbayDeNames(string $rawText, int $inputArrCount): array
    {
        return $this->requestService($rawText, $inputArrCount, $this->instructionPaths['ebay_to_type']);
    }

    /**
     * Генерирует название - тип товара - на основе входных данных из TecDoc
     * @throws \Exception
     */
    public function generateProductTypeFromTecdoc(string $rawText, int $inputArrCount): array
    {
        return $this->requestService($rawText, $inputArrCount, $this->instructionPaths['tecdoc_to_type']);
    }

    /**
     * Генерирует укороченное название - не больше 80 символов
     * @throws \Exception
     */
    public function generateShortenEbayName(string $rawText, int $inputArrCount): array
    {
        return $this->requestService($rawText, $inputArrCount, $this->instructionPaths['shorten_name']);
    }
}
