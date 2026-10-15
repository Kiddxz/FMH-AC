<?php

session_start();

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT id, first_name, last_name, email, password, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if ($user["status"] !== "active") {

                $message = "Your account is inactive.";

            } elseif (
                password_verify(
                    $password,
                    $user["password"]
                )
            ) {

                // Store user information in session
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["first_name"] = $user["first_name"];
                $_SESSION["last_name"] = $user["last_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                // Redirect based on role
                switch ($user["role"]) {

                    case "patient":
                        header("Location: ../patient/dashboard.php");
                        break;

                    case "assistant":
                        header("Location: ../assistant/dashboard.php");
                        break;

                    case "dentist":
                        header("Location: ../dentist/dashboard.php");
                        break;

                    case "superadmin":
                        header("Location: ../superadmin/dashboard.php");
                        break;

                    default:
                        session_destroy();
                        $message = "Invalid user role.";
                        exit;
                }

                exit;

            } else {

                $message = "Incorrect email or password.";
            }

        } else {

            $message = "Incorrect email or password.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - SupremeDent</title>

</head>

<body>

    <h1>SupremeDent Login</h1>

    <?php if (!empty($message)): ?>

        <p>
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>

    <?php if (isset($_GET["registered"])): ?>

        <p>
            Account created successfully.
            You can now login.
        </p>

    <?php endif; ?>

    <form method="POST">

        <div>

            <label>Email</label>

            <input type="email" name="email" required>

        </div>

        <br>

        <div>

            <label>Password</label>

            <input type="password" name="password" required>

        </div>

        <br>

        <button type="submit">
            Login
        </button>

    </form>

    <br>

    <a href="signup.php">
        Patient Sign Up
    </a>

</body>

</html>