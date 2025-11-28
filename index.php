<?php
/**
 * SACSWeb Educacional - Homepage
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 * TCC - Foco Educacional
 */

require_once 'config/config.php';

// Se já estiver logado, redirecionar para dashboard
if (isLoggedIn()) {
    redirect('/sacsweb/website/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/icone.png">
    <title>SACSWeb - Sistema Educacional de Segurança Cibernética</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-pink: #FF1493;
            --primary-pink-light: #FF69B4;
            --dark-bg: #0a0a0a;
            --dark-gray: #1a1a1a;
            --light-gray: #2a2a2a;
            --text-light: #e0e0e0;
            --text-muted: #888;
            --accent-blue: #00BFFF;
            --accent-purple: #9370DB;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--dark-bg);
            color: var(--text-light);
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 20, 147, 0.2);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-blue) !important;
            text-decoration: none;
        }

        .navbar-nav .nav-link {
            color: var(--text-light) !important;
            font-weight: 500;
            margin: 0 1rem;
            transition: color 0.3s;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary-pink) !important;
        }

        .btn-login {
            background: var(--primary-pink);
            color: white;
            border: none;
            padding: 0.6rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-login:hover {
            background: var(--primary-pink-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 20, 147, 0.4);
            color: white;
        }

        /* Hero Section */
        .hero-section {
            min-height: 90vh;
            display: flex;
            align-items: center;
            position: relative;
            padding: 5rem 0;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--dark-gray) 100%);
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 20, 147, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0, 191, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(147, 112, 219, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary-pink) 0%, var(--accent-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
            font-weight: 400;
        }

        .hero-description {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 3rem;
            line-height: 1.8;
            max-width: 600px;
        }

        .btn-hero {
            background: var(--primary-pink);
            color: white;
            border: none;
            padding: 1rem 3rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 1rem;
        }

        .btn-hero:hover {
            background: var(--primary-pink-light);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 20, 147, 0.5);
            color: white;
        }

        .btn-hero-outline {
            background: transparent;
            color: var(--primary-pink);
            border: 2px solid var(--primary-pink);
            padding: 1rem 3rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-hero-outline:hover {
            background: var(--primary-pink);
            color: white;
            transform: translateY(-3px);
        }

        /* Circuit Background */
        .circuit-bg {
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            opacity: 0.1;
            background-image: 
                linear-gradient(90deg, transparent 0%, rgba(255, 20, 147, 0.3) 50%, transparent 100%),
                linear-gradient(0deg, transparent 0%, rgba(0, 191, 255, 0.3) 50%, transparent 100%);
            background-size: 100px 100px;
            animation: circuitMove 20s linear infinite;
        }

        @keyframes circuitMove {
            0% { background-position: 0 0; }
            100% { background-position: 100px 100px; }
        }

        /* Section Styles */
        .section {
            padding: 5rem 0;
            position: relative;
        }

        .section-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 4rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Cards */
        .feature-card {
            background: var(--dark-gray);
            border: 1px solid rgba(255, 20, 147, 0.2);
            border-radius: 20px;
            padding: 2.5rem;
            height: 100%;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-pink), var(--accent-blue));
            transform: scaleX(0);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary-pink);
            box-shadow: 0 20px 40px rgba(255, 20, 147, 0.2);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-icon {
            font-size: 3.5rem;
            color: var(--primary-pink);
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-light);
        }

        .feature-description {
            color: var(--text-muted);
            line-height: 1.8;
        }

        /* Objectives Section */
        .objectives-section {
            background: var(--dark-gray);
            position: relative;
        }

        .objective-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(255, 20, 147, 0.05);
            border-left: 4px solid var(--primary-pink);
            border-radius: 10px;
        }

        .objective-icon {
            font-size: 2rem;
            color: var(--primary-pink);
            margin-right: 1.5rem;
            flex-shrink: 0;
        }

        .objective-content h4 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .objective-content p {
            color: var(--text-muted);
            line-height: 1.8;
        }

        /* What We Deliver Section */
        .deliver-section {
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--dark-gray) 100%);
        }

        .deliver-card {
            background: var(--light-gray);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid transparent;
        }

        .deliver-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-pink);
            box-shadow: 0 15px 35px rgba(255, 20, 147, 0.3);
        }

        .deliver-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-pink), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .deliver-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .deliver-description {
            color: var(--text-muted);
            line-height: 1.8;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary-pink) 0%, var(--accent-blue) 100%);
            padding: 5rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }

        .cta-content {
            position: relative;
            z-index: 2;
        }

        .cta-title {
            font-size: 3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
        }

        .cta-description {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2.5rem;
        }

        .btn-cta {
            background: white;
            color: var(--primary-pink);
            border: none;
            padding: 1.2rem 4rem;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: 700;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            color: var(--primary-pink);
        }

        /* Footer */
        .footer {
            background: var(--dark-bg);
            border-top: 1px solid rgba(255, 20, 147, 0.2);
            padding: 3rem 0;
            text-align: center;
        }

        .footer-text {
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: var(--primary-pink);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .btn-hero, .btn-hero-outline {
                display: block;
                width: 100%;
                margin-bottom: 1rem;
                margin-right: 0;
            }
        }

        /* Animated Background Elements */
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }

        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 20s infinite;
        }

        .shape-1 {
            width: 200px;
            height: 200px;
            background: var(--primary-pink);
            border-radius: 50%;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 150px;
            height: 150px;
            background: var(--accent-blue);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            top: 60%;
            right: 10%;
            animation-delay: 5s;
        }

        .shape-3 {
            width: 100px;
            height: 100px;
            background: var(--accent-purple);
            border-radius: 50%;
            bottom: 20%;
            left: 20%;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -30px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="assets/images/icone.png" alt="SACSWeb Logo" style="height: 50px; margin-right: 10px;"> SACSWeb
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="border: 1px solid var(--primary-pink); color: var(--primary-pink);">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#sobre">Sobre</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#objetivos">Objetivos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#entregas">O que Entregamos</a>
                    </li>
                    <li class="nav-item">
                        <a href="website/login.php" class="btn-login">
                            <i class="fas fa-sign-in-alt"></i> Entrar
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="circuit-bg"></div>
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1 class="hero-title">
                        SEGURANÇA<br>
                        <span style="color: var(--text-light);">CIBERNÉTICA</span>
                    </h1>
                    <p class="hero-subtitle">
                        ETHICAL HACKING, FORENSICS & DEVSECOPS
                    </p>
                    <p class="hero-description">
                        Sistema educacional completo para aprender sobre ataques cibernéticos, 
                        vulnerabilidades web e técnicas de proteção através de teoria e prática.
                    </p>
                    <div>
                        <a href="website/login.php" class="btn-hero">
                            <i class="fas fa-rocket"></i> Começar Agora
                        </a>
                        <a href="#sobre" class="btn-hero-outline">
                            <i class="fas fa-info-circle"></i> Saiba Mais
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div style="font-size: 15rem; opacity: 0.1; line-height: 1;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sobre Section -->
    <section id="sobre" class="section">
        <div class="container">
            <h2 class="section-title">O que é o <span style="color: var(--primary-pink);">SACSWeb</span>?</h2>
            <p class="section-subtitle">
                Uma plataforma educacional inovadora desenvolvida como Trabalho de Conclusão de Curso (TCC) 
                para ensinar segurança cibernética de forma prática e interativa.
            </p>

            <div class="row g-4 mt-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3 class="feature-title">Aprendizado Teórico</h3>
                        <p class="feature-description">
                            Compreenda os conceitos fundamentais de segurança cibernética através de 
                            explicações detalhadas sobre vulnerabilidades, ataques e proteções.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-code"></i>
                        </div>
                        <h3 class="feature-title">Demonstrações Práticas</h3>
                        <p class="feature-description">
                            Veja vulnerabilidades em ação através de exemplos de código real, 
                            compreendendo como os ataques funcionam na prática.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Técnicas de Proteção</h3>
                        <p class="feature-description">
                            Aprenda como se defender contra ataques cibernéticos através de 
                            boas práticas e implementações seguras de código.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Objetivos Section -->
    <section id="objetivos" class="section objectives-section">
        <div class="container">
            <h2 class="section-title">Nossos <span style="color: var(--primary-pink);">Objetivos</span></h2>
            <p class="section-subtitle">
                O SACSWeb foi desenvolvido com objetivos claros de educação e conscientização sobre segurança cibernética.
            </p>

            <div class="row mt-5">
                <div class="col-lg-6">
                    <div class="objective-item">
                        <div class="objective-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="objective-content">
                            <h4>Educação Acessível</h4>
                            <p>
                                Tornar o aprendizado de segurança cibernética acessível a todos, 
                                desde iniciantes até profissionais que desejam aprimorar seus conhecimentos.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="objective-item">
                        <div class="objective-icon">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <div class="objective-content">
                            <h4>Conscientização</h4>
                            <p>
                                Conscientizar sobre a importância da segurança cibernética e os riscos 
                                reais que existem no ambiente digital atual.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="objective-item">
                        <div class="objective-icon">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="objective-content">
                            <h4>Aprendizado Prático</h4>
                            <p>
                                Fornecer uma experiência prática e interativa, permitindo que os alunos 
                                vejam vulnerabilidades em ação e aprendam como corrigi-las.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="objective-item">
                        <div class="objective-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="objective-content">
                            <h4>Acompanhamento de Progresso</h4>
                            <p>
                                Permitir que os alunos acompanhem seu progresso através de um sistema 
                                de pontuação e histórico de atividades completadas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- O que Entregamos Section -->
    <section id="entregas" class="section deliver-section">
        <div class="container">
            <h2 class="section-title">O que <span style="color: var(--primary-pink);">Entregamos</span></h2>
            <p class="section-subtitle">
                Uma plataforma completa com módulos educacionais, exercícios interativos e ferramentas de aprendizado.
            </p>

            <div class="row g-4 mt-5">
                <div class="col-md-4">
                    <div class="deliver-card">
                        <div class="deliver-number">6</div>
                        <h3 class="deliver-title">Módulos Educacionais</h3>
                        <p class="deliver-description">
                            Módulos completos sobre segurança web, XSS, SQL Injection, 
                            validação de dados e muito mais.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="deliver-card">
                        <div class="deliver-number">21+</div>
                        <h3 class="deliver-title">Exercícios Interativos</h3>
                        <p class="deliver-description">
                            Quizzes com 4 alternativas, exercícios práticos e avaliações 
                            para testar seu conhecimento.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="deliver-card">
                        <div class="deliver-number">100%</div>
                        <h3 class="deliver-title">Gratuito e Acessível</h3>
                        <p class="deliver-description">
                            Plataforma totalmente gratuita e de código aberto, 
                            desenvolvida para fins educacionais.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="deliver-card">
                        <div class="deliver-icon" style="font-size: 3rem; color: var(--primary-pink); margin-bottom: 1rem;">
                            <i class="fas fa-code-branch"></i>
                        </div>
                        <h3 class="deliver-title">Código Demonstrativo</h3>
                        <p class="deliver-description">
                            Exemplos de código vulnerável e seguro para compreender 
                            as diferenças na prática.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="deliver-card">
                        <div class="deliver-icon" style="font-size: 3rem; color: var(--primary-pink); margin-bottom: 1rem;">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3 class="deliver-title">Sistema de Pontuação</h3>
                        <p class="deliver-description">
                            Acompanhe seu progresso através de pontos e conquistas 
                            ao completar módulos e exercícios.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="deliver-card">
                        <div class="deliver-icon" style="font-size: 3rem; color: var(--primary-pink); margin-bottom: 1rem;">
                            <i class="fas fa-history"></i>
                        </div>
                        <h3 class="deliver-title">Histórico Completo</h3>
                        <p class="deliver-description">
                            Visualize todo seu histórico de atividades, módulos concluídos 
                            e desempenho ao longo do tempo.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <div class="container">
                <h2 class="cta-title">Pronto para Começar?</h2>
                <p class="cta-description">
                    Junte-se ao SACSWeb e comece sua jornada no aprendizado de segurança cibernética hoje mesmo.
                </p>
                <a href="website/login.php" class="btn-cta">
                    <i class="fas fa-rocket"></i> Acessar Plataforma
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="footer-text">
                <strong>SACSWeb Educacional</strong> - Sistema para Ensino de Ataques Cibernéticos e Proteções
            </p>
            <p class="footer-text">
                Desenvolvido como Trabalho de Conclusão de Curso (TCC)
            </p>
            <div class="footer-links">
                <a href="#sobre">Sobre</a>
                <a href="#objetivos">Objetivos</a>
                <a href="#entregas">O que Entregamos</a>
                <a href="website/login.php">Login</a>
            </div>
            <p class="footer-text mt-4" style="font-size: 0.9rem;">
                © 2025 SACSWeb. Todos os direitos reservados.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(10, 10, 10, 0.98)';
            } else {
                navbar.style.background = 'rgba(10, 10, 10, 0.95)';
            }
        });
    </script>
</body>
</html>
