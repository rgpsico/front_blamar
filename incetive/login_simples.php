<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$defaultUser = 'admin';
$defaultPass = '123456';

$error = '';
$redirect = isset($_GET['redirect']) ? trim((string)$_GET['redirect']) : 'venues/venues.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = isset($_POST['login']) ? trim((string)$_POST['login']) : '';
    $senha = isset($_POST['senha']) ? trim((string)$_POST['senha']) : '';
    $redirectPost = isset($_POST['redirect']) ? trim((string)$_POST['redirect']) : 'venues/venues.php';

    if ($login === $defaultUser && $senha === $defaultPass) {
        $_SESSION['user'] = 1;
        $_SESSION['login'] = $login;
        header('Location: ' . $redirectPost);
        exit;
    }

    $error = 'Login ou senha invalidos.';
    $redirect = $redirectPost;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login simples</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f5f7;
            color: #222;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            width: 100%;
            max-width: 360px;
            background: #fff;
            border: 1px solid #d9e0e6;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
        }
        h1 {
            margin: 0 0 8px;
            font-size: 22px;
        }
        p {
            margin: 0 0 16px;
            font-size: 13px;
            color: #4d5c67;
        }
        label {
            display: block;
            font-size: 13px;
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            height: 38px;
            border: 1px solid #cfd8df;
            border-radius: 6px;
            padding: 0 10px;
            margin-bottom: 12px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            height: 40px;
            border: 0;
            border-radius: 6px;
            background: #123650;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
        }
        .error {
            background: #ffe9e9;
            color: #9f2222;
            border: 1px solid #ffcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 13px;
            margin-bottom: 12px;
        }
        .hint {
            margin-top: 12px;
            font-size: 12px;
            color: #6a7882;
        }
        .hint code {
            background: #eef2f5;
            padding: 2px 4px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <form class="card" method="post" action="">
        <h1>Login</h1>
        <p>Pagina simples para autenticar sessao do incentivo.</p>

        <?php if ($error !== '') : ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8'); ?>">

        <label for="login">Login</label>
        <input id="login" name="login" type="text" autocomplete="username" required>

        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" autocomplete="current-password" required>

        <button type="submit">Entrar</button>

        <div class="hint">
            Credenciais: <code>admin</code> / <code>123456</code>
        </div>
    </form>
</body>
</html>
