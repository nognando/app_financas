<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$mensagem = '';
$tipoMensagem = '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // --- LÓGICA DE INSERÇÃO ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $tipo = $_POST['tipo'];
        $descricao = trim($_POST['descricao']);
        $valor = str_replace(',', '.', $_POST['valor']); 
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
            // REDIRECIONA DE VOLTA PARA O HISTÓRICO COM MENSAGEM DE SUCESSO
            header("Location: transacoes.php?msg=sucesso");
            exit;
        } else {
            $mensagem = "Erro ao salvar transação.";
            $tipoMensagem = "alerta-erro";
        }
    }

    // Busca categorias para popular o Select
    $listaCategorias = $db->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();

} catch(PDOException $e) {
    $mensagem = "Erro no banco de dados: " . $e->getMessage();
    $tipoMensagem = "alerta-erro";
}

require_once 'includes/header.php';
?>

<section class="destaque">
    <div class="container">
        <h2>Nova Transação</h2>
    </div>
</section>

<main class="container">
    
    <?php if(!empty($mensagem)): ?>
        <div class="alerta <?php echo $tipoMensagem; ?>"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <section class="grade-projetos" style="display: block; max-width: 600px; margin: 0 auto;">
        <article class="cartao-projeto">
            
            <form action="nova_transacao.php" method="POST" id="form-transacao">

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
                    <label>Status Inicial</label>
                    <select name="status" class="form-control">
                        <option value="pendente">Pendente</option>
                        <option value="pago">Consolidado (Pago/Recebido)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="botao btn-sucesso" style="flex: 1;">Salvar Transação</button>
                    <a href="transacoes.php" class="botao" style="flex: 1; background: #6c757d; border-color: #6c757d; text-align: center;">Cancelar</a>
                </div>

            </form>
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
        selectCategoria.disabled = false;
        selectCategoria.value = "";

        opcoesCategorias.forEach(option => {
            if (option.value === "") return;
            if (option.getAttribute('data-tipo') === tipoSelecionado) {
                option.style.display = 'block';
                option.disabled = false;
            } else {
                option.style.display = 'none';
                option.disabled = true;
            }
        });

        selectCategoria.selectedIndex = 0;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>