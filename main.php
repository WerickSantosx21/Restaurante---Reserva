
<?php
require_once __DIR__ . '/conexao.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Início | Restaurante</title>

    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>

    <!-- MENU LATERAL -->
    <aside class="sidebar">

        <div class="logo">
            <span class="logo-icone">♨</span>
            <h2>Restaurante</h2>
            <p>Sabor em cada momento</p>
        </div>

        <nav>
            <a href="main.php" class="ativo">
                <span>⌂</span> Início
            </a>

            <a href="reservas.php">
                <span>▣</span> Reservar mesa
            </a>

            <a href="minhas-reservas.php">
                <span>✓</span> Minhas reservas
            </a>

            <a href="cardapio.php">
                <span>♨</span> Cardápio
            </a>

            <a href="logout.php">
                <span>⇥</span> Sair
            </a>
        </nav>

        <div class="sidebar-rodape">
            Boa comida,<br>
            boas histórias!
        </div>

    </aside>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="conteudo">

        <header class="topo">
            <div>
                <h1>Página inicial</h1>
                <p>Bem-vindo ao nosso restaurante!</p>
            </div>
        </header>

        <!-- BANNER PRINCIPAL -->
        <section class="banner">

            <div class="banner-conteudo">

                <p class="subtitulo">SEJA BEM-VINDO</p>

                <h2>
                    Uma experiência especial
                    espera por você.
                </h2>

                <p>
                    Escolha seu prato favorito e reserve sua mesa.
                </p>

                <a href="reservas.php" class="botao">
                    Reservar agora
                    <span>→</span>
                </a>

            </div>

        </section>

        <!-- CARTÕES DE ACESSO RÁPIDO -->
        <section class="atalhos">

            <a href="reservas.php" class="cartao">

                <img src="img/mesa.jpg" alt="Mesa preparada para jantar">

                <div class="cartao-conteudo">
                    <h3>Reservar mesa</h3>
                    <p>Escolha a data e o horário da sua visita.</p>
                    <span class="acessar">Acessar →</span>
                </div>

            </a>

            <a href="cardapio.php" class="cartao">

                <img src="img/cardapio.jpg" alt="Prato especial do restaurante">

                <div class="cartao-conteudo">
                    <h3>Nosso cardápio</h3>
                    <p>Conheça os pratos disponíveis.</p>
                    <span class="acessar">Acessar →</span>
                </div>

            </a>

            <a href="minhas-reservas.php" class="cartao">

                <img src="img/reserva.jpg" alt="Agenda de reservas">

                <div class="cartao-conteudo">
                    <h3>Minhas reservas</h3>
                    <p>Consulte suas reservas realizadas.</p>
                    <span class="acessar">Acessar →</span>
                </div>

            </a>

        </section>

        <!-- DIFERENCIAIS DO RESTAURANTE -->
        <section class="diferenciais">

            <div>
                <span>♨</span>
                <h3>Pratos selecionados</h3>
                <p>Ingredientes frescos e de qualidade.</p>
            </div>

            <div>
                <span>❧</span>
                <h3>Ambiente aconchegante</h3>
                <p>O lugar perfeito para bons momentos.</p>
            </div>

            <div>
                <span>♢</span>
                <h3>Atendimento especial</h3>
                <p>Nossa equipe está pronta para receber você.</p>
            </div>

            <div>
                <span>☆</span>
                <h3>Experiência única</h3>
                <p>Mais que uma refeição, um momento especial.</p>
            </div>

        </section>

        <!-- RODAPÉ -->
        <footer class="rodape">
            Restaurante • Boa comida, boas histórias.
        </footer>

    </main>

</body>
</html>
