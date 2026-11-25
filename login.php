
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$banco = "restaurante";
$usuario_bco = "root";
$senha_bco = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8",
        $usuario_bco,
        $senha_bco
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("ERRO CONEXÃO BANCO: " . $e->getMessage());
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['password'] ?? '';

    if (!empty($email) && !empty($senha)) {

        $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {

            $senhaBanco = $usuario['password'];

            
            $senhaValida = password_verify($senha, $senhaBanco);

           
            if (!$senhaValida && hash_equals($senhaBanco, md5($senha))) {

                $senhaValida = true;

               
                $novoHash = password_hash($senha, PASSWORD_DEFAULT);

                $update = $pdo->prepare(
                    "UPDATE usuarios SET password = :senha
                     WHERE idusuario = :id"
                );

                $update->execute([
                    ':senha' => $novoHash,
                    ':id' => $usuario['idusuario']
                ]);
            }

            if ($senhaValida) {

                session_regenerate_id(true);

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
        $erro = "Por favor, preencha todos os campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/login.css">

    <title>Login - Sistema de Reserva</title>

    <style>
        
    </style>
</head>

<body>

    <div class="login-container">

        <h2>Entrar no Sistema</h2>

        <p class="subtitle">Reserva de Restaurante</p>

        <?php if (!empty($erro)): ?>
            <div class="error-box">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">

            <div class="input-group">
                <label for="email">E-mail</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="seuemail@email.com"
                >
            </div>

            <div class="input-group">
                <label for="password">Senha</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="••••••••"
                >
            </div>

            <button type="submit" class="btn-submit">
                Acessar Painel
            </button>

            <div class="signup-link">
                Não tem uma conta?
                <a href="cadastro.php">Cadastre-se</a>
            </div>

        </form>

    </div>

</body>
</html>
