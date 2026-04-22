<?php
/**
 * Generate Sample Patient Reports
 * Hospital Management System
 */

include '../db.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_sample_reports') {
    try {
        // Get patient IDs with role 'patient'
        $patients = $pdo->query("SELECT id FROM users WHERE role = 'patient' LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($patients)) {
            $message = '❌ No patients found in database.';
        } else {
            $sample_reports = [
                ['Medical Assessment', 'Patient presents with symptoms of hypertension. Blood pressure readings consistently above 140/90. Recommended lifestyle changes including reduced sodium intake and regular exercise. Follow-up appointment scheduled in 2 weeks.'],
                ['Lab Results', "Complete Blood Count (CBC) Results:\n- Hemoglobin: 13.5 g/dL (Normal)\n- White Blood Cell Count: 7.2 K/uL (Normal)\n- Platelet Count: 250 K/uL (Normal)\nChemistry Panel:\n- Glucose: 95 mg/dL (Normal)\n- Creatinine: 1.0 mg/dL (Normal)\nAll values within normal limits."],
                ['Diagnosis', 'Patient diagnosed with Type 2 Diabetes Mellitus. Fasting blood glucose level 156 mg/dL. HbA1c 7.8%. Patient counseled on diet, exercise, and medication management. Prescribed Metformin 500mg twice daily.'],
                ['Treatment Plan', "Comprehensive treatment plan for Managing Chronic Migraines:\n1. Preventive Therapy: Start Propranolol 40mg daily\n2. Acute Treatment: Sumatriptan 50mg as needed\n3. Lifestyle Modifications: Regular sleep, stress management, hydration"],
                ['Discharge Summary', 'Patient Name: John Smith\nAdmission Date: 2026-04-10\nDischarge Date: 2026-04-15\nDiagnosis: Acute Appendicitis\nProcedure: Laparoscopic Appendectomy\nPost-operative Course: Uneventful.'],
                ['Prescription Report', "Current Active Prescriptions:\n1. Lisinopril 10mg - Daily (Hypertension)\n2. Atorvastatin 40mg - Daily (Cholesterol)\n3. Metformin 500mg - Twice Daily (Diabetes)"],
                ['Medical Assessment', 'Annual Physical Examination: Vital Signs Normal. Physical Exam: Normal findings on cardiovascular, respiratory, and abdominal examination. All routine screening tests normal.'],
                ['Lab Results', "Thyroid Function Test Results:\n- TSH: 2.1 mIU/L (Normal)\n- Free T4: 1.2 ng/dL (Normal)\n- Free T3: 3.1 pg/mL (Normal)\nNo evidence of thyroid disease."],
                ['Diagnosis', 'Patient diagnosed with Anxiety Disorder, Generalized Type. Symptoms include persistent worry and sleep disturbance. Started on Sertraline 50mg daily.'],
                ['Treatment Plan', "Orthopedic Treatment Plan - Knee Osteoarthritis:\n1. Physical Therapy: 2x/week for 6 weeks\n2. Pain Management: Ibuprofen 400mg as needed\n3. Lifestyle: Weight management and activity modification"]
            ];
            
            $count = 0;
            foreach ($patients as $patient_id) {
                if ($count >= count($sample_reports)) break;
                
                $report = $sample_reports[$count];
                $stmt = $pdo->prepare('INSERT INTO patient_reports (patient_id, report_type, content, created_at) VALUES (?, ?, ?, NOW())');
                $stmt->execute([$patient_id, $report[0], $report[1]]);
                $count++;
            }
            
            $message = "✅ Successfully created $count sample reports!";
            $success = true;
        }
    } catch (Exception $e) {
        $message = '❌ Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Sample Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 600px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px 15px 0 0 !important;
            padding: 30px;
            text-align: center;
        }
        .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .card-body {
            padding: 30px;
        }
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        .btn {
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            width: 100%;
            font-size: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        h2 {
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .subtitle {
            color: #666;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <div class="icon">📋</div>
                <h2 style="color: white; margin: 0;">Sample Reports Generator</h2>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>" role="alert">
                        <?= $message ?>
                    </div>
                    <?php if ($success): ?>
                        <a href="../reports.php" class="btn btn-primary">
                            <i class="bi bi-arrow-right"></i> View Reports
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="subtitle">Generate sample patient reports to test the reporting system.</p>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <h5 style="color: #333; margin-bottom: 15px;">📊 What will be created:</h5>
                        <ul style="color: #666; margin-bottom: 0;">
                            <li>10 sample patient reports</li>
                            <li>Various report types (Medical Assessment, Lab Results, Diagnosis, etc.)</li>
                            <li>Realistic medical content</li>
                            <li>Automatically linked to first 10 patients</li>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="generate_sample_reports">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle-fill"></i> Generate Sample Reports
                        </button>
                    </form>
                    
                    <hr style="margin: 20px 0;">
                    <a href="../reports.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Reports
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
