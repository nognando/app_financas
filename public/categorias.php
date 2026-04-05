<?php
// 1. Inclui o arquivo de conexão com o banco de dados
//require_once 'config/database.php';

// Variáveis para controlar as mensagens de feedback na tela
$mensagem = '';
$tipoMensagem = '';

// 2. Verifica se o formulário foi enviado (Método POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Pega os dados do formulário e remove espaços em branco extras
    $nome = trim($_POST['nome']);
    $tipo = trim($_POST['tipo']);

    // Validação simples: verifica se os campos não estão vazios
    if (!empty($nome) && !empty($tipo)) {
        
        try {
            // Instancia o banco e pega a conexão
            $database = new Database();
            $db = $database->getConnection();

            // Prepara a query SQL com os "bind parameters" (:nome, :tipo) para evitar SQL Injection
            $query = "INSERT INTO categorias (nome, tipo) VALUES (:nome, :tipo)";
            $stmt = $db->prepare($query);

            // Vincula os valores e executa
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':tipo', $tipo);

            if ($stmt->execute()) {
                $mensagem = "Categoria '$nome' adicionada com sucesso!";
                $tipoMensagem = "alerta-sucesso";
            } else {
                $mensagem = "Erro ao adicionar a categoria.";
                $tipoMensagem = "alerta-erro";
            }

        } catch(PDOException $e) {
            $mensagem = "Erro no banco de dados: " . $e->getMessage();
            $tipoMensagem = "alerta-erro";
        }

    } else {
        $mensagem = "Por favor, preencha todos os campos.";
        $tipoMensagem = "alerta-erro";
    }
}

// 3. Carrega o cabeçalho (Visual)
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
                    <input type="text" id="nome" name="nome" class="form-control" placeholder="Ex: Alimentação, Salário..." required>
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
    </section>

</main>

<?php
// 4. Carrega o rodapé
require_once 'includes/footer.php';
?>