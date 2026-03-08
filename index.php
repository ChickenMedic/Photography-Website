<?php
require_once 'config.php';

// Fetch all photos
$stmtPhotos = $pdo->query("SELECT * FROM photos ORDER BY created_at DESC");
$photos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);

// Fetch all locations
$stmtLocs = $pdo->query("SELECT * FROM locations ORDER BY name ASC");
$locations = $stmtLocs->fetchAll(PDO::FETCH_ASSOC);

// Fetch all projects
$stmtProjects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC");
$projects = $stmtProjects->fetchAll(PDO::FETCH_ASSOC);

$photoQuotes = [
    "Photography takes an instant out of time, altering life by holding it still.",
    "The world is a canvas to the imagination.",
    "To travel is to discover that everyone is wrong about other countries.",
    "A camera is a save button for the mind's eye.",
    "Wandering through cities, discovering new perspectives.",
    "You don't take a photograph, you make it.",
    "Photography is the story I fail to put into words.",
    "Look and think before opening the shutter. The heart and mind are the true lens of the camera.",
    "A good photograph is one that communicates a fact, touches the heart.",
    "There are no bad pictures; that's just how your face looks sometimes.",
    "If your pictures aren't good enough, you aren't close enough.",
    "The painter constructs, the photographer discloses.",
    "Photography is a way of feeling, of touching, of loving.",
    "What I like about photographs is that they capture a moment that's gone forever.",
    "A tear contains an ocean. A photographer is aware of the tiny moments in a person's life.",
    "We are making photographs to understand what our lives mean to us.",
    "The eye should learn to listen before it looks.",
    "Only photograph what you love.",
    "The best thing about a picture is that it never changes, even when the people in it do.",
    "To me, photography is an art of observation.",
    "Skill in photography is acquired by practice and not by purchase.",
    "There is one thing the photograph must contain, the humanity of the moment.",
    "Taking pictures is savouring life intensely, every hundredth of a second.",
    "Your first 10,000 photographs are your worst.",
    "Photography is truth. The cinema is truth twenty-four times per second.",
    "When words become unclear, I shall focus with photographs.",
    "God creates the beauty. My camera and I are a witness.",
    "Every viewer is going to get a different thing. That’s the thing about painting, photography.",
    "I walk, I look, I see, I stop, I photograph.",
    "Great photography is about depth of feeling, not depth of field."
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h(SITE_NAME); ?></title>
    <!-- Google Fonts for typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500;700&family=Playfair+Display:ital@0;1&family=Cormorant+Garamond:ital,wght@0,400;1,400&family=Cinzel&family=Dancing+Script&family=Great+Vibes&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Navigation -->
    <nav class="glass-nav">
        <div class="nav-container">
            <a href="#" class="logo">SD</a>
            <ul class="nav-links">
                <li><a href="#gallery">Gallery</a></li>
                <li><a href="#projects">Projects</a></li>
                <li><a href="#about">About</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero" id="home">
        <div class="hero-slider">
            <!-- User placeholder images: Add london.jpg, vienna.jpg, sicily.jpg, paris.jpg to assets/img -->
            <div class="hero-slide active" style="background-image: url('assets/img/london.jpg');"></div>
            <div class="hero-slide" style="background-image: url('assets/img/vienna.jpg');"></div>
            <div class="hero-slide" style="background-image: url('assets/img/sicily.jpg');"></div>
            <div class="hero-slide" style="background-image: url('assets/img/paris.jpg');"></div>
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <h1 class="animate-up">Sam Dawson</h1>
            <p class="subtitle animate-up delay-1" id="dynamic-quote">Capturing light, shadows, and everything in between.</p>
        </div>
        <div class="scroll-indicator animate-up delay-2">
            <span>Scroll</span>
            <div class="mouse"></div>
        </div>
    </header>

    <!-- Gallery Section (Masonry) -->
    <section id="gallery" class="section">
        <div class="container section-header" style="display: flex; align-items: center; gap: 30px;">
            <h2 class="fade-in">Selected Works</h2>
            <button id="random-pic-btn" class="btn-primary fade-in" style="padding: 10px 20px; font-size: 0.9rem;">Surprise Me</button>
        </div>
        
        <?php if (empty($locations) && empty($photos)): ?>
            <div class="container empty-state fade-in">
                <p>No photos or locations have been added yet.</p>
            </div>
        <?php else: ?>
            <div class="masonry-grid container">
                <!-- Location Cards -->
                <?php foreach ($locations as $loc): ?>
                    <?php 
                    // Skip inactive locations
                    if (isset($loc['is_active']) && $loc['is_active'] == 0) continue;

                    // Find first photo for cover and count photos
                    $cover = null;
                    $photoCount = 0;
                    foreach($photos as $p) {
                        if ($p['location_id'] == $loc['id']) { 
                            if (!$cover) {
                                $cover = $p['filename']; 
                            }
                            $photoCount++;
                        }
                    }
                    
                    // Skip if no photos
                    if ($photoCount === 0) continue;
                    ?>
                    <div class="masonry-item location-card fade-in" 
                         data-location-id="<?php echo $loc['id']; ?>" 
                         data-name="<?php echo h($loc['name']); ?>">
                        
                        <?php if ($cover): ?>
                            <img src="uploads/<?php echo h($cover); ?>" alt="<?php echo h($loc['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div style="width: 100%; height: 300px; background: #1e293b; display:flex; align-items:center; justify-content:center; color: #64748b;">No Photos</div>
                        <?php endif; ?>
                        
                        <div class="overlay">
                            <div class="overlay-content">
                                <h3><?php echo h($loc['name']); ?></h3>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php
                // Display photos not assigned to any location as individual items (optional, but good for UX)
                foreach ($photos as $photo): 
                    if ($photo['location_id'] === null):
                ?>
                    <div class="masonry-item photo-item fade-in" 
                         data-image="uploads/<?php echo h($photo['filename']); ?>"
                         data-title="<?php echo h($photo['title'] ?: 'Untitled'); ?>"
                         data-desc="<?php echo h($photo['description']); ?>">
                        
                        <img src="uploads/<?php echo h($photo['filename']); ?>" alt="<?php echo h($photo['title']); ?>" loading="lazy">
                        
                        <div class="overlay">
                            <div class="overlay-content">
                                <h3 class="photo-quote">"<?php echo h($photoQuotes[array_rand($photoQuotes)]); ?>"</h3>
                            </div>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Projects Section -->
    <section id="projects" class="section bg-darker">
        <div class="container section-header">
            <h2 class="fade-in">Projects</h2>
        </div>

        <div class="container">
            <?php if (empty($projects)): ?>
                <div class="empty-state fade-in">
                    <p>No projects have been added yet.</p>
                </div>
            <?php else: ?>
                <div class="projects-grid">
                    <?php foreach ($projects as $project): ?>
                        <div class="project-card fade-in">
                            <?php if ($project['cover_image']): ?>
                                <div class="project-img-wrapper">
                                    <img src="uploads/<?php echo h($project['cover_image']); ?>" alt="<?php echo h($project['title']); ?>" loading="lazy" class="project-img">
                                </div>
                            <?php else: ?>
                                <div class="project-img-wrapper fallback-bg"></div>
                            <?php endif; ?>
                            
                            <div class="project-info">
                                <h3><?php echo h($project['title']); ?></h3>
                                <p><?php echo h($project['description']); ?></p>
                                <?php if ($project['url']): ?>
                                    <a href="<?php echo h($project['url']); ?>" target="_blank" class="btn-primary">View Project</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section">
        <div class="container section-header">
            <h2 class="fade-in">About Me</h2>
        </div>
        <div class="container">
            <div class="fade-in" style="max-width: 800px; line-height: 1.8; color: var(--text-secondary); font-size: 1.1rem;">
                <p style="margin-bottom: 20px;">Hi, I'm Sam Dawson, a photographer with a passion for capturing the world's beauty, one frame at a time. My journey in photography began with a simple curiosity and has evolved into a lifelong pursuit of light and shadow.</p>
                <p>Through my lens, I explore landscapes, cityscapes, and the quiet moments that often go unnoticed. This portfolio is a curated collection of my favorite works from various locations around the globe.</p>
                <p>Feel free to reach out for collaborations or just to say hello!</p>
            </div>
        </div>
    </section>

    <!-- Lightbox Modal -->
    <div id="lightbox" class="lightbox">
        <button class="close-btn">&times;</button>
        <div class="lightbox-content">
            <img id="lightbox-img" src="" alt="">
            <div class="lightbox-caption">
                <h3 id="lightbox-title"></h3>
                <p id="lightbox-desc"></p>
            </div>
        </div>
    </div>

    <!-- Dynamic Location Gallery Modal -->
    <div id="location-gallery-modal" class="location-gallery-modal">
        <button class="close-gallery-btn">&times;</button>
        <div class="gallery-header container">
            <h2 id="gallery-title">Location</h2>
        </div>
        <div id="gallery-container" class="container">
            <!-- Photos dynamically injected here -->
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Sam Dawson. All rights reserved.</p>
            <p><a href="admin.php" style="color: #475569; text-decoration: none; font-size: 12px;">Admin Login</a></p>
        </div>
    </footer>

    <script>
        const allPhotosDB = <?php echo json_encode($photos); ?>;
        const photoQuotes = <?php echo json_encode($photoQuotes); ?>;
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>
