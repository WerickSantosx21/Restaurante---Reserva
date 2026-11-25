
<?php
session_start();

require_once __DIR__ . '/conexao.php';

if (!isset($_SESSION['idusuario'])) {
    header('Location: login.php');
    exit;
}

// Buscar mesas disponíveis no banco
$sqlMesas = "SELECT * FROM mesas
             WHERE status = 'Disponível'
             ORDER BY numero_mesa";

$stmtMesas = $pdo->prepare($sqlMesas);
$stmtMesas->execute();
$mesas = $stmtMesas->fetchAll(PDO::FETCH_ASSOC);


// Buscar horários disponíveis no banco
$sqlHorarios = "SELECT * FROM horarios
                WHERE status = 'Disponível'
                ORDER BY horario";

$stmtHorarios = $pdo->prepare($sqlHorarios);
$stmtHorarios->execute();
$horarios = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reservas | Sistema Restaurante</title>

    <link rel="stylesheet" href="assets/css/reservas.css">
</head>

<body>

    <!-- MENU LATERAL -->
    <aside class="sidebar">

        <div class="logo">
            <span class="logo-icone">♨</span>
            <h2>Restaurante</h2>
            <p>Sistema de Reservas</p>
        </div>

        <nav>

            <a href="main.php">
                <span>⌂</span>
                Início
            </a>

            <a href="reservas.php" class="ativo">
                <span>▣</span>
                Reservar mesa
            </a>

            <a href="minhas-reservas.php">
                <span>✓</span>
                Minhas reservas
            </a>

            <a href="cardapio.php">
                <span>♨</span>
                Gerenciamento de cardapio
            </a>

            <a href="logout.php">
                <span>⇥</span>
                Sair
            </a>

        </nav>

        <div class="sidebar-rodape">
            Sistema de<br>
            gerenciamento
        </div>

    </aside>


    <!-- CONTEÚDO PRINCIPAL -->
    <main class="conteudo">

        <!-- CABEÇALHO -->
        <header class="topo">

            <div>
                <span class="subtitulo">PAINEL ADMINISTRATIVO</span>
                <h1>Gestão de reservas</h1>
                <p>Gerencie os horários e a disponibilidade das mesas.</p>
            </div>

        </header>


        <!-- FILTROS DA RESERVA -->
        <section class="formulario-reserva">

            <div class="titulo-painel">
                <div>
                    <h2>Dados da reserva</h2>
                    <p>Informe os detalhes para consultar as mesas.</p>
                </div>
            </div>

            <div class="campos-reserva">

                <div class="campo-formulario">

                    <label for="data">Data da reserva</label>

                    <input
                        type="date"
                        id="data"
                        name="data"
                        required
                    >

                </div>


                <div class="campo-formulario">

                    <label for="pessoas">Quantidade de pessoas</label>

                    <select id="pessoas" name="pessoas" required>

                        <option value="">Selecione</option>

                        <option value="1">1 pessoa</option>
                        <option value="2">2 pessoas</option>
                        <option value="3">3 pessoas</option>
                        <option value="4">4 pessoas</option>
                        <option value="5">5 pessoas</option>
                        <option value="6">6 pessoas</option>

                    </select>

                </div>


                <div class="campo-formulario">

                    <label for="horario">Horário da reserva</label>

                    <select id="horario" name="horario" required>

                        <option value="">Selecione</option>

                        <option value="18:00">18:00</option>
                        <option value="19:00">19:00</option>
                        <option value="20:00">20:00</option>
                        <option value="21:00">21:00</option>

                    </select>

                </div>

            </div>

        </section>


        <!-- PAINEL DE ESCOLHA DAS MESAS -->
        <section class="area-mesas">

            <div class="titulo-painel">

                <div>
                    <h2>Disponibilidade das mesas</h2>
                    <p>Selecione uma mesa para realizar a reserva.</p>
                </div>

                <span class="total-mesas">08 mesas cadastradas</span>

            </div>


            <!-- LEGENDA -->
            <div class="legenda-mesas">

                <div class="legenda-item">
                    <span class="indicador disponivel"></span>
                    Disponível
                </div>

                <div class="legenda-item">
                    <span class="indicador selecionada"></span>
                    Selecionada
                </div>

                <div class="legenda-item">
                    <span class="indicador ocupada"></span>
                    Ocupada
                </div>

            </div>


            <!-- PLANTA DO RESTAURANTE -->
            <div class="planta-restaurante">

                <div class="planta-cabecalho">
                    <span>PLANTA DO RESTAURANTE</span>
                    <span>ÁREA DE ATENDIMENTO</span>
                </div>


                <div class="grade-mesas">

                    <button type="button" class="mesa disponivel" data-capacidade="2">
                        <span class="mesa-numero">01</span>
                        <span class="mesa-nome">Mesa 01</span>
                        <small>2 lugares</small>
                    </button>


                    <button type="button" class="mesa disponivel" data-capacidade="4">
                        <span class="mesa-numero">02</span>
                        <span class="mesa-nome">Mesa 02</span>
                        <small>4 lugares</small>
                    </button>


                    <button type="button" class="mesa disponivel" data-capacidade="6">
                        <span class="mesa-numero">03</span>
                        <span class="mesa-nome">Mesa 03</span>
                        <small>6 lugares</small>
                    </button>


                    <button type="button" class="mesa disponivel" data-capacidade="2">
                        <span class="mesa-numero">04</span>
                        <span class="mesa-nome">Mesa 04</span>
                        <small>2 lugares</small>
                    </button>


                    <button type="button" class="mesa disponivel" data-capacidade="4">
                        <span class="mesa-numero">05</span>
                        <span class="mesa-nome">Mesa 05</span>
                        <small>4 lugares</small>
                    </button>


                    <button type="button" class="mesa disponivel" data-capacidade="6">
                        <span class="mesa-numero">06</span>
                        <span class="mesa-nome">Mesa 06</span>
                        <small>6 lugares</small>
                    </button>


                    <button type="button" class="mesa disponivel" data-capacidade="4">
                        <span class="mesa-numero">07</span>
                        <span class="mesa-nome">Mesa 07</span>
                        <small>4 lugares</small>
                    </button>


                    <button type="button" class="mesa disponivel" data-capacidade="2">
                        <span class="mesa-numero">08</span>
                        <span class="mesa-nome">Mesa 08</span>
                        <small>2 lugares</small>
                    </button>

                </div>

            </div>


            <!-- RESUMO DA SELEÇÃO -->
            <div class="resumo-selecao">

                <div>
                    <span class="resumo-label">Mesa selecionada</span>
                    <strong id="mesa-selecionada">Nenhuma mesa selecionada</strong>
                </div>

                <div>
                    <span class="resumo-label">Capacidade</span>
                    <strong id="capacidade-mesa">-</strong>
                </div>

            </div>

        </section>

    </main>

<script>
    // Captura todas as mesas disponíveis.
    const mesas = document.querySelectorAll('.mesa.disponivel');

    // Elementos que mostram o resumo da seleção.
    const mesaSelecionada = document.getElementById('mesa-selecionada');
    const capacidadeMesa = document.getElementById('capacidade-mesa');

    // Guarda a mesa escolhida.
    let mesaAtual = null;

    mesas.forEach(function(mesa) {

        mesa.addEventListener('click', function() {

            // Remove a seleção anterior.
            mesas.forEach(function(item) {
                item.classList.remove('selecionada');
            });

            // Seleciona a mesa clicada.
            mesa.classList.add('selecionada');

            // Guarda o número e a capacidade.
            mesaAtual = mesa.querySelector('.mesa-numero').textContent;
            const capacidade = mesa.dataset.capacidade;

            // Atualiza o resumo na tela.
            mesaSelecionada.textContent = mesaAtual;
            capacidadeMesa.textContent = capacidade + ' lugares';

        });

    });
</script>
</body>

</body>

</html>
