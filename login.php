<?php
session_start();
require_once 'db_config.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$max_attempts = 3;    
$lockout_time = 120;  

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}

function initAttempt($username) {
    if (!isset($_SESSION['login_attempts'][$username])) {
        $_SESSION['login_attempts'][$username] = [
            'count' => 0,
            'last_fail' => 0
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    initAttempt($username);

    $attempt = &$_SESSION['login_attempts'][$username];

    if ($attempt['count'] >= $max_attempts) {
        $remaining = ($attempt['last_fail'] + $lockout_time) - time();
        if ($remaining > 0) {
            header("Location: login.php?error=" . urlencode("Too many failed attempts. Try again in $remaining seconds."));
            exit();
        } else {
            
            $attempt['count'] = 0;
            $attempt['last_fail'] = 0;
        }
    }

    if ($username === '' || $password === '') {
        header("Location: login.php?error=" . urlencode("Please enter username and password"));
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE BINARY username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $attempt['count']++;
            $attempt['last_fail'] = time();

            if ($attempt['count'] >= $max_attempts) {
                header("Location: login.php?error=" . urlencode("Too many failed attempts. Locked for 2 minutes."));
                exit();
            }

            header("Location: login.php?error=" . urlencode("Invalid username or password"));
            exit();
        }

        if (!empty($user['status']) && $user['status'] === 'inactive') {
            header("Location: login.php?error=" . urlencode("This account is deactivated. Contact admin."));
            exit();
        }

        $dbPassword = $user['password'];
        $userId = $user['id'];
        $authenticated = false;

        if (password_verify($password, $dbPassword)) {
            $authenticated = true;

            if (password_needs_rehash($dbPassword, PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $userId]);
            }
        }
        elseif ($dbPassword === md5($password)) {
            $authenticated = true;

            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $userId]);
        }

      
        if (!$authenticated) {
            $attempt['count']++;
            $attempt['last_fail'] = time();

            if ($attempt['count'] >= $max_attempts) {
                header("Location: login.php?error=" . urlencode("Too many failed attempts. Locked for 2 minutes."));
                exit();
            }

            header("Location: login.php?error=" . urlencode("Invalid username or password"));
            exit();
        }


        $attempt['count'] = 0;
        $attempt['last_fail'] = 0;

        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_id'] = $userId;


        $session_id = session_id();
        $checkStmt = $pdo->prepare("
            SELECT id FROM users_log 
            WHERE user_id = ? AND session_id = ? AND logout_time IS NULL LIMIT 1
        ");
        $checkStmt->execute([$userId, $session_id]);

        if ($checkStmt->rowCount() === 0) {
            $logStmt = $pdo->prepare("
                INSERT INTO users_log (user_id, session_id, login_time) 
                VALUES (?, ?, NOW())
            ");
            $logStmt->execute([$userId, $session_id]);
        }

        header("Location: index.php?success=" . urlencode($user['username'] . "!"));
        exit();

    } catch (PDOException $e) {
        header("Location: login.php?error=" . urlencode("Login failed. Please try again."));
        exit();
    }
}
?>


