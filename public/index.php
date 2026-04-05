<?php
// Futuramente: require_once '../config/database.php';

// Simulação de dados para visualização
$mesCorrente = date('F / Y'); 
$entradas = 2500.00;
$saidas = 1250.50;
$saldo = $entradas - $saidas;

function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NogLabs Finanças - com GIT</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css">
</head>
<body id="home">

    <header class="navbar-fixa">
        <div class="navbar-topo">
            <a href="index.php" class="logo">NogLabs Finanças</a>
            <button id="botao-menu" class="botao-menu">&#9776;</button>
        </div>
        
        <nav class="menu" id="menu-links">
            <a href="index.php">Dashboard</a>
            <a href="#">Transações</a>
            <a href="#">Configurações</a>
        </nav>
    </header>

    <section class="destaque">
        <div class="container">
            <h2>Mês de Referência: <?php echo $mesCorrente; ?></h2>
        </div>
    </section>

    <main class="container">
        
        <section class="grade-projetos">
            <article class="cartao-projeto border-entrada">
                <h3>Total Entradas</h3>
                <p class="valor-positivo"><?php echo formatarMoeda($entradas); ?></p>
            </article>

            <article class="cartao-projeto border-saida">
                <h3>Total Saídas</h3>
                <p class="valor-negativo"><?php echo formatarMoeda($saidas); ?></p>
            </article>
        </section>

        <section class="saldo-container">
            <article class="cartao-projeto card-saldo-total">
                <h3>Saldo Atual</h3>
                <p class="valor-saldo"><?php echo formatarMoeda($saldo); ?></p>
            </article>
        </section>

        <section class="acoes-rapidas">
            <div class="grade-projetos">
                <a href="#" class="botao btn-sucesso">+ Receita</a>
                <a href="#" class="botao btn-perigo">- Despesa</a>
            </div>
        </section>

    </main>

    <footer class="rodape">
        <p>Finanças NogLabs &copy; 2026</p>
    </footer>

    <script>
        const botaoMenu = document.getElementById('botao-menu');
        const menuLinks = document.getElementById('menu-links');
        
        botaoMenu.addEventListener('click', () => menuLinks.classList.toggle('ativo'));
        
        document.querySelectorAll('.menu a').forEach(link => {
            link.addEventListener('click', () => menuLinks.classList.remove('ativo'));
        });
    </script>
</body>
</html>