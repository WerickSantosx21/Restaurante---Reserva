

<?php



session_start();

require_once __DIR__ . '/conexao.php';



// Verifica se está logado.

if (!isset($_SESSION['idusuario'])) {

    header('Location: login.php');

    exit;

}



// ======================================

// CONSULTAR DISPONIBILIDADE DAS MESAS

// ======================================



if (

    $_SERVER['REQUEST_METHOD'] === 'GET' &&

    isset($_GET['acao']) &&

    $_GET['acao'] === 'disponibilidade'

) {

    header('Content-Type: application/json; charset=utf-8');



    $data = $_GET['data'] ?? '';

    $idhorario = (int) ($_GET['idhorario'] ?? 0);



    $dataObjeto = DateTime::createFromFormat('!Y-m-d', $data);



    if (

        !$dataObjeto ||

        $dataObjeto->format('Y-m-d') !== $data ||

        $idhorario <= 0

    ) {

        http_response_code(400);



        echo json_encode([

            'sucesso' => false,

            'mensagem' => 'Data ou horário inválido.'

        ]);



        exit;

    }



    try {

        $sqlMesas = "

            SELECT

                m.idmesa,

                m.numero_mesa,

                m.capacidade,

                CASE

                    WHEN EXISTS (

                        SELECT 1

                        FROM reservas r

                        WHERE r.idmesa = m.idmesa

                        AND r.idhorario = :idhorario

                        AND r.data_reserva = :data

                        AND r.status <> 'Cancelada'

                    )

                    THEN 1

                    ELSE 0

                END AS ocupada

            FROM mesas m

            WHERE m.status = 'Disponível'

            ORDER BY m.numero_mesa ASC

        ";



        $stmtMesas = $pdo->prepare($sqlMesas);



        $stmtMesas->execute([

            ':idhorario' => $idhorario,

            ':data' => $data

        ]);



        $mesas = $stmtMesas->fetchAll(PDO::FETCH_ASSOC);



        echo json_encode([

            'sucesso' => true,

            'mesas' => $mesas

        ]);



    } catch (PDOException $e) {

        error_log($e->getMessage());



        http_response_code(500);



        echo json_encode([

            'sucesso' => false,

            'mensagem' => 'Erro ao consultar as mesas.'

        ]);

    }



    exit;

}



$mensagem = '';

$tipoMensagem = '';



// ======================================

// CADASTRAR RESERVA

// ======================================



