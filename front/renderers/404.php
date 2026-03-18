<?php
/**
 * ============================================================
 * Page 404 générique – Écosystème immobilier
 * Sans header / footer / DB
 * Utilisable sur tous les sites
 * ============================================================
 */

http_response_code(404);
?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>404 – Page introuvable</title>

<meta name="robots" content="noindex, nofollow">

<style>

body{
margin:0;
font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
background:#f5f7fa;
display:flex;
align-items:center;
justify-content:center;
height:100vh;
text-align:center;
color:#333;
}

.container{
max-width:700px;
padding:40px;
}

.icon{
font-size:80px;
animation:float 3s ease-in-out infinite;
}

@keyframes float{
0%{transform:translateY(0)}
50%{transform:translateY(-12px)}
100%{transform:translateY(0)}
}

.number{
font-size:120px;
font-weight:800;
color:#0f3d68;
margin:10px 0;
}

.title{
font-size:28px;
font-weight:700;
margin-bottom:12px;
}

.text{
font-size:17px;
color:#666;
margin-bottom:35px;
line-height:1.6;
}

.btn{
display:inline-block;
padding:14px 26px;
border-radius:6px;
text-decoration:none;
font-weight:600;
font-size:16px;
background:#0f3d68;
color:white;
transition:0.2s;
}

.btn:hover{
background:#14508c;
}

</style>

</head>

<body>

<div class="container">

<div class="icon">🔎🏠</div>

<div class="number">404</div>

<h1 class="title">Cette page est introuvable</h1>

<p class="text">
On a cherché cette page comme un agent cherche le bien parfait…  
mais elle semble avoir disparu du site.
<br><br>
Pas d’inquiétude, vous pouvez continuer votre visite depuis l’accueil.
</p>

<a href="/" class="btn">
Retour à l’accueil
</a>

</div>

</body>

</html>