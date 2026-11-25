<?php
session_start();
require_once __DIR__ . '/conexao.php';

if (!isset($_SESSION['idusuario'])) {
    header('Location: login.php');
    exit;
}

$idusuario = (int) $_SESSION['idusuario'];
$mensagem = '';
$tipoMensagem = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function h($valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function validarDataReserva(string $data): bool {
    $objeto = DateTime::createFromFormat('!Y-m-d', $data);
    return $objeto && $objeto->format('Y-m-d') === $data && $data >= date('Y-m-d');
}

// Processa cancelamento ou alteração da reserva.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenEnviado = $_POST['csrf_token'] ?? '';
    $acao = $_POST['acao'] ?? '';
    $idreserva = (int) ($_POST['idreserva'] ?? 0);

    if (!hash_equals($csrfToken, $tokenEnviado)) {
        $mensagem = 'Sua sessão de segurança expirou. Atualize a página e tente novamente.';
        $tipoMensagem = 'erro';
    } elseif ($idreserva <= 0) {
        $mensagem = 'Reserva inválida.';
        $tipoMensagem = 'erro';
    } else {
        try {
            // Sempre confirma que a reserva pertence ao usuário logado.
            $stmtReserva = $pdo->prepare("SELECT * FROM reservas WHERE idreserva = :idreserva AND idusuario = :idusuario LIMIT 1");
            $stmtReserva->execute([':idreserva' => $idreserva, ':idusuario' => $idusuario]);
            $reservaAtual = $stmtReserva->fetch(PDO::FETCH_ASSOC);

            if (!$reservaAtual) {
                throw new Exception('Reserva não encontrada.');
            }
            if ($reservaAtual['status'] === 'Cancelada') {
                throw new Exception('Essa reserva já foi cancelada e não pode ser alterada.');
            }
            if ($reservaAtual['data_reserva'] < date('Y-m-d')) {
                throw new Exception('Não é possível alterar ou cancelar uma reserva que já passou.');
            }

            if ($acao === 'cancelar') {
                $stmtCancelar = $pdo->prepare("UPDATE reservas SET status = 'Cancelada' WHERE idreserva = :idreserva AND idusuario = :idusuario");
                $stmtCancelar->execute([':idreserva' => $idreserva, ':idusuario' => $idusuario]);
                $mensagem = 'Reserva cancelada com sucesso.';
                $tipoMensagem = 'sucesso';
            } elseif ($acao === 'alterar') {
                $data = trim($_POST['data_reserva'] ?? '');
                $pessoas = (int) ($_POST['quantidade_pessoas'] ?? 0);
                $idmesa = (int) ($_POST['idmesa'] ?? 0);
                $idhorario = (int) ($_POST['idhorario'] ?? 0);

                if (!validarDataReserva($data) || $pessoas < 1 || $pessoas > 6 || $idmesa <= 0 || $idhorario <= 0) {
                    throw new Exception('Confira a data, a quantidade de pessoas, a mesa e o horário.');
                }

                $pdo->beginTransaction();

                // Bloqueia a reserva que está sendo editada.
                $stmtLock = $pdo->prepare("SELECT idreserva FROM reservas WHERE idreserva = :idreserva AND idusuario = :idusuario AND status <> 'Cancelada' FOR UPDATE");
                $stmtLock->execute([':idreserva' => $idreserva, ':idusuario' => $idusuario]);
                if (!$stmtLock->fetch()) {
                    throw new Exception('A reserva não está mais disponível para alteração.');
                }

                $stmtMesa = $pdo->prepare("SELECT capacidade FROM mesas WHERE idmesa = :idmesa AND status = 'Disponível' FOR UPDATE");
                $stmtMesa->execute([':idmesa' => $idmesa]);
                $mesa = $stmtMesa->fetch(PDO::FETCH_ASSOC);
                if (!$mesa) {
                    throw new Exception('A mesa escolhida não está disponível.');
                }
                if ($pessoas > (int) $mesa['capacidade']) {
                    throw new Exception('Essa mesa não comporta a quantidade de pessoas informada.');
                }

                $stmtHorario = $pdo->prepare("SELECT idhorario FROM horarios WHERE idhorario = :idhorario AND status = 'Disponível'");
                $stmtHorario->execute([':idhorario' => $idhorario]);
                if (!$stmtHorario->fetch()) {
                    throw new Exception('O horário escolhido não está disponível.');
                }

                // Verifica conflito com outra reserva, ignorando a própria reserva.
                $stmtConflito = $pdo->prepare("SELECT idreserva FROM reservas WHERE idmesa = :idmesa AND idhorario = :idhorario AND data_reserva = :data AND status <> 'Cancelada' AND idreserva <> :idreserva LIMIT 1 FOR UPDATE");
                $stmtConflito->execute([
                    ':idmesa' => $idmesa,
                    ':idhorario' => $idhorario,
                    ':data' => $data,
                    ':idreserva' => $idreserva
                ]);
                if ($stmtConflito->fetch()) {
                    throw new Exception('Essa mesa já está reservada nessa data e horário. Escolha outra combinação.');
                }

                $stmtUpdate = $pdo->prepare("UPDATE reservas SET idmesa = :idmesa, idhorario = :idhorario, data_reserva = :data, quantidade_pessoas = :pessoas WHERE idreserva = :idreserva AND idusuario = :idusuario");
                $stmtUpdate->execute([
                    ':idmesa' => $idmesa,
                    ':idhorario' => $idhorario,
                    ':data' => $data,
                    ':pessoas' => $pessoas,
                    ':idreserva' => $idreserva,
                    ':idusuario' => $idusuario
                ]);

                $pdo->commit();
                $mensagem = 'Alterações salvas! Sua reserva foi atualizada.';
                $tipoMensagem = 'sucesso';
            } else {
                throw new Exception('Ação não reconhecida.');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $mensagem = $e instanceof PDOException
                ? 'Não foi possível concluir a operação. Verifique os dados e tente novamente.'
                : $e->getMessage();
            $tipoMensagem = 'erro';
            if ($e instanceof PDOException) {
                error_log($e->getMessage());
            }
        }
    }
}

// Carrega reserva para edição, se o usuário clicou em Alterar.
$reservaEdicao = null;
$idEdicao = (int) ($_GET['editar'] ?? 0);
if ($idEdicao > 0) {
    $stmtEdicao = $pdo->prepare("SELECT r.*, m.numero_mesa FROM reservas r INNER JOIN mesas m ON m.idmesa = r.idmesa WHERE r.idreserva = :idreserva AND r.idusuario = :idusuario LIMIT 1");
    $stmtEdicao->execute([':idreserva' => $idEdicao, ':idusuario' => $idusuario]);
    $reservaEdicao = $stmtEdicao->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$reservaEdicao && !$mensagem) {
        $mensagem = 'Reserva não encontrada.';
        $tipoMensagem = 'erro';
    } elseif ($reservaEdicao && ($reservaEdicao['status'] === 'Cancelada' || $reservaEdicao['data_reserva'] < date('Y-m-d'))) {
        $reservaEdicao = null;
        $mensagem = 'Essa reserva não pode mais ser alterada.';
        $tipoMensagem = 'erro';
    }
}

