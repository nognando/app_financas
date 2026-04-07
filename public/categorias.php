<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$mensagem = '';
$tipoMensagem = '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. CAPTURA MENSAGEM DE SUCESSO DA EDIÇÃO
    if (isset($_GET['msg']) && $_GET['msg'] == 'sucesso') {
        $mensagem = "Categoria atualizada com sucesso!";
        $tipoMensagem = "alerta-sucesso";
    }

    // 2. LÓGICA DE EXCLUSÃO
    if (isset($_GET['excluir'])) {
        $idExcluir = $_GET['excluir'];
        
        try {
            $queryDel = "DELETE FROM categorias WHERE id = :id";
            $stmtDel = $db->prepare($queryDel);
            $stmtDel->bindParam(':id', $idExcluir);
            
            if ($stmtDel->execute()) {
                $mensagem = "Categoria excluída com sucesso!";
                $tipoMensagem = "alerta-sucesso";
            }
        } catch (PDOException $e) {
            // Se o erro for de Chave Estrangeira (Categoria em uso)
            if ($e->getCode() == 23000) {
                $mensagem = "Ação bloqueada: Você não pode excluir esta categoria pois já existem lançamentos usando ela. Edite o nome ou exclua as transações primeiro.";
            } else {
                $mensagem = "Erro ao excluir: " . $e->getMessage();
            }
            $tipoMensagem = "alerta-erro";
        }
    }

    // 3. LÓGICA DE INSERÇÃO (NOVA CATEGORIA)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome = trim($_POST['nome']);
        $tipo = trim($_POST['tipo']);

        if (!empty($nome) && !empty($tipo)) {
            $queryInsert = "INSERT INTO categorias (nome, tipo) VALUES (:nome, :tipo)";
            $stmtInsert = $db->prepare($queryInsert);
            $stmtInsert->bindParam(':nome', $nome);
            $stmtInsert->bindParam(':tipo', $tipo);

            if ($stmtInsert->execute()) {
                $mensagem = "Categoria '$nome' adicionada com sucesso!";
                $tipoMensagem = "alerta-sucesso";
            } else {
                $mensagem = "Erro ao adicionar a categoria.";
                $tipoMensagem = "alerta-erro";
            }
        } else {
            $mensagem = "Por favor, preencha todos os campos.";
            $tipoMensagem = "alerta-erro";
        }
    }

    // 4. BUSCA CATEGORIAS CADASTRADAS
    $querySelect = "SELECT id, nome, tipo FROM categorias ORDER BY tipo ASC, nome ASC";
    $stmtSelect = $db->prepare($querySelect);
    $stmtSelect->execute();
    $listaCategorias = $stmtSelect->fetchAll();

} catch(PDOException $e) {
    $mensagem = "Erro crítico no banco de dados: " . $e->getMessage();
    $tipoMensagem = "alerta-erro";
    $listaCategorias = []; 
}

require_once 'includes/header.php';
?>

<section class="destaque">
    <div class="container">
        <h2>Gerenciar Categorias</h2>
    </div>
</section>

<main class="container">
    
    <?php if(!empty($mensagem)): ?>
        <div class="alerta <?php echo $tipoMensagem; ?>">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <section class="grade-projetos">
        
        <article class="cartao-projeto">
            <h3>Nova Categoria</h3>
            <form action="categorias.php" method="POST">
                <div class="form-group">
                    <label for="nome">Nome da Categoria</label>
                    <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Alimentação..." required>
                </div>

                <div class="form-group">
                    <label for="tipo">Tipo de Movimentação</label>
                    <select id="tipo" name="tipo" class="form-control" required>
                        <option value="" disabled selected>Selecione...</option>
                        <option value="entrada">Entrada (Receita)</option>
                        <option value="saida">Saída (Despesa)</option>
                    </select>
                </div>

                <button type="submit" class="botao btn-sucesso" style="width: 100%;">Salvar Categoria</button>
            </form>
        </article>

        <article class="cartao-projeto">
            <h3>Categorias Cadastradas</h3>
            <div class="table-responsive">
                <table class="tabela-dados">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($listaCategorias) > 0): ?>
                            <?php foreach($listaCategorias as $cat): ?>
                                <tr>
                                    <td data-label="Nome da Categoria"><?php echo htmlspecialchars($cat['nome']); ?></td>
                                    <td data-label="Tipo">
                                        <?php if($cat['tipo'] == 'entrada'): ?>
                                            <span class="badge-sucesso">Entrada</span>
                                        <?php else: ?>
                                            <span class="badge-perigo">Saída</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Ações">
                                        <a href="editar_categoria.php?id=<?php echo $cat['id']; ?>" class="btn-acao" style="background-color: #ffc107;" title="Editar">✏️</a>
                                        <a href="categorias.php?excluir=<?php echo $cat['id']; ?>" class="btn-acao btn-excluir" onclick="return confirm('Deseja excluir esta categoria?')" title="Excluir">✖</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center;">Nenhuma categoria cadastrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

    </section>
</main>

<?php require_once 'includes/footer.php'; ?>