<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Login Form</title>
<style>
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #FFF2F2;
  margin: 0;
  padding: 0;
  height: 100vh;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: vertical;
  -webkit-box-direction: normal;
      -ms-flex-direction: column;
          flex-direction: column;
  -webkit-box-pack: center;
      -ms-flex-pack: center;
          justify-content: center;  
  -webkit-box-align: center;  
      -ms-flex-align: center;  
          align-items: center;
  background-image: url("deans01.jpg");
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    background-attachment: fixed;    
}
.logo { text-align: center; margin-bottom: 15px; }
.logo img { width: 80px; height: auto; border-radius: 50%; -webkit-box-shadow: 0px 4px 10px rgba(0,0,0,0.1); box-shadow: 0px 4px 10px rgba(0,0,0,0.1); }
.error-msg { position: absolute; top: 20px; width: 80%; max-width: 400px; background: red; color: #fff; padding: 12px; border-radius: 8px; text-align: center; font-weight: bold; -webkit-box-shadow: 0px 4px 12px rgba(0,0,0,0.2); box-shadow: 0px 4px 12px rgba(0,0,0,0.2); -webkit-animation: fadeIn 0.5s ease-in-out; animation: fadeIn 0.5s ease-in-out; }
.container { max-width: 350px; background: -o-linear-gradient(bottom, #fff 0%, #f4f7fb 100%); background: -webkit-gradient(linear, left bottom, left top, from(#fff), to(#f4f7fb)); background: linear-gradient(0deg, #fff 0%, #f4f7fb 100%); border-radius: 40px; padding: 20px 35px; border: 5px solid #fff; -webkit-box-shadow: rgba(133, 189, 215, 0.878) 0px 30px 30px -20px; box-shadow: rgba(133, 189, 215, 0.878) 0px 30px 30px -20px; margin: 20px; }
.heading { text-align: center; font-weight: 900; font-size: 30px; color: #000; }
.form { margin-top: 20px; }
.form .input { width: 90%; background: white; border: none; padding: 15px 20px; border-radius: 20px; margin-top: 15px; -webkit-box-shadow: #cff0ff 0px 10px 10px -5px; box-shadow: #cff0ff 0px 10px 10px -5px; border-inline: 2px solid transparent; }
.form .input::-webkit-input-placeholder { color: rgb(170, 170, 170); }
.form .input::-moz-placeholder { color: rgb(170, 170, 170); }
.form .input:-ms-input-placeholder { color: rgb(170, 170, 170); }
.form .input::-ms-input-placeholder { color: rgb(170, 170, 170); }
.form .input::placeholder { color: rgb(170, 170, 170); }
.form .input:focus { outline: none; border-inline: 2px solid #12B1D1; }
.form .login-button { display: block; width: 100%; font-weight: bold; background: -o-linear-gradient(45deg, #1089d3 0%, #12b1d1 100%); background: linear-gradient(45deg, #1089d3 0%, #12b1d1 100%); color: white; padding-block: 15px; margin: 20px auto; border-radius: 20px; border: none; -webkit-transition: all 0.2s ease-in-out; -o-transition: all 0.2s ease-in-out; transition: all 0.2s ease-in-out; }
.form .login-button:hover { -webkit-transform: scale(1.03); -ms-transform: scale(1.03); transform: scale(1.03); }
.form .login-button:active { -webkit-transform: scale(0.95); -ms-transform: scale(0.95); transform: scale(0.95); }
.form-links { text-align: center; margin-top: 10px; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; }
.form-links a { font-size: 13px; color: #0099ff; text-decoration: none; }
#preloader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #fff; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -webkit-box-align: center; -ms-flex-align: center; align-items: center; z-index: 9999; }
.loader { width: 120px; height: 20px; background: -o-linear-gradient(#000 0 0) 0/0% no-repeat lightgray; background: -webkit-gradient(linear, left top, left bottom, color-stop(0, #000)) 0/0% no-repeat lightgray; background: linear-gradient(#000 0 0) 0/0% no-repeat lightgray; -webkit-animation: l1 3s infinite linear; animation: l1 3s infinite linear; }
@-webkit-keyframes l1 { 100% { background-size: 100% } }
@keyframes l1 { 100% { background-size: 100% } }
@-webkit-keyframes fadeIn { from { opacity: 0; -webkit-transform: translateY(-10px); transform: translateY(-10px); } to { opacity: 1; -webkit-transform: translateY(0); transform: translateY(0); } }
@keyframes fadeIn { from { opacity: 0; -webkit-transform: translateY(-10px); transform: translateY(-10px); } to { opacity: 1; -webkit-transform: translateY(0); transform: translateY(0); } }
.toast { position: fixed; top: 25px; right: 25px; background: #28a745; color: white; padding: 14px 22px; border-radius: 8px; font-weight: bold; -webkit-box-shadow: 0px 4px 12px rgba(0,0,0,0.25); box-shadow: 0px 4px 12px rgba(0,0,0,0.25); opacity: 0; -webkit-transform: translateX(100%); -ms-transform: translateX(100%); transform: translateX(100%); -webkit-transition: all 0.4s ease-in-out; -o-transition: all 0.4s ease-in-out; transition: all 0.4s ease-in-out; z-index: 99999; }
.toast.error { background: #d9534f; }
.toast.warning { background: #f0ad4e; }
.toast.show { opacity: 1; -webkit-transform: translateX(0); -ms-transform: translateX(0); transform: translateX(0); }
</style>
</head>
<body>

<div id="toast" class="toast"></div>

<?php if (!empty($error)) echo "<div class='error-msg'>$error</div>"; ?>

<div class="container">
    <div class="logo">
        <img src="deans.png" alt="Logo" />
    </div>
    <div class="heading">Dean's Food Store</div>
    <form method="POST" action="login.php" class="form" autocomplete="off">
        <input type="hidden" name="login" value="1">
        <input required class="input" type="text" name="username" id="username" placeholder="Username" autocomplete="username">
        <input required class="input" type="password" name="password" id="password" placeholder="Password" autocomplete="current-password">
        <input class="login-button" type="submit" value="Sign In">
        <div class="form-links">
            <a href="register.php">Register</a>
        </div>
    </form>
</div>

<script>
function showToast(message, type = "success") {
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.className = "toast " + type + " show";
    setTimeout(() => { toast.className = "toast " + type; }, 3000);
}
</script>

<?php if (isset($_GET['error'])): ?>
<script>
showToast("<?php echo addslashes($_GET['error']); ?>", "error");
</script>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<script>    
showToast("<?php echo addslashes($_GET['success']); ?>", "success");
</script>
<?php endif; ?>

</body>
</html>
