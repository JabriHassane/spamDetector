# Enhanced Spam Doctor with Machine Learning

A powerful PHP-based spam detection system that combines traditional dictionary-based filtering with machine learning algorithms for superior spam detection accuracy.

## Features

- **Machine Learning Integration**: Uses PHP-ML library with Naive Bayes and SVM classifiers
- **Dual Detection Method**: Combines ML prediction with traditional dictionary-based detection
- **Self-Learning**: Automatically improves by learning from detected spam
- **HTML Support**: Can process both plain text and HTML content
- **Training Data Management**: Automatically collects and manages training data
- **Flexible Configuration**: Customizable confidence thresholds and model types
- **Backward Compatible**: Maintains compatibility with legacy methods

## Requirements

- PHP 7.4 or higher
- Composer
- PHP-ML library (`composer require php-ai/php-ml`)

## Installation

1. Install via Composer:
```bash
composer require php-ai/php-ml
```

2. Include the class in your project:
```php
require_once 'vendor/autoload.php';
use JBTools\SpamDetector\EnhancedSpamDoctor;
```

## Directory Structure

```
project/
├── src/
│   └── JBTools/
│       └── SpamDetector/
│           └── EnhancedSpamDoctor.php
├── data/
│   ├── spam_data.txt          # Dictionary of spam keywords
│   ├── training_data.json     # ML training dataset
│   ├── spam_model.dat         # Trained ML model
│   └── collected_spam.txt     # Log of detected spam messages
├── composer.json
└── index.php                  # Test interface
```

## Quick Start

### Basic Usage

```php
<?php
require_once 'vendor/autoload.php';
use JBTools\SpamDetector\EnhancedSpamDoctor;

// Initialize with Naive Bayes (default) or SVM
$spamDoctor = new EnhancedSpamDoctor('naive_bayes');

// Check a message
$message = "URGENT! You have won $1000000! Click here now!";
$spamDoctor->check($message);

// Get results
if ($spamDoctor->isSpam()) {
    echo "Message is SPAM!\n";
    echo "Spam Probability: " . $spamDoctor->getSpamProbability() . "\n";
    echo "Combined Score: " . $spamDoctor->getSpamScore() . "\n";
} else {
    echo "Message is HAM (not spam)\n";
}
```

### Advanced Usage

```php
// Set custom confidence threshold
$spamDoctor->setConfidenceThreshold(0.8);

// Add custom filter items
$spamDoctor->setFilterItems(['custom', 'spam', 'words'], true);

// Add training samples manually
$spamDoctor->addTrainingSample("This is definitely spam!", true);
$spamDoctor->addTrainingSample("This is a normal message", false);

// Retrain the model with new data
$spamDoctor->retrainModel();

// Get model statistics
$stats = $spamDoctor->getModelStats();
print_r($stats);
```

## Methods Reference

### Core Methods

- `check($text, $is_html = false)` - Main spam detection method
- `isSpam()` - Returns boolean if content is spam
- `getSpamProbability()` - Returns ML prediction probability (0-1)
- `getSpamScore()` - Returns combined ML + traditional score (0-1)

### Training Methods

- `addTrainingSample($text, $isSpam)` - Add new training data
- `trainModel()` - Train the ML model with current data
- `retrainModel()` - Recreate and retrain the model
- `teachDoctor($json_data)` - Bulk add spam keywords from JSON

### Configuration Methods

- `setConfidenceThreshold($threshold)` - Set spam detection threshold
- `setFilterItems($items, $append = false)` - Add custom spam keywords
- `getModelStats()` - Get training data statistics

### Legacy Methods (Backward Compatibility)

- `getSpamPositions()` - Get positions of found spam words
- `getSpamItems()` - Get detected spam items with counts
- `getHighlighted($html = false)` - Get content with spam words highlighted

## Machine Learning Models

### Naive Bayes (Default)
- Fast training and prediction
- Works well with small datasets
- Good for text classification
- Probabilistic output

### Support Vector Machine (SVM)
- Better accuracy with larger datasets
- More computationally intensive
- Good for complex patterns
- Binary classification

```php
// Use SVM instead of Naive Bayes
$spamDoctor = new EnhancedSpamDoctor('svm');
```

## Data Files

### spam_data.txt
Dictionary of spam keywords (comma-separated):
```
urgent,free money,act now,limited time,winner,congratulations...
```

### training_data.json
ML training dataset:
```json
{
    "samples": ["message 1", "message 2", ...],
    "labels": ["spam", "ham", ...]
}
```

### collected_spam.txt
Log of detected spam messages:
```
[2025-06-29 12:24:04] URGENT! You have won $1000000!
[2025-06-29 12:25:15] Free money! Click here now!
```

## Configuration Options

### Model Types
- `naive_bayes` - Fast, probabilistic classifier (default)
- `svm` - Support Vector Machine with linear kernel

### Confidence Threshold
Default: 0.7 (70% confidence required for spam classification)
```php
$spamDoctor->setConfidenceThreshold(0.85); // 85% confidence required
```

## Error Handling

The class throws exceptions for:
- Empty text content
- Invalid JSON data
- Missing required files
- Model loading failures

```php
try {
    $spamDoctor->check($message);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Performance Considerations

- **Initial Training**: First run may be slower due to model creation
- **Model Persistence**: Trained models are saved and reloaded automatically
- **Memory Usage**: Large training datasets may require more memory
- **Prediction Speed**: Naive Bayes is faster than SVM for predictions

## Best Practices

1. **Regular Retraining**: Retrain models periodically with new data
2. **Balanced Dataset**: Maintain roughly equal spam/ham samples
3. **Quality Control**: Review auto-collected spam before using for training
4. **Threshold Tuning**: Adjust confidence threshold based on false positive/negative rates
5. **Custom Keywords**: Add domain-specific spam keywords

## Example Output

```
Checking message: "URGENT! You have won $1000000! Click here now!"

Result: SPAM DETECTED
ML Probability: 0.92
Traditional Matches: 4 keywords found
Combined Score: 0.89
Detected Keywords: urgent, winner, click here, money

Model Statistics:
- Total Training Samples: 48
- Spam Samples: 25
- Ham Samples: 23
- Model Type: naive_bayes
- Confidence Threshold: 0.7
```

## Troubleshooting

### Common Issues

1. **Model Not Loading**: Check file permissions for data directory
2. **Low Accuracy**: Need more training data or balanced dataset
3. **Memory Errors**: Reduce training dataset size or increase PHP memory limit
4. **False Positives**: Lower confidence threshold or add legitimate terms to ham samples

### Debug Mode

Enable error reporting for debugging:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Contributing

1. Fork the repository
2. Create feature branch
3. Add tests for new functionality
4. Submit pull request

## License

This project is open source. Please check the license file for details.

## Support

For issues and questions:
- Check the troubleshooting section
- Review the method documentation
- Test with the provided index.php example