<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NogLabs Finanças</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!--<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">-->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700;900&display=swap" rel="stylesheet">

    <!--<link rel="stylesheet" href="css/style.css">-->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body id="home">

    <header class="navbar-fixa">
        <div class="navbar-topo">
            <a href="index.php" class="logo">NogLabs Finanças</a>
            <button id="botao-menu" class="botao-menu">&#9776;</button>
        </div>
        
        <nav class="menu" id="menu-links">
            <a href="index.php">Dashboard</a>
            <a href="transacoes.php">Transações</a>
            <a href="categorias.php">Categorias</a>
            <a href="#">Configurações</a>
        </nav>
    </header>