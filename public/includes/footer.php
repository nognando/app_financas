<footer class="rodape">
        <p>Finanças NogLabs &copy; <?php echo date('Y'); ?></p>
    </footer>

    <script>
        const botaoMenu = document.getElementById('botao-menu');
        const menuLinks = document.getElementById('menu-links');
        
        botaoMenu.addEventListener('click', () => menuLinks.classList.toggle('ativo'));
        
        document.querySelectorAll('.menu a').forEach(link => {
            link.addEventListener('click', () => menuLinks.classList.remove('ativo'));
        });
    </script>
</body>
</html>