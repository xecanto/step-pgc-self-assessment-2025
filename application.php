<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and has user role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details for display
$stmt = $conn->prepare("SELECT first_name, last_name, cnic FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user already has an application
$stmt = $conn->prepare("SELECT id FROM applications WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$existing_application = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = isset($_POST['submit_final']) ? 'submitted' : 'draft';

    // Validate required fields if submitting final application
    if ($status === 'submitted') {
        $required_fields = ['program', 'father_name', 'mobile', 'email', 'address', 'campus_name', 'board_roll_no', 'board_marks', 'college_name'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $error_message = "Please fill all required fields before submitting.";
                break;
            }
        }
    }

    if (empty($error_message)) {
        // Handle photo upload
        $photo_path = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $photo_name = $user['cnic'] . time() . '_' . $_FILES['photo']['name'];
            $target_path = $upload_dir . $photo_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                $photo_path = $target_path;
            }
        }

        $full_name = $user['first_name'] . ' ' . $user['last_name'];

        if ($existing_application) {
            // Update existing application
            if ($photo_path) {
                $sql = "UPDATE applications SET 
                        program = ?,
                        full_name = ?,
                        father_name = ?,
                        mobile = ?,
                        email = ?,
                        address = ?,
                        campus_name = ?,
                        board_roll_no = ?,
                        board_marks = ?,
                        college_name = ?,
                        status = ?,
                        photo_path = ?,
                        WHERE user_id = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "sssssssssssssssi",
                    $_POST['program'],
                    $full_name,
                    $_POST['father_name'],
                    $_POST['mobile'],
                    $_POST['email'],
                    $_POST['address'],
                    $_POST['campus_name'],
                    $_POST['board_roll_no'],
                    $_POST['board_marks'],
                    $_POST['college_name'],
                    $status,
                    $photo_path,
                    $user_id
                );
            } else {
                $sql = "UPDATE applications SET 
                        program = ?,
                        full_name = ?,
                        father_name = ?,
                        mobile = ?,
                        email = ?,
                        address = ?,
                        campus_name = ?,
                        board_roll_no = ?,
                        board_marks = ?,
                        college_name = ?,
                        status = ?,
                        WHERE user_id = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssssssssssssi",
                    $_POST['program'],
                    $full_name,
                    $_POST['father_name'],
                    $_POST['mobile'],
                    $_POST['email'],
                    $_POST['address'],
                    $_POST['campus_name'],
                    $_POST['board_roll_no'],
                    $_POST['board_marks'],
                    $_POST['college_name'],
                    $status,
                    $user_id
                );
            }
        } else {
            // Create new application
            $sql = "INSERT INTO applications (
                user_id, program, full_name, father_name, mobile, email, address,
                campus_name, board_roll_no, board_marks, college_name, photo_path, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "issssssssssss",
                $user_id,
                $_POST['program'],
                $full_name,
                $_POST['father_name'],
                $_POST['mobile'],
                $_POST['email'],
                $_POST['address'],
                $_POST['campus_name'],
                $_POST['board_roll_no'],
                $_POST['board_marks'],
                $_POST['college_name'],
                $photo_path,
                $status,
            );
        }

        if ($stmt->execute()) {
            $success_message = "Application " . ($status === 'submitted' ? 'submitted' : 'saved as draft') . " successfully!";
            // Refresh the page to show updated data
            header("Location: application.php?success=" . urlencode($success_message));
            exit();
        } else {
            $error_message = "Error saving application!";
        }
    }
}

// Fetch existing application for display
$stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();

// Split registration slip number into individual digits
$registration_slip_no = str_split($application['registration_slip_no'] ?? '');

// Display success message if redirected after save
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STEP Self Assessment Test - 2025</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .animate-slide-in {
            animation: slideIn 0.5s ease-out forwards;
        }

        .input-focus {
            transition: all 0.3s ease;
        }

        .input-focus:focus {
            transform: scale(1.01);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .program-checkbox:checked+span {
            background-color: #1E40AF;
            color: white;
        }

        .floating-label {
            transition: all 0.2s ease-in-out;
        }

        .input-field:focus+.floating-label,
        .input-field:not(:placeholder-shown)+.floating-label {
            transform: translateY(-1.5rem) scale(0.85);
            color: #1E40AF;
        }
        .bg-app-image {
            background-image: linear-gradient(rgba(0, 0, 0, 0.2), rgba(0, 0, 0, 0.2)), url('step-bg.jpg');
            background-size: cover;
            background-position: center;
            }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr !important;
            }

            .tab-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 1rem;
            }

            .action-buttons button {
                width: 100%;
            }

            .program-selection {
                flex-direction: column;
                gap: 0.5rem;
            }

            .program-selection label {
                width: 100%;
            }

            .program-selection span {
                display: block;
                text-align: center;
            }
            
        }
    </style>
