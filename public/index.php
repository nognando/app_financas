<?php
// Ativa exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ',', '.');
}

// Array para traduzir os meses para Português
$mesesPt = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
    '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
    '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
    '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. LÓGICA DO FILTRO DE MÊS
    $competencia = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
    $dataBase = new DateTime($competencia . '-01');
    $mesAlvo = $dataBase->format('m');
    $anoAlvo = $dataBase->format('Y');
    
    $nomeMesExibicao = $mesesPt[$mesAlvo] . ' / ' . $anoAlvo;

    $dataAnterior = clone $dataBase;
    $dataAnterior->modify('-1 month');
    $linkAnterior = $dataAnterior->format('Y-m');

    $dataProxima = clone $dataBase;
    $dataProxima->modify('+1 month');
    $linkProximo = $dataProxima->format('Y-m');

    // 2. BUSCAR TOTAIS (BASEADOS NO MÊS ALVO)
    
    // Total Realizado (Pago)
    $sqlReal = "SELECT 
                    SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas,
                    SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas
                FROM transacoes 
                WHERE status = 'pago' AND MONTH(data_transacao) = :mes AND YEAR(data_transacao) = :ano";
    $stmtReal = $db->prepare($sqlReal);
    $stmtReal->execute(['mes' => $mesAlvo, 'ano' => $anoAlvo]);
    $dadosReal = $stmtReal->fetch();

    $entradasReal = $dadosReal['entradas'] ?? 0;
    $saidasReal = $dadosReal['saidas'] ?? 0;
    $saldoReal = $entradasReal - $saidasReal;

    // Total Previsto (Tudo)
    $sqlPrevisto = "SELECT 
                        SUM(CASE WHEN tipo = 'entrada' THEN valor ELSE 0 END) as entradas,
                        SUM(CASE WHEN tipo = 'saida' THEN valor ELSE 0 END) as saidas
                    FROM transacoes 
                    WHERE MONTH(data_transacao) = :mes AND YEAR(data_transacao) = :ano";
    $stmtPrevisto = $db->prepare($sqlPrevisto);
    $stmtPrevisto->execute(['mes' => $mesAlvo, 'ano' => $anoAlvo]);
    $dadosPrevisto = $stmtPrevisto->fetch();

    $entradasPrevisto = $dadosPrevisto['entradas'] ?? 0;
    $saidasPrevisto = $dadosPrevisto['saidas'] ?? 0;
    $saldoPrevisto = $entradasPrevisto - $saidasPrevisto;


    // ==========================================
    // 3. BUSCAR DADOS POR CATEGORIA (NOVO)
    // ==========================================
    
    // Despesas por Categoria
    $sqlDespesasCat = "SELECT c.nome, SUM(t.valor) as total
                       FROM transacoes t
                       JOIN categorias c ON t.categoria_id = c.id
                       WHERE t.tipo = 'saida' AND MONTH(t.data_transacao) = :mes AND YEAR(t.data_transacao) = :ano
                       GROUP BY c.nome
                       ORDER BY total DESC";
    $stmtDespesasCat = $db->prepare($sqlDespesasCat);
    $stmtDespesasCat->execute(['mes' => $mesAlvo, 'ano' => $anoAlvo]);
    $despesasPorCategoria = $stmtDespesasCat->fetchAll();

    // Receitas por Categoria
    $sqlReceitasCat = "SELECT c.nome, SUM(t.valor) as total
                       FROM transacoes t
                       JOIN categorias c ON t.categoria_id = c.id
                       WHERE t.tipo = 'entrada' AND MONTH(t.data_transacao) = :mes AND YEAR(t.data_transacao) = :ano
                       GROUP BY c.nome
                       ORDER BY total DESC";
    $stmtReceitasCat = $db->prepare($sqlReceitasCat);
    $stmtReceitasCat->execute(['mes' => $mesAlvo, 'ano' => $anoAlvo]);
    $receitasPorCategoria = $stmtReceitasCat->fetchAll();

} catch(PDOException $e) {
    die("Erro ao carregar dashboard: " . $e->getMessage());
}

require_once 'includes/header.php';
?>

<section class="destaque">
    <div class="container">
        <div class="navegacao-mes">
            <a href="index.php?mes=<?php echo $linkAnterior; ?>" class="botao-mes" title="Mês Anterior">&#10094;</a>
            <h2><?php echo $nomeMesExibicao; ?></h2>
            <a href="index.php?mes=<?php echo $linkProximo; ?>" class="botao-mes" title="Próximo Mês">&#10095;</a>
        </div>
    </div>
