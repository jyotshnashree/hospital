<?php
/**
 * AI Symptom Checker Module
 * Hospital Management System - Simple symptom analysis
 */

include '../db.php';
checkAuth();

// Simple symptom database
$symptoms_db = [
    'fever' => ['Flu', 'COVID-19', 'Malaria', 'Typhoid'],
    'cough' => ['Cold', 'Flu', 'COVID-19', 'Asthma', 'Bronchitis'],
    'headache' => ['Migraine', 'Tension Headache', 'Sinus Infection', 'Flu'],
    'sore_throat' => ['Strep Throat', 'Pharyngitis', 'Cold', 'Tonsillitis'],
    'shortness_of_breath' => ['Asthma', 'COVID-19', 'Pneumonia', 'Heart Disease'],
    'chest_pain' => ['Heart Attack', 'Angina', 'Acid Reflux', 'Pneumonia'],
    'nausea' => ['Food Poisoning', 'Gastroenteritis', 'Pregnancy', 'Migraines'],
    'body_ache' => ['Flu', 'COVID-19', 'Fibromyalgia', 'Arthritis'],
    'fatigue' => ['Anemia', 'Thyroid Issues', 'Depression', 'Sleep Apnea'],
    'dizziness' => ['Inner Ear Problem', 'Vertigo', 'Low Blood Pressure', 'Anemia'],
];

$recommendations = null;
$severity = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_symptoms = $_POST['symptoms'] ?? [];
    
    if (!empty($selected_symptoms)) {
        $possible_conditions = [];
        $symptom_count = count($selected_symptoms);
        
        foreach ($selected_symptoms as $symptom) {
            if (isset($symptoms_db[$symptom])) {
                foreach ($symptoms_db[$symptom] as $condition) {
                    $possible_conditions[$condition] = ($possible_conditions[$condition] ?? 0) + 1;
                }
            }
        }
        
        // Sort by frequency
        arsort($possible_conditions);
        
        // Determine severity
        $high_risk = ['chest_pain', 'shortness_of_breath'];
        $has_high_risk = array_intersect($selected_symptoms, $high_risk);
        
        if (!empty($has_high_risk)) {
            $severity = 'high';
        } elseif ($symptom_count >= 3) {
            $severity = 'medium';
        } else {
            $severity = 'low';
        }
        
        $recommendations = array_slice($possible_conditions, 0, 5, true);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['book_appointment']) && isset($_POST['condition'])) {
    $_SESSION['symptom_checker_result'] = [
        'condition' => $_POST['condition'],
        'symptoms' => $_POST['selected_symptoms'] ?? []
    ];
    header('Location: ../patient/book_appointment.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Symptom Checker - Hospital Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
<?php include '../inc/header.php'; ?>

<div class="container py-5">
    <div style="margin-bottom: 2.5rem;">
        <h1 style="font-size: 2.5rem;">🤖 AI Symptom Checker</h1>
        <p class="text-muted">Quick symptom analysis and doctor recommendations</p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">🔍 Select Your Symptoms</h5>
                </div>
                <div class="card-body">
                    <form method="post" id="symptomForm">
                        <p class="text-muted mb-3">Select all symptoms you are experiencing:</p>
                        
                        <div class="row">
                            <?php foreach ($symptoms_db as $symptom_key => $conditions): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="symptoms[]" 
                                               value="<?= $symptom_key ?>" id="symptom_<?= $symptom_key ?>">
                                        <label class="form-check-label" for="symptom_<?= $symptom_key ?>">
                                            <?= ucfirst(str_replace('_', ' ', $symptom_key)) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                🔍 Analyze Symptoms
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($recommendations !== null): ?>
                <div class="card shadow mt-4">
                    <div class="card-header" style="background: <?php 
                        echo $severity === 'high' ? '#dc3545' : ($severity === 'medium' ? '#ffc107' : '#28a745');
                    ?>; color: white;">
                        <h5 class="mb-0">
                            <?php 
                            if ($severity === 'high') echo "⚠️ HIGH PRIORITY - Seek Medical Help";
                            elseif ($severity === 'medium') echo "⚡ MEDIUM PRIORITY - See Doctor Soon";
                            else echo "✅ LOW PRIORITY - Monitor Condition";
                            ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6>Possible Conditions:</h6>
                        <div class="list-group">
                            <?php foreach ($recommendations as $condition => $score): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <strong><?= htmlspecialchars($condition) ?></strong>
                                        <br><small class="text-muted">Match Score: <?= $score ?> symptom(s)</small>
                                    </span>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="condition" value="<?= htmlspecialchars($condition) ?>">
                                        <input type="hidden" name="selected_symptoms" value="<?= htmlspecialchars(json_encode($_POST['symptoms'] ?? [])) ?>">
                                        <button type="submit" name="book_appointment" class="btn btn-sm btn-info">
                                            📅 Book Appointment
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="alert alert-warning mt-4">
                            <strong>⚠️ Disclaimer:</strong> This is a preliminary analysis based on reported symptoms. 
                            It is NOT a medical diagnosis. Always consult with a qualified healthcare professional 
                            for proper diagnosis and treatment. In case of emergency, call 911 or visit the nearest hospital.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">ℹ️ How It Works</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li>Select all your current symptoms</li>
                        <li>Click "Analyze Symptoms"</li>
                        <li>Review possible conditions</li>
                        <li>Book appointment with appropriate doctor</li>
                        <li>Get professional diagnosis and treatment</li>
                    </ol>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">🚨 Emergency Signs</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Seek immediate medical help if you have:</strong></p>
                    <ul class="mb-0 small">
                        <li>Severe chest pain</li>
                        <li>Difficulty breathing</li>
                        <li>Severe headache</li>
                        <li>Loss of consciousness</li>
                        <li>Severe injuries</li>
                        <li>Poisoning</li>
                    </ul>
                    <p class="mt-3 mb-0"><strong>Emergency: Call 911</strong></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