if ($_SERVER['REQUEST_METHOD'] === 'POST') {



    $data = $_POST['data_reserva'] ?? '';

    $pessoas = (int) ($_POST['quantidade_pessoas'] ?? 0);

    $idhorario = (int) ($_POST['idhorario'] ?? 0);

    $idmesa = (int) ($_POST['idmesa'] ?? 0);



    $idusuario = (int) $_SESSION['idusuario'];



    $dataObjeto = DateTime::createFromFormat('!Y-m-d', $data);



    if (

        !$dataObjeto ||

        $dataObjeto->format('Y-m-d') !== $data ||

        $pessoas <= 0 ||

        $idhorario <= 0 ||

        $idmesa <= 0

    ) {

        $mensagem = 'Preencha todos os campos e selecione uma mesa.';

        $tipoMensagem = 'erro';



    } elseif ($data < date('Y-m-d')) {



        $mensagem = 'Não é possível reservar uma data passada.';

        $tipoMensagem = 'erro';



    } else {



        try {



            $pdo->beginTransaction();



            // Verifica se a mesa existe e está disponível.

            $sqlMesa = "

                SELECT capacidade

                FROM mesas

                WHERE idmesa = :idmesa

                AND status = 'Disponível'

                FOR UPDATE

            ";



            $stmtMesa = $pdo->prepare($sqlMesa);



            $stmtMesa->execute([

                ':idmesa' => $idmesa

            ]);



            $mesa = $stmtMesa->fetch(PDO::FETCH_ASSOC);



            if (!$mesa) {

                throw new Exception('Mesa não encontrada ou indisponível.');

            }



            // Confere a capacidade.

            if ($pessoas > (int) $mesa['capacidade']) {

                throw new Exception(

                    'Quantidade de pessoas maior que a capacidade da mesa.'

                );

            }



            // Confere o horário.

            $sqlHorario = "

                SELECT idhorario

                FROM horarios

                WHERE idhorario = :idhorario

                AND status = 'Disponível'

            ";



            $stmtHorario = $pdo->prepare($sqlHorario);



            $stmtHorario->execute([

                ':idhorario' => $idhorario

            ]);



            if (!$stmtHorario->fetch()) {

                throw new Exception('Horário indisponível.');

            }



            // Verifica se a mesa já foi reservada.

            $sqlVerifica = "

                SELECT idreserva

                FROM reservas

                WHERE idmesa = :idmesa

                AND idhorario = :idhorario

                AND data_reserva = :data

                AND status <> 'Cancelada'

                LIMIT 1

                FOR UPDATE

            ";



            $stmtVerifica = $pdo->prepare($sqlVerifica);



            $stmtVerifica->execute([

                ':idmesa' => $idmesa,

                ':idhorario' => $idhorario,

                ':data' => $data

            ]);



            if ($stmtVerifica->fetch()) {

                throw new Exception(

                    'Essa mesa já está reservada nesse dia e horário. Escolha outra.'

                );

            }



            // Insere a reserva.

            $sqlInsert = "

                INSERT INTO reservas (

                    idusuario,

                    idmesa,

                    idhorario,

                    data_reserva,

                    quantidade_pessoas,

                    status,

                    data_criacao

                )

                VALUES (

                    :idusuario,

                    :idmesa,

                    :idhorario,

                    :data,

                    :pessoas,

                    'Confirmada',

                    NOW()

                )

            ";



            $stmtInsert = $pdo->prepare($sqlInsert);



            $stmtInsert->execute([

                ':idusuario' => $idusuario,

                ':idmesa' => $idmesa,

                ':idhorario' => $idhorario,

                ':data' => $data,

                ':pessoas' => $pessoas

            ]);



            $pdo->commit();



            $mensagem = 'Reserva cadastrada com sucesso!';

            $tipoMensagem = 'sucesso';



        } catch (PDOException $e) {



            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }



            error_log($e->getMessage());



            $mensagem = 'Erro ao cadastrar a reserva no banco de dados.';

            $tipoMensagem = 'erro';



        } catch (Exception $e) {



            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }



            $mensagem = $e->getMessage();

            $tipoMensagem = 'erro';

        }

    }

}



// ======================================

// CONSULTAR HORÁRIOS

// ======================================



$sqlHorarios = "

    SELECT *

    FROM horarios

    WHERE status = 'Disponível'

    ORDER BY horario

";



$stmtHorarios = $pdo->prepare($sqlHorarios);

$stmtHorarios->execute();



$horarios = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);



?>



<!DOCTYPE html>

<html lang="pt-br">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">



    <title>Reservar mesa | Restaurante</title>



    <link rel="stylesheet" href="assets/css/reservas.css">

</head>