</head>
<?php
$activeSection = ($existing_application && $application['status'] !== 'draft') ? 'registration' : 'personal';
?>

<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen p-6 bg-app-image" x-data="{ activeSection: '<?php echo $activeSection; ?>' }">>
    <div class="max-w-6xl mx-auto ">

        <div class="bg-white rounded-2xl shadow-xl overflow-hidden animate-slide-in">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-900 to-red-800 p-8 text-white relative">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center space-x-4">
                        <img src="step.png" alt="STEP Prep Logo" class="h-12">
                        <div class="h-8 w-px bg-white/30"></div>
                        <img src="pgc.png" alt="Punjab Colleges Logo" class="h-12">
                    </div>
                </div>
                <h1 class="text-3xl font-bold text-center">STEP SELF ASSESSMENT TEST</h1>
                <div class="text-sm font-light text-center">
                    Academic Year 2025
                </div>
                <!-- STEP FAISALABAD 03000456068-03000456069 visit: https://step.pgc.edu/step·self-assessment-test -->

                <div class="flex items-center space-x-4">
                    <a href="https://step.pgc.edu/step-self-assessment-test" target="_blank" class="text-white hover:underline flex">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5l7 7-7 7" />
                        </svg>
                    step.pgc.edu    
                        
                    </a>
                    <a href="tel:03000456068" class="text-white hover:underline">03000456068</a>
                    <a href="tel:03000456069" class="text-white hover:underline">03000456069</a>
                </div>



                <!-- Application Status & Logout Button -->
                <div class="absolute top-4 right-4 flex items-center space-x-4">
                    <?php if ($application): ?>
                        <div class="px-4 py-2 rounded-lg text-sm font-medium <?php
                                                                                echo match ($application['status']) {
                                                                                    'draft' => 'bg-gray-100 text-gray-800',
                                                                                    'submitted' => 'bg-blue-100 text-blue-800',
                                                                                    'rejected' => 'bg-red-100 text-red-800',
                                                                                    'accepted' => 'bg-green-100 text-green-800',
                                                                                    default => 'bg-gray-100 text-gray-800'
                                                                                };
                                                                                ?>">
                            <span class="font-semibold">Status:</span> <?php echo ucfirst($application['status']); ?>
                        </div>
                    <?php endif; ?>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-all">Logout</a>
                </div>
            </div>
            
            <!-- Progress Tabs -->
            <div class="bg-gray-50 p-4 flex justify-center space-x-4 border-b tab-buttons">
                <button
                    @click="activeSection = 'personal'"
                    :class="{'bg-blue-600 text-white': activeSection === 'personal', 'bg-gray-200': activeSection !== 'personal'}"
                    class="px-6 py-2 rounded-full transition-all duration-300 hover:shadow-md cursor-pointer">
                    Personal Details
                </button>
                <button
                    @click="activeSection = 'academic'"
                    :class="{'bg-blue-600 text-white': activeSection === 'academic', 'bg-gray-200': activeSection !== 'academic'}"
                    class="px-6 py-2 rounded-full transition-all duration-300 hover:shadow-md cursor-pointer">
                    Academic Details
                </button>
                <button
                    @click="activeSection = 'registration'"
                    :class="{'bg-blue-600 text-white': activeSection === 'registration', 'bg-gray-200': activeSection !== 'registration'}"
                    class="px-6 py-2 rounded-full transition-all duration-300 hover:shadow-md cursor-pointer">
                    Registration
                </button>
            </div>
            <?php if (isset($error_message)): ?>
    <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-2">
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <!-- *ICS STUDENTS HAVING SUBJECT  COMBINATION OF ECONOMICS /STATISTICS ARE NOT ELIGIBLE FOR THE SSAT 2025 -->
                <div class="bg-red-100 text-red-800 p-4 rounded-lg">
                    ICS STUDENTS HAVING SUBJECT  COMBINATION OF ECONOMICS /STATISTICS ARE NOT ELIGIBLE FOR THE SSAT 2025
                </div>

            <form method="POST" enctype="multipart/form-data" class="p-8">
                <!-- Add disabled state check -->
                <?php $isDisabled = ($application && $application['status'] !== 'draft') ? 'disabled' : ''; ?>

                

                <!-- Personal Details Section -->
                <div x-show="activeSection === 'personal'" class="space-y-6 animate-slide-in">
                    <!-- Photo Upload Section -->
                    <div class="flex flex-col md:flex-row gap-6 mb-6">
                        <div class="w-full md:w-3/4">
                            <!-- Existing personal details fields -->
                            <div class="space-y-6">
                                <div class="relative">
                                    <input type="text" name="full_name" value="<?php echo $user['first_name'] . ' ' . $user['last_name']; ?>" 
                                           class="input-field input-focus w-full p-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none" 
                                           placeholder=" " disabled>
                                    <label class="floating-label absolute left-4 top-4 text-gray-500 pointer-events-none bg-white px-1">Full Name</label>
                                </div>
                                <div class="relative">
                                    <input type="text" name="cnic" value="<?php echo $user['cnic']; ?>" 
                                           class="input-field input-focus w-full p-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none" 
                                           placeholder=" " disabled>
                                    <label class="floating-label absolute left-4 top-4 text-gray-500 pointer-events-none bg-white px-1">CNIC</label>
                                </div>
                                <div class="relative">
                                    <input type="text" name="father_name" value="<?php echo $application['father_name'] ?? ''; ?>" class="input-field input-focus w-full p-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none" placeholder=" " <?php echo $isDisabled; ?>>
                                    <label class="floating-label absolute left-4 top-4 text-gray-500 pointer-events-none bg-white px-1">Father's Name</label>
                                </div>

                                <div class="grid grid-cols-2 gap-6 form-grid">
                                    <div class="relative">
                                        <input type="tel" name="mobile" value="<?php echo $application['mobile'] ?? ''; ?>" class="input-field input-focus w-full p-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none" placeholder=" " <?php echo $isDisabled; ?>>
                                        <label class="floating-label absolute left-4 top-4 text-gray-500 pointer-events-none bg-white px-1">Mobile Number</label>
                                    </div>
                                    <div class="relative">
                                        <input type="email" name="email" value="<?php echo $application['email'] ?? ''; ?>" class="input-field input-focus w-full p-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none" placeholder=" " <?php echo $isDisabled; ?>>
                                        <label class="floating-label absolute left-4 top-4 text-gray-500 pointer-events-none bg-white px-1">Email Address</label>
                                    </div>
                                </div>

                                <div class="relative">
                                    <input type="text" name="address" value="<?php echo $application['address'] ?? ''; ?>" class="input-field input-focus w-full p-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none" placeholder=" " <?php echo $isDisabled; ?>>
                                    <label class="floating-label absolute left-4 top-4 text-gray-500 pointer-events-none bg-white px-1">Home Address</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="w-full md:w-1/4">
    <div class="relative w-full aspect-[3/4] border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 overflow-hidden <?php echo $isDisabled ? 'cursor-not-allowed' : 'cursor-pointer hover:bg-gray-100'; ?> transition-colors">
        <?php if ($isDisabled): ?>
            <img id="uploaded-photo" src="<?php echo $application['photo_path'] ?? ''; ?>"
                 alt="Uploaded photo"
                 class="absolute inset-0 w-full h-full object-cover">
        <?php else: ?>
            <input type="file" id="photo-upload" name="photo" accept="image/*" 
                   class="hidden" onchange="previewImage(event)">
            
            <label class="absolute inset-0 flex items-center justify-center" for="photo-upload" id="upload-label">
                <div class="text-center" id="upload-text">
                    <div class="text-blue-500 mb-2">Upload Photo</div>
                    <div class="text-sm text-gray-500">Click or drag</div>
                </div>
            </label>
            
            <div class="relative w-full h-full">
                <img id="uploaded-photo" src="<?php echo $application['photo_path'] ?? ''; ?>"
                     alt="Uploaded photo"
                     class="absolute inset-0 w-full h-full object-cover <?php echo empty($application['photo_path']) ? 'hidden' : ''; ?>">
                <label for="photo-upload" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity cursor-pointer">
                    <span class="text-white text-sm">Edit</span>
                </label>
            </div>
        <?php endif; ?>
    </div>
