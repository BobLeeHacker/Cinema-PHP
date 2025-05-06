<?php
// Include necessary files
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Logout user
logoutUser();

// Redirect to login page with success message
setFlashMessage('Você foi desconectado com sucesso.', 'success');
redirect('login.php');
