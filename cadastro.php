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
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['password'] ?? '';

    if (!empty($nome) && !empty($email) && !empty($senha)) {
        // Verifica se o e-mail já está cadastrado no sistema
        $sql_check = "SELECT idusuario FROM usuarios WHERE email = :email LIMIT 1";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindValue(':email', $email);
        $stmt_check->execute();
        
        if ($stmt_check->fetch()) {
            $erro = "Este e-mail já está cadastrado no sistema.";
        } else {
            // Insere o novo usuário gravando a senha em MD5 e definindo situação como 1 (Ativo)
            $sql = "INSERT INTO usuarios (name, email, password, situação) VALUES (:name, :email, MD5(:password), 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':name', $nome);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':password', $senha);
            
            if ($stmt->execute()) {
                $sucesso = "Conta criada com sucesso! Redirecionando...";
                // Redireciona para o login depois de 2 segundos
                header("Refresh: 2; url=login.php");
            } else {
                $erro = "Ocorreu um erro ao registrar sua conta.";
            }
        }
    } else {
        $erro = "Por favor, preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema de Reserva</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #11111d 0%, #1e1e2f 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
        }

        .register-container {
            background: #191929; /* Painel sutilmente diferente e mais escuro que o login */
            padding: 40px;
            border-radius: 16px; /* Bordas um pouco mais arredondadas */
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 440px; /* Um pouco mais largo para acomodar melhor os campos */
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .register-container h2 {
            margin-bottom: 8px;
            font-weight: 600;
            color: #fff;
            text-align: center;
        }

        .register-container p.subtitle {
            color: #8a8aa8;
            font-size: 14px;
            text-align: center;
            margin-bottom: 28px;
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

        .success-box {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid rgba(40, 167, 69, 0.2);
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .input-group {
            margin-bottom: 18px;
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
            background: #11111d;
            border: 1px solid #282842;
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            outline: none;
            border-color: #28a745; /* Destaque em verde na digitação do cadastro */
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25);
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: #28a745; /* Botão verde indicando criação/sucesso */
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
            margin-top: 10px;
        }

        .btn-register:hover {
            background: #218838;
        }

        .btn-register:active {
            transform: scale(0.98);
        }

        .login-link {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #8a8aa8;
        }

        .login-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .login-link a:hover {
            color: #3395ff;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="register-container">
        <h2>Criar Nova Conta</h2>
        <p class="subtitle">Insira os dados para acessar o sistema</p>
        
        <?php if (!empty($erro)): ?>
            <div class="error-box">
                <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="success-box">
                <?php echo htmlspecialchars($sucesso); ?>
            </div>
        <?php endif; ?>

        <form action="cadastro.php" method="POST">
            <div class="input-group">
                <label for="name">Nome Completo</label>
                <input type="text" id="name" name="name" required placeholder="Ex: João Silva">
            </div>

            <div class="input-group">
                <label for="email">E-mail corporativo</label>
                <input type="email" id="email" name="email" required placeholder="exemplo@email.com">
            </div>
            
            <div class="input-group">
                <label for="password">Defina uma Senha</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn-register">Finalizar Cadastro</button>
            
            <div class="login-link">
                Já possui uma conta? <a href="login.php">Fazer Login</a>
            </div>
        </form>
    </div>

</body>
</html>
