<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$banco = "restaurante"; 
$usuario_bco = "root";              
$senha_bco = "";              

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8", $usuario_bco, $senha_bco);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERRO CONEXÃO BANCO: " . $e->getMessage());
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['password'] ?? '';

    if(!empty($email) && !empty($senha)) {
        $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            if(md5($senha) === $usuario['password']) {
                $_SESSION['idusuario'] = $usuario['idusuario'];
                $_SESSION['name'] = $usuario['name'];
                header('Location: main.php');
                exit;
            } else {
                $erro = "E-mail ou senha incorretos.";
            }
        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    } else {
        $erro = 'Por favor, preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Reserva</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1e1e2f 0%, #11111d 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }

        .login-container {
            background: #1f1f31;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .login-container h2 {
            margin-bottom: 8px;
            font-weight: 600;
            color: #fff;
            text-align: center;
        }

        .login-container p.subtitle {
            color: #8a8aa8;
            font-size: 14px;
            text-align: center;
            margin-bottom: 24px;
        }

        .error-box {
            background: rgba(255, 75, 75, 0.1);
            color: #ff4b4b;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 75, 75, 0.2);
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #cbcbde;
        }

        .input-group input {
            width: 100%;
            padding: 12px 16px;
            background: #141423;
            border: 1px solid #2e2e48;
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #0069d9;
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        /* ESTILO NOVO: Link de cadastro centralizado e elegante */
        .signup-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #8a8aa8;
        }

        .signup-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .signup-link a:hover {
            color: #3395ff;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Entrar no Sistema</h2>
        <p class="subtitle">Reserva de Restaurante</p>
        
        <?php if (!empty($erro)): ?>
            <div class="error-box">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="input-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required placeholder="seuemail@email.com">
            </div>
            
            <div class="input-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn-submit">Acessar Painel</button>
            
            <!-- LINK NOVO ADICIONADO AQUI -->
            <div class="signup-link">
                Não tem uma conta? <a href="cadastro.php">Cadastre-se</a>
            </div>
        </form>
    </div>

</body>
</html>