$horarios = $pdo->query("SELECT idhorario, horario FROM horarios WHERE status = 'Disponível' ORDER BY horario")->fetchAll(PDO::FETCH_ASSOC);

$mesasEdicao = [];
if ($reservaEdicao) {
    $stmtMesas = $pdo->prepare("SELECT idmesa, numero_mesa, capacidade FROM mesas WHERE status = 'Disponível' AND capacidade >= :pessoas ORDER BY numero_mesa");
    $stmtMesas->execute([
        ':pessoas' => (int) $reservaEdicao['quantidade_pessoas']
    ]);
    $mesasEdicao = $stmtMesas->fetchAll(PDO::FETCH_ASSOC);

    // Garante que a mesa atual apareça mesmo se a disponibilidade mudou depois da reserva.
    $idsMesas = array_column($mesasEdicao, 'idmesa');
    if (!in_array($reservaEdicao['idmesa'], $idsMesas)) {
        $stmtMesaAtual = $pdo->prepare("SELECT idmesa, numero_mesa, capacidade FROM mesas WHERE idmesa = :idmesa");
        $stmtMesaAtual->execute([':idmesa' => $reservaEdicao['idmesa']]);
        $mesaAtual = $stmtMesaAtual->fetch(PDO::FETCH_ASSOC);
        if ($mesaAtual) array_unshift($mesasEdicao, $mesaAtual);
    }
}

