<?php
// ATIVE O MODO DEBUG (Remova quando colocar em produção oficial)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Inclui o arquivo de conexão com o banco de dados
require_once '../config/database.php';

$mensagem = '';
$tipoMensagem = '';

try {
    // 2. Instancia a conexão com o banco DE IMEDIATO (vamos precisar para salvar e para listar)
    $database = new Database();
    $db = $database->getConnection();

    // 3. Lógica para SALVAR uma nova categoria (se o formulário foi enviado)
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

    // 4. Lógica para BUSCAR as categorias cadastradas (executa sempre)
    // Ordenando primeiro pelo tipo (entradas juntas, saídas juntas) e depois em ordem alfabética
    $querySelect = "SELECT id, nome, tipo FROM categorias ORDER BY tipo ASC, nome ASC";
    $stmtSelect = $db->prepare($querySelect);
    $stmtSelect->execute();
    $listaCategorias = $stmtSelect->fetchAll();

} catch(PDOException $e) {
    $mensagem = "Erro crítico no banco de dados: " . $e->getMessage();
    $tipoMensagem = "alerta-erro";
    $listaCategorias = []; // Deixa a lista vazia em caso de erro para não quebrar a tela
}

// 5. Carrega o cabeçalho (Visual)
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
            
            <form action="" method="POST">
                <div class="form-group">
                    <label for="nome">Nome da Categoria</label>
                    <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Alimentação, Salário, Investimentos..." required>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($listaCategorias) > 0): ?>
                            
                            <?php foreach($listaCategorias as $cat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cat['nome']); ?></td>
                                    <td>
                                        <?php if($cat['tipo'] == 'entrada'): ?>
                                            <span class="badge-sucesso">Entrada</span>
                                        <?php else: ?>
                                            <span class="badge-perigo">Saída</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center; color: #6c757d; padding: 20px;">
                                    Nenhuma categoria cadastrada ainda.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

    </section>

</main>

<?php
// 6. Carrega o rodapé
require_once 'includes/footer.php';
?>