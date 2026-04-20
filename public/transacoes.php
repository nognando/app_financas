<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

$mensagem = '';
$tipoMensagem = '';

$mesesPt = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março',
    '04' => 'Abril', '05' => 'Maio', '06' => 'Junho',
    '07' => 'Julho', '08' => 'Agosto', '09' => 'Setembro',
    '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];

try {
    $database = new Database();
    $db = $database->getConnection();

    $competencia = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
    $filtroStatus = isset($_GET['status']) ? $_GET['status'] : 'todos'; 
    
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

    $sqlCondicaoStatus = "";
    $params = ['mes' => $mesAlvo, 'ano' => $anoAlvo];

    if ($filtroStatus == 'pago' || $filtroStatus == 'pendente') {
        $sqlCondicaoStatus = " AND t.status = :status ";
        $params['status'] = $filtroStatus;
    }

    $queryTrans = "SELECT t.*, c.nome as categoria_nome 
                   FROM transacoes t 
                   JOIN categorias c ON t.categoria_id = c.id 
                   WHERE MONTH(t.data_transacao) = :mes AND YEAR(t.data_transacao) = :ano
                   " . $sqlCondicaoStatus . "
                   ORDER BY t.data_transacao ASC, t.id DESC";
    
    $stmtTrans = $db->prepare($queryTrans);
    $stmtTrans->execute($params);
    $listaTransacoes = $stmtTrans->fetchAll();

    $listaReceitas = [];
    $listaDespesas = [];
    $totalReceitas = 0;
    $totalDespesas = 0;

    foreach ($listaTransacoes as $tr) {
        if ($tr['tipo'] == 'entrada') {
            $listaReceitas[] = $tr;
            $totalReceitas += $tr['valor'];
        } else {
            $listaDespesas[] = $tr;
            $totalDespesas += $tr['valor'];
        }
    }

} catch(PDOException $e) {
    $mensagem = "Erro: " . $e->getMessage();
    $tipoMensagem = "alerta-erro";
}

require_once 'includes/header.php';
?>

<section class="destaque">
    <div class="container">
        <div class="navegacao-mes">
            <a href="transacoes.php?mes=<?php echo $linkAnterior; ?>&status=<?php echo $filtroStatus; ?>" class="botao-mes" title="Mês Anterior">&#10094;</a>
            <h2><?php echo $nomeMesExibicao; ?></h2>
            <a href="transacoes.php?mes=<?php echo $linkProximo; ?>&status=<?php echo $filtroStatus; ?>" class="botao-mes" title="Próximo Mês">&#10095;</a>
        </div>
    </div>
</section>

