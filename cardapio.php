
<?php
session_start();
require_once __DIR__ . '/conexao.php';

// TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function voltar($mensagem = '') {
    header('Location: cardapio.php' . ($mensagem ? '?msg=' . $mensagem : ''));
    exit;
}

$pastaImagens = __DIR__ . '/uploads/cardapio/';

if (!is_dir($pastaImagens)) {
    mkdir($pastaImagens, 0755, true);
}

$mensagem = '';

// PROCESSAMENTO DOS FORMULARIOS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // VALIDAR CSRF
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Ação não autorizada.');
    }

    $acao = $_POST['acao'] ?? '';

    // EXCLUIR PRATO
    if ($acao === 'excluir') {

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            voltar('erro_id');
        }

        $stmt = $pdo->prepare(
            "SELECT imagem FROM cardapio_itens WHERE id = ?"
        );
        $stmt->execute([$id]);

        $prato = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prato) {
            voltar('nao_encontrado');
        }

        $stmt = $pdo->prepare(
            "DELETE FROM cardapio_itens WHERE id = ?"
        );
        $stmt->execute([$id]);

        // EXCLUIR IMAGEM
        $imagem = $prato['imagem'] ?? '';

        if (strpos($imagem, 'uploads/cardapio/') === 0) {

            $arquivo = substr($imagem, strlen('uploads/cardapio/'));

            if ($arquivo !== '' && basename($arquivo) === $arquivo) {

                $caminho = $pastaImagens . $arquivo;

                if (is_file($caminho)) {
                    unlink($caminho);
                }
            }
        }

        voltar('excluido');
    }

    // ALTERNAR DISPONIBILIDADE
    if ($acao === 'alternar') {

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id || $id < 1) {
            voltar('erro_id');
        }

        $stmt = $pdo->prepare("
            UPDATE cardapio_itens
            SET disponivel = CASE
                WHEN disponivel = 1 THEN 0
                ELSE 1
            END
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        voltar('status');
    }

    // CADASTRAR OU EDITAR
    if ($acao === 'salvar') {

        $id = $_POST['id'] ?? '';
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $preco = str_replace(',', '.', $_POST['preco'] ?? '');

        if (
            $nome === '' ||
            $categoria === '' ||
            !is_numeric($preco) ||
            (float)$preco < 0
        ) {
            $mensagem = 'Preencha os campos corretamente.';
        } else {

            $imagem = '';

            // RECUPERAR IMAGEM ANTIGA NA EDICAO
            if ($id !== '') {

                $stmt = $pdo->prepare(
                    "SELECT imagem FROM cardapio_itens WHERE id = ?"
                );
                $stmt->execute([(int)$id]);

                $registro = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$registro) {
                    $mensagem = 'Prato não encontrado.';
                } else {
                    $imagem = $registro['imagem'] ?? '';
                }
            }

            // UPLOAD
            if (
                $mensagem === '' &&
                isset($_FILES['imagem']) &&
                $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {

                    $mensagem = 'Erro no envio da imagem.';

                } elseif ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {

                    $mensagem = 'A imagem deve ter no máximo 5 MB.';

                } else {

                    $info = getimagesize($_FILES['imagem']['tmp_name']);
                    $mime = $info['mime'] ?? '';

                    $tipos = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp'
                    ];

                    if ($info === false || !isset($tipos[$mime])) {

                        $mensagem = 'Imagem inválida. Use JPG, PNG ou WEBP.';

                    } else {

                        $extensao = $tipos[$mime];
                        $novoNome = bin2hex(random_bytes(16)) . '.' . $extensao;

                        if (move_uploaded_file(
                            $_FILES['imagem']['tmp_name'],
                            $pastaImagens . $novoNome
                        )) {

                            $imagemNova = 'uploads/cardapio/' . $novoNome;

                            // EXCLUIR IMAGEM ANTIGA
                            if ($imagem && strpos($imagem, 'uploads/cardapio/') === 0) {

                                $nomeAntigo = substr($imagem, strlen('uploads/cardapio/'));

                                if ($nomeAntigo !== '' && basename($nomeAntigo) === $nomeAntigo) {
                                    $caminhoAntigo = $pastaImagens . $nomeAntigo;

                                    if (is_file($caminhoAntigo)) {
                                        unlink($caminhoAntigo);
                                    }
                                }
                            }

                            $imagem = $imagemNova;

                        } else {
                            $mensagem = 'Erro ao salvar a imagem.';
                        }
                    }
                }
            }

            // SALVAR NO BANCO
            if ($mensagem === '') {

                if ($id !== '') {

                    $stmt = $pdo->prepare("
                        UPDATE cardapio_itens
                        SET nome = ?, descricao = ?, categoria = ?, preco = ?, imagem = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $nome,
                        $descricao,
                        $categoria,
                        $preco,
                        $imagem,
                        (int)$id
                    ]);

                    voltar('atualizado');

                } else {

                    $stmt = $pdo->prepare("
                        INSERT INTO cardapio_itens
                        (nome, descricao, categoria, preco, imagem)
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $nome,
                        $descricao,
                        $categoria,
                        $preco,
                        $imagem
                    ]);

                    voltar('cadastrado');
                }
            }
        }
    }
}

