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
    "To me, photography is an art of observation. It's about finding something interesting in an ordinary place.|Elliott Erwitt",
    "Your first 10,000 photographs are your worst.|Henri Cartier-Bresson",
    "There are no rules for good photographs, there are only good photographs.|Ansel Adams",
    "Photography is the story I fail to put into words.|Destin Sparks",
    "If your pictures aren't good enough, you aren't close enough.|Robert Capa",
    "You don't take a photograph, you make it.|Ansel Adams",
    "The camera is an instrument that teaches people how to see without a camera.|Dorothea Lange",
    "A tear contains an ocean. A photographer is aware of the tiny moments in a person's life.|Robert Frank",
    "It is an illusion that photos are made with the camera... they are made with the eye, heart and head.|Henri Cartier-Bresson",
    "The best thing about a picture is that it never changes, even when the people in it do.|Andy Warhol",
    "Photography is a way of feeling, of touching, of loving.|Aaron Siskind",
    "A good photograph is one that communicates a fact, touches the heart and leaves the viewer a changed person.|Irving Penn",
    "Photography takes an instant out of time, altering life by holding it still.|Dorothea Lange",
    "I never have taken a picture I've intended. They're always better or worse.|Diane Arbus",
    "When words become unclear, I shall focus with photographs. When images become inadequate, I shall be content with silence.|Ansel Adams",
    "To photograph is to hold one's breath, when all faculties converge to capture fleeting reality.|Henri Cartier-Bresson",
    "The eye should learn to listen before it looks.|Robert Frank",
    "A camera is a tool for learning how to see without a camera.|Dorothea Lange",
    "Look and think before opening the shutter. The heart and mind are the true lens of the camera.|Yousuf Karsh",
    "Which of my photographs is my favorite? The one I’m going to take tomorrow.|Imogen Cunningham",
    "I walk, I look, I see, I stop, I photograph.|Leon Levinstein",
    "Character, like a photograph, develops in darkness.|Yousuf Karsh",
    "A portrait is not made in the camera but on either side of it.|Edward Steichen",
    "Great photography is about depth of feeling, not depth of field.|Peter Adams",
    "There is one thing the photograph must contain, the humanity of the moment.|Robert Frank",
    "Taking pictures is savoring life intensely, every hundredth of a second.|Marc Riboud",
    "The painter constructs, the photographer discloses.|Susan Sontag",
    "To consult the rules of composition before making a picture is a little like consulting the law of gravitation before going for a walk.|Edward Weston",
    "We are making photographs to understand what our lives mean to us.|Ralph Hattersley",
    "Photography for me is not looking, it's feeling.|Don McCullin",
    "To me, photography is the simultaneous recognition, in a fraction of a second, of the significance of an event.|Henri Cartier-Bresson",
    "You just have to live and life will give you pictures.|Henri Cartier-Bresson",
    "Of all the means of expression, photography is the only one that fixes a precise moment in time.|Henri Cartier-Bresson",
    "A photograph is neither taken or seized by force. It offers itself up.|Henri Cartier-Bresson",
    "In photography, the smallest thing can be a great subject.|Henri Cartier-Bresson",
    "The camera is an extension of the eye.|Henri Cartier-Bresson",
    "For me, the camera is a sketch book, an instrument of intuition and spontaneity.|Henri Cartier-Bresson",
    "A good photograph is knowing where to stand.|Ansel Adams",
    "Landscape photography is the supreme test of the photographer.|Ansel Adams",
    "Sometimes I do get to places just when God's ready to have somebody click the shutter.|Ansel Adams",
    "A great photograph is one that fully expresses what one feels, in the deepest sense, about what is being photographed.|Ansel Adams",
    "There is nothing worse than a sharp image of a fuzzy concept.|Ansel Adams",
    "You don't make a photograph just with a camera. You bring to the act of photography all the pictures you have seen, the books you have read, the music you have heard, the people you have loved.|Ansel Adams",
    "To the complaint, 'There are no people in these photographs,' I respond, 'There are always two people: the photographer and the viewer.'|Ansel Adams"
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500;700&family=Playfair+Display:ital@0;1&family=Cormorant+Garamond:ital,wght@0,400;1,400&family=Cinzel&family=Dancing+Script&family=Great+Vibes&family=Oswald:wght@500;700&family=Roboto+Mono:wght@400;700&family=Permanent+Marker&family=Anton&family=Bebas+Neue&family=Lora:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Navigation -->
    <nav class="glass-nav">
        <div class="nav-container">
            <a href="/" class="logo"><span class="letter-span">S</span><span class="letter-span">D</span>.</a>
            <ul class="nav-links">
                <li><a href="/#gallery">Gallery</a></li>
                <li><a href="/#projects">Projects</a></li>
                <li><a href="/#about">About</a></li>
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
            
            <!-- Floating Shapes Background -->
            <div class="shapes-container">
                <div class="shape circle shape-1"></div>
                <div class="shape triangle shape-2"></div>
                <div class="shape square shape-3"></div>
                <div class="shape circle shape-4"></div>
                <div class="shape triangle shape-5"></div>
                <div class="shape circle shape-6"></div>
            </div>
            
            <div class="gradient-overlay"></div>
        </div>
        <div class="hero-content">
            <h1 class="animate-up" id="dynamic-name">
                <span class="word-wrap">
                    <span class="letter-span" style="font-family: 'Outfit', sans-serif;">S</span><span class="letter-span" style="font-family: 'Outfit', sans-serif;">a</span><span class="letter-span" style="font-family: 'Outfit', sans-serif;">m</span>
                </span>
                <span class="word-wrap">
                    <span class="letter-span" style="font-family: 'Outfit', sans-serif;">D</span><span class="letter-span" style="font-family: 'Outfit', sans-serif;">a</span><span class="letter-span" style="font-family: 'Outfit', sans-serif;">w</span><span class="letter-span" style="font-family: 'Outfit', sans-serif;">s</span><span class="letter-span" style="font-family: 'Outfit', sans-serif;">o</span><span class="letter-span" style="font-family: 'Outfit', sans-serif;">n</span>
                </span>
            </h1>
            <?php
                // Get a random initial quote from the array
                $initialQuote = $photoQuotes[array_rand($photoQuotes)];
                $quoteParts = explode('|', $initialQuote);
                $initialText = isset($quoteParts[0]) ? $quoteParts[0] : '';
                $initialAuthor = isset($quoteParts[1]) ? $quoteParts[1] : '';
            ?>
            <p class="subtitle animate-up delay-1" id="dynamic-quote-container">
                <span id="dynamic-quote-text">"<?php echo h($initialText); ?>"</span><br>
                <span id="dynamic-quote-author" class="quote-author"><?php echo h($initialAuthor); ?></span>
            </p>
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
        
        <div class="container fade-in">
            <div id="featured-slideshow" class="featured-slideshow">
                <div id="featured-slideshow-inner" class="featured-slideshow-inner"></div>
            </div>
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
                                <h3><?php echo h($photo['title'] ?: 'Untitled'); ?></h3>
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
                <?php
                // Group projects by series
                $groupedProjects = [];
                $ungroupedProjects = [];

                foreach ($projects as $project) {
                    $series = trim($project['series_name']);
                    if (!empty($series)) {
                        if (!isset($groupedProjects[$series])) $groupedProjects[$series] = [];
                        $groupedProjects[$series][] = $project;
                    } else {
                        $ungroupedProjects[] = $project;
                    }
                }
                ?>

                <!-- Render Grouped Projects -->
                <?php foreach ($groupedProjects as $seriesName => $seriesProjects): ?>
                    <div class="series-group fade-in">
                        <div class="series-bg-text"><?php echo h($seriesName); ?></div>
                        <h3 class="series-title" style="margin-bottom: 30px; color: var(--accent); position:relative; z-index:2;"><?php echo h($seriesName); ?> Series</h3>
                        <div class="projects-grid">
                            <?php foreach ($seriesProjects as $project): ?>
                                <div class="project-card">
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
                                            <a href="<?php echo h($project['url']); ?>" class="btn-primary">View Project</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Render Ungrouped Projects -->
                <?php if (!empty($ungroupedProjects)): ?>
                    <div class="projects-grid">
                        <?php foreach ($ungroupedProjects as $project): ?>
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
                                        <a href="<?php echo h($project['url']); ?>" class="btn-primary">View Project</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
    </script>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
