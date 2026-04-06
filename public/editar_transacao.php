<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$mensagem = '';
$tipoMensagem = '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. VERIFICAR SE EXISTE ID PARA EDITAR
    if (!isset($_GET['id'])) {
        header("Location: transacoes.php");
        exit;
    }

    $id = $_GET['id'];

    // 2. PROCESSAR O UPDATE (Se o formulário for enviado)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $descricao = trim($_POST['descricao']);
        $valor = str_replace(',', '.', $_POST['valor']);
        $data = $_POST['data_transacao'];
        $tipo = $_POST['tipo'];
        $categoria_id = $_POST['categoria_id'];
        $status = $_POST['status'];

        $queryUpdate = "UPDATE transacoes 
                        SET descricao = :desc, valor = :val, data_transacao = :dat, 
                            tipo = :tip, categoria_id = :cat, status = :sta 
                        WHERE id = :id";
        
        $stmt = $db->prepare($queryUpdate);
        $stmt->bindParam(':desc', $descricao);
        $stmt->bindParam(':val', $valor);
        $stmt->bindParam(':dat', $data);
        $stmt->bindParam(':tip', $tipo);
        $stmt->bindParam(':cat', $categoria_id);
        $stmt->bindParam(':sta', $status);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            header("Location: transacoes.php?msg=sucesso"); // Redireciona de volta
            exit;
        }
    }

    // 3. BUSCAR DADOS ATUAIS DA TRANSAÇÃO
    $stmt = $db->prepare("SELECT * FROM transacoes WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $transacao = $stmt->fetch();

    if (!$transacao) {
        die("Transação não encontrada.");
    }

    // 4. BUSCAR CATEGORIAS PARA O SELECT
    $listaCategorias = $db->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();

} catch(PDOException $e) {
    $mensagem = "Erro: " . $e->getMessage();
    $tipoMensagem = "alerta-erro";
}

require_once 'includes/header.php';
?>

<section class="destaque">
    <div class="container">
        <h2>Editar Transação</h2>
    </div>
</section>

<main class="container">
    <section class="grade-projetos" style="display: block; max-width: 600px; margin: 0 auto;">
        <article class="cartao-projeto">
            <form action="editar_transacao.php?id=<?php echo $id; ?>" method="POST" id="form-edit">
                
                <div class="form-group">
                    <label>Descrição</label>
                    <input type="text" name="descricao" class="form-control" value="<?php echo htmlspecialchars($transacao['descricao']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Valor (R$)</label>
                    <input type="text" name="valor" class="form-control" value="<?php echo number_format($transacao['valor'], 2, ',', ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Data</label>
                    <input type="date" name="data_transacao" class="form-control" value="<?php echo $transacao['data_transacao']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo" id="tipo_transacao" class="form-control">
                        <option value="saida" <?php echo $transacao['tipo'] == 'saida' ? 'selected' : ''; ?>>Saída (Despesa)</option>
                        <option value="entrada" <?php echo $transacao['tipo'] == 'entrada' ? 'selected' : ''; ?>>Entrada (Receita)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Categoria</label>
                    <select name="categoria_id" id="categoria_id" class="form-control">
                        <?php foreach($listaCategorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    data-tipo="<?php echo $cat['tipo']; ?>"
                                    <?php echo $transacao['categoria_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="pendente" <?php echo $transacao['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="pago" <?php echo $transacao['status'] == 'pago' ? 'selected' : ''; ?>>Consolidado (Pago/Recebido)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="botao btn-sucesso" style="flex: 1;">Atualizar</button>
                    <a href="transacoes.php" class="botao" style="flex: 1; background: #6c757d; text-align: center;">Cancelar</a>
                </div>
            </form>
        </article>
    </section>
</main>

<script>
// Reaproveitamos a lógica de filtro de categorias que fizemos antes
document.addEventListener('DOMContentLoaded', function() {
    const selectTipo = document.getElementById('tipo_transacao');
    const selectCategoria = document.getElementById('categoria_id');
    const opcoesCategorias = Array.from(selectCategoria.options);

    function filtrar() {
        const tipoSelecionado = selectTipo.value;
        opcoesCategorias.forEach(option => {
            if (option.getAttribute('data-tipo') === tipoSelecionado) {
                option.style.display = 'block';
                option.disabled = false;
            } else {
                option.style.display = 'none';
                option.disabled = true;
            }
        });
    }

    selectTipo.addEventListener('change', filtrar);
    filtrar(); // Executa ao carregar para mostrar as categorias certas do tipo atual
});
</script>

<?php require_once 'includes/footer.php'; ?>