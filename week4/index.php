
<?php
session_start();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$fields = [
    'name', 'pob', 'dob', 'address', 'gender',
    'telephone', 'email', 'parent_name',
    'parent_telephone', 'relationship', 'student_id',
    'faculty', 'department', 'class_name', 'registration_date'
];

$labels = [
    'name' => 'Full Name',
    'pob' => 'Place of Birth',
    'dob' => 'Date of Birth',
    'address' => 'Address',
    'gender' => 'Gender',
    'telephone' => 'Telephone',
    'email' => 'Email',
    'parent_name' => 'Parent Name',
    'parent_telephone' => 'Parent Telephone',
    'relationship' => 'Relationship',
    'student_id' => 'Student ID',
    'faculty' => 'Faculty',
    'department' => 'Department',
    'class_name' => 'Class',
    'registration_date' => 'Registration Date'
];

$sections = [
    'Personal Information' => [
        'name', 'pob', 'dob', 'address', 'gender',
        'telephone', 'email'
    ],
    'Parent Information' => [
        'parent_name', 'parent_telephone', 'relationship'
    ],
    'Academic Information' => [
        'student_id', 'faculty', 'department',
        'class_name', 'registration_date'
    ]
];

$data = array_fill_keys($fields, '');
$errors = [];
$registered = isset($_SESSION['registered_student']);

if ($registered) {
    $data = $_SESSION['registered_student'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registered = false;
    $action = $_POST['action'] ?? 'register';

    if ($action === 'register') {
        foreach ($fields as $field) {
            $data[$field] = trim($_POST[$field] ?? '');

            if ($data[$field] === '') {
                $errors[$field] = $labels[$field] . ' is required.';
            }
        }

        // Validate student name
        if ($data['name'] !== '' &&
            !preg_match('/^[\p{L} ]+$/u', $data['name'])) {
            $errors['name'] = 'Name must contain letters and spaces only.';
        }

        // Validate parent name
        if ($data['parent_name'] !== '' &&
            !preg_match('/^[\p{L} ]+$/u', $data['parent_name'])) {
            $errors['parent_name'] =
                'Parent name must contain letters and spaces only.';
        }

        // Validate email
        if ($data['email'] !== '' &&
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        // Validate image upload
        $image = $_FILES['image'] ?? null;
        $extension = '';

        if (!$image || $image['error'] === UPLOAD_ERR_NO_FILE) {
            $errors['image'] = 'Please select a student image.';
        } elseif ($image['error'] !== UPLOAD_ERR_OK) {
            $errors['image'] = 'Image upload failed.';
        } else {
            $extension = strtolower(
                pathinfo($image['name'], PATHINFO_EXTENSION)
            );

            $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
            $imageInfo = @getimagesize($image['tmp_name']);
            $allowedMimes = [
                'image/png', 'image/jpeg',
                'image/gif', 'image/webp'
            ];

            if (
                !in_array($extension, $allowedExtensions, true) ||
                !$imageInfo ||
                !in_array($imageInfo['mime'] ?? '', $allowedMimes, true)
            ) {
                $errors['image'] =
                    'Upload a valid PNG, JPG, JPEG, GIF, or WEBP image.';
            } elseif ($image['size'] > 5 * 1024 * 1024) {
                $errors['image'] = 'Image must be 5 MB or smaller.';
            }
        }

        // Save information when all fields are valid
        if (empty($errors)) {
            $uploadFolder = __DIR__ . '/uploads/';

            if (!is_dir($uploadFolder)) {
                mkdir($uploadFolder, 0755, true);
            }

            $imageName = bin2hex(random_bytes(12)) . '.' . $extension;

            if (move_uploaded_file(
                $image['tmp_name'],
                $uploadFolder . $imageName
            )) {
                $data['image'] = 'uploads/' . $imageName;

                $_SESSION['registered_student'] = $data;
                $registered = true;
            } else {
                $errors['image'] = 'Could not save the uploaded image.';
            }
        }
    } elseif ($action === 'new') {
        unset($_SESSION['registered_student']);
        $data = array_fill_keys($fields, '');
        $registered = false;
    }
}

$firstError = array_key_first($errors);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Registration System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>
body {
    background: #f1f5f9;
}
.container {
    max-width: 1000px;
}
.card {
    border: none;
    border-radius: 15px;
}
.header {
    background: #1756a9;
    color: white;
    border-radius: 15px 15px 0 0;
}
.section-title {
    color: #1756a9;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 8px;
    margin-top: 25px;
}
.student-image {
    width: 125px;
    height: 125px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid #dbeafe;
}
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background: white;
    }
}
</style>
</head>

<body>
<div class="container py-5">
<div class="card shadow">

<div class="header text-center p-4">
    <h2>Student Registration System</h2>
    <h6>Jamhuriya University of Science &amp; Technology</h6>
    <p class="mb-0">Faculty of Computer &amp; IT</p>
</div>

<div class="card-body p-4 p-md-5">