</div>

                    </div>
                </div>

                <!-- Academic Details Section -->
                <div x-show="activeSection === 'academic'" class="space-y-6 animate-slide-in">
                <!-- Program Selection -->
                <div class="mb-8 animate-slide-in">
                    <h3 class="text-lg font-semibold mb-4 text-gray-700">Select Program</h3>
                    <fieldset>
                        <legend class="sr-only">Program</legend>
                        <div class="flex space-x-4 program-selection">
                            <?php
                            $programs = ['MDCAT', 'ECAT', 'ECAT-CS'];
                            foreach ($programs as $program) {
                                $checked = ($application && $application['program'] === $program) ? 'checked' : '';
                                echo "
                                <label class='relative'>
                                    <input type='radio' name='program' value='$program' $checked required $isDisabled
                                        class='hidden peer'>
                                    <span class='px-6 py-3 rounded-lg border-2 border-blue-600 cursor-pointer inline-block
                                        transition-all duration-300 hover:bg-blue-50 peer-checked:bg-blue-600 peer-checked:text-white
                                        " . ($isDisabled ? 'cursor-not-allowed' : '') . "'>
                                        $program
                                    </span>
                                </label>";
                            }
                            ?>
                        </div>
                    </fieldset>
                </div>    
                
                <div class="relative">
                        <input type="text" name="campus_name" value="<?php echo $application['campus_name'] ?? ''; ?>" class="input-field input-focus w-full p-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none" placeholder=" " <?php echo $isDisabled; ?>>
                        <label class="floating-label absolute left-4 top-4 text-gray-500 pointer-events-none bg-white px-1">Campus Name</label>
                    </div>

                    <div class="grid grid-cols-2 gap-6 form-grid">
                        <div class="relative">
                            <input type="text" name="board_roll_no" value="<?php echo $application['board_roll_no'] ?? ''; ?>" class="input-field input-focus w-full p-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none" placeholder=" " <?php echo $isDisabled; ?>>
                            <label class="floating-label absolute left-4 top-4 text-gray-500 pointer-events-none bg-white px-1">Board Roll No</label>
                        </div>
                        <div class="relative">
                            <input type="text" name="board_marks" value="<?php echo $application['board_marks'] ?? ''; ?>" class="input-field input-focus w-full p-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none" placeholder=" " <?php echo $isDisabled; ?>>
                            <label class="floating-label absolute left-4 top-4 text-gray-500 pointer-events-none bg-white px-1">Board Marks</label>
                        </div>
                    </div>

                    <div class="relative">
                        <input type="text" name="college_name" value="<?php echo $application['college_name'] ?? ''; ?>" class="input-field input-focus w-full p-4 border-2 border-gray-200 rounded-lg focus:border-blue-500 outline-none" placeholder=" " <?php echo $isDisabled; ?>>
                        <label class="floating-label absolute left-4 top-4 text-gray-500 pointer-events-none bg-white px-1">College Name</label>
                    </div>
                </div>

                <!-- Registration Slip Section -->
                <div x-show="activeSection === 'registration'" class="animate-slide-in">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-xl font-semibold mb-6 text-center text-gray-800">Registration Slip (For Office use Only)</h3>

                        <div class="grid grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div class="grid grid-cols-6 gap-2">
                                    <?php for ($i = 0; $i < 6; $i++): ?>
                                        <input type="text" value="<?php echo $registration_slip_no[$i] ?? ''; ?>" class="w-full p-3 text-center border rounded bg-gray-100" readonly>
                                    <?php endfor; ?>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-2">Test Date</label>
                                        <input type="text" value="23-FEB-2025" class="w-full p-3 border rounded bg-gray-100" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-2">Test Time</label>
                                        <input type="text" value="03:00 to 04:20 pm" class="w-full p-3 border rounded bg-gray-100" readonly>
                                    </div>
                                </div>

                                <!-- STEP FAISALABAD 03000456068-03000456069 -->
                                 <h2 class="font-bold">
                                    CENTER: STEP FAISALABAD 03000456068-03000456069
                                 </h2>
                            </div>

                            <!-- Read-Only Display in Another Section -->
