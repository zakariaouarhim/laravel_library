<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\ApiCache;
use App\Models\SystemSetting;
use Carbon\Carbon;

class APIService
{
    protected $apiSource;
    protected $apiKey;

    public function __construct()
{
    // Try environment variables first, then fall back to database settings
    $this->apiKey = env('GOOGLE_BOOKS_API_KEY') 
        ?? SystemSetting::where('setting_key', 'google_books_api_key')->value('setting_value');
    
    $this->apiSource = (env('GOOGLE_BOOKS_API_ENABLED', true) 
        || SystemSetting::where('setting_key', 'google_books_api_enabled')->value('setting_value')) 
        ? 'google_books' 
        : 'open_library';
    
    if (empty($this->apiKey)) {
        throw new \Exception('Google Books API key is not configured. Please set GOOGLE_BOOKS_API_KEY in .env or add to system_settings table.');
    }
}
    public function fetchBookDataByISBN($isbn)
    {
        // Add debug logging
        \Log::info('APIService received ISBN: ' . var_export($isbn, true));
        
        if (empty($isbn) || trim($isbn) === '') {
            throw new \Exception('ISBN is required for API fetch. Received: ' . var_export($isbn, true));
        }

        // Clean ISBN
        $cleanIsbn = preg_replace('/[^0-9X]/', '', $isbn);
        \Log::info('Fetching book data for cleaned ISBN: ' . $cleanIsbn);

        // Check cache first with retry mechanism
        $cachedData = $this->getCachedData($cleanIsbn);
        if ($cachedData) {
            \Log::info('Found cached data for ISBN: ' . $cleanIsbn);
            return $cachedData;
        }

        try {
            // Fetch from API with retry mechanism
            $responseData = $this->fetchFromAPIWithRetry($cleanIsbn);
            
            // Cache the response with error handling
            $this->cacheResponse($cleanIsbn, $responseData);
            
            return $responseData;

        } catch (\Exception $e) {
            \Log::error('API fetch failed for ISBN: ' . $cleanIsbn . ' - Error: ' . $e->getMessage());
            throw new \Exception('Failed to fetch book data from API: ' . $e->getMessage());
        }
    }

    protected function getCachedData($isbn)
    {
        try {
            $cached = ApiCache::where('cache_key', $isbn)
                            ->where('expires_at', '>', now())
                            ->first();
            
            if ($cached) {
                $data = json_decode($cached->response_data, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                } else {
                    // Cache is corrupted, delete it
                    \Log::warning('Corrupted cache found for ISBN: ' . $isbn . ', deleting...');
                    $cached->delete();
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Error reading cache for ISBN ' . $isbn . ': ' . $e->getMessage());
        }
        
        return null;
    }

    protected function fetchFromAPIWithRetry($isbn, $maxRetries = 3)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                \Log::info("API fetch attempt {$attempt} for ISBN: {$isbn}");
                
                $response = Http::withOptions([
                    'verify' => false,
                    'timeout' => 30,
                    'connect_timeout' => 10
                ])->retry(2, 1000) // Built-in retry for HTTP requests
                ->get("https://www.googleapis.com/books/v1/volumes", [
                    'q' => "isbn:{$isbn}",
                    'key' => $this->apiKey
                ]);

                if (!$response->successful()) {
                    throw new \Exception('API request failed with status: ' . $response->status() . ' - ' . $response->body());
                }

                $responseData = $response->json();
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON response from API: ' . json_last_error_msg());
                }

                \Log::info('API Response received successfully', [
                    'isbn' => $isbn, 
                    'has_items' => isset($responseData['items']),
                    'total_items' => $responseData['totalItems'] ?? 0
                ]);

                return $responseData;

            } catch (\Exception $e) {
                $lastException = $e;
                \Log::warning("API fetch attempt {$attempt} failed for ISBN {$isbn}: " . $e->getMessage());
                
                if ($attempt < $maxRetries) {
                    // Wait before retry (exponential backoff)
                    sleep(pow(2, $attempt - 1));
                }
            }
        }

        throw $lastException;
    }

    protected function cacheResponse($isbn, $responseData)
    {
        try {
            // Use database transaction to ensure atomicity
            DB::transaction(function () use ($isbn, $responseData) {
                // Delete any existing cache entry first
                ApiCache::where('cache_key', $isbn)->delete();
                
                // Create new cache entry
                $cacheData = [
                    'cache_key' => $isbn,
                    'api_source' => $this->apiSource,
                    'response_data' => json_encode($responseData),
                    'expires_at' => now()->addHours(SystemSetting::getSetting('api_cache_duration', 24))
                ];

                // Check if table has timestamp columns
                $tableColumns = \Schema::getColumnListing('api_caches');
                if (in_array('created_at', $tableColumns)) {
                    $cacheData['created_at'] = now();
                    $cacheData['updated_at'] = now();
                }

                ApiCache::create($cacheData);
                
                \Log::info('Successfully cached API response for ISBN: ' . $isbn);
            });
            
        } catch (\Exception $e) {
            // Don't fail the entire operation if caching fails
            \Log::error('Failed to cache API response for ISBN ' . $isbn . ': ' . $e->getMessage());
        }
    }

    // Alternative cURL method with better error handling
    public function fetchBookDataByISBNCurl($isbn)
    {
        if (empty($isbn)) {
            throw new \Exception('ISBN is required for API fetch');
        }

        $cleanIsbn = preg_replace('/[^0-9X]/', '', $isbn);

        // Check cache first
        $cachedData = $this->getCachedData($cleanIsbn);
        if ($cachedData) {
            return $cachedData;
        }

        $url = "https://www.googleapis.com/books/v1/volumes?q=isbn:{$cleanIsbn}&key={$this->apiKey}";
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        curl_close($ch);
        
        if ($response === false || !empty($error)) {
            throw new \Exception('cURL error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP error: {$httpCode}. Response: " . substr($response, 0, 200));
        }
        
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg() . '. Response: ' . substr($response, 0, 200));
        }

        // Cache the response
        $this->cacheResponse($cleanIsbn, $responseData);

        return $responseData;
    }

    public static function getBookCoverUrl($apiData)
    {
        return $apiData['items'][0]['volumeInfo']['imageLinks']['thumbnail'] ?? null;
    }
}