<main class="container">
    
    <?php if(!empty($mensagem)): ?>
        <div class="alerta <?php echo $tipoMensagem; ?>"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
        <h3 style="margin: 0;">Lançamentos do Mês</h3>
        <a href="nova_transacao.php" class="botao btn-sucesso" style="margin: 0;">+ Nova Transação</a>
    </div>

    <div style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 15px; overflow-x: auto;">
        <a href="transacoes.php?mes=<?php echo $competencia; ?>&status=todos" class="botao" style="margin: 0; <?php echo $filtroStatus == 'todos' ? 'background-color: #000; color: #fff;' : 'background-color: #e9ecef; color: #495057; border-color: #ced4da;'; ?>">Todas</a>
        <a href="transacoes.php?mes=<?php echo $competencia; ?>&status=pendente" class="botao" style="margin: 0; <?php echo $filtroStatus == 'pendente' ? 'background-color: #ffc107; color: #000; border-color: #ffc107;' : 'background-color: #e9ecef; color: #495057; border-color: #ced4da;'; ?>">⏳ Pendentes</a>
        <a href="transacoes.php?mes=<?php echo $competencia; ?>&status=pago" class="botao" style="margin: 0; <?php echo $filtroStatus == 'pago' ? 'background-color: #28a745; color: #fff; border-color: #28a745;' : 'background-color: #e9ecef; color: #495057; border-color: #ced4da;'; ?>">✔ Consolidadas</a>
    </div>

    <section style="display: flex; flex-direction: column; gap: 30px;">
        
        <!-- BLOCO DE RECEITAS -->
        <article class="cartao-projeto" style="border-top: 5px solid #28a745; margin: 0; padding: 0; overflow: hidden;">
            <h3 style="color: #28a745; padding: 20px 20px 10px 20px; margin: 0;">⬇️ Receitas</h3>
            
            <div class="table-responsive">
                <table class="tabela-dados" style="margin: 0; border-radius: 0;">
                    <thead>
                        <tr>
                            <th class="ocultar-mobile">Data</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th class="ocultar-mobile">Status</th>
                            <th class="ocultar-mobile">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($listaReceitas) > 0): ?>
                            <?php foreach($listaReceitas as $tr): ?>
                                <!-- Linha principal visível -->
                                <tr class="linha-clicavel" onclick="toggleDetalhes('det-rec-<?php echo $tr['id']; ?>')">
                                    <td class="ocultar-mobile"><?php echo date('d/m/Y', strtotime($tr['data_transacao'])); ?></td>
                                    <td class="indicador-clique">
                                        <strong><?php echo htmlspecialchars($tr['descricao']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($tr['categoria_nome']); ?></small>
                                    </td>
                                    <td class="valor-positivo">+ <?php echo number_format($tr['valor'], 2, ',', '.'); ?></td>
                                    <td class="ocultar-mobile">
                                        <span class="<?php echo $tr['status'] == 'pago' ? 'badge-pago' : 'badge-pendente'; ?>">
                                            <?php echo $tr['status'] == 'pago' ? 'Recebido' : 'Pendente'; ?>
                                        </span>
                                    </td>
                                    <td class="ocultar-mobile">
                                        <?php if($tr['status'] == 'pendente'): ?>
                                            <a href="transacoes.php?consolidar=<?php echo $tr['id']; ?>&mes=<?php echo $competencia; ?>&status=<?php echo $filtroStatus; ?>" class="btn-acao btn-consolidar" title="Consolidar">✔</a>
                                        <?php endif; ?>
                                        <a href="editar_transacao.php?id=<?php echo $tr['id']; ?>" class="btn-acao" style="background-color: #ffc107;" title="Editar">✏️</a>
                                        <a href="transacoes.php?excluir=<?php echo $tr['id']; ?>&mes=<?php echo $competencia; ?>&status=<?php echo $filtroStatus; ?>" class="btn-acao btn-excluir" onclick="return confirm('Excluir esta receita?')" title="Excluir">✖</a>
                                    </td>
                                </tr>
                                
                                <!-- Linha Oculta (Expandida no Celular) -->
                                <tr id="det-rec-<?php echo $tr['id']; ?>" class="linha-detalhes">
                                    <td colspan="5" style="padding: 0;">
                                        <div class="box-detalhes">
                                            <p><strong>📅 Data:</strong> <?php echo date('d/m/Y', strtotime($tr['data_transacao'])); ?></p>
                                            <p><strong>🏷️ Status:</strong> 
                                                <span class="<?php echo $tr['status'] == 'pago' ? 'badge-pago' : 'badge-pendente'; ?>">
                                                    <?php echo $tr['status'] == 'pago' ? 'Recebido' : 'Pendente'; ?>
                                                </span>
                                            </p>
                                            <div style="margin-top: 15px; display: flex; gap: 8px;">
                                                <?php if($tr['status'] == 'pendente'): ?>
                                                    <a href="transacoes.php?consolidar=<?php echo $tr['id']; ?>&mes=<?php echo $competencia; ?>&status=<?php echo $filtroStatus; ?>" class="botao btn-sucesso" style="padding: 5px 10px; font-size: 0.8rem; margin: 0;">✔ Consolidar</a>
                                                <?php endif; ?>
                                                <a href="editar_transacao.php?id=<?php echo $tr['id']; ?>" class="botao" style="background-color: #ffc107; color: #000; padding: 5px 10px; font-size: 0.8rem; margin: 0; border-color: #ffc107;">✏️ Editar</a>
                                                <a href="transacoes.php?excluir=<?php echo $tr['id']; ?>&mes=<?php echo $competencia; ?>&status=<?php echo $filtroStatus; ?>" onclick="return confirm('Excluir?')" class="botao btn-perigo" style="padding: 5px 10px; font-size: 0.8rem; margin: 0;">✖ Excluir</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; color: #888; padding: 20px;">Nenhuma receita encontrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #e8f5e9;">
                            <td colspan="2" class="esconder-celular" style="text-align: right; font-weight: 700; color: #28a745;">Total Filtrado:</td>
                            <td class="valor-positivo" style="font-weight: 900;">+ <?php echo number_format($totalReceitas, 2, ',', '.'); ?></td>
                            <td colspan="2" class="esconder-celular"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </article>

        <!-- BLOCO DE DESPESAS -->
        <article class="cartao-projeto" style="border-top: 5px solid #dc3545; margin: 0; padding: 0; overflow: hidden;">
            <h3 style="color: #dc3545; padding: 20px 20px 10px 20px; margin: 0;">⬆️ Despesas</h3>
            
            <div class="table-responsive">
                <table class="tabela-dados" style="margin: 0; border-radius: 0;">
                    <thead>
                        <tr>
                            <th class="ocultar-mobile">Data</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th class="ocultar-mobile">Status</th>
                            <th class="ocultar-mobile">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($listaDespesas) > 0): ?>
                            <?php foreach($listaDespesas as $tr): ?>
                                <!-- Linha principal visível -->
                                <tr class="linha-clicavel" onclick="toggleDetalhes('det-desp-<?php echo $tr['id']; ?>')">
                                    <td class="ocultar-mobile"><?php echo date('d/m/Y', strtotime($tr['data_transacao'])); ?></td>
                                    <td class="indicador-clique">
                                        <strong><?php echo htmlspecialchars($tr['descricao']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($tr['categoria_nome']); ?></small>
                                    </td>
                                    <td class="valor-negativo">- <?php echo number_format($tr['valor'], 2, ',', '.'); ?></td>
                                    <td class="ocultar-mobile">
                                        <span class="<?php echo $tr['status'] == 'pago' ? 'badge-pago' : 'badge-pendente'; ?>">
                                            <?php echo $tr['status'] == 'pago' ? 'Pago' : 'Pendente'; ?>
                                        </span>
                                    </td>
                                    <td class="ocultar-mobile">
                                        <?php if($tr['status'] == 'pendente'): ?>
                                            <a href="transacoes.php?consolidar=<?php echo $tr['id']; ?>&mes=<?php echo $competencia; ?>&status=<?php echo $filtroStatus; ?>" class="btn-acao btn-consolidar" title="Consolidar">✔</a>
                                        <?php endif; ?>
                                        <a href="editar_transacao.php?id=<?php echo $tr['id']; ?>" class="btn-acao" style="background-color: #ffc107;" title="Editar">✏️</a>
                                        <a href="transacoes.php?excluir=<?php echo $tr['id']; ?>&mes=<?php echo $competencia; ?>&status=<?php echo $filtroStatus; ?>" class="btn-acao btn-excluir" onclick="return confirm('Excluir esta despesa?')" title="Excluir">✖</a>
                                    </td>
                                </tr>

                                <!-- Linha Oculta (Expandida no Celular) -->
                                <tr id="det-desp-<?php echo $tr['id']; ?>" class="linha-detalhes">
                                    <td colspan="5" style="padding: 0;">
                                        <div class="box-detalhes">
                                            <p><strong>📅 Data:</strong> <?php echo date('d/m/Y', strtotime($tr['data_transacao'])); ?></p>
                                            <p><strong>🏷️ Status:</strong> 
                                                <span class="<?php echo $tr['status'] == 'pago' ? 'badge-pago' : 'badge-pendente'; ?>">
                                                    <?php echo $tr['status'] == 'pago' ? 'Pago' : 'Pendente'; ?>
                                                </span>
                                            </p>
                                            <div style="margin-top: 15px; display: flex; gap: 8px;">
                                                <?php if($tr['status'] == 'pendente'): ?>
                                                    <a href="transacoes.php?consolidar=<?php echo $tr['id']; ?>&mes=<?php echo $competencia; ?>&status=<?php echo $filtroStatus; ?>" class="botao btn-sucesso" style="padding: 5px 10px; font-size: 0.8rem; margin: 0;">✔ Pagar</a>
                                                <?php endif; ?>
                                                <a href="editar_transacao.php?id=<?php echo $tr['id']; ?>" class="botao" style="background-color: #ffc107; color: #000; padding: 5px 10px; font-size: 0.8rem; margin: 0; border-color: #ffc107;">✏️ Editar</a>
                                                <a href="transacoes.php?excluir=<?php echo $tr['id']; ?>&mes=<?php echo $competencia; ?>&status=<?php echo $filtroStatus; ?>" onclick="return confirm('Excluir?')" class="botao btn-perigo" style="padding: 5px 10px; font-size: 0.8rem; margin: 0;">✖ Excluir</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; color: #888; padding: 20px;">Nenhuma despesa encontrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f8d7da;">
                            <td colspan="2" class="esconder-celular" style="text-align: right; font-weight: 700; color: #dc3545;">Total Filtrado:</td>
                            <td class="valor-negativo" style="font-weight: 900;">- <?php echo number_format($totalDespesas, 2, ',', '.'); ?></td>
                            <td colspan="2" class="esconder-celular"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </article>

    </section>
</main>

<!-- Script para a Sanfona no Celular -->
<script>
function toggleDetalhes(idElemento) {
    // Só aciona o efeito se estiver no celular (largura menor que 768px)
    if (window.innerWidth < 768) {
        var linhaDetalhe = document.getElementById(idElemento);
        
        // Verifica se a linha atual já está aberta
        if (linhaDetalhe.style.display === 'table-row') {
            linhaDetalhe.style.display = 'none'; // Fecha
        } else {
            // (Opcional) Fecha todas as outras que estiverem abertas
            var todasAsLinhas = document.querySelectorAll('.linha-detalhes');
            todasAsLinhas.forEach(function(linha) {
                linha.style.display = 'none';
            });
            
            // Abre a linha clicada
            linhaDetalhe.style.display = 'table-row';
        }
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>