</section>

<main class="container">
    <a href="nova_transacao.php" class="botao btn-sucesso" style="text-align: center;">+ Nova Transação</a>
    <h3 style="margin-bottom: 15px; color: #666;">Saldo Realizado <small>(Pago/Recebido no mês)</small></h3>
    <section class="grade-projetos">
        <article class="cartao-projeto" style="border-left: 5px solid #28a745;">
            <h3>Total Realizado</h3>
            <p style="font-size: 1.2rem; font-weight: 700;">Entradas: <span class="valor-positivo"><?php echo formatarMoeda($entradasReal); ?></span></p>
            <p style="font-size: 1.2rem; font-weight: 700;">Saídas: <span class="valor-negativo"><?php echo formatarMoeda($saidasReal); ?></span></p>
        </article>

        <article class="cartao-projeto card-saldo-total" style="background-color: #e8f5e9; border-left: 5px solid #28a745;">
            <h3>Saldo em Conta</h3>
            <p class="valor-saldo"><?php echo formatarMoeda($saldoReal); ?></p>
        </article>
    </section>

    <hr style="margin: 40px 0; border: 0; border-top: 1px solid #ddd;">

    <h3 style="margin-bottom: 15px; color: #666;">Saldo Previsto <small>(Tudo do mês)</small></h3>
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

    <hr style="margin: 40px 0; border: 0; border-top: 1px solid #ddd;">

    <h3 style="margin-bottom: 15px; color: #666;">Análise por Categorias <small>(Previsto do mês)</small></h3>
    <section class="grade-projetos">
        
        <article class="cartao-projeto">
            <h3 style="color: #dc3545; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">Despesas</h3>
            <ul class="lista-categorias">
                <?php if(count($despesasPorCategoria) > 0): ?>
                    <?php foreach($despesasPorCategoria as $cat): ?>
                        <?php 
                            // Calcula o percentual (evita divisão por zero se não houver saídas)
                            $percentual = ($saidasPrevisto > 0) ? ($cat['total'] / $saidasPrevisto) * 100 : 0;
                        ?>
                        <li class="item-categoria">
                            <div class="info-categoria">
                                <span><?php echo htmlspecialchars($cat['nome']); ?></span>
                                <div>
                                    <strong><?php echo formatarMoeda($cat['total']); ?></strong> 
                                    <span style="font-size: 0.8rem; color: #888;">(<?php echo number_format($percentual, 1, ',', '.'); ?>%)</span>
                                </div>
                            </div>
                            <div class="barra-fundo">
                                <div class="barra-progresso bg-saida" style="width: <?php echo $percentual; ?>%;"></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #888;">Nenhuma despesa registrada.</p>
                <?php endif; ?>
            </ul>
        </article>

        <article class="cartao-projeto">
            <h3 style="color: #28a745; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">Receitas</h3>
            <ul class="lista-categorias">
                <?php if(count($receitasPorCategoria) > 0): ?>
                    <?php foreach($receitasPorCategoria as $cat): ?>
                        <?php 
                            // Calcula o percentual (evita divisão por zero)
                            $percentual = ($entradasPrevisto > 0) ? ($cat['total'] / $entradasPrevisto) * 100 : 0;
                        ?>
                        <li class="item-categoria">
                            <div class="info-categoria">
                                <span><?php echo htmlspecialchars($cat['nome']); ?></span>
                                <div>
                                    <strong><?php echo formatarMoeda($cat['total']); ?></strong> 
                                    <span style="font-size: 0.8rem; color: #888;">(<?php echo number_format($percentual, 1, ',', '.'); ?>%)</span>
                                </div>
                            </div>
                            <div class="barra-fundo">
                                <div class="barra-progresso bg-entrada" style="width: <?php echo $percentual; ?>%;"></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #888;">Nenhuma receita registrada.</p>
                <?php endif; ?>
            </ul>
        </article>

    </section>

    <section class="acoes-rapidas" style="margin-top: 40px;">
        <div class="grade-projetos">
            <a href="nova_transacao.php" class="botao btn-sucesso" style="text-align: center;">+ Novo Lançamento</a>
            <a href="categorias.php" class="botao" style="text-align: center; background-color: #6c757d; border-color: #6c757d;">Gerenciar Categorias</a>
        </div>
    </section>

</main>

<?php require_once 'includes/footer.php'; ?>