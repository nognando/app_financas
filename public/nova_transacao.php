<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$mensagem = '';
$tipoMensagem = '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // --- LÓGICA DE INSERÇÃO (AGORA COM SUPORTE A RECORRÊNCIA) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $tipo = $_POST['tipo'];
        $descricaoBase = trim($_POST['descricao']);
        $valor = str_replace(',', '.', $_POST['valor']); 
        $dataInicial = $_POST['data_transacao'];
        $categoria_id = $_POST['categoria_id'];
        $statusInicial = $_POST['status'];

        // Captura os campos de recorrência
        $isRecorrente = isset($_POST['recorrente']) ? true : false;
        $qtdMeses = isset($_POST['qtd_meses']) ? (int)$_POST['qtd_meses'] : 1;

        // Se não marcou a caixa, garante que rode apenas 1 vez
        if (!$isRecorrente) {
            $qtdMeses = 1;
        }

        // Prepara a query uma única vez (melhor performance)
        $queryInsert = "INSERT INTO transacoes (tipo, descricao, valor, data_transacao, categoria_id, status) 
                        VALUES (:tipo, :descricao, :valor, :data, :categoria_id, :status)";
        $stmt = $db->prepare($queryInsert);

        $sucesso = true;
        $dataObj = new DateTime($dataInicial);

        // Laço de repetição: Cria 1 ou N transações
        for ($i = 0; $i < $qtdMeses; $i++) {
            
            // Clona a data original para não perder a referência do dia
            $novaData = clone $dataObj;
            if ($i > 0) {
                $novaData->modify("+$i month");
            }
            $dataFormatada = $novaData->format('Y-m-d');

            // Regra: O mês atual usa o status selecionado. Meses futuros nascem 'pendentes'.
            $statusAtual = ($i == 0) ? $statusInicial : 'pendente';
            
            // Opcional: Adiciona um marcador (1/12) na descrição para você saber que é fixa
            $descricaoFinal = $isRecorrente ? $descricaoBase . " (" . ($i + 1) . "/$qtdMeses)" : $descricaoBase;

            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':descricao', $descricaoFinal);
            $stmt->bindParam(':valor', $valor);
            $stmt->bindParam(':data', $dataFormatada);
            $stmt->bindParam(':categoria_id', $categoria_id);
            $stmt->bindParam(':status', $statusAtual);

            if (!$stmt->execute()) {
                $sucesso = false;
            }
        }

        if ($sucesso) {
            header("Location: transacoes.php?msg=sucesso");
            exit;
        } else {
            $mensagem = "Houve um erro ao salvar algumas transações.";
            $tipoMensagem = "alerta-erro";
        }
    }

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
                    <input type="text" name="descricao" class="form-control" placeholder="Ex: Conta de Luz, Aluguel, Salário..." required>
                </div>

                <div class="form-group">
                    <label>Valor (R$)</label>
                    <input type="text" name="valor" class="form-control" placeholder="0,00" required>
                </div>

                <div class="form-group">
                    <label>Data de Vencimento/Recebimento</label>
                    <input type="date" name="data_transacao" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                
                <div class="form-group">
                    <label>Status do Mês Atual</label>
                    <select name="status" class="form-control">
                        <option value="pendente">Pendente</option>
                        <option value="pago">Consolidado (Pago/Recebido)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; color: #00a2ed;">
                        <input type="checkbox" name="recorrente" id="check-recorrente" value="1" style="width: 20px; height: 20px; cursor: pointer;">
                        💸 Esta é uma transação fixa (Repetir mensalmente)
                    </label>
                </div>

                <div class="form-group" id="box-meses" style="display: none; background: #f1f9ff; padding: 15px; border-radius: 6px; border-left: 4px solid #00a2ed;">
                    <label>Repetir por quantos meses? (Incluindo este)</label>
                    <input type="number" name="qtd_meses" id="qtd_meses" class="form-control" min="2" max="60" value="12">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Ex: Se colocar 12, lançaremos a de hoje + 11 meses para frente. As parcelas futuras nascerão automaticamente como "Pendentes".
                    </small>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="botao btn-sucesso" style="flex: 1;">Salvar Transação</button>
                    <a href="transacoes.php" class="botao" style="flex: 1; background: #6c757d; border-color: #6c757d; text-align: center;">Cancelar</a>
                </div>

            </form>
        </article>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Lógica de Filtragem de Categorias (que já tínhamos)
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

    // 2. NOVA LÓGICA: Exibir/Esconder caixa de meses de recorrência
    const checkRecorrente = document.getElementById('check-recorrente');
    const boxMeses = document.getElementById('box-meses');

    checkRecorrente.addEventListener('change', function() {
        if(this.checked) {
            boxMeses.style.display = 'block';
        } else {
            boxMeses.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>