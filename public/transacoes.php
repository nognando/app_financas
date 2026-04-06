<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$mensagem = '';
$tipoMensagem = '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // --- LÓGICA 1: EXCLUSÃO ---
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

    // --- LÓGICA 2: ALTERAR STATUS (CONSOLIDAR) ---
    if (isset($_GET['consolidar'])) {
        $idConsolidar = $_GET['consolidar'];
        $queryStatus = "UPDATE transacoes SET status = 'pago' WHERE id = :id";
        $stmtStatus = $db->prepare($queryStatus);
        $stmtStatus->bindParam(':id', $idConsolidar);
        $stmtStatus->execute();
    }

    // --- LÓGICA 3: INSERÇÃO ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $tipo = $_POST['tipo'];
        $descricao = trim($_POST['descricao']);
        $valor = str_replace(',', '.', $_POST['valor']); // Converte vírgula em ponto
        $data = $_POST['data_transacao'];
        $categoria_id = $_POST['categoria_id'];
        $status = $_POST['status'];

        $queryInsert = "INSERT INTO transacoes (tipo, descricao, valor, data_transacao, categoria_id, status) 
                        VALUES (:tipo, :descricao, :valor, :data, :categoria_id, :status)";
        
        $stmt = $db->prepare($queryInsert);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':descricao', $descricao);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':data', $data);
        $stmt->bindParam(':categoria_id', $categoria_id);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            $mensagem = "Transação lançada com sucesso!";
            $tipoMensagem = "alerta-sucesso";
        }
    }

    // --- BUSCA DADOS PARA A TELA ---
    // Categorias para o select
    $listaCategorias = $db->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();

    // Transações com JOIN para pegar o nome da categoria
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
        <h2>Gerenciar Transações</h2>
    </div>
</section>

<main class="container">
    
    <?php if(!empty($mensagem)): ?>
        <div class="alerta <?php echo $tipoMensagem; ?>"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <section class="grade-projetos">
        <article class="cartao-projeto">
            <h3>Novo Lançamento</h3>
            <form action="transacoes.php" method="POST" id="form-transacao">
                
                <div class="form-group">
                    <label>Descrição</label>
                    <input type="text" name="descricao" class="form-control" placeholder="Ex: Compra Mercado" required>
                </div>

                <div class="form-group">
                    <label>Valor (R$)</label>
                    <input type="text" name="valor" class="form-control" placeholder="0,00" required>
                </div>

                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data_transacao" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="tipo_transacao">Tipo de Movimentação</label>
                    <select name="tipo" id="tipo_transacao" class="form-control" required>
                        <option value="" disabled selected>Selecione primeiro o tipo...</option>
                        <option value="saida">Saída (Despesa)</option>
                        <option value="entrada">Entrada (Receita)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="categoria_id">Categoria</label>
                    <select name="categoria_id" id="categoria_id" class="form-control" required disabled>
                        <option value="" disabled selected>Selecione o tipo acima primeiro</option>
                        <?php foreach($listaCategorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" data-tipo="<?php echo $cat['tipo']; ?>">
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status Inicial</label>
                    <select name="status" class="form-control">
                        <option value="pendente">Pendente</option>
                        <option value="pago">Consolidado (Pago/Recebido)</option>
                    </select>
                </div>

                <button type="submit" class="botao btn-sucesso" style="width: 100%;">Lançar Transação</button>
            </form>
        </article>

        <article class="cartao-projeto">
            <h3>Histórico Recente</h3>
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
                        <?php foreach($listaTransacoes as $tr): ?>
                            <tr>
                                <td data-label="Data">
                                    <?php echo date('d/m', strtotime($tr['data_transacao'])); ?>
                                </td>
                                
                                <td data-label="Descrição">
                                    <strong><?php echo htmlspecialchars($tr['descricao']); ?></strong><br>
                                    <small style="color: #666;"><?php echo $tr['categoria_nome']; ?></small>
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
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectTipo = document.getElementById('tipo_transacao');
    const selectCategoria = document.getElementById('categoria_id');
    const opcoesCategorias = Array.from(selectCategoria.options);

    selectTipo.addEventListener('change', function() {
        const tipoSelecionado = this.value;

        // Habilita o campo de categoria
        selectCategoria.disabled = false;
        
        // Limpa a seleção atual
        selectCategoria.value = "";

        // Filtra as opções
        opcoesCategorias.forEach(option => {
            if (option.value === "") return; // Pula o "Selecione..."

            if (option.getAttribute('data-tipo') === tipoSelecionado) {
                option.style.display = 'block';
                option.disabled = false;
            } else {
                option.style.display = 'none';
                option.disabled = true;
            }
        });

        // Força o reset para a primeira opção válida visível
        selectCategoria.selectedIndex = 0;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>