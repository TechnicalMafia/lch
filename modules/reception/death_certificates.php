<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole('reception');

// Get record ID from URL
$record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($record_id <= 0) {
    header('Location: /lch/modules/reception/view_death_records.php?error=invalid_id');
    exit;
}

// Get death record details
$record = getDeathRecord($record_id);

if (!$record) {
    header('Location: /lch/modules/reception/view_death_records.php?error=record_not_found');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Death Certificate - <?php echo htmlspecialchars($record['deceased_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            .certificate { margin: 0 !important; }
        }
        
        .certificate-border {
            border: 8px solid #7f1d1d;
            border-image: linear-gradient(45deg, #7f1d1d, #dc2626, #ef4444, #dc2626, #7f1d1d) 1;
            position: relative;
        }
        
        .certificate-border::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            background: linear-gradient(45deg, #374151, #6b7280, #9ca3af, #6b7280, #374151);
            z-index: -1;
            border-radius: 12px;
        }
        
        .ornament {
            background: radial-gradient(circle, #374151, #6b7280);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-block;
        }
        
        .seal {
            width: 100px;
            height: 100px;
            border: 3px solid #7f1d1d;
            border-radius: 50%;
            background: radial-gradient(circle, #fecaca, #7f1d1d);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7f1d1d;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            line-height: 1.2;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Print Button -->
    <div class="no-print p-4 text-center">
        <button onclick="window.print()" class="bg-red-600 text-white py-2 px-6 rounded hover:bg-red-700 mr-4">
            <i class="fas fa-print mr-2"></i> Print Certificate
        </button>
        <a href="death_record_details.php?id=<?php echo $record['id']; ?>" class="bg-blue-600 text-white py-2 px-6 rounded hover:bg-blue-700 mr-4">
            <i class="fas fa-arrow-left mr-2"></i> Back to Details
        </a>
        <a href="view_death_records.php" class="bg-gray-600 text-white py-2 px-6 rounded hover:bg-gray-700">
            <i class="fas fa-list mr-2"></i> All Records
        </a>
    </div>

    <!-- Death Certificate -->
    <div class="certificate max-w-4xl mx-auto my-8 p-8">
        <div class="certificate-border bg-white p-8 relative">
            <!-- Ornamental corners -->
            <div class="absolute top-4 left-4"><div class="ornament"></div></div>
            <div class="absolute top-4 right-4"><div class="ornament"></div></div>
            <div class="absolute bottom-4 left-4"><div class="ornament"></div></div>
            <div class="absolute bottom-4 right-4"><div class="ornament"></div></div>
            
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="text-red-800 text-2xl font-bold mb-2">ISLAMIC REPUBLIC OF PAKISTAN</div>
                <div class="text-lg font-semibold mb-2">GOVERNMENT OF KHYBER PAKHTUNKHWA</div>
                <div class="text-base mb-4">Union Council - Haripur District</div>
                <div class="border-t-2 border-b-2 border-red-600 py-3 my-4">
                    <h1 class="text-3xl font-bold text-red-800">DEATH CERTIFICATE</h1>
                    <div class="text-sm text-gray-600 mt-1">شہادت وفات</div>
                </div>
            </div>

            <!-- Certificate Content -->
            <div class="grid grid-cols-3 gap-8">
                <!-- Left Column - Details -->
                <div class="col-span-2 space-y-4">
                    <div class="text-center mb-6">
                        <p class="text-lg">This is to certify that according to the record of</p>
                        <p class="text-xl font-bold text-red-800">LIFE CARE HOSPITAL</p>
                        <p>Haripur, Khyber Pakhtunkhwa, Pakistan</p>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="font-semibold">Name of Deceased:</span>
                            <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                                <span class="text-lg font-bold text-red-800"><?php echo htmlspecialchars($record['deceased_name']); ?></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="font-semibold">Date of Death:</span>
                                <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                                    <span class="text-lg font-bold text-red-800"><?php echo date('d F Y', strtotime($record['date_of_death'])); ?></span>
                                </div>
                            </div>
                            <div>
                                <span class="font-semibold">Time of Death:</span>
                                <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                                    <span class="text-lg font-bold"><?php echo date('h:i A', strtotime($record['time_of_death'])); ?></span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <span class="font-semibold">Age at Death:</span>
                            <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                                <span class="text-lg font-bold"><?php echo $record['age_at_death']; ?> years</span>
                            </div>
                        </div>

                        <div>
                            <span class="font-semibold">Place of Death:</span>
                            <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                                <span class="text-lg font-bold"><?php echo htmlspecialchars($record['place_of_death']); ?></span>
                            </div>
                        </div>

                        <div>
                            <span class="font-semibold">Father's Name:</span>
                            <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                                <span class="text-lg font-bold"><?php echo htmlspecialchars($record['father_name']); ?></span>
                            </div>
                        </div>

                        <?php if ($record['mother_name']): ?>
                        <div>
                            <span class="font-semibold">Mother's Name:</span>
                            <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                                <span class="text-lg font-bold"><?php echo htmlspecialchars($record['mother_name']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($record['spouse_name']): ?>
                        <div>
                            <span class="font-semibold">Spouse Name:</span>
                            <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                                <span class="text-lg font-bold"><?php echo htmlspecialchars($record['spouse_name']); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div>
                            <span class="font-semibold">NIC Number:</span>
                            <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                                <span class="text-lg font-bold"><?php echo $record['deceased_nic'] ?: 'Not Available'; ?></span>
                            </div>
                        </div>

                        <div>
                            <span class="font-semibold">Cause of Death:</span>
                            <div class="border border-gray-400 mt-1 p-2 bg-gray-50">
                                <span class="font-medium"><?php echo nl2br(htmlspecialchars($record['cause_of_death'])); ?></span>
                            </div>
                        </div>

                        <div>
                            <span class="font-semibold">Permanent Address:</span>
                            <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                                <span class="font-medium"><?php echo htmlspecialchars($record['address']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Seal and Signatures -->
                <div class="text-center space-y-6">
                    <div class="mx-auto seal">
                        <div>
                            OFFICIAL<br>
                            SEAL<br>
                            <i class="fas fa-certificate text-lg"></i>
                        </div>
                    </div>
                    
                    <div class="space-y-4 text-xs">
                        <div>
                            <div class="border-t border-gray-400 pt-2 mt-8">
                                <span class="font-semibold">Medical Officer</span><br>
                                <?php echo $record['doctor_name'] ? 'Dr. ' . htmlspecialchars($record['doctor_name']) : 'Medical Officer'; ?>
                            </div>
                        </div>
                        
                        <div>
                            <div class="border-t border-gray-400 pt-2 mt-8">
                                <span class="font-semibold">Registrar</span><br>
                                Life Care Hospital
                            </div>
                        </div>

                        <?php if ($record['autopsy_required']): ?>
                        <div class="mt-4 p-2 bg-yellow-100 border border-yellow-400 rounded text-xs">
                            <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                            <div class="font-semibold text-yellow-800">AUTOPSY REQUIRED</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Next of Kin Information -->
            <div class="mt-6 pt-4 border-t border-gray-300">
                <h3 class="font-semibold text-sm mb-3">NEXT OF KIN INFORMATION</h3>
                <div class="grid grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="font-semibold">Name:</span>
                        <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                            <?php echo htmlspecialchars($record['next_of_kin']); ?>
                        </div>
                    </div>
                    <div>
                        <span class="font-semibold">Relation:</span>
                        <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                            <?php echo htmlspecialchars($record['next_of_kin_relation']); ?>
                        </div>
                    </div>
                    <div>
                        <span class="font-semibold">Contact:</span>
                        <div class="border-b border-dotted border-gray-400 mt-1 pb-1">
                            <?php echo htmlspecialchars($record['next_of_kin_contact']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Information -->
            <div class="mt-6 pt-4 border-t-2 border-gray-300">
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <div><span class="font-semibold">Certificate No:</span> <?php echo $record['certificate_number']; ?></div>
                        <div><span class="font-semibold">Registration Date:</span> <?php echo date('d F Y', strtotime($record['created_at'])); ?></div>
                        <div><span class="font-semibold">Issued By:</span> <?php echo htmlspecialchars($record['issued_by']); ?></div>
                    </div>
                    <div class="text-right">
                        <div><span class="font-semibold">Registration Time:</span> <?php echo date('h:i A', strtotime($record['created_at'])); ?></div>
                        <div><span class="font-semibold">Document Type:</span> Official Death Certificate</div>
                        <div><span class="font-semibold">Status:</span> Certified Copy</div>
                    </div>
                </div>
                
                <div class="text-center mt-4 text-xs text-gray-600">
                    <p><span class="font-semibold">Life Care Hospital</span> - Maternity Home & Pain Clinic</p>
                    <p>Naseem Town Opposite Utman Marriage Hall Haripur, KPK, Pakistan</p>
                    <p>Contact: 0332-2400010, 0346-5888603, Phone: 0995-321234</p>
                </div>
            </div>

            <!-- Legal Notice -->
            <div class="mt-6 p-3 bg-red-50 border border-red-200 rounded text-xs">
                <p class="text-center font-semibold text-red-800">IMPORTANT LEGAL NOTICE</p>
                <p class="text-center">This certificate is issued under the authority of Death Registration Act. Any false information or tampering with this document is a punishable offense under the law. This document is required for legal proceedings, insurance claims, and inheritance matters.</p>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>