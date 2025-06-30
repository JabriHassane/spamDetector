<?php
/**
 * Enhanced Spam Doctor Test Interface
 * 
 * This script provides a web interface to test the Enhanced Spam Doctor
 * with various messages and see real-time spam detection results.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/spamAIdetector/spamDetector.php';
// Auto-load dependencies
require_once 'vendor/autoload.php';

// Import the Enhanced Spam Doctor class


use JBTools\SpamDetector\EnhancedSpamDoctor;

// Initialize the spam detector
$spamDoctor = new EnhancedSpamDoctor('naive_bayes');

// Test messages array
$testMessages = [
    // Spam messages
    "URGENT! You have won $1000000! Click here now to claim your prize!",
    "FREE MONEY! Work from home and earn $5000 per week guaranteed!",
    "Congratulations! You've been selected for exclusive cryptocurrency investment!",
    "Your account will be suspended unless you verify immediately!",
    "Amazing weight loss pill - lose 30 pounds in 30 days or money back!",
    "Hot singles in your area want to meet you tonight!",
    "Last chance! Limited time offer expires in 24 hours!",
    "Click here for instant Bitcoin profits - automated system!",
    "Special discount only for you - save 90% today only!",
    "WINNER! Claim your lottery jackpot of $50000 now!",
    
    // Ham (legitimate) messages
    "Meeting scheduled for tomorrow at 2 PM in conference room A",
    "Thanks for your email, I'll review the document and get back to you",
    "The project deadline has been moved to next Friday",
    "Please find the quarterly report attached to this email",
    "Happy birthday! Hope you have a wonderful celebration",
    "Reminder: Team lunch is at noon today in the cafeteria",
    "The weather forecast shows rain tomorrow, bring an umbrella",
    "Your Amazon order has been shipped and will arrive Monday",
    "Conference call has been rescheduled to 3 PM today",
    "Thank you for the excellent presentation yesterday"
];

// Handle form submissions
$results = [];
$message = '';
$isManualTest = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_message']) && !empty($_POST['test_message'])) {
        $message = trim($_POST['test_message']);
        $isManualTest = true;
        
        try {
            // Check the message
            $spamDoctor->check($message, isset($_POST['is_html']));
            
            // Store results
            $results[] = [
                'message' => $message,
                'is_spam' => $spamDoctor->isSpam(),
                'ml_probability' => $spamDoctor->getSpamProbability(),
                'combined_score' => $spamDoctor->getSpamScore(),
                'spam_items' => $spamDoctor->getSpamItems(),
                'highlighted' => $spamDoctor->getHighlighted(),
                'is_manual' => true
            ];
            
            // If it's spam and user confirmed, add to training data
            if (isset($_POST['confirm_spam']) && $_POST['confirm_spam'] === 'yes') {
                $spamDoctor->addTrainingSample($message, true);
            } elseif (isset($_POST['confirm_spam']) && $_POST['confirm_spam'] === 'no') {
                $spamDoctor->addTrainingSample($message, false);
            }
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
    
    // Handle bulk testing
    if (isset($_POST['bulk_test'])) {
        foreach ($testMessages as $testMsg) {
            try {
                $spamDoctor->check($testMsg);
                $results[] = [
                    'message' => $testMsg,
                    'is_spam' => $spamDoctor->isSpam(),
                    'ml_probability' => $spamDoctor->getSpamProbability(),
                    'combined_score' => $spamDoctor->getSpamScore(),
                    'spam_items' => $spamDoctor->getSpamItems(),
                    'highlighted' => $spamDoctor->getHighlighted(),
                    'is_manual' => false
                ];
            } catch (Exception $e) {
                // Skip problematic messages
                continue;
            }
        }
    }
    
    // Handle model retraining
    if (isset($_POST['retrain_model'])) {
        try {
            $spamDoctor->retrainModel();
            $retrain_success = "Model retrained successfully!";
        } catch (Exception $e) {
            $retrain_error = "Error retraining model: " . $e->getMessage();
        }
    }
}

// Get model statistics
$modelStats = $spamDoctor->getModelStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Spam Doctor - Test Interface</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        textarea, input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        textarea {
            height: 120px;
            resize: vertical;
        }
        
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn:hover {
            background-color: #0056b3;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ccc;
        }
        
        .result.spam {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }
        
        .result.ham {
            background-color: #d4edda;
            border-left-color: #28a745;
        }
        
        .stats {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .stats h3 {
            margin-top: 0;
            color: #495057;
        }
        
        .highlighted-spam {
            color: red;
            font-weight: bold;
        }
        
        .message-text {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
            font-style: italic;
        }
        
        .score-bar {
            width: 100%;
            height: 20px;
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 5px 0;
        }
        
        .score-fill {
            height: 100%;
            background-color: #28a745;
            transition: width 0.3s ease;
        }
        
        .score-fill.spam {
            background-color: #dc3545;
        }
        
        .checkbox-group {
            margin: 10px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 5px;
        }
        
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ Enhanced Spam Doctor Test Interface</h1>
        
        <!-- Model Statistics -->
        <div class="stats">
            <h3>📊 Model Statistics</h3>
            <div class="two-column">
                <div>
                    <strong>Total Training Samples:</strong> <?php echo $modelStats['total_samples']; ?><br>
                    <strong>Spam Samples:</strong> <?php echo $modelStats['spam_samples']; ?><br>
                    <strong>Ham Samples:</strong> <?php echo $modelStats['ham_samples']; ?>
                </div>
                <div>
                    <strong>Model Type:</strong> <?php echo strtoupper($modelStats['model_type']); ?><br>
                    <strong>Confidence Threshold:</strong> <?php echo $modelStats['confidence_threshold']; ?><br>
                    <strong>Success Rate:</strong> ~<?php echo round(($modelStats['total_samples'] > 0 ? ($modelStats['spam_samples'] + $modelStats['ham_samples']) / $modelStats['total_samples'] * 100 : 0), 1); ?>%
                </div>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($retrain_success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($retrain_success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($retrain_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($retrain_error); ?></div>
        <?php endif; ?>
        
        <!-- Test Form -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="test_message">Enter Message to Test:</label>
                <textarea name="test_message" id="test_message" placeholder="Enter your message here to test for spam detection..."><?php echo htmlspecialchars($message); ?></textarea>
            </div>
            
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="is_html" value="1"> Message contains HTML
                </label>
            </div>
            
            <?php if ($isManualTest && !empty($results)): ?>
                <div class="form-group">
                    <label>Confirm Classification (helps improve the model):</label>
                    <label><input type="radio" name="confirm_spam" value="yes"> Yes, this is spam</label><br>
                    <label><input type="radio" name="confirm_spam" value="no"> No, this is not spam</label><br>
                    <label><input type="radio" name="confirm_spam" value="skip" checked> Skip (don't add to training)</label>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn">🔍 Test Message</button>
            <button type="submit" name="bulk_test" class="btn btn-success">🚀 Run Bulk Test</button>
            <button type="submit" name="retrain_model" class="btn btn-danger" onclick="return confirm('Are you sure you want to retrain the model? This may take some time.')">🔄 Retrain Model</button>
        </form>
    </div>
    
    <!-- Results -->
    <?php if (!empty($results)): ?>
        <div class="container">
            <h2>🎯 Detection Results</h2>
            
            <?php foreach ($results as $result): ?>
                <div class="result <?php echo $result['is_spam'] ? 'spam' : 'ham'; ?>">
                    <div class="message-text">
                        <strong>Message:</strong> <?php echo htmlspecialchars($result['message']); ?>
                    </div>
                    
                    <div class="two-column">
                        <div>
                            <strong>Classification:</strong> 
                            <span style="color: <?php echo $result['is_spam'] ? '#dc3545' : '#28a745'; ?>; font-weight: bold;">
                                <?php echo $result['is_spam'] ? '🚨 SPAM' : '✅ HAM (Not Spam)'; ?>
                            </span><br>
                            
                            <strong>ML Probability:</strong> <?php echo round($result['ml_probability'] * 100, 1); ?>%<br>
                            <strong>Combined Score:</strong> <?php echo round($result['combined_score'] * 100, 1); ?>%
                        </div>
                        
                        <div>
                            <?php if (!empty($result['spam_items'])): ?>
                                <strong>Detected Keywords:</strong><br>
                                <?php foreach ($result['spam_items'] as $item): ?>
                                    <span class="highlighted-spam"><?php echo htmlspecialchars($item['item']); ?></span> (<?php echo $item['count']; ?>x)<br>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <strong>No spam keywords detected</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Score Bar -->
                    <div style="margin-top: 10px;">
                        <strong>Spam Confidence:</strong>
                        <div class="score-bar">
                            <div class="score-fill <?php echo $result['is_spam'] ? 'spam' : ''; ?>" 
                                 style="width: <?php echo round($result['combined_score'] * 100, 1); ?>%"></div>
                        </div>
                        <?php echo round($result['combined_score'] * 100, 1); ?>%
                    </div>
                    
                    <?php if ($result['highlighted'] !== $result['message']): ?>
                        <div style="margin-top: 10px;">
                            <strong>Highlighted Text:</strong>
                            <div class="message-text">
                                <?php echo $result['highlighted']; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Summary Statistics -->
            <?php if (count($results) > 1): ?>
                <?php
                $spamCount = count(array_filter($results, function($r) { return $r['is_spam']; }));
                $hamCount = count($results) - $spamCount;
                $avgSpamScore = array_sum(array_map(function($r) { return $r['combined_score']; }, 
                                array_filter($results, function($r) { return $r['is_spam']; }))) / max($spamCount, 1);
                $avgHamScore = array_sum(array_map(function($r) { return $r['combined_score']; }, 
                               array_filter($results, function($r) { return !$r['is_spam']; }))) / max($hamCount, 1);
                ?>
                
                <div class="stats">
                    <h3>📈 Test Summary</h3>
                    <div class="two-column">
                        <div>
                            <strong>Total Messages:</strong> <?php echo count($results); ?><br>
                            <strong>Detected as Spam:</strong> <?php echo $spamCount; ?><br>
                            <strong>Detected as Ham:</strong> <?php echo $hamCount; ?>
                        </div>
                        <div>
                            <strong>Avg Spam Score:</strong> <?php echo round($avgSpamScore * 100, 1); ?>%<br>
                            <strong>Avg Ham Score:</strong> <?php echo round($avgHamScore * 100, 1); ?>%<br>
                            <strong>Detection Rate:</strong> <?php echo round(($spamCount / count($results)) * 100, 1); ?>%
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Instructions -->
    <div class="container">
        <h2>📋 Instructions</h2>
        <ol>
            <li><strong>Single Message Test:</strong> Enter a message in the text area and click "Test Message"</li>
            <li><strong>Bulk Testing:</strong> Click "Run Bulk Test" to test with predefined spam and ham messages</li>
            <li><strong>Training:</strong> When testing single messages, you can confirm if the classification is correct to improve the model</li>
            <li><strong>Retraining:</strong> Click "Retrain Model" to rebuild the ML model with all collected training data</li>
            <li><strong>HTML Support:</strong> Check the HTML box if your message contains HTML tags</li>
        </ol>
        
        <h3>🔍 Understanding the Results</h3>
        <ul>
            <li><strong>ML Probability:</strong> The machine learning model's confidence that the message is spam</li>
            <li><strong>Combined Score:</strong> Weighted combination of ML prediction and traditional keyword matching</li>
            <li><strong>Detected Keywords:</strong> Spam keywords found in the message using traditional dictionary matching</li>
            <li><strong>Highlighted Text:</strong> Message with detected spam keywords highlighted in red</li>
        </ul>
        
        <h3>📁 Data Files</h3>
        <ul>
            <li><strong>data/spam_data.txt:</strong> Dictionary of spam keywords</li>
            <li><strong>data/training_data.json:</strong> Machine learning training dataset</li>
            <li><strong>data/collected_spam.txt:</strong> Log of all detected spam messages</li>
            <li><strong>data/spam_model.dat:</strong> Trained machine learning model</li>
        </ul>
        
        <p><strong>Note:</strong> The system automatically learns from spam messages and improves over time. 
        Confirmed spam messages are added to the training data and spam dictionary for better future detection.</p>
    </div>
</body>
</html>