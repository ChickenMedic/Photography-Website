<?php
require_once 'config.php';

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (empty($url)) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM projects WHERE url = ?");
$stmt->execute([$url]);
$project = $stmt->fetch();

if (!$project) {
    // If not found, maybe they are accessing a physical file that used to exist, or it's a 404
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 Not Found</h1>";
    echo "<p>The project you are looking for does not exist.</p>";
    echo "<a href='/PersonalWebsite/'>Return Home</a>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($project['title']); ?> | Sam Dawson Photography</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Cormorant+Garamond:ital,wght@0,300;0,600;1,400&family=Inter:wght@300;400;600;800&family=Outfit:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    
    <style>
        .navbar {
            padding-top: 35px;
        }

        .article-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 120px 20px 60px;
        }
        
        .article-header {
            margin-bottom: 50px;
            text-align: center;
        }
        
        .article-header h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            margin-bottom: 10px;
            line-height: 1.1;
        }
        
        .article-header p {
            color: var(--text-secondary);
            font-size: 1.2rem;
            font-weight: 300;
        }

        .article-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #cbd5e1;
        }

        .article-content h1, .article-content h2, .article-content h3 {
            color: #f8fafc;
        }
        
        .article-content h2 {
            font-size: 1.8rem;
            margin: 40px 0 20px;
        }

        .article-content p {
            margin-bottom: 20px;
        }

        .article-content strong {
            color: #f8fafc;
        }

        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }

        .article-content code {
            background: #1e293b;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #7dd3fc;
            font-family: monospace;
        }

        .code-block-wrapper {
            position: relative;
            margin: 30px 0;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            transition: border-color 0.3s ease;
        }

        .code-block-wrapper:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }

        .code-block-wrapper pre {
            margin: 0;
            padding: 24px 100px 24px 24px;
            overflow-x: auto;
            background: transparent;
            border: none;
            white-space: pre;
        }

        .code-block-wrapper code {
            background: transparent;
            padding: 0;
            color: #e2e8f0;
            font-size: 1rem;
            line-height: 1.6;
            white-space: pre;
        }

        .copy-code-btn {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            z-index: 10;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #cbd5e1;
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .copy-code-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, #6366f1, #a855f7, #ec4899);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .copy-code-btn span {
            position: relative;
            z-index: 1;
            color: inherit;
        }

        .copy-code-btn:hover {
            border-color: transparent;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4);
        }

        .copy-code-btn:hover::before {
            opacity: 1;
        }

        .copy-code-btn.copied {
            border-color: transparent;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
        }

        .copy-code-btn.copied::before {
            background: linear-gradient(90deg, #22c55e, #10b981, #059669);
            opacity: 1;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 30px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #60a5fa;
        }
    </style>
</head>
<body>

    <nav class="navbar" id="navbar">
        <div class="container">
            <a href="/" class="logo"><span class="letter-span">S</span><span class="letter-span">D</span>.</a>
        </div>
    </nav>

    <div class="container article-container">
        <a href="/#projects" class="back-link">← Back to Projects</a>
        
        <header class="article-header animate-up">
            <h1><?php echo h($project['title']); ?></h1>
            <p><?php echo h($project['description']); ?></p>
        </header>

        <article class="article-content animate-up delay-1">
            <?php 
                // Notice we DO NOT use htmlspecialchars (h) here, 
                // because we want the raw HTML generated by TinyMCE to render.
                echo $project['content']; 
            ?>
        </article>

    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Sam Dawson. All rights reserved.</p>
        </div>
    </footer>
    
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