<?php if ($registered): ?>

    <!-- RESULT TABLE -->
    <div class="text-center mb-4">
        <?php if (!empty($data['image'])): ?>
            <img src="<?= e($data['image']) ?>"
                 class="student-image" alt="Student image">
        <?php endif; ?>

        <h3 class="mt-3"><?= e($data['name']) ?></h3>
        <span class="badge bg-success">Registration Successful</span>
    </div>

    <h4 class="section-title">Student Information</h4>

    <?php foreach ($sections as $sectionTitle => $sectionFields): ?>
        <h5 class="text-primary mt-4">
            <?= e($sectionTitle) ?>
        </h5>

        <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <tbody>
            <?php foreach ($sectionFields as $field): ?>
                <tr>
                    <th class="table-primary" style="width:35%">
                        <?= e($labels[$field]) ?>
                    </th>
                    <td><?= e($data[$field]) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endforeach; ?>

    <div class="d-flex gap-2 mt-4 no-print">
        <form method="POST">
            <input type="hidden" name="action" value="new">
            <button type="submit" class="btn btn-primary">
                Register Another Student
            </button>
        </form>

        <button onclick="window.print()" class="btn btn-secondary">
            Print Result
        </button>
    </div>

<?php else: ?>

    <!-- REGISTRATION FORM -->
    <p class="text-muted">
        Please complete all required fields.
    </p>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul class="mb-0">
            <?php foreach ($errors as $message): ?>
                <li><?= e($message) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="registrationForm" method="POST"
          enctype="multipart/form-data" novalidate>

        <input type="hidden" name="action" value="register">

        <?php foreach ($sections as $sectionTitle => $sectionFields): ?>
            <h4 class="section-title"><?= e($sectionTitle) ?></h4>

            <div class="row g-3">
            <?php foreach ($sectionFields as $field): ?>
                <div class="col-md-6">
                    <label class="form-label"
                           for="<?= e($field) ?>">
                        <?= e($labels[$field]) ?>
                        <span class="text-danger">*</span>
                    </label>

                    <?php if ($field === 'gender'): ?>
                        <select class="form-select"
                                id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <?php foreach (['Male','Female','Other'] as $option): ?>
                                <option value="<?= e($option) ?>"
                                <?= $data['gender'] === $option ? 'selected' : '' ?>>
                                    <?= e($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($field === 'relationship'): ?>
                        <select class="form-select"
                                id="relationship" name="relationship" required>
                            <option value="">Select Relationship</option>
                            <?php foreach (['Father','Mother','Guardian','Other'] as $option): ?>
                                <option value="<?= e($option) ?>"
                                <?= $data['relationship'] === $option ? 'selected' : '' ?>>
                                    <?= e($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php else: ?>
                        <?php
                        $type = 'text';

                        if (in_array($field, ['dob', 'registration_date'], true)) {
                            $type = 'date';
                        } elseif ($field === 'email') {
                            $type = 'email';
                        } elseif (in_array($field, ['telephone', 'parent_telephone'], true)) {
                            $type = 'tel';
                        }
                        ?>

                        <input
                            type="<?= e($type) ?>"
                            class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>"
                            id="<?= e($field) ?>"
                            name="<?= e($field) ?>"
                            value="<?= e($data[$field]) ?>"
                            required
                        >

                        <?php if (isset($errors[$field])): ?>
                            <div class="invalid-feedback">
                                <?= e($errors[$field]) ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($sectionTitle === 'Personal Information'): ?>
                <div class="col-md-6">
                    <label class="form-label" for="image">
                        Student Image <span class="text-danger">*</span>
                    </label>
                    <input type="file" class="form-control"
                           id="image" name="image"
                           accept=".png,.jpg,.jpeg,.gif,.webp"
                           required>
                    <small class="text-muted">Images only, maximum 5 MB.</small>

                    <?php if (isset($errors['image'])): ?>
                        <div class="text-danger small">
                            <?= e($errors['image']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            </div>
        <?php endforeach; ?>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-lg">
                Register Student
            </button>
            <button type="reset" class="btn btn-outline-secondary btn-lg">
                Clear Form
            </button>
        </div>
    </form>

    <script>
    document.getElementById('registrationForm')
    .addEventListener('submit', function(event) {
        const form = this;
        const name = document.getElementById('name');
        const email = document.getElementById('email');
        const image = document.getElementById('image');

        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }

        if (!/^[\p{L} ]+$/u.test(name.value.trim())) {
            event.preventDefault();
            name.setCustomValidity(
                'Name must contain letters and spaces only.'
            );
            name.reportValidity();
            name.setCustomValidity('');
            return;
        }

        if (!email.checkValidity()) {
            event.preventDefault();
            email.reportValidity();
            return;
        }

        if (!image.files.length ||
            !/\.(png|jpe?g|gif|webp)$/i.test(image.files[0].name)) {
            event.preventDefault();
            image.setCustomValidity(
                'Please select a valid image file.'
            );
            image.reportValidity();
            image.setCustomValidity('');
        }
    });
    </script>

<?php endif; ?>

</div>
</div>
</div>
</body>
</html>
