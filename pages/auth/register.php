<?php
require_once '../../includes/config.php';
$db = getDB();

if ($_POST['signUp']) {
    $username = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type_id = (int) $_POST['user_type'];

    // Validate user type
    if (!in_array($user_type_id, [2, 3])) { // Only allow student (2) and company (3)
        echo "<script>alert('Please select a valid account type'); window.location.href='login.php';</script>";
        exit();
    }

    try {
        // Begin transaction for atomic operation
        $db->beginTransaction();
        
        // Check if email already exists using prepared statement
        $checkEmail = "SELECT * FROM users WHERE email = ?";
        $stmt = $db->prepare($checkEmail);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $db->rollback();
            echo "<script>alert('This email is already registered!'); window.location.href='login.php';</script>";
            exit();
        }
        
        // Check if username already exists
        $checkUsername = "SELECT * FROM users WHERE username = ?";
        $stmt = $db->prepare($checkUsername);
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $db->rollback();
            echo "<script>alert('This username is already taken!'); window.location.href='login.php';</script>";
            exit();
        }

        // Insert new user using prepared statement with selected user type
        $password_hash = hashPassword($password);
        $insertQuery = "INSERT INTO users(username, email, password_hash, user_type_id) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($insertQuery);
        
        $result = $stmt->execute([$username, $email, $password_hash, $user_type_id]);
        
        if (!$result) {
            throw new Exception('Failed to create user account');
        }

        // Commit the transaction
        $db->commit();
        
        echo "<script>alert('Registration successful!'); window.location.href='login.php';</script>";
        logActivity('User registration successful', "New user registered: $username ($email) as type ID: $user_type_id");
        
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        logActivity('Database error during registration', $e->getMessage());
        echo "<script>alert('Database error occurred during registration. Please try again.'); window.location.href='login.php';</script>";
        exit();
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        logActivity('Error during registration', $e->getMessage());
        echo "<script>alert('An error occurred during registration: " . addslashes($e->getMessage()) . "'); window.location.href='login.php';</script>";
        exit();
    }
}

if(isset($_POST['signIn'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Login using prepared statement
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$email]);
    
    if($stmt->rowCount() > 0){
        $row = $stmt->fetch();
        // Verify password
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = getUserRole($row['user_type_id']);

            // Check if user has completed profile based on user type
            if ($row['user_type_id'] == 2) {
                // Student - check student_profiles table
                $check_profile = "SELECT user_id FROM student_profiles WHERE user_id = ?";
                $stmt = $db->prepare($check_profile);
                $stmt->execute([$row['user_id']]);

                if($stmt->rowCount() > 0) {
                    // Profile exists, redirect to main page
                    echo "<script>
                        alert('Welcome back, " . $row['username'] . "!');
                        window.location.href='../../index.php';
                    </script>";
                } else {
                    // No profile, redirect to student details form
                    echo "<script>
                        alert('Welcome " . $row['username'] . "! Please enter your student details to complete your profile.');
                        window.location.href='user_details.php';
                    </script>";
                }
            } elseif ($row['user_type_id'] == 3) {
                // Company - check company_profiles table
                $check_profile = "SELECT user_id FROM company_profiles WHERE user_id = ?";
                $stmt = $db->prepare($check_profile);
                $stmt->execute([$row['user_id']]);

                if($stmt->rowCount() > 0) {
                    // Profile exists, redirect to main page
                    echo "<script>
                        alert('Welcome back, " . $row['username'] . "!');
                        window.location.href='../../index.php';
                    </script>";
                } else {
                    // No profile, redirect to company details form
                    echo "<script>
                        alert('Welcome " . $row['username'] . "! Please enter your company details to complete your profile.');
                        window.location.href='company_details.php';
                    </script>";
                }
            } else {
                // Admin or other user types - redirect to main page
                echo "<script>
                    alert('Welcome back, " . $row['username'] . "!');
                    window.location.href='../../index.php';
                </script>";
            }
        } else {
            echo "<script>alert('Incorrect email or password'); window.location.href='login.php';</script>";
        }
    }
    else {
        echo "<script>alert('Incorrect email or password'); window.location.href='login.php';</script>";
    }
}
?>