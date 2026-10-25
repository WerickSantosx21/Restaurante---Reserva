<?php
require_once __DIR__. '/conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['password'] ?? '';
}

if(!empty($email) && !empty($senha)) {
    $sql = "SELECT * FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if($usuario && password_verify($senha, $usuario['password'])) {
        $_SESSION['idusuario'] = $usuario['idusuario'];
        $_SESSION['name'] = $usuario['name'];
        header('Location: main.php');
        exit;
    } else {
        $erro = 'E-mail ou senha incorretos.';
    }

} else {
    $erro = 'Por favor, preencha todos os campos.';

}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Reserva de Restaurante</title>
    <link rel="stylesheet" href="assets/index.css">
</head>
<body>
    <div class="login-container" style="max-width: 400px; margin: 50px auto; padding: 20px; font-family: sans-serif;">
        <h2>Entrar no Sistema</h2>
        
        <?php if (!empty($erro)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($erro); ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div style="margin-bottom: 15px;">
                <label for="email" style="display: block; margin-bottom: 5px;">E-mail:</label>
                <input type="email" id="email" name="email" required style="width: 100%; padding: 8px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="password" style="display: block; margin-bottom: 5px;">Senha:</label>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 8px;">
            </div>
            
            <button type="submit" style="padding: 10px 15px; background: #007bff; color: white; border: none; cursor: pointer;">Entrar</button>
        </form>
    </div>
</body>
</html>