<?php
// Ativa exibição de erros para facilitar o desenvolvimento
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// Função auxiliar para formatar moeda
function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. DATA ATUAL: Vamos focar no mês corrente
    $mesAtual = date('m');
    $anoAtual = date('Y');
    $nomeMes = date('F / Y');

    // 2. BUSCAR TOTAIS DO BANCO DE DADOS
    
    // Total Realizado (Somente o que está 'pago')
    $sqlReal = "SELECT 
                    SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas,
                    SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas
                FROM transacoes 
                WHERE status = 'pago' 
                AND MONTH(data_transacao) = :mes AND YEAR(data_transacao) = :ano";
    
    $stmtReal = $db->prepare($sqlReal);
    $stmtReal->execute(['mes' => $mesAtual, 'ano' => $anoAtual]);
    $dadosReal = $stmtReal->fetch();

    $entradasReal = $dadosReal['entradas'] ?? 0;
    $saidasReal = $dadosReal['saidas'] ?? 0;
    $saldoReal = $entradasReal - $saidasReal;

    // Total Previsto (Tudo: 'pago' + 'pendente')
    $sqlPrevisto = "SELECT 
                        SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas,
                        SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas
                    FROM transacoes 
                    WHERE MONTH(data_transacao) = :mes AND YEAR(data_transacao) = :ano";
    
    $stmtPrevisto = $db->prepare($sqlPrevisto);
    $stmtPrevisto->execute(['mes' => $mesAtual, 'ano' => $anoAtual]);
    $dadosPrevisto = $stmtPrevisto->fetch();

    $entradasPrevisto = $dadosPrevisto['entradas'] ?? 0;
    $saidasPrevisto = $dadosPrevisto['saidas'] ?? 0;
    $saldoPrevisto = $entradasPrevisto - $saidasPrevisto;

} catch(PDOException $e) {
    die("Erro ao carregar dashboard: " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<section class="destaque">
    <div class="container">
        <h2>Mês de Referência: <?php echo $nomeMes; ?></h2>
    </div>
</section>

<main class="container">
    
    <h3 style="margin-bottom: 15px; color: #666;">Saldo Realizado <small>(O que já foi pago/recebido)</small></h3>
    <section class="grade-projetos">
        <article class="cartao-projeto border-entrada">
            <h3>Entradas Reais</h3>
            <p class="valor-positivo"><?php echo formatarMoeda($entradasReal); ?></p>
        </article>

        <article class="cartao-projeto border-saida">
            <h3>Saídas Reais</h3>
            <p class="valor-negativo"><?php echo formatarMoeda($saidasReal); ?></p>
        </article>

        <article class="cartao-projeto card-saldo-total" style="background-color: #e8f5e9; border-left: 5px solid #28a745;">
            <h3>Saldo em Conta</h3>
            <p class="valor-saldo"><?php echo formatarMoeda($saldoReal); ?></p>
        </article>
    </section>

    <hr style="margin: 40px 0; border: 0; border-top: 1px solid #ddd;">

    <h3 style="margin-bottom: 15px; color: #666;">Saldo Previsto <small>(Considerando pendências)</small></h3>
    <section class="grade-projetos">
        <article class="cartao-projeto" style="border-left: 5px solid #ccc;">
            <h3>Total Planejado</h3>
            <p style="font-size: 1.2rem; font-weight: 700;">Entradas: <span class="valor-positivo"><?php echo formatarMoeda($entradasPrevisto); ?></span></p>
            <p style="font-size: 1.2rem; font-weight: 700;">Saídas: <span class="valor-negativo"><?php echo formatarMoeda($saidasPrevisto); ?></span></p>
        </article>

        <article class="cartao-projeto card-saldo-total">
            <h3>Saldo Final Previsto</h3>
            <p class="valor-saldo" style="color: #00a2ed;"><?php echo formatarMoeda($saldoPrevisto); ?></p>
        </article>
    </section>

    <section class="acoes-rapidas" style="margin-top: 40px;">
        <div class="grade-projetos">
            <a href="transacoes.php" class="botao btn-sucesso" style="text-align: center;">+ Novo Lançamento</a>
            <a href="categorias.php" class="botao" style="text-align: center; background-color: #6c757d; border-color: #6c757d;">Gerenciar Categorias</a>
        </div>
    </section>

</main>

<?php require_once 'includes/footer.php'; ?>