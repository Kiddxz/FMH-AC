<?php

require_once "../config/database.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    // Check required fields
    if (
        empty($firstName) ||
        empty($lastName) ||
        empty($email) ||
        empty($password) ||
        empty($confirmPassword)
    ) {

        $message = "Please complete all fields.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters.";
        $messageType = "error";

    } else {

        // Check if email already exists
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {

            $message = "This email is already registered. Please login.";
            $messageType = "error";

        } else {

            // Secure password hashing
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Patient accounts created through signup
            $role = "patient";
            $status = "active";

            $stmt = $conn->prepare(
                "INSERT INTO users
                (first_name, last_name, email, password, role, status)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssssss",
                $firstName,
                $lastName,
                $email,
                $hashedPassword,
                $role,
                $status
            );

            if ($stmt->execute()) {

                // Redirect to login after successful registration
                header("Location: login.php?registered=1");
                exit;

            } else {

                $message = "Something went wrong while creating your account.";
                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Patient Sign Up - SupremeDent</title>

    <link
        rel="stylesheet"
        href="../assets/css/styles.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

</head>

<body>

<div class="auth-page">

    <div class="auth-shell">

        <!-- LEFT SIDE -->

        <aside class="auth-panel">

            <img
                src="../assets/images/logo.png"
                alt="SupremeDent Logo"
            >

            <h2>
                Join SupremeDent
            </h2>

            <p>
                Create your patient account to book appointments,
                view dental records, and keep track of your treatment.
            </p>

        </aside>


        <!-- RIGHT SIDE -->

        <section class="auth-form-panel">

            <a
                href="../index.php"
                class="back-home"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Back to Home
            </a>


            <h1>
                SUPREMEDENT
            </h1>

            <p>
                Patient Registration
            </p>


            <?php if (!empty($message)): ?>

                <div
                    class="auth-message
                    <?php echo $messageType === 'error'
                        ? 'is-error'
                        : 'is-success'; ?>"
                >

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
            >

                <!-- FIRST + LAST NAME -->

                <div class="auth-grid two-up">

                    <div class="auth-field">

                        <label for="first_name">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            class="input-field"
                            placeholder="First name"
                            value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                            required
                        >

                    </div>


                    <div class="auth-field">

                        <label for="last_name">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            class="input-field"
                            placeholder="Last name"
                            value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="auth-field">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="input-field"
                        placeholder="you@example.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="auth-field">

                    <label for="password">
                        Password
                    </label>

                    <div class="password-control">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="input-field"
                            placeholder="Create a password"
                            required
                        >

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="auth-field">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <div class="password-control">

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="input-field"
                            placeholder="Re-enter your password"
                            required
                        >

                    </div>

                </div>


                <!-- TERMS -->

                <label class="terms-check">

                    <input
                        type="checkbox"
                        required
                    >

                    <span>
                        I agree to the
                        <a href="#">
                            Terms and Conditions
                        </a>
                        and
                        <a href="#">
                            Privacy Policy
                        </a>.
                    </span>

                </label>


                <!-- CREATE ACCOUNT -->

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    Create Patient Account

                </button>

            </form>


            <!-- LOGIN LINK -->

            <div class="auth-footer">

                Already have an account?

                <a href="login.php">
                    Login
                </a>

            </div>

        </section>

    </div>

</div>

</body>

</html>