$stmtLista = $pdo->prepare("SELECT r.idreserva, r.data_reserva, r.quantidade_pessoas, r.status, r.idmesa, r.idhorario, m.numero_mesa, m.capacidade, h.horario FROM reservas r INNER JOIN mesas m ON m.idmesa = r.idmesa INNER JOIN horarios h ON h.idhorario = r.idhorario WHERE r.idusuario = :idusuario ORDER BY r.data_reserva DESC, h.horario DESC, r.idreserva DESC");
$stmtLista->execute([':idusuario' => $idusuario]);
$reservas = $stmtLista->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas reservas | Restaurante</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#171725;color:#f5f6fb;font-family:Arial,Helvetica,sans-serif;min-height:100vh}.sidebar{width:250px;position:fixed;inset:0 auto 0 0;background:#202032;border-right:1px solid #343448;padding:28px 20px;display:flex;flex-direction:column;z-index:5}.logo{text-align:center;padding:8px 0 28px;border-bottom:1px solid #38384c}.logo-icone{font-size:30px;color:#3983f4}.logo h2{font-size:22px;margin:14px 0 7px}.logo p{font-size:12px;color:#9ca3bd;margin:0}.menu-titulo{font-size:11px;letter-spacing:1.2px;color:#818ba9;font-weight:bold;margin:28px 0 14px}.sidebar nav{display:grid;gap:8px}.sidebar nav a{display:flex;align-items:center;gap:15px;padding:17px 15px;border-radius:9px;color:#e6e8f2;text-decoration:none;font-size:15px;line-height:1.4}.sidebar nav a span{width:18px;text-align:center;color:#b9c8e7;font-size:18px}.sidebar nav a:hover,.sidebar nav a.ativo{background:#347ee8;color:white}.sidebar nav a.ativo span{color:white}.menu-secundario{margin-top:30px}.sidebar-rodape{margin-top:auto;color:#aab0c6;font-size:12px;padding:15px 0 0}.status-online{display:inline-block;width:9px;height:9px;border-radius:50%;background:#31bd83;margin-right:8px}.conteudo{margin-left:250px;padding:48px 40px;max-width:1500px}.topo{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:32px}.subtitulo{font-size:11px;letter-spacing:1.5px;color:#3987ff;font-weight:bold}.topo h1{font-size:30px;margin:15px 0 9px}.topo p{color:#aeb5ca;margin:0;font-size:14px}.topo-status{color:#aeb5ca;font-size:12px;white-space:nowrap}.painel{background:#202032;border:1px solid #39394e;border-radius:13px;padding:25px;margin-bottom:24px}.painel-cabecalho{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:22px}.painel h2{font-size:19px;margin:0 0 8px}.painel .descricao{font-size:13px;color:#aeb5ca;margin:0}.contador{background:#2b3d5b;color:#8bbaff;border-radius:20px;padding:8px 12px;font-size:12px;white-space:nowrap}.mensagem{padding:14px 17px;border-radius:9px;margin:0 0 22px;font-size:14px}.mensagem.sucesso{background:#173d35;border:1px solid #246c58;color:#8be0bd}.mensagem.erro{background:#45252d;border:1px solid #79404c;color:#ffb1bd}.lista-reservas{display:grid;gap:15px}.reserva-card{background:#252538;border:1px solid #3b3b50;border-radius:11px;padding:20px;display:grid;grid-template-columns:1fr auto;gap:18px;align-items:center}.reserva-card.cancelada{opacity:.68}.reserva-info{display:grid;grid-template-columns:repeat(4,minmax(100px,1fr));gap:18px}.info-label{display:block;color:#929bb5;font-size:11px;margin-bottom:8px}.info-valor{font-size:15px;font-weight:600;color:#f5f6fb}.status{display:inline-block;padding:7px 11px;border-radius:20px;font-size:11px;font-weight:bold}.status.confirmada{background:#173d35;color:#72d9b1}.status.cancelada{background:#45252d;color:#ff9eaa}.acoes{display:flex;gap:9px;justify-content:flex-end;flex-wrap:wrap}.botao{border:0;border-radius:8px;padding:10px 14px;font-size:12px;font-weight:bold;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}.botao-editar{background:#347ee8;color:white}.botao-cancelar{background:#482832;color:#ffb3bd}.botao-confirmar{background:#347ee8;color:#fff;padding:12px 19px}.botao-voltar{background:#343448;color:#e2e5ef}.vazio{text-align:center;padding:42px 20px;color:#aeb5ca}.vazio strong{display:block;color:#f5f6fb;font-size:17px;margin-bottom:9px}.form-edicao{margin-top:20px;border-top:1px solid #3b3b50;padding-top:22px}.campos{display:grid;grid-template-columns:repeat(4,minmax(130px,1fr));gap:15px;margin:18px 0}.campo label{display:block;font-size:12px;color:#aeb5ca;margin-bottom:8px}.campo input,.campo select{width:100%;background:#191927;border:1px solid #45455d;color:#f5f6fb;padding:12px;border-radius:8px;font-size:14px;outline:none}.campo input:focus,.campo select:focus{border-color:#3987ff}.acoes-form{display:flex;gap:10px;justify-content:flex-end}.aviso{font-size:12px;color:#aeb5ca;margin:0 0 14px}.rodape{text-align:center;color:#737d99;font-size:11px;margin-top:32px}@media(max-width:1050px){.reserva-info{grid-template-columns:repeat(2,minmax(100px,1fr))}.campos{grid-template-columns:repeat(2,minmax(130px,1fr))}}@media(max-width:760px){.sidebar{position:relative;width:100%;height:auto;padding:15px}.logo{padding-bottom:15px}.sidebar nav{grid-template-columns:repeat(2,1fr)}.menu-titulo,.sidebar-rodape{display:none}.conteudo{margin-left:0;padding:28px 16px}.topo{align-items:flex-start}.topo-status{display:none}.reserva-card{grid-template-columns:1fr}.acoes{justify-content:flex-start}.campos{grid-template-columns:1fr}.painel{padding:18px}.painel-cabecalho{align-items:flex-start}}
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="logo"><span class="logo-icone">♨</span><h2>Restaurante</h2><p>Sistema de Reservas</p></div>
    <div class="menu-titulo">MENU PRINCIPAL</div>
    <nav>
        <a href="main.php"><span>⌂</span>Início</a>
        <a href="reservas.php"><span>▣</span>Reservar mesa</a>
        <a href="minhas-reservas.php" class="ativo"><span>✓</span>Minhas reservas</a>
        <a href="cardapio.php"><span>♨</span>Gerenciamento de cardápio</a>
    </nav>
    <div class="menu-titulo menu-secundario">CONTA</div>
    <nav><a href="logout.php"><span>⇥</span>Sair do sistema</a></nav>
    <div class="sidebar-rodape"><span class="status-online"></span>Sistema operacional</div>