<div class="flex flex-col items-center space-y-4">
    <div class="relative w-40 h-48 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
        <img id="uploaded-photo-readonly" src="<?php echo $application['photo_path'] ?? ''; ?>"
             alt="Uploaded photo"
             class="absolute inset-0 w-full h-full object-cover rounded-lg">
    </div>
</div>
                        </div>
                    </div>

                    <!-- Submit Buttons - Only show if application is in draft status -->
                    <?php if (!$application || $application['status'] === 'draft'): ?>
                    <div class="mt-8 flex justify-end space-x-4 action-buttons">
                        <button type="submit" name="save_draft" 
                                class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                            Save as Draft
                        </button>
                        <button type="submit" name="submit_final" 
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                            Submit Application
                        </button>
                    </div>
                    <?php endif; ?>
            </form>
        </div>
    </div>

    <script>
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const img = document.getElementById('uploaded-photo');
            const imgReadOnly = document.getElementById('uploaded-photo-readonly');
            img.src = reader.result;
            imgReadOnly.src = reader.result;
            img.classList.remove('hidden');
            imgReadOnly.classList.remove('hidden');
            document.getElementById('upload-text').classList.add('hidden');
        }
        reader.readAsDataURL(event.target.files[0]);
    }

    document.getElementById('uploaded-photo').addEventListener('click', function() {
        document.getElementById('photo-upload').click();
    });
</script>

</body>

</html>