<?php
// Futuramente: require_once 'config/database.php';

// Simulação de dados para visualização
$mesCorrente = date('F / Y'); 
$entradas = 2500.00;
$saidas = 1250.50;
$saldo = $entradas - $saidas;

function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}

// 1. Carrega o cabeçalho
require_once 'includes/header.php';
?>

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

<?php
// 2. Carrega o rodapé
require_once 'includes/footer.php';
?>