</aside>

<main class="conteudo">
    <header class="topo">
        <div><span class="subtitulo">ÁREA DO CLIENTE</span><h1>Minhas reservas</h1><p>Acompanhe, altere ou cancele suas reservas.</p></div>
        <div class="topo-status"><span class="status-online"></span>Sistema ativo</div>
    </header>

    <?php if ($mensagem): ?><div class="mensagem <?= h($tipoMensagem) ?>"><?= h($mensagem) ?></div><?php endif; ?>

    <section class="painel">
        <div class="painel-cabecalho">
            <div><h2>Histórico de reservas</h2><p class="descricao">Aqui aparecem as reservas feitas pela sua conta.</p></div>
            <span class="contador"><?= count($reservas) ?> reserva(s)</span>
        </div>

        <?php if (!$reservas): ?>
            <div class="vazio"><strong>Você ainda não possui reservas.</strong>Quando fizer uma reserva, ela aparecerá aqui.<br><br><a class="botao botao-editar" href="reservas.php">Reservar uma mesa</a></div>
        <?php else: ?>
            <div class="lista-reservas">
                <?php foreach ($reservas as $r):
                    $podeAlterar = $r['status'] !== 'Cancelada' && $r['data_reserva'] >= date('Y-m-d');
                ?>
                    <article class="reserva-card <?= $r['status'] === 'Cancelada' ? 'cancelada' : '' ?>">
                        <div class="reserva-info">
                            <div><span class="info-label">RESERVA Nº</span><span class="info-valor">#<?= (int)$r['idreserva'] ?></span></div>
                            <div><span class="info-label">DATA</span><span class="info-valor"><?= date('d/m/Y', strtotime($r['data_reserva'])) ?></span></div>
                            <div><span class="info-label">HORÁRIO</span><span class="info-valor"><?= h(substr($r['horario'], 0, 5)) ?></span></div>
                            <div><span class="info-label">MESA</span><span class="info-valor">Mesa <?= h($r['numero_mesa']) ?></span></div>
                            <div><span class="info-label">PESSOAS</span><span class="info-valor"><?= (int)$r['quantidade_pessoas'] ?> pessoa(s)</span></div>
                            <div><span class="info-label">CAPACIDADE DA MESA</span><span class="info-valor"><?= (int)$r['capacidade'] ?> lugares</span></div>
                            <div><span class="info-label">STATUS</span><span class="status <?= $r['status'] === 'Cancelada' ? 'cancelada' : 'confirmada' ?>"><?= h($r['status']) ?></span></div>
                        </div>
                        <div class="acoes">
                            <?php if ($podeAlterar): ?>
                                <a class="botao botao-editar" href="minhas-reservas.php?editar=<?= (int)$r['idreserva'] ?>">Alterar</a>
                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja cancelar esta reserva?');">
                                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                    <input type="hidden" name="idreserva" value="<?= (int)$r['idreserva'] ?>">
                                    <input type="hidden" name="acao" value="cancelar">
                                    <button class="botao botao-cancelar" type="submit">Cancelar</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <?php if ($reservaEdicao && (int)$reservaEdicao['idreserva'] === (int)$r['idreserva']): ?>
                            <form class="form-edicao" method="POST" action="minhas-reservas.php">
                                <h2>Alterar reserva #<?= (int)$r['idreserva'] ?></h2>
                                <p class="aviso">Escolha os novos dados e clique em “Confirmar alterações”. A disponibilidade será validada novamente ao salvar.</p>
                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                <input type="hidden" name="idreserva" value="<?= (int)$r['idreserva'] ?>">
                                <input type="hidden" name="acao" value="alterar">
                                <div class="campos">
                                    <div class="campo"><label for="data_reserva">Data</label><input type="date" id="data_reserva" name="data_reserva" min="<?= date('Y-m-d') ?>" value="<?= h($reservaEdicao['data_reserva']) ?>" required></div>
                                    <div class="campo"><label for="quantidade_pessoas">Quantidade de pessoas</label><select id="quantidade_pessoas" name="quantidade_pessoas" required><?php for ($i=1;$i<=6;$i++): ?><option value="<?= $i ?>" <?= (int)$reservaEdicao['quantidade_pessoas']===$i?'selected':'' ?>><?= $i ?> <?= $i===1?'pessoa':'pessoas' ?></option><?php endfor; ?></select></div>
                                    <div class="campo"><label for="idhorario">Horário</label><select id="idhorario" name="idhorario" required><?php foreach ($horarios as $h): ?><option value="<?= (int)$h['idhorario'] ?>" <?= (int)$reservaEdicao['idhorario']===(int)$h['idhorario']?'selected':'' ?>><?= h(substr($h['horario'],0,5)) ?></option><?php endforeach; ?></select></div>
                                    <div class="campo"><label for="idmesa">Mesa disponível</label><select id="idmesa" name="idmesa" required><?php foreach ($mesasEdicao as $m): ?><option value="<?= (int)$m['idmesa'] ?>" <?= (int)$reservaEdicao['idmesa']===(int)$m['idmesa']?'selected':'' ?>>Mesa <?= h($m['numero_mesa']) ?> — <?= (int)$m['capacidade'] ?> lugares</option><?php endforeach; ?></select></div>
                                </div>
                                <div class="acoes-form"><a class="botao botao-voltar" href="minhas-reservas.php">Voltar</a><button class="botao botao-confirmar" type="submit">Confirmar alterações</button></div>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <footer class="rodape">Sistema de Reservas • Restaurante</footer>
</main>
</body>
</html>
