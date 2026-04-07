<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$mensagem = '';
$tipoMensagem = '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Captura mensagem de sucesso vinda de outras páginas (Nova ou Editar)
    if (isset($_GET['msg']) && $_GET['msg'] == 'sucesso') {
        $mensagem = "Operação realizada com sucesso!";
        $tipoMensagem = "alerta-sucesso";
    }

    // --- LÓGICA DE EXCLUSÃO ---
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

    // --- LÓGICA DE CONSOLIDAR (PAGO) ---
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

    // --- BUSCA HISTÓRICO PARA A TELA ---
    $queryTrans = "SELECT t.*, c.nome as categoria_nome 
                   FROM transacoes t 
                   JOIN categorias c ON t.categoria_id = c.id 
                   ORDER BY t.data_transacao DESC, t.id DESC";
    $listaTransacoes = $db->query($queryTrans)->fetchAll();

} catch(PDOException $e) {
    $mensagem = "Erro: " . $e->getMessage();
    $tipoMensagem = "alerta-erro";
}

require_once 'includes/header.php';
?>

<section class="destaque">
    <div class="container">
        <h2>Histórico de Transações</h2>
    </div>
</section>

<main class="container">
    
    <?php if(!empty($mensagem)): ?>
        <div class="alerta <?php echo $tipoMensagem; ?>"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <section class="grade-projetos">
        <article class="cartao-projeto">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0;">Lançamentos</h3>
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
                                            <a href="transacoes.php?consolidar=<?php echo $tr['id']; ?>" class="btn-acao btn-consolidar" title="Consolidar">✔</a>
                                        <?php endif; ?>
                                        <a href="editar_transacao.php?id=<?php echo $tr['id']; ?>" class="btn-acao" style="background-color: #ffc107;" title="Editar">✏️</a>
                                        <a href="transacoes.php?excluir=<?php echo $tr['id']; ?>" class="btn-acao btn-excluir" onclick="return confirm('Excluir esta transação?')" title="Excluir">✖</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center;">Nenhuma transação encontrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>