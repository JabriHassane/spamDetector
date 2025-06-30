<?php
/**
 * Enhanced Spam Doctor with Machine Learning
 * Uses PHP-ML library for spam detection with AI models
 * 
 * Installation: composer require php-ai/php-ml
 */

namespace JBTools\SpamDetector;

use Exception;
use Phpml\Classification\NaiveBayes;
use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Pipeline;
use Phpml\ModelManager;

/**
 * Class EnhancedSpamDoctor
 * Machine Learning powered spam detection
 */
class EnhancedSpamDoctor
{
    private $spamDictionary;
    private $trainingDataFile;
    private $modelFile;
    private $spamDataFile;
    
    private $textContent = '';
    private $isHTML = false;
    private $replaceRule = '';
    
    private $spamFoundPositions = [];
    private $spamFoundItems = [];
    private $spamFoundContentItems = [];
    private $spamContentHighlightedText = '';
    private $spamContentHighlightedHtml = '';
    
    private $userFilterItems = [];
    private $classifier;
    private $pipeline;
    private $spamProbability = 0.0;
    private $isSpam = false;
    
    // ML Model configuration
    private $modelType = 'naive_bayes'; // 'naive_bayes' or 'svm'
    private $confidenceThreshold = 0.7;
    
    /**
     * Constructor
     */
    public function __construct($modelType = 'naive_bayes')
    {
        $this->spamDictionary = __DIR__ . '/../data/spam_data.txt';
        $this->trainingDataFile = __DIR__ . '/../data/training_data.json';
        $this->modelFile = __DIR__ . '/../data/spam_model.dat';
        $this->spamDataFile = __DIR__ . '/../data/collected_spam.txt';
        $this->modelType = $modelType;
        
        $this->initializeModel();
    }
    
    /**
     * Initialize or load the ML model
     */
    private function initializeModel()
    {
        if (file_exists($this->modelFile)) {
            $this->loadModel();
        } else {
            $this->createModel();
        }
    }
    
    /**
     * Create a new ML model
     */
    private function createModel()
    {
        $tokenizer = new WordTokenizer();
        $vectorizer = new TokenCountVectorizer($tokenizer);
        $tfIdfTransformer = new TfIdfTransformer();

        if ($this->modelType === 'svm') {
            $classifier = new SVC(Kernel::LINEAR, 1.0);
        } else {
            $classifier = new NaiveBayes();
        }

        // The first argument is an array of transformers, the second is the estimator/classifier
        $this->pipeline = new Pipeline(
            [$vectorizer, $tfIdfTransformer],
            $classifier
        );

        // Train with initial data if available
        $this->trainModel();
    }
    
    /**
     * Load existing model
     */
    private function loadModel()
    {
        try {
            $modelManager = new ModelManager();
            $this->pipeline = $modelManager->restoreFromFile($this->modelFile);
        } catch (Exception $e) {
            // If loading fails, create new model
            $this->createModel();
        }
    }
    
    /**
     * Save the model
     */
    private function saveModel()
    {
        $modelManager = new ModelManager();
        $modelManager->saveToFile($this->pipeline, $this->modelFile);
    }
    
    /**
     * Train the model with collected data
     */
    public function trainModel()
    {
        $trainingData = $this->getTrainingData();
        
        if (empty($trainingData['samples']) || empty($trainingData['labels'])) {
            // Use default training data if no data available
            $this->createDefaultTrainingData();
            $trainingData = $this->getTrainingData();
        }
        
        if (!empty($trainingData['samples']) && !empty($trainingData['labels'])) {
            $this->pipeline->train($trainingData['samples'], $trainingData['labels']);
            $this->saveModel();
        }
    }
    
    /**
     * Get training data from file
     */
    private function getTrainingData()
    {
        if (!file_exists($this->trainingDataFile)) {
            return ['samples' => [], 'labels' => []];
        }
        
        $data = json_decode(file_get_contents($this->trainingDataFile), true);
        return $data ?? ['samples' => [], 'labels' => []];
    }
    
