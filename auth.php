<?php
session_start();
require_once 'database/config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/index.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] == 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: user/index.php');
                }
                exit();
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Email tidak ditemukan!';
        }
        $stmt->close();
    }
}

// Handle Register
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, 'user')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $phone);
            
            if ($stmt->execute()) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan saat registrasi!';
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rumah Sakit Sehat Sentosa</title>
    <link rel="stylesheet" href="../styles.css">
    <style>
        /* ===== AUTH PAGE STYLES ===== */
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
        }

        .container-auth {
            flex: 1;
        }

        .auth-container {
            max-width: 1200px;
            margin: 3rem auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            padding: 0 20px;
            align-items: start;
        }

        /* ===== FORM STYLING ===== */
        .auth-form {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease;
            border-top: 5px solid var(--primary-color);
        }

        .auth-form:hover {
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .auth-form h2 {
            color: var(--primary-color);
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.7rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-group input:hover {
            border-color: var(--primary-color);
            background-color: #ffffff;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(0, 168, 107, 0.1);
        }

        .form-group button {
            width: 100%;
            padding: 0.95rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, #008c5e 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .form-group button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 168, 107, 0.3);
        }

        .form-group button:active {
            transform: translateY(0);
        }

        /* ===== ALERT MESSAGES ===== */
        .alert {
            padding: 1.1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
            border-left: 4px solid;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background-color: #fef5f5;
            color: #721c24;
            border-left-color: #e74c3c;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #155724;
            border-left-color: #27ae60;
        }

        /* ===== TOGGLE FORM ===== */
        .toggle-form {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-light);
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
        }

        .toggle-form a {
            color: var(--primary-color);
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .toggle-form a:hover {
            color: #008c5e;
            text-decoration: underline;
        }

        .toggle-form p {
            margin-top: 1rem;
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .form-hidden {
            display: none;
        }

        /* ===== INFO BOX ===== */
        .info-box {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            position: sticky;
            top: 100px;
        }

        .info-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
        }

        .info-box h3 {
            margin-bottom: 1.2rem;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .info-box p {
            margin-bottom: 1.2rem;
            line-height: 1.8;
            opacity: 0.95;
        }

        .info-box h4 {
            margin-top: 1.8rem;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
            opacity: 0.95;
        }

        .info-box .feature {
            display: flex;
            align-items: center;
            margin: 0.9rem 0;
            gap: 12px;
            padding: 0.6rem 0;
            opacity: 0.9;
            transition: all 0.3s ease;
        }

        .info-box .feature:hover {
            opacity: 1;
            padding-left: 8px;
        }

        .info-box .feature::before {
            content: "✓";
            font-weight: bold;
            font-size: 1.3rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            min-height: 24px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            flex-shrink: 0;
        }

        .info-box .info-note {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.85rem;
            opacity: 0.8;
            line-height: 1.6;
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--text-dark);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: auto;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 968px) {
            .auth-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .info-box {
                position: static;
            }
        }

        @media (max-width: 600px) {
            .auth-form {
                padding: 2rem;
            }

            .auth-form h2 {
                font-size: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .info-box {
                padding: 2rem;
            }

            .info-box h3 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">Rumah Sakit Sehat Sentosa</div>
        </div>
    </header>

    <div class="auth-container">
        <!-- Login Form -->
        <div class="auth-form" id="loginForm">
            <h2>Login</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="login_email">Email</label>
                    <input type="email" id="login_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="login_password">Password</label>
                    <input type="password" id="login_password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="login">Login</button>
                </div>
            </form>
            
            <div class="toggle-form">
                Belum punya akun? <a onclick="toggleForm()">Daftar di sini</a>
                <p style="margin-top: 1rem; font-size: 0.85rem; opacity: 0.9;">
                    Demo: admin@sehat-sentosa.com / admin123
                </p>
            </div>
        </div>

        <!-- Register Form -->
        <div class="auth-form form-hidden" id="registerForm">
            <h2>Daftar Akun</h2>
            
            <?php if (!empty($error) && isset($_POST['register'])): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="reg_name">Nama Lengkap</label>
                    <input type="text" id="reg_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="reg_email">Email</label>
                    <input type="email" id="reg_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="reg_phone">Nomor Telepon</label>
                    <input type="tel" id="reg_phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="reg_password">Password</label>
                    <input type="password" id="reg_password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="reg_confirm_password">Konfirmasi Password</label>
                    <input type="password" id="reg_confirm_password" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="register">Daftar</button>
                </div>
            </form>
            
            <div class="toggle-form">
                Sudah punya akun? <a onclick="toggleForm()">Login di sini</a>
            </div>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <h3>🏥 Selamat Datang</h3>
            <p>Rumah Sakit Sehat Sentosa menyediakan layanan kesehatan terbaik dengan teknologi modern dan dokter profesional.</p>
            
            <h4>✨ Fitur Unggulan Kami:</h4>
            <div class="feature">Booking appointment dengan dokter spesialis</div>
            <div class="feature">Riwayat medical record lengkap dan terintegrasi</div>
            <div class="feature">Layanan emergency 24/7 siap siaga</div>
            <div class="feature">Konsultasi dengan dokter profesional bersertifikat</div>
            <div class="feature">Sistem pembayaran yang aman dan terpercaya</div>
            
            <div class="info-note">
                💡 <strong>Akses Demo:</strong> Email: admin@sehat-sentosa.com | Password: admin123
                <br>Untuk mengakses fitur admin, gunakan akun admin yang telah tersedia.
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Rumah Sakit Sehat Sentosa. Semua hak dilindungi.</p>
    </footer>

    <script>
        function toggleForm() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            loginForm.classList.toggle('form-hidden');
            registerForm.classList.toggle('form-hidden');
        }
    </script>
</body>
</html>
