<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$mensagem = '';
$tipoMensagem = '';

// Array para traduzir os meses
$mesesPt = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
    '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
    '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
    '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];

try {
    $database = new Database();
    $db = $database->getConnection();

    // ==========================================
    // 1. LÓGICA DO FILTRO DE MÊS
    // ==========================================
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

    // ==========================================
    // 2. CAPTURA AÇÕES (COM MENSAGENS)
    // ==========================================
    if (isset($_GET['msg']) && $_GET['msg'] == 'sucesso') {
        $mensagem = "Operação realizada com sucesso!";
        $tipoMensagem = "alerta-sucesso";
    }

    if (isset($_GET['excluir'])) {
        $idExcluir = $_GET['excluir'];
        $queryDel = "DELETE FROM transacoes WHERE id = :id";
        $stmtDel = $db->prepare($queryDel);
        $stmtDel->bindParam(':id', $idExcluir);
        if ($stmtDel->execute()) {
            $mensagem = "Transação excluída com sucesso!";
            $tipoMensagem = "alerta-sucesso";
        }
    }

    if (isset($_GET['consolidar'])) {
        $idConsolidar = $_GET['consolidar'];
        $queryStatus = "UPDATE transacoes SET status = 'pago' WHERE id = :id";
        $stmtStatus = $db->prepare($queryStatus);
        $stmtStatus->bindParam(':id', $idConsolidar);
        if ($stmtStatus->execute()) {
            $mensagem = "Transação consolidada!";
            $tipoMensagem = "alerta-sucesso";
        }
    }

    // ==========================================
    // 3. BUSCA HISTÓRICO FILTRADO PELO MÊS
    // ==========================================
    $queryTrans = "SELECT t.*, c.nome as categoria_nome 
                   FROM transacoes t 
                   JOIN categorias c ON t.categoria_id = c.id 
                   WHERE MONTH(t.data_transacao) = :mes AND YEAR(t.data_transacao) = :ano
                   ORDER BY t.data_transacao DESC, t.id DESC";
    
    $stmtTrans = $db->prepare($queryTrans);
    $stmtTrans->execute(['mes' => $mesAlvo, 'ano' => $anoAlvo]);
    $listaTransacoes = $stmtTrans->fetchAll();

} catch(PDOException $e) {
    $mensagem = "Erro: " . $e->getMessage();
    $tipoMensagem = "alerta-erro";
}

require_once 'includes/header.php';
?>

<section class="destaque">
    <div class="container">
        <div class="navegacao-mes">
            <a href="transacoes.php?mes=<?php echo $linkAnterior; ?>" class="botao-mes" title="Mês Anterior">&#10094;</a>
            <h2><?php echo $nomeMesExibicao; ?></h2>
            <a href="transacoes.php?mes=<?php echo $linkProximo; ?>" class="botao-mes" title="Próximo Mês">&#10095;</a>
        </div>
    </div>
</section>

<main class="container">
    
    <?php if(!empty($mensagem)): ?>
        <div class="alerta <?php echo $tipoMensagem; ?>"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <section class="grade-projetos">
        <article class="cartao-projeto">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0;">Lançamentos do Mês</h3>
                <a href="nova_transacao.php" class="botao btn-sucesso" style="margin: 0;">+ Nova Transação</a>
            </div>

            <div class="table-responsive">
                <table class="tabela-dados">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($listaTransacoes) > 0): ?>
                            <?php foreach($listaTransacoes as $tr): ?>
                                <tr>
                                    <td data-label="Data"><?php echo date('d/m/Y', strtotime($tr['data_transacao'])); ?></td>
                                    
                                    <td data-label="Descrição">
                                        <strong><?php echo htmlspecialchars($tr['descricao']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($tr['categoria_nome']); ?></small>
                                    </td>
                                    
                                    <td data-label="Valor" class="<?php echo $tr['tipo'] == 'entrada' ? 'valor-positivo' : 'valor-negativo'; ?>">
                                        <?php echo $tr['tipo'] == 'entrada' ? '+' : '-'; ?> 
                                        <?php echo number_format($tr['valor'], 2, ',', '.'); ?>
                                    </td>
                                    
                                    <td data-label="Status">
                                        <span class="<?php echo $tr['status'] == 'pago' ? 'badge-pago' : 'badge-pendente'; ?>">
                                            <?php echo $tr['status'] == 'pago' ? 'Pago' : 'Pendente'; ?>
                                        </span>
                                    </td>
                                    
                                    <td data-label="Ações">
                                        <?php if($tr['status'] == 'pendente'): ?>
                                            <a href="transacoes.php?consolidar=<?php echo $tr['id']; ?>&mes=<?php echo $competencia; ?>" class="btn-acao btn-consolidar" title="Consolidar">✔</a>
                                        <?php endif; ?>
                                        
                                        <a href="editar_transacao.php?id=<?php echo $tr['id']; ?>" class="btn-acao" style="background-color: #ffc107;" title="Editar">✏️</a>
                                        
                                        <a href="transacoes.php?excluir=<?php echo $tr['id']; ?>&mes=<?php echo $competencia; ?>" class="btn-acao btn-excluir" onclick="return confirm('Excluir esta transação?')" title="Excluir">✖</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">Nenhuma transação encontrada para este mês.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>