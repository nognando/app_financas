<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$mensagem = '';
$tipoMensagem = '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. VERIFICA SE O ID FOI PASSADO
    if (!isset($_GET['id'])) {
        header("Location: categorias.php");
        exit;
    }

    $id = $_GET['id'];

    // 2. PROCESSA A ATUALIZAÇÃO SE O FORMULÁRIO FOR ENVIADO
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome = trim($_POST['nome']);
        $tipo = $_POST['tipo'];

        $queryUpdate = "UPDATE categorias SET nome = :nome, tipo = :tipo WHERE id = :id";
        $stmt = $db->prepare($queryUpdate);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':id', $id);

        if ($stmt->execute()) {
            // Volta para a listagem com mensagem de sucesso
            header("Location: categorias.php?msg=sucesso");
            exit;
        }
    }

    // 3. BUSCA OS DADOS ATUAIS DA CATEGORIA PARA PREENCHER O FORMULÁRIO
    $stmt = $db->prepare("SELECT * FROM categorias WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $categoria = $stmt->fetch();

    if (!$categoria) {
        die("Categoria não encontrada.");
    }

} catch(PDOException $e) {
    $mensagem = "Erro: " . $e->getMessage();
    $tipoMensagem = "alerta-erro";
}

require_once 'includes/header.php';
?>

<section class="destaque">
    <div class="container">
        <h2>Editar Categoria</h2>
    </div>
</section>

<main class="container">
    <section class="grade-projetos" style="display: block; max-width: 500px; margin: 0 auto;">
        <article class="cartao-projeto">
            
            <form action="editar_categorias.php?id=<?php echo $id; ?>" method="POST">
                
                <div class="form-group">
                    <label for="nome">Nome da Categoria</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($categoria['nome']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="tipo">Tipo de Movimentação</label>
                    <select id="tipo" name="tipo" class="form-control" required>
                        <option value="entrada" <?php echo $categoria['tipo'] == 'entrada' ? 'selected' : ''; ?>>Entrada (Receita)</option>
                        <option value="saida" <?php echo $categoria['tipo'] == 'saida' ? 'selected' : ''; ?>>Saída (Despesa)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="botao btn-sucesso" style="flex: 1;">Atualizar</button>
                    <a href="categorias.php" class="botao" style="flex: 1; background: #6c757d; border-color: #6c757d; text-align: center;">Cancelar</a>
                </div>

            </form>
            
        </article>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>