    /**
     * Create default training data
     */
    private function createDefaultTrainingData()
    {
        $spamSamples = [
            "URGENT! You have won $1000000! Click here now!",
            "Free money! No strings attached! Act now!",
            "Congratulations! You are our lucky winner!",
            "Limited time offer! Buy now or miss out forever!",
            "Work from home and earn $5000 per week!",
            "Lose weight fast with this miracle pill!",
            "Hot singles in your area want to meet you!",
            "Your account will be closed unless you verify now!",
            "Claim your prize now! Click this link immediately!",
            "Make money fast! No experience needed!"
        ];
        
        $hamSamples = [
            "Meeting scheduled for tomorrow at 2 PM",
            "Thanks for your email, I'll get back to you soon",
            "The project deadline is next Friday",
            "Please review the attached document",
            "Happy birthday! Hope you have a great day",
            "Reminder: Team lunch is at noon today",
            "The weather forecast shows rain tomorrow",
            "Your order has been shipped and will arrive Monday",
            "Conference call moved to 3 PM today",
            "Thanks for the great work on the presentation"
        ];
        
        $samples = array_merge($spamSamples, $hamSamples);
        $labels = array_merge(
            array_fill(0, count($spamSamples), 'spam'),
            array_fill(0, count($hamSamples), 'ham')
        );
        
        $this->saveTrainingData($samples, $labels);
    }
    
