<?php
/**
 * Usage Example for Enhanced Spam Detector with Machine Learning
 */

require_once __DIR__ . '/spamAIdetector/spamDetector.php';

require_once 'vendor/autoload.php';
use JBTools\SpamDetector\EnhancedSpamDetector;


try {
    // Initialize the enhanced spam detector with ML capabilities
    $spamDetector = new EnhancedSpamDetector('naive_bayes'); // or 'svm'
    
    // Example 1: Basic spam detection
    $testText = "URGENT! You have won $1000000! Click here now to claim your prize!";
    $spamDetector->check($testText);
    
    echo "=== Basic Spam Detection ===\n";
    echo "Text: $testText\n";
    echo "Is Spam (ML): " . ($spamDetector->isSpam() ? 'YES' : 'NO') . "\n";
    echo "Spam Probability: " . round($spamDetector->getSpamProbability() * 100, 2) . "%\n";
    echo "Combined Score: " . round($spamDetector->getSpamScore() * 100, 2) . "%\n";
    echo "Traditional Spam Items Found: " . count($spamDetector->getSpamItems()) . "\n";
    echo "Highlighted Text: " . $spamDetector->getHighlighted() . "\n\n";
    
    // Example 2: HTML content detection
    $htmlContent = '<p>Congratulations! <strong>You are our lucky winner!</strong> 
                    <a href="#">Click here for FREE money!</a></p>';
    $spamDetector->check($htmlContent, true);
    
    echo "=== HTML Content Detection ===\n";
    echo "HTML: $htmlContent\n";
    echo "Is Spam: " . ($spamDetector->isSpam() ? 'YES' : 'NO') . "\n";
    echo "Spam Probability: " . round($spamDetector->getSpamProbability() * 100, 2) . "%\n";
    echo "Highlighted HTML: " . $spamDetector->getHighlighted(true) . "\n\n";
    
    // Example 3: Training the model with new data
    echo "=== Training Examples ===\n";
    
    // Add spam samples
    $spamSamples = [
        "Act now! Limited time offer expires soon!",
        "You've been selected for a special discount!",
        "Claim your reward immediately!"
    ];
    
    foreach ($spamSamples as $spam) {
        $spamDetector->addTrainingSample($spam, true);
        echo "Added spam sample: $spam\n";
    }
    
    // Add legitimate samples
    $hamSamples = [
        "Meeting rescheduled to 3 PM tomorrow",
        "Please find the attached report",
        "Thank you for your email"
    ];
    
    foreach ($hamSamples as $ham) {
        $spamDetector->addTrainingSample($ham, false);
        echo "Added legitimate sample: $ham\n";
    }
    
    // Retrain the model
    $spamDetector->retrainModel();
    echo "Model retrained with new samples\n\n";
    
    // Example 4: Model statistics
    echo "=== Model Statistics ===\n";
    $stats = $spamDetector->getModelStats();
    foreach ($stats as $key => $value) {
        echo ucfirst(str_replace('_', ' ', $key)) . ": $value\n";
    }
    echo "\n";
    
    // Example 5: Custom filter items
    echo "=== Custom Filter Items ===\n";
    $customSpamWords = ['cryptocurrency', 'bitcoin', 'investment opportunity'];
    $spamDetector->setFilterItems($customSpamWords);
    
    $testCrypto = "Amazing cryptocurrency investment opportunity! Buy bitcoin now!";
    $spamDetector->check($testCrypto);
    
    echo "Text: $testCrypto\n";
    echo "Is Spam: " . ($spamDetector->isSpam() ? 'YES' : 'NO') . "\n";
    echo "Traditional Items Found: " . count($spamDetector->getSpamItems()) . "\n";
    print_r($spamDetector->getSpamItems());
    echo "\n";
    
    // Example 6: Teaching the doctor with JSON data
    echo "=== Teaching with JSON Data ===\n";
    $jsonTeachingData = json_encode([
        'urgent', 'act now', 'limited time', 'exclusive offer',
        ['winner', 'congratulations', 'selected'],
        'free money'
    ]);
    
    $learnedCount = $spamDetector->teachDoctor($jsonTeachingData);
    echo "Doctor learned $learnedCount new spam terms\n";
    
    // Example 7: Confidence threshold adjustment
    echo "=== Confidence Threshold Testing ===\n";
    $borderlineSpam = "Special offer for you! Save 50% today only.";
    
    // Test with different thresholds
    $thresholds = [0.3, 0.5, 0.7, 0.9];
    foreach ($thresholds as $threshold) {
        $spamDetector->setConfidenceThreshold($threshold);
        $spamDetector->check($borderlineSpam);
        
        echo "Threshold: $threshold - ";
        echo "Probability: " . round($spamDetector->getSpamProbability() * 100, 2) . "% - ";
        echo "Classification: " . ($spamDetector->isSpam() ? 'SPAM' : 'HAM') . "\n";
    }
    
    // === Custom Test: Store New Spam and Ham ===
    echo "=== Custom Storage Test ===\n";
    $uniqueSpam = "This is a brand new spam offer just for you! Claim your secret prize now!";
    $uniqueHam = "Let's meet for coffee at 10am tomorrow.";

    // Check and store spam
    $spamDetector->check($uniqueSpam);
    echo "Checked unique spam: \"$uniqueSpam\"\n";
    echo "Is Spam: " . ($spamDetector->isSpam() ? 'YES' : 'NO') . "\n";

    // Check and store ham
    $spamDetector->check($uniqueHam);
    echo "Checked unique ham: \"$uniqueHam\"\n";
    echo "Is Spam: " . ($spamDetector->isSpam() ? 'YES' : 'NO') . "\n";

    // Now verify if they were stored
    $collectedSpam = file_get_contents(__DIR__ . '/data/collected_spam.txt');
    $trainingData = json_decode(file_get_contents(__DIR__ . '/data/training_data.json'), true);

    echo "Spam message stored in collected_spam.txt? ";
    echo (strpos($collectedSpam, $uniqueSpam) !== false) ? "YES\n" : "NO\n";

    echo "Spam message stored in training_data.json? ";
    echo (in_array($uniqueSpam, $trainingData['samples'])) ? "YES\n" : "NO\n";

    echo "Ham message stored in training_data.json? ";
    echo (in_array($uniqueHam, $trainingData['samples'])) ? "YES\n" : "NO\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

/*
=== UPDATED README ===

# Enhanced PHP Spam Doctor with Machine Learning

Advanced spam detection library combining traditional dictionary-based filtering with machine learning models for superior accuracy.

## Features

### 🧠 Machine Learning Powered
- **Naive Bayes** and **Support Vector Machine (SVM)** classifiers
- **TF-IDF vectorization** for text feature extraction
- **Self-learning capabilities** with automatic model improvement
- **Probability scoring** for spam likelihood assessment

### 🎯 Enhanced Detection
- Combines ML predictions with traditional keyword filtering
- HTML content processing and spam highlighting
- Custom filter items and replace rules
- Spam message collection for continuous learning

### 📊 Advanced Analytics
- Spam probability scores
- Combined confidence ratings
- Model performance statistics
- Training data management

## Installation

```bash
composer require php-ai/php-ml
composer require tbetool/spam-doctor
```

## Quick Start

```php
use TBETool\EnhancedSpamDoctor;

$spamDoctor = new EnhancedSpamDoctor('naive_bayes');
$spamDoctor->check("URGENT! You won $1000000!");

echo "Is Spam: " . ($spamDoctor->isSpam() ? 'YES' : 'NO');
echo "Probability: " . round($spamDoctor->getSpamProbability() * 100) . "%";
```

## Advanced Usage

### Training the Model
```php
// Add training samples
$spamDoctor->addTrainingSample("Buy now or miss out!", true);  // spam
$spamDoctor->addTrainingSample("Meeting at 2 PM", false);      // not spam

// Retrain model
$spamDoctor->retrainModel();
```

### Model Configuration
```php
// Use SVM instead of Naive Bayes
$spamDoctor = new EnhancedSpamDoctor('svm');

// Set confidence threshold
$spamDoctor->setConfidenceThreshold(0.8);
```

### Get Detailed Results
```php
$spamDoctor->check($text);

// ML-based results
$isSpam = $spamDoctor->isSpam();
$probability = $spamDoctor->getSpamProbability();
$combinedScore = $spamDoctor->getSpamScore();

// Traditional results
$spamItems = $spamDoctor->getSpamItems();
$positions = $spamDoctor->getSpamPositions();
$highlighted = $spamDoctor->getHighlighted();
```

## API Reference

### Core Methods
- `check($text, $isHtml = false)` - Analyze text for spam
- `isSpam()` - Get ML prediction result
- `getSpamProbability()` - Get spam probability (0-1)
- `getSpamScore()` - Get combined ML + traditional score

### Training Methods
- `addTrainingSample($text, $isSpam)` - Add training data
- `retrainModel()` - Retrain with new data
- `teachDoctor($jsonData)` - Bulk teach with JSON data

### Configuration
- `setConfidenceThreshold($threshold)` - Set spam threshold
- `setFilterItems($items, $append)` - Custom spam keywords
- `setReplaceRule($jsonRule)` - Text replacement rules

### Analytics
- `getModelStats()` - Model training statistics
- `getSpamItems()` - Traditional spam matches
- `getSpamPositions()` - Spam keyword positions
- `getHighlighted($html)` - Highlighted spam content

## Data Files

The library creates several data files:
- `data/spam_model.dat` - Trained ML model
- `data/training_data.json` - Training samples and labels
- `data/collected_spam.txt` - Detected spam messages
- `data/spam_data.txt` - Traditional spam dictionary

## Model Types

### Naive Bayes
- Fast training and prediction
- Good for text classification
- Handles small datasets well
- Default choice for most use cases

### Support Vector Machine (SVM)
- Higher accuracy on complex datasets
- Better generalization
- Slower training but fast prediction
- Recommended for production systems

## Best Practices

1. **Continuous Learning**: Regularly add new samples with `addTrainingSample()`
2. **Balanced Training**: Maintain roughly equal spam/ham samples
3. **Threshold Tuning**: Adjust confidence threshold based on your needs
4. **Regular Retraining**: Retrain model weekly/monthly for best results
5. **Monitoring**: Check model statistics to ensure good performance

## License

MIT License - feel free to use in commercial projects.

## Contributing

Issues and pull requests welcome on GitHub.
*/