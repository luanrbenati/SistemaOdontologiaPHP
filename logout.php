<?php
session_start();
// Destrói todas as variáveis de sessão
session_unset();
session_destroy();
// Redireciona para o login
header("Location: index.php");
exit;
?>