    /**
     * Save training data to file
     */
    private function saveTrainingData($samples, $labels)
    {
        $data = ['samples' => $samples, 'labels' => $labels];
        
        $path_explode = explode('/', $this->trainingDataFile);
        $path_dir = str_replace(end($path_explode), '', $this->trainingDataFile);
        
        if (!is_dir($path_dir)) {
            mkdir($path_dir, 0777, true);
        }
        
        file_put_contents($this->trainingDataFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Add new training sample
     */
    public function addTrainingSample($text, $isSpam)
    {
        $trainingData = $this->getTrainingData();
        $trainingData['samples'][] = $text;
        $trainingData['labels'][] = $isSpam ? 'spam' : 'ham';
        
        $this->saveTrainingData($trainingData['samples'], $trainingData['labels']);
        
        // If spam, also save to spam collection
        if ($isSpam) {
            $this->saveSpamMessage($text);
        }
    }
    
    /**
     * Save spam message to collection file
     */
    private function saveSpamMessage($text)
    {
        $path_explode = explode('/', $this->spamDataFile);
        $path_dir = str_replace(end($path_explode), '', $this->spamDataFile);
        
        if (!is_dir($path_dir)) {
            mkdir($path_dir, 0777, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $spamEntry = "[$timestamp] $text" . PHP_EOL;
        file_put_contents($this->spamDataFile, $spamEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Main check function - combines ML prediction with traditional detection
     */
    public function check($text, $is_html = false)
    {
        if (empty($text)) {
            throw new Exception('Text content is missing. Please provide some text to check');
        }

        $this->isHTML = $is_html;
        $this->textContent = $text;

        // Process HTML if needed
        if ($this->isHTML) {
            $this->_processHtml();
        }

        // Initialize content variables
        $this->spamContentHighlightedText = $this->textContent;
        $this->spamContentHighlightedHtml = $text;

        // Perform ML prediction
        $this->performMLPrediction();

        // Perform traditional dictionary-based detection
        $this->performTraditionalDetection();

        // If ML predicts spam, save for future training
        if ($this->isSpam) {
            // Save to spam collection
            $this->saveSpamMessage($this->textContent);

            $trainingData = $this->getTrainingData();
            if (!in_array($this->textContent, $trainingData['samples'])) {
                $this->addTrainingSample($this->textContent, true);
            }

            // Add new spam words from this message
            $this->addNewSpamWordsFromMessage($this->textContent);
        }
    }
    
    /**
     * Perform ML-based spam prediction
     */
    private function performMLPrediction()
    {
        try {
            $prediction = $this->pipeline->predict([$this->textContent]);
            $this->isSpam = ($prediction[0] === 'spam');
            
            // Try to get probability if supported
            if (method_exists($this->pipeline, 'predictProbability')) {
                $probabilities = $this->pipeline->predictProbability([$this->textContent]);
                $this->spamProbability = $probabilities[0]['spam'] ?? 0.0;
            } else {
                $this->spamProbability = $this->isSpam ? 0.8 : 0.2;
            }
            
        } catch (Exception $e) {
            // Fallback to traditional detection if ML fails
            $this->spamProbability = 0.0;
            $this->isSpam = false;
        }
    }
    
    /**
     * Perform traditional dictionary-based detection
     */
    private function performTraditionalDetection()
    {
        if (!is_file($this->spamDictionary)) {
            $this->_createDictionary();
        }
        
        $this->_prepareSpamDictionaryItems();
        
        foreach ($this->spamDictionaryItems as $d_item) {
            $lastPos = 0;
            
            while (($lastPos = stripos($this->textContent, $d_item, $lastPos)) !== false) {
                $this->spamFoundPositions[] = $lastPos;
                
                $sub_str_item = substr($this->textContent, $lastPos, strlen($d_item));
                if (!in_array($sub_str_item, $this->spamFoundContentItems)) {
                    $this->spamFoundContentItems[] = $sub_str_item;
                }
                
                $index = array_search($d_item, array_column($this->spamFoundItems, 'item'));
                if ($index !== false) {
                    $this->spamFoundItems[$index]['count'] += 1;
                } else {
                    $this->spamFoundItems[] = ['item' => $d_item, 'count' => 1];
                }
                
                // Highlight spam content
                $this->spamContentHighlightedText = preg_replace(
                    '/\p{L}*?' . preg_quote($d_item) . '\p{L}*/ui',
                    '<span style="color:red;">$0</span>',
                    $this->spamContentHighlightedText
                );
                
                $this->spamContentHighlightedHtml = preg_replace(
                    '/\p{L}*?' . preg_quote($d_item) . '\p{L}*/ui',
                    '<span style="color:red;">$0</span>',
                    $this->spamContentHighlightedHtml
                );
                
                $lastPos = $lastPos + strlen($d_item);
            }
            
            $this->_teachDoctor($d_item);
        }
        
        sort($this->spamFoundPositions);
    }
    
    /**
     * Get spam probability from ML model
     */
    public function getSpamProbability()
    {
        return $this->spamProbability;
    }
    
    /**
     * Check if content is spam based on ML prediction
     */
    public function isSpam()
    {
        return $this->isSpam;
    }
    
    /**
     * Get combined spam score (ML + traditional)
     */
    public function getSpamScore()
    {
        $mlScore = $this->spamProbability;
        $traditionalScore = min(count($this->spamFoundItems) * 0.1, 1.0);
        
        // Weighted combination
        return ($mlScore * 0.7) + ($traditionalScore * 0.3);
    }
    
    /**
     * Retrain model with new data
     */
    public function retrainModel()
    {
        $this->createModel();
        $this->trainModel();
    }
    
    /**
     * Set confidence threshold for spam detection
     */
    public function setConfidenceThreshold($threshold)
    {
        $this->confidenceThreshold = max(0.0, min(1.0, $threshold));
    }
    
    /**
     * Get model statistics
     */
    public function getModelStats()
    {
        $trainingData = $this->getTrainingData();
        $spamCount = count(array_filter($trainingData['labels'], function($label) {
            return $label === 'spam';
        }));
        $hamCount = count($trainingData['labels']) - $spamCount;
        
        return [
            'total_samples' => count($trainingData['samples']),
            'spam_samples' => $spamCount,
            'ham_samples' => $hamCount,
            'model_type' => $this->modelType,
            'confidence_threshold' => $this->confidenceThreshold
        ];
    }
    
    // Legacy methods for backward compatibility
    public function getSpamPositions()
    {
        return $this->spamFoundPositions;
    }
    
    public function getSpamItems()
    {
        return $this->spamFoundItems;
    }
    
    public function getHighlighted($html = false)
    {
        if ($html) {
            return $this->spamContentHighlightedHtml;
        }
        return $this->spamContentHighlightedText;
    }
    
    public function setFilterItems($items = [], $append = false)
    {
        if (empty($items) || !is_array($items)) {
            throw new Exception('Items must be a non-empty array');
        }
        
        if ($append) {
            $this->userFilterItems = array_merge($this->userFilterItems, $items);
        } else {
            $this->userFilterItems = $items;
        }
    }
    
    public function setReplaceRule($json_rule)
    {
        if (empty($json_rule)) {
            throw new Exception('Json rule empty.');
        }
        $this->replaceRule = $json_rule;
    }
    
    public function teachDoctor($json_data)
    {
        if (!$json_data || empty($json_data)) {
            throw new Exception('Please provide data in json string format');
        }
        
        $data = json_decode($json_data, true);
        if ($data === null) {
            throw new Exception('Invalid JSON data');
        }
        
        $flattened = $this->_arrayFlatten($data);
        $total_taught = 0;
        
        foreach ($flattened as $item) {
            if ($this->_teachDoctor($item)) {
                $total_taught++;
            }
        }
        
        return $total_taught;
    }
    
    // Private helper methods (keeping original functionality)
    private function _processHtml()
    {
        $this->textContent = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $this->textContent);
        $this->textContent = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', '', $this->textContent);
        $this->textContent = trim(strip_tags($this->textContent));
        $this->textContent = preg_replace("/[[:blank:]]+/", " ", $this->textContent);
        $this->textContent = preg_replace('/\s{2,}/', "\n", $this->textContent);
    }
    
    private function _createDictionary()
    {
        $path_explode = explode('/', $this->spamDictionary);
        $path_dir = str_replace(end($path_explode), '', $this->spamDictionary);
        
        if (!is_dir($path_dir)) {
            mkdir($path_dir, 0777, true);
        }
        
        file_put_contents($this->spamDictionary, '');
    }
    
    private function _prepareSpamDictionaryItems()
    {
        $this->spamDictionaryItems = $this->_getDictionaryItems();
        
        if ($this->userFilterItems) {
            $this->spamDictionaryItems = array_merge($this->spamDictionaryItems, $this->userFilterItems);
        }
        
        $items = [];
        foreach ($this->spamDictionaryItems as $item) {
            $item = trim($item);
            if (!in_array($item, $items) && !empty($item)) {
                $items[] = $item;
            }
        }
        
        $this->spamDictionaryItems = $items;
    }
    
    private function _teachDoctor($d_item)
    {
        $dictionary_content = file_get_contents($this->spamDictionary);
        
        if (empty($dictionary_content)) {
            file_put_contents($this->spamDictionary, $d_item);
            return true;
        }
        
        if (strpos($dictionary_content, $d_item) === false) {
            $dictionary_content = trim($dictionary_content, ',');
            $dictionary_content = $dictionary_content . ',' . $d_item;
            file_put_contents($this->spamDictionary, $dictionary_content);
            return true;
        }
        
        return false;
    }
    
    private function _getDictionaryItems()
    {
        if (!file_exists($this->spamDictionary)) {
            return [];
        }
        
        $dictionary_content = file_get_contents($this->spamDictionary);
        if (empty($dictionary_content)) {
            return [];
        }
        
        return array_filter(explode(',', $dictionary_content), function($item) {
            return !empty(trim($item));
        });
    }
    
    private function _arrayFlatten($data)
    {
        if (!is_array($data)) {
            return false;
        }
        
        $result = [];
        foreach ($data as $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->_arrayFlatten($value));
            } else {
                $result[] = $value;
            }
        }
        
        return $result;
    }
    
    private function addNewSpamWordsFromMessage($message)
    {
        $dictionaryItems = $this->_getDictionaryItems();
        $words = preg_split('/\W+/', strtolower($message));
        $newWords = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 4 && !in_array($word, $dictionaryItems) && !empty($word)) {
                $newWords[] = $word;
            }
        }

        // Add new words to dictionary
        foreach ($newWords as $word) {
            $this->_teachDoctor($word);
        }
    }
}