<body>



    <aside class="sidebar">



        <div class="logo">

            <span class="logo-icone">♨</span>

            <h2>Restaurante</h2>

            <p>Sistema de Reservas</p>

        </div>



        <div class="menu-titulo">

            MENU PRINCIPAL

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



        </nav>



        <div class="menu-titulo menu-secundario">

            CONTA

        </div>



        <nav>

            <a href="logout.php">

                <span>⇥</span>

                Sair do sistema

            </a>

        </nav>



        <div class="sidebar-rodape">

            <span class="status-online"></span>

            Sistema operacional

        </div>



    </aside>



    <main class="conteudo">



        <header class="topo">



            <div>

                <span class="subtitulo">

                    GERENCIAMENTO DE RESERVAS

                </span>



                <h1>Reservar mesa</h1>



                <p>

                    Selecione os dados para realizar sua reserva.

                </p>

            </div>



            <div class="topo-status">

                <span class="status-online"></span>

                Sistema ativo

            </div>



        </header>



        <?php if (!empty($mensagem)): ?>



            <div class="mensagem <?= htmlspecialchars($tipoMensagem) ?>">

                <?= htmlspecialchars($mensagem) ?>

            </div>



        <?php endif; ?>



        <form action="reservas.php" method="POST" id="formReserva">



            <section class="formulario-reserva">



                <div class="titulo-painel">

                    <h2>Dados da reserva</h2>

                    <p>Informe os dados da sua visita.</p>

                </div>



                <div class="campos-reserva">



                    <div class="campo-formulario">



                        <label for="data">

                            Data da reserva

                        </label>



                        <input

                            type="date"

                            id="data"

                            name="data_reserva"

                            min="<?= date('Y-m-d') ?>"

                            required

                        >



                    </div>



                    <div class="campo-formulario">



                        <label for="pessoas">

                            Quantidade de pessoas

                        </label>



                        <select

                            id="pessoas"

                            name="quantidade_pessoas"

                            required

                        >



                            <option value="">Selecione</option>



                            <?php for ($i = 1; $i <= 6; $i++): ?>



                                <option value="<?= $i ?>">

                                    <?= $i ?>

                                    <?= $i == 1 ? 'pessoa' : 'pessoas' ?>

                                </option>



                            <?php endfor; ?>



                        </select>



                    </div>



                    <div class="campo-formulario">



                        <label for="horario">

                            Horário

                        </label>



                        <select

                            id="horario"

                            name="idhorario"

                            required

                        >



                            <option value="">Selecione um horário</option>



                            <?php foreach ($horarios as $horario): ?>



                                <option value="<?= htmlspecialchars($horario['idhorario']) ?>">

                                    <?= htmlspecialchars(substr($horario['horario'], 0, 5)) ?>

                                </option>



                            <?php endforeach; ?>



                        </select>



                    </div>



                </div>



            </section>



            <section class="area-mesas">



                <div class="titulo-painel">

                    <h2>Disponibilidade de mesas</h2>

                    <p>Selecione uma mesa para sua reserva.</p>

                </div>



                <div class="legenda-mesas">



                    <div class="item-legenda">

                        <span class="bolinha-legenda bolinha-verde"></span>

                        Disponível

                    </div>



                    <div class="item-legenda">

                        <span class="bolinha-legenda bolinha-amarela"></span>

                        Selecionada

                    </div>



                    <div class="item-legenda">

                        <span class="bolinha-legenda bolinha-vermelha"></span>

                        Ocupada

                    </div>



                </div>



                <div class="planta-restaurante">



                    <div class="planta-cabecalho">

                        <h3>Planta do restaurante</h3>

                        <span>Área de atendimento</span>

                    </div>



                    <div class="grade-mesas" id="grade-mesas">



                        <p class="aviso-sem-mesas">

                            Selecione uma data e um horário para consultar as mesas.

                        </p>



                    </div>



                </div>



                <div class="resumo-selecao">



                    <div>

                        <h3>Mesa selecionada</h3>



                        <strong id="mesa-selecionada">

                            Nenhuma mesa selecionada

                        </strong>

                    </div>



                    <div>

                        <h3>Capacidade</h3>

                        <strong id="capacidade-mesa">-</strong>

                    </div>



                </div>



                <input

                    type="hidden"

                    name="idmesa"

                    id="idmesa"

                    required

                >



                <button type="submit" class="botao-confirmar">

                    Confirmar reserva →

                </button>



            </section>



        </form>



        <footer class="rodape">

            Sistema de Reservas • Restaurante

        </footer>



    </main>



    <script>



        const campoData = document.getElementById('data');

        const campoHorario = document.getElementById('horario');

        const campoIdMesa = document.getElementById('idmesa');



        const gradeMesas = document.getElementById('grade-mesas');

        const mesaSelecionada = document.getElementById('mesa-selecionada');

        const capacidadeMesa = document.getElementById('capacidade-mesa');



        // Limpa a seleção atual.

        function limparSelecao() {



            campoIdMesa.value = '';



            mesaSelecionada.textContent = 'Nenhuma mesa selecionada';



            capacidadeMesa.textContent = '-';



        }



        // Consulta as mesas disponíveis.

        async function consultarMesas() {



            const data = campoData.value;

            const horario = campoHorario.value;



            limparSelecao();



            if (!data || !horario) {



                gradeMesas.innerHTML = `

                    <p class="aviso-sem-mesas">

                        Selecione uma data e um horário para consultar as mesas.

                    </p>

                `;



                return;

            }



            gradeMesas.innerHTML = `

                <p class="aviso-sem-mesas">

                    Consultando disponibilidade...

                </p>

            `;



            try {



                // Consulta a própria reservas.php.

                const url = new URL('reservas.php', window.location.href);



                url.searchParams.set('acao', 'disponibilidade');

                url.searchParams.set('data', data);

                url.searchParams.set('idhorario', horario);



                const resposta = await fetch(url.toString(), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-store'
                });

                // Lê como texto antes de converter para JSON, para revelar erros PHP/HTML.
                const textoResposta = await resposta.text();
                let resultado;

                try {
                    resultado = JSON.parse(textoResposta);
                } catch (erroJson) {
                    console.error('Resposta recebida do PHP (não é JSON válido):', textoResposta);
                    throw new Error('O servidor não retornou JSON válido. Verifique a resposta do PHP.');
                }



                if (!resposta.ok || !resultado.sucesso) {



                    throw new Error(

                        resultado.mensagem || 'Erro ao consultar mesas.'

                    );



                }

                if (!Array.isArray(resultado.mesas)) {
                    throw new Error('A resposta do servidor não contém a lista de mesas.');
                }



                gradeMesas.innerHTML = '';



                if (!resultado.mesas || resultado.mesas.length === 0) {



                    gradeMesas.innerHTML = `

                        <p class="aviso-sem-mesas">

                            Nenhuma mesa cadastrada está disponível.

                        </p>

                    `;



                    return;

                }



                resultado.mesas.forEach(function(mesa) {



                    const ocupada = Number(mesa.ocupada) === 1;



                    const botao = document.createElement('button');



                    botao.type = 'button';



                    botao.className = ocupada

                        ? 'mesa ocupada'

                        : 'mesa disponivel';



                    const numero = document.createElement('span');



                    numero.className = 'mesa-numero';



                    numero.textContent =

                        'Mesa ' + String(mesa.numero_mesa).padStart(2, '0');



                    const capacidade = document.createElement('strong');



                    capacidade.textContent = mesa.capacidade + ' lugares';



                    const status = document.createElement('small');



                    status.textContent = ocupada ? 'Ocupada' : 'Disponível';



                    botao.appendChild(numero);

                    botao.appendChild(capacidade);

                    botao.appendChild(status);



                    if (ocupada) {



                        botao.disabled = true;



                    } else {



                        botao.addEventListener('click', function() {



                            document.querySelectorAll('.mesa.selecionada')

                                .forEach(function(item) {

                                    item.classList.remove('selecionada');

                                });



                            botao.classList.add('selecionada');



                            campoIdMesa.value = mesa.idmesa;



                            mesaSelecionada.textContent = numero.textContent;



                            capacidadeMesa.textContent =

                                mesa.capacidade + ' lugares';



                        });



                    }



                    gradeMesas.appendChild(botao);



                });



            } catch (erro) {



                console.error('Erro ao consultar mesas:', erro);



                gradeMesas.innerHTML = `

                    <p class="aviso-sem-mesas">

                        Não foi possível consultar as mesas.

                        Confira o console do navegador.

                    </p>

                `;
            }

        }


        campoData.addEventListener('change', consultarMesas);


        campoHorario.addEventListener('change', consultarMesas);


        document.getElementById('formReserva')

            .addEventListener('submit', function(event) {
                if (!campoIdMesa.value) {
                    event.preventDefault();

                    alert('Selecione uma mesa disponível.');
                }
            });
    </script>



</body>

</html>