// MENSAGENS
$mensagens = [
    'excluido' => 'Prato excluído com sucesso!',
    'atualizado' => 'Prato atualizado com sucesso!',
    'cadastrado' => 'Prato cadastrado com sucesso!',
    'status' => 'Disponibilidade atualizada!',
    'erro_id' => 'Identificador inválido.',
    'nao_encontrado' => 'Prato não encontrado.'
];

if (isset($_GET['msg'], $mensagens[$_GET['msg']])) {
    $mensagem = $mensagens[$_GET['msg']];
}

// BUSCAR PRATO PARA EDICAO
$editar = null;

if (isset($_GET['editar'])) {

    $idEditar = filter_var($_GET['editar'], FILTER_VALIDATE_INT);

    if ($idEditar && $idEditar > 0) {

        $stmt = $pdo->prepare(
            "SELECT * FROM cardapio_itens WHERE id = ?"
        );

        $stmt->execute([$idEditar]);

        $editar = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// LISTAR PRATOS
$stmt = $pdo->query(
    "SELECT * FROM cardapio_itens ORDER BY id DESC"
);

$pratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categorias = [
    'Entradas',
    'Pratos principais',
    'Massas',
    'Sobremesas',
    'Bebidas'
];
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="assets/css/cardapio.css">

<title>Gerenciar Cardápio | Aurora Restaurante</title>
</head>

<body>

<div class="container">

    <h1>Gerenciamento do Cardápio</h1>

    <p class="subtitulo">
        Cadastre, edite e controle os pratos do Aurora Restaurante.
    </p>

    <?php if ($mensagem): ?>
        <div class="mensagem">
            <?= e($mensagem) ?>
        </div>
    <?php endif; ?>

    <div class="painel">

        <h2>
            <?= $editar ? 'Editar prato' : 'Cadastrar novo prato' ?>
        </h2>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="acao" value="salvar">

            <input type="hidden" name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>">

            <input type="hidden" name="id"
                value="<?= e($editar['id'] ?? '') ?>">

            <div class="form-grid">

                <div class="campo">
                    <label>Nome do prato</label>
                    <input type="text" name="nome"
                        placeholder="Ex: Filé à parmegiana"
                        value="<?= e($editar['nome'] ?? '') ?>" required>
                </div>

                <div class="campo">
                    <label>Categoria</label>

                    <select name="categoria" required>
                        <option value="">Selecione</option>

                        <?php foreach ($categorias as $categoria): ?>

                            <option value="<?= e($categoria) ?>"
                                <?= ($editar['categoria'] ?? '') === $categoria ? 'selected' : '' ?>>
                                <?= e($categoria) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="campo">
                    <label>Preço (R$)</label>

                    <input type="number" name="preco"
                        step="0.01" min="0"
                        placeholder="39.90"
                        value="<?= e($editar['preco'] ?? '') ?>" required>
                </div>

                <div class="campo">
                    <label>Foto do prato</label>

                    <input type="file" name="imagem"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                </div>

                <div class="campo full">
                    <label>Descrição</label>

                    <textarea name="descricao"
                        placeholder="Descreva os ingredientes e detalhes do prato..."><?= e($editar['descricao'] ?? '') ?></textarea>
                </div>

            </div>

            <br>

            <button type="submit">
                <?= $editar ? 'Salvar alterações' : 'Cadastrar prato' ?>
            </button>

            <?php if ($editar): ?>
                <a href="cardapio.php" class="botao cancelar">
                    Cancelar
                </a>
            <?php endif; ?>

        </form>

    </div>

    <div class="painel">

        <h2>Pratos cadastrados</h2>

        <table class="tabela">

            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach ($pratos as $prato): ?>

                <tr>

                    <td>
                        <?php if ($prato['imagem']): ?>

                            <img class="foto"
                                src="<?= e($prato['imagem']) ?>"
                                alt="<?= e($prato['nome']) ?>">

                        <?php else: ?>
                            Sem foto
                        <?php endif; ?>
                    </td>

                    <td><?= e($prato['nome']) ?></td>

                    <td><?= e($prato['categoria']) ?></td>

                    <td>
                        R$ <?= number_format($prato['preco'], 2, ',', '.') ?>
                    </td>

                    <td>
                        <?php if ($prato['disponivel']): ?>
                            <span class="ativo">Disponível</span>
                        <?php else: ?>
                            <span class="inativo">Indisponível</span>
                        <?php endif; ?>
                    </td>

                    <td>

                        <div class="acoes">

                            <a class="botao editar"
                                href="cardapio.php?editar=<?= (int)$prato['id'] ?>">
                                Editar
                            </a>

                            <form method="POST" style="display:inline;">

                                <input type="hidden" name="acao" value="alternar">

                                <input type="hidden" name="csrf_token"
                                    value="<?= e($_SESSION['csrf_token']) ?>">

                                <input type="hidden" name="id"
                                    value="<?= (int)$prato['id'] ?>">

                                <button type="submit" class="botao disponibilidade">
                                    <?= $prato['disponivel'] ? 'Desativar' : 'Ativar' ?>
                                </button>

                            </form>

                            <button
                                type="button"
                                class="botao excluir"
                                onclick='abrirModalExcluir(
                                    <?= (int)$prato['id'] ?>,
                                    <?= json_encode($prato['nome']) ?>
                                )'>
                                Excluir
                            </button>

                        </div>

                    </td>

                </tr>

            <?php endforeach; ?>

            <?php if (empty($pratos)): ?>
                <tr>
                    <td colspan="6">Nenhum prato cadastrado.</td>
                </tr>
            <?php endif; ?>

            </tbody>

        </table>

    </div>

    <a href="main.php" class="botao">Voltar ao painel</a>

</div>

<!-- MODAL DE EXCLUSAO -->

<div id="modalExcluir" class="modal-overlay">

    <div class="modal-caixa">

        <div class="modal-icone">!</div>

        <h2>Excluir prato?</h2>

        <p>
            Tem certeza que deseja excluir
            <strong id="nomePratoExcluir"></strong>?
        </p>

        <p class="aviso-modal">
            Essa ação não poderá ser desfeita.
        </p>

        <form method="POST" action="cardapio.php">

            <input type="hidden" name="acao" value="excluir">

            <input type="hidden" name="csrf_token"
                value="<?= e($_SESSION['csrf_token']) ?>">

            <input type="hidden" name="id"
                id="idPratoExcluir">

            <div class="modal-botoes">

                <button type="button"
                    class="botao cancelar"
                    onclick="fecharModalExcluir()">
                    Cancelar
                </button>

                <button type="submit" class="botao excluir">
                    Sim, excluir
                </button>

            </div>

        </form>

    </div>

</div>

<script>

function abrirModalExcluir(id, nome) {

    document.getElementById('idPratoExcluir').value = id;

    document.getElementById('nomePratoExcluir').textContent = nome;

    document.getElementById('modalExcluir').style.display = 'flex';
}

function fecharModalExcluir() {

    document.getElementById('modalExcluir').style.display = 'none';
}

document.getElementById('modalExcluir').addEventListener('click', function(event) {

    if (event.target === this) {
        fecharModalExcluir();
    }

});

document.addEventListener('keydown', function(event) {

    if (event.key === 'Escape') {
        fecharModalExcluir();
    }

});

</script>

</body>
</html>
