
<?php
require_once __DIR__ . '/conexao.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Painel inicial | Sistema Restaurante</title>

    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>

    <!-- MENU LATERAL -->
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

            <a href="main.php" class="ativo">
                <span>⌂</span>
                Início
            </a>

            <a href="reservas.php">
                <span>▣</span>
                Reservar mesa
            </a>

            <a href="minhas-reservas.php">
                <span>✓</span>
                Minhas reservas
            </a>

            <a href="cardapio.php">
                <span>♨</span>
                Cardápio
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


    <!-- CONTEÚDO PRINCIPAL -->
    <main class="conteudo">

        <!-- CABEÇALHO -->
        <header class="topo">

            <div class="topo-texto">

                <span class="subtitulo">
                    PAINEL ADMINISTRATIVO
                </span>

                <h1>Painel inicial</h1>

                <p>
                    Bem-vindo ao sistema de gerenciamento do restaurante.
                </p>

            </div>

            <div class="topo-status">
                <span class="status-online"></span>
                Sistema ativo
            </div>

        </header>


        <!-- MENSAGEM DE BOAS-VINDAS -->
        <section class="painel-boas-vindas">

            <div class="boas-vindas-texto">

                <span>ÁREA DE GERENCIAMENTO</span>

                <h2>
                    Gerencie seu restaurante
                    em um só lugar.
                </h2>

                <p>
                    Acesse os módulos do sistema para realizar reservas,
                    consultar seus agendamentos e visualizar o cardápio.
                </p>

                <a href="reservas.php" class="botao-principal">
                    Nova reserva
                    <span>→</span>
                </a>

            </div>

            <div class="boas-vindas-icone">
                ♨
            </div>

        </section>


        <!-- MÓDULOS DO SISTEMA -->
        <section class="secao-modulos">

            <div class="secao-cabecalho">

                <div>
                    <h2>Módulos do sistema</h2>

                    <p>
                        Selecione uma funcionalidade para continuar.
                    </p>
                </div>

            </div>


            <div class="grade-modulos">


                <!-- MÓDULO DE RESERVAS -->
                <a href="reservas.php" class="modulo">

                    <div class="modulo-icone azul">
                        ▣
                    </div>

                    <div class="modulo-conteudo">

                        <h3>Reservar mesa</h3>

                        <p>
                            Consulte a disponibilidade e escolha
                            uma mesa para sua reserva.
                        </p>

                    </div>

                    <span class="modulo-acessar">
                        Acessar módulo →
                    </span>

                </a>


                <!-- MÓDULO MINHAS RESERVAS -->
                <a href="minhas-reservas.php" class="modulo">

                    <div class="modulo-icone roxo">
                        ✓
                    </div>

                    <div class="modulo-conteudo">

                        <h3>Minhas reservas</h3>

                        <p>
                            Consulte e acompanhe as reservas
                            cadastradas no sistema.
                        </p>

                    </div>

                    <span class="modulo-acessar">
                        Acessar módulo →
                    </span>

                </a>


                <!-- MÓDULO CARDÁPIO -->
                <a href="cardapio.php" class="modulo">

                    <div class="modulo-icone verde">
                        ♨
                    </div>

                    <div class="modulo-conteudo">

                        <h3>Cardápio</h3>

                        <p>
                            Consulte os produtos e os pratos
                            disponíveis no restaurante.
                        </p>

                    </div>

                    <span class="modulo-acessar">
                        Acessar módulo →
                    </span>

                </a>


            </div>

        </section>


        <!-- INFORMAÇÕES DO SISTEMA -->
        <section class="informacoes-sistema">

            <div class="informacao-cabecalho">
                <h2>Informações do sistema</h2>
                <span>VISÃO GERAL</span>
            </div>

            <div class="informacao-conteudo">

                <div class="informacao-item">

                    <span class="informacao-icone">▣</span>

                    <div>
                        <strong>Reservas</strong>
                        <p>Gerenciamento de agendamentos</p>
                    </div>

                </div>


                <div class="informacao-item">

                    <span class="informacao-icone">♨</span>

                    <div>
                        <strong>Produtos</strong>
                        <p>Consulta dos itens do cardápio</p>
                    </div>

                </div>


                <div class="informacao-item">

                    <span class="informacao-icone">✓</span>

                    <div>
                        <strong>Controle</strong>
                        <p>Organização das informações</p>
                    </div>

                </div>

            </div>

        </section>


        <!-- RODAPÉ -->
        <footer class="rodape">

            <span>Sistema de Reservas • Restaurante</span>

            <span>Gerenciamento de restaurante</span>

        </footer>

    </main>

